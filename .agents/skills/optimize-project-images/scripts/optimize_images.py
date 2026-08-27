#!/usr/bin/env python3
"""Back up, resize, convert, and validate a folder of raster images."""

from __future__ import annotations

import argparse
import json
import os
import re
import shutil
import stat
import subprocess
import sys
import uuid
from dataclasses import dataclass, field
from datetime import datetime, timezone
from pathlib import Path
from typing import Any, Sequence


SUPPORTED_INPUTS = {
    ".avif",
    ".bmp",
    ".gif",
    ".jpeg",
    ".jpg",
    ".png",
    ".tif",
    ".tiff",
    ".webp",
}
OUTPUT_EXTENSIONS = {
    "avif": ".avif",
    "jpeg": ".jpg",
    "png": ".png",
    "webp": ".webp",
}
EXPECTED_MAGICK_FORMATS = {
    "avif": {"AVIF", "HEIC"},
    "jpeg": {"JPEG", "JPG"},
    "png": {"PNG"},
    "webp": {"WEBP"},
}
SPACE_CUSHION_BYTES = 64 * 1024 * 1024


class OptimizationError(RuntimeError):
    """A safe, user-actionable optimization failure."""


def utc_now() -> str:
    return datetime.now(timezone.utc).isoformat(timespec="seconds").replace("+00:00", "Z")


def positive_integer(value: str) -> int:
    parsed = int(value)
    if parsed <= 0:
        raise argparse.ArgumentTypeError("must be greater than zero")
    return parsed


def quality_value(value: str) -> int:
    parsed = int(value)
    if not 1 <= parsed <= 100:
        raise argparse.ArgumentTypeError("must be between 1 and 100")
    return parsed


def human_bytes(value: int) -> str:
    sign = "-" if value < 0 else ""
    amount = float(abs(value))
    for unit in ("B", "KiB", "MiB", "GiB", "TiB"):
        if amount < 1024 or unit == "TiB":
            return f"{sign}{amount:.1f} {unit}"
        amount /= 1024
    return f"{sign}{amount:.1f} TiB"


def find_project_root() -> Path:
    script_path = Path(__file__).resolve()
    for candidate in script_path.parents:
        if (candidate / ".git").exists() and (candidate / "AGENTS.md").is_file():
            return candidate
    raise OptimizationError("Could not resolve the project root from the script location.")


def nearest_existing_parent(path: Path) -> Path:
    candidate = path
    while not candidate.exists():
        if candidate.parent == candidate:
            raise OptimizationError(f"No existing parent found for {path}")
        candidate = candidate.parent
    return candidate


def is_relative_to(path: Path, parent: Path) -> bool:
    try:
        path.relative_to(parent)
        return True
    except ValueError:
        return False


@dataclass(frozen=True)
class Options:
    source: Path
    project_root: Path
    backup_root: Path
    output_format: str
    max_width: int
    quality: int
    remove_original: bool
    recursive: bool
    existing: str
    dry_run: bool
    skip_space_check: bool


@dataclass
class PlanItem:
    source: Path
    destination: Path
    relative_source: str
    relative_destination: str
    source_bytes: int
    skip_reason: str | None = None

    @property
    def actionable(self) -> bool:
        return self.skip_reason is None


@dataclass
class RunState:
    run_id: str
    started_at_utc: str
    backup_directory: Path
    backup_source: Path
    manifest_path: Path
    status: str = "planned"
    backup_completed_at_utc: str | None = None
    finished_at_utc: str | None = None
    error: str | None = None
    results: list[dict[str, Any]] = field(default_factory=list)


class ImageMagick:
    def __init__(self) -> None:
        magick = shutil.which("magick")
        if magick:
            self.convert_prefix = [magick]
            self.identify_prefix = [magick, "identify"]
            return

        convert = shutil.which("convert")
        identify = shutil.which("identify")
        if convert and identify:
            self.convert_prefix = [convert]
            self.identify_prefix = [identify]
            return

        raise OptimizationError(
            "ImageMagick is required. Install a version providing 'magick', or both 'convert' and 'identify'."
        )

    def convert(self, source: Path, destination: Path, options: Options) -> None:
        command = [
            *self.convert_prefix,
            f"{source}[0]",
            "-auto-orient",
            "-colorspace",
            "sRGB",
            "-resize",
            f"{options.max_width}x>",
            "-strip",
        ]

        if options.output_format == "jpeg":
            command.extend(["-background", "white", "-alpha", "remove", "-alpha", "off"])
        if options.output_format == "webp":
            command.extend(["-define", "webp:method=6"])

        command.extend(["-quality", str(options.quality), str(destination)])
        result = subprocess.run(command, capture_output=True, text=True, check=False)
        if result.returncode != 0:
            detail = result.stderr.strip() or result.stdout.strip() or "unknown ImageMagick error"
            raise OptimizationError(f"Conversion failed for {source}: {detail}")

    def identify(self, image: Path) -> tuple[str, int, int]:
        command = [
            *self.identify_prefix,
            "-ping",
            "-format",
            "%m|%w|%h",
            f"{image}[0]",
        ]
        result = subprocess.run(command, capture_output=True, text=True, check=False)
        if result.returncode != 0:
            detail = result.stderr.strip() or result.stdout.strip() or "unknown identify error"
            raise OptimizationError(f"Output validation failed for {image}: {detail}")

        parts = result.stdout.strip().split("|")
        if len(parts) != 3:
            raise OptimizationError(f"Unexpected ImageMagick validation response for {image}")
        try:
            return parts[0].upper(), int(parts[1]), int(parts[2])
        except ValueError as error:
            raise OptimizationError(f"Invalid dimensions returned for {image}") from error


class ImageOptimizationRun:
    def __init__(self, options: Options, image_magick: ImageMagick) -> None:
        self.options = options
        self.image_magick = image_magick
        self.plan: list[PlanItem] = []
        self.source_tree_bytes = 0

    def execute(self) -> int:
        self._validate_paths()
        self.plan = self._build_plan()
        actionable = [item for item in self.plan if item.actionable]
        self.source_tree_bytes = self._tree_size(self.options.source)
        self._print_plan(actionable)

        if not actionable:
            print("Nothing requires conversion.")
            return 0

        self._check_disk_space(actionable)
        if self.options.dry_run:
            print("Dry run complete; no backup or image changes were made.")
            return 0

        state = self._create_run_state()
        self._write_manifest(state)

        try:
            self._create_backup(state)
            state.status = "converting"
            self._write_manifest(state)

            for item in self.plan:
                if item.skip_reason is not None:
                    state.results.append(self._skipped_result(item))
                    self._write_manifest(state)
                    continue
                state.results.append(self._convert_item(item))
                self._write_manifest(state)

            state.status = "completed"
            state.finished_at_utc = utc_now()
            self._write_manifest(state)
            self._print_summary(state)
            return 0
        except KeyboardInterrupt:
            state.status = "interrupted"
            state.error = "Interrupted by user"
            state.finished_at_utc = utc_now()
            self._write_manifest(state)
            print(f"Interrupted. Backup and manifest retained at: {state.backup_directory}", file=sys.stderr)
            return 130
        except Exception as error:
            state.status = "failed"
            state.error = str(error)
            state.finished_at_utc = utc_now()
            self._write_manifest(state)
            print(f"ERROR: {error}", file=sys.stderr)
            print(f"Backup and manifest retained at: {state.backup_directory}", file=sys.stderr)
            return 1

    def _validate_paths(self) -> None:
        source = self.options.source
        project_root = self.options.project_root
        backup_root = self.options.backup_root

        if not source.is_dir():
            raise OptimizationError(f"Source folder does not exist or is not a directory: {source}")
        if source == project_root:
            raise OptimizationError("Refusing to optimize the project root. Pass a specific image folder.")
        if is_relative_to(backup_root, source):
            raise OptimizationError("Backup root cannot be inside the source folder.")
        if is_relative_to(source, backup_root):
            raise OptimizationError("Refusing to optimize a folder inside the backup root.")

    def _image_files(self) -> list[Path]:
        iterator = self.options.source.rglob("*") if self.options.recursive else self.options.source.glob("*")
        return sorted(
            (
                path
                for path in iterator
                if path.is_file() and not path.is_symlink() and path.suffix.lower() in SUPPORTED_INPUTS
            ),
            key=lambda path: path.relative_to(self.options.source).as_posix().casefold(),
        )

    def _build_plan(self) -> list[PlanItem]:
        output_extension = OUTPUT_EXTENSIONS[self.options.output_format]
        items: list[PlanItem] = []
        destinations: dict[Path, Path] = {}

        for source in self._image_files():
            destination = source.with_suffix(output_extension)
            relative_source = source.relative_to(self.options.source).as_posix()
            relative_destination = destination.relative_to(self.options.source).as_posix()
            item = PlanItem(
                source=source,
                destination=destination,
                relative_source=relative_source,
                relative_destination=relative_destination,
                source_bytes=source.stat().st_size,
            )

            same_path = destination == source
            if same_path and not self.options.remove_original:
                item.skip_reason = "already uses output format; keep-originals prevents in-place replacement"
            elif destination.exists() and not same_path:
                if self.options.existing == "error":
                    raise OptimizationError(
                        f"Destination already exists: {destination}. Use --existing skip or --existing replace."
                    )
                if self.options.existing == "skip":
                    item.skip_reason = "destination exists"

            if item.actionable:
                normalized_destination = Path(os.path.normcase(str(destination.resolve(strict=False))))
                prior_source = destinations.get(normalized_destination)
                if prior_source is not None and prior_source != source:
                    raise OptimizationError(
                        f"Output collision: {prior_source} and {source} would both write {destination}. "
                        "Rename one source before continuing."
                    )
                destinations[normalized_destination] = source

            items.append(item)

        return items

    @staticmethod
    def _tree_size(root: Path) -> int:
        total = 0
        for path in root.rglob("*"):
            if path.is_file() and not path.is_symlink():
                try:
                    total += path.stat().st_size
                except FileNotFoundError:
                    continue
        return total

    def _check_disk_space(self, actionable: list[PlanItem]) -> None:
        if self.options.skip_space_check:
            print("WARNING: free-space safety check skipped by request.")
            return

        backup_parent = nearest_existing_parent(self.options.backup_root)
        source_device = self.options.source.stat().st_dev
        backup_device = backup_parent.stat().st_dev
        action_bytes = sum(item.source_bytes for item in actionable)
        largest_action = max(item.source_bytes for item in actionable)
        source_work_bytes = action_bytes if not self.options.remove_original else largest_action

        if source_device == backup_device:
            required = self.source_tree_bytes + source_work_bytes + SPACE_CUSHION_BYTES
            free = shutil.disk_usage(backup_parent).free
            print(f"Space required:  about {human_bytes(required)} on the shared source/backup disk")
            print(f"Space available: {human_bytes(free)}")
            if free < required:
                raise OptimizationError(
                    f"Insufficient free space: need about {human_bytes(required)}, have {human_bytes(free)}."
                )
            return

        backup_required = self.source_tree_bytes + SPACE_CUSHION_BYTES
        backup_free = shutil.disk_usage(backup_parent).free
        print(f"Backup space:    about {human_bytes(backup_required)} required; {human_bytes(backup_free)} available")
        if backup_free < backup_required:
            raise OptimizationError(
                f"Insufficient backup-disk space: need about {human_bytes(backup_required)}, "
                f"have {human_bytes(backup_free)}."
            )

        source_required = source_work_bytes + SPACE_CUSHION_BYTES
        source_free = shutil.disk_usage(self.options.source).free
        print(f"Source workspace: about {human_bytes(source_required)} required; {human_bytes(source_free)} available")
        if source_free < source_required:
            raise OptimizationError(
                f"Insufficient source-disk workspace: need about {human_bytes(source_required)}, "
                f"have {human_bytes(source_free)}."
            )

    def _print_plan(self, actionable: list[PlanItem]) -> None:
        skipped = len(self.plan) - len(actionable)
        print(f"Source:          {self.options.source}")
        print(f"Backup root:     {self.options.backup_root}")
        print(f"Output format:   {self.options.output_format}")
        print(f"Maximum width:   {self.options.max_width}px")
        print(f"Quality:         {self.options.quality}")
        print(f"Remove original: {'yes' if self.options.remove_original else 'no'}")
        print(f"Images found:    {len(self.plan)}")
        print(f"Will convert:    {len(actionable)}")
        print(f"Will skip:       {skipped}")
        print(f"Folder size:     {human_bytes(self.source_tree_bytes)}")

    def _create_run_state(self) -> RunState:
        timestamp = datetime.now(timezone.utc).strftime("%Y%m%dT%H%M%SZ")
        suffix = uuid.uuid4().hex[:8]
        safe_name = re.sub(r"[^A-Za-z0-9._-]+", "-", self.options.source.name).strip("-_") or "images"
        run_id = f"{timestamp}-{suffix}"
        backup_directory = self.options.backup_root / f"{run_id}-{safe_name}"
        backup_directory.mkdir(parents=True, exist_ok=False)
        return RunState(
            run_id=run_id,
            started_at_utc=utc_now(),
            backup_directory=backup_directory,
            backup_source=backup_directory / "source",
            manifest_path=backup_directory / "manifest.json",
        )

    def _create_backup(self, state: RunState) -> None:
        print(f"Creating complete backup: {state.backup_source}")
        shutil.copytree(self.options.source, state.backup_source, symlinks=True, copy_function=shutil.copy2)
        state.backup_completed_at_utc = utc_now()
        state.status = "backup_completed"
        self._write_manifest(state)

    def _convert_item(self, item: PlanItem) -> dict[str, Any]:
        source_stat = item.source.stat()
        temporary = item.destination.parent / (
            f".{item.destination.stem}.{uuid.uuid4().hex}.tmp{item.destination.suffix}"
        )
        try:
            self.image_magick.convert(item.source, temporary, self.options)
            detected_format, width, height = self.image_magick.identify(temporary)
            expected_formats = EXPECTED_MAGICK_FORMATS[self.options.output_format]
            if detected_format not in expected_formats:
                raise OptimizationError(
                    f"Expected {self.options.output_format}, but {temporary} identifies as {detected_format}."
                )
            if width <= 0 or height <= 0 or width > self.options.max_width:
                raise OptimizationError(
                    f"Invalid output dimensions for {item.source}: {width}x{height}."
                )

            output_bytes = temporary.stat().st_size
            if output_bytes <= 0:
                raise OptimizationError(f"Converted output is empty: {temporary}")

            os.chmod(temporary, stat.S_IMODE(source_stat.st_mode))
            os.replace(temporary, item.destination)
            removed_original = False
            if self.options.remove_original and item.destination != item.source:
                item.source.unlink()
                removed_original = True

            print(f"Converted: {item.relative_source} -> {item.relative_destination} ({width}x{height})")
            return {
                "source": item.relative_source,
                "destination": item.relative_destination,
                "status": "converted",
                "source_bytes": item.source_bytes,
                "output_bytes": output_bytes,
                "saved_bytes": item.source_bytes - output_bytes,
                "width": width,
                "height": height,
                "detected_format": detected_format,
                "original_removed": removed_original,
            }
        finally:
            if temporary.exists():
                temporary.unlink()

    @staticmethod
    def _skipped_result(item: PlanItem) -> dict[str, Any]:
        print(f"Skipped:   {item.relative_source} ({item.skip_reason})")
        return {
            "source": item.relative_source,
            "destination": item.relative_destination,
            "status": "skipped",
            "reason": item.skip_reason,
            "source_bytes": item.source_bytes,
        }

    def _manifest_data(self, state: RunState) -> dict[str, Any]:
        converted = [result for result in state.results if result["status"] == "converted"]
        skipped = [result for result in state.results if result["status"] == "skipped"]
        return {
            "schema_version": 1,
            "run_id": state.run_id,
            "status": state.status,
            "started_at_utc": state.started_at_utc,
            "backup_completed_at_utc": state.backup_completed_at_utc,
            "finished_at_utc": state.finished_at_utc,
            "error": state.error,
            "project_root": str(self.options.project_root),
            "source": str(self.options.source),
            "backup_directory": str(state.backup_directory),
            "backup_source": str(state.backup_source),
            "options": {
                "output_format": self.options.output_format,
                "max_width": self.options.max_width,
                "quality": self.options.quality,
                "remove_original": self.options.remove_original,
                "recursive": self.options.recursive,
                "existing": self.options.existing,
            },
            "summary": {
                "images_discovered": len(self.plan),
                "images_converted": len(converted),
                "images_skipped": len(skipped),
                "source_tree_bytes_before": self.source_tree_bytes,
                "converted_source_bytes": sum(result["source_bytes"] for result in converted),
                "output_bytes": sum(result["output_bytes"] for result in converted),
                "saved_bytes": sum(result["saved_bytes"] for result in converted),
            },
            "results": state.results,
        }

    def _write_manifest(self, state: RunState) -> None:
        temporary = state.manifest_path.with_suffix(".json.tmp")
        temporary.write_text(
            json.dumps(self._manifest_data(state), indent=2, ensure_ascii=False) + "\n",
            encoding="utf-8",
        )
        os.replace(temporary, state.manifest_path)

    def _print_summary(self, state: RunState) -> None:
        data = self._manifest_data(state)
        summary = data["summary"]
        print("Optimization completed.")
        print(f"Converted:       {summary['images_converted']}")
        print(f"Skipped:         {summary['images_skipped']}")
        print(f"Input bytes:     {human_bytes(summary['converted_source_bytes'])}")
        print(f"Output bytes:    {human_bytes(summary['output_bytes'])}")
        print(f"Bytes saved:     {human_bytes(summary['saved_bytes'])}")
        print(f"Backup:          {state.backup_source}")
        print(f"Manifest:        {state.manifest_path}")


def parse_options(arguments: Sequence[str]) -> Options:
    parser = argparse.ArgumentParser(
        description=(
            "Create a complete project-local backup, then recursively resize and convert raster images. "
            "Defaults: WebP, 1024px maximum width, quality 82, remove validated originals."
        )
    )
    parser.add_argument("folder", help="Folder containing images (absolute or relative to the current directory).")
    parser.add_argument(
        "--output-format",
        choices=sorted((*OUTPUT_EXTENSIONS, "jpg")),
        default="webp",
        help="Converted image format (default: webp).",
    )
    parser.add_argument(
        "--max-width",
        type=positive_integer,
        default=1024,
        help="Maximum output width without upscaling (default: 1024).",
    )
    parser.add_argument(
        "--quality",
        type=quality_value,
        default=82,
        help="ImageMagick output quality from 1 to 100 (default: 82).",
    )

    removal = parser.add_mutually_exclusive_group()
    removal.add_argument(
        "--remove-original",
        dest="remove_original",
        action="store_true",
        help="Remove each original after its converted output validates (default).",
    )
    removal.add_argument(
        "--keep-originals",
        dest="remove_original",
        action="store_false",
        help="Keep original files beside converted outputs.",
    )
    parser.set_defaults(remove_original=True)

    recursion = parser.add_mutually_exclusive_group()
    recursion.add_argument(
        "--recursive",
        dest="recursive",
        action="store_true",
        help="Process nested folders (default).",
    )
    recursion.add_argument(
        "--no-recursive",
        dest="recursive",
        action="store_false",
        help="Process only files directly inside the target folder.",
    )
    parser.set_defaults(recursive=True)

    parser.add_argument(
        "--existing",
        choices=("error", "skip", "replace"),
        default="error",
        help="Behavior when a converted destination already exists (default: error).",
    )
    parser.add_argument(
        "--backup-root",
        help="Backup directory (default: <project-root>/arch/backups). Relative paths resolve from project root.",
    )
    parser.add_argument("--dry-run", action="store_true", help="Validate and print the plan without writing files.")
    parser.add_argument(
        "--skip-space-check",
        action="store_true",
        help="Bypass conservative free-space validation (not recommended).",
    )
    parsed = parser.parse_args(arguments)

    project_root = find_project_root()
    source = Path(parsed.folder).expanduser().resolve(strict=False)
    if parsed.backup_root:
        backup_root_input = Path(parsed.backup_root).expanduser()
        backup_root = (
            backup_root_input if backup_root_input.is_absolute() else project_root / backup_root_input
        ).resolve(strict=False)
    else:
        backup_root = project_root / "arch" / "backups"

    output_format = "jpeg" if parsed.output_format == "jpg" else parsed.output_format

    return Options(
        source=source,
        project_root=project_root,
        backup_root=backup_root,
        output_format=output_format,
        max_width=parsed.max_width,
        quality=parsed.quality,
        remove_original=parsed.remove_original,
        recursive=parsed.recursive,
        existing=parsed.existing,
        dry_run=parsed.dry_run,
        skip_space_check=parsed.skip_space_check,
    )


def main(arguments: Sequence[str] | None = None) -> int:
    try:
        options = parse_options(arguments if arguments is not None else sys.argv[1:])
        return ImageOptimizationRun(options, ImageMagick()).execute()
    except OptimizationError as error:
        print(f"ERROR: {error}", file=sys.stderr)
        return 2


if __name__ == "__main__":
    raise SystemExit(main())
