---
name: optimize-project-images
description: Safely reduce and convert Maruderm image folders with a complete project-local backup, deterministic ImageMagick conversion, validation, and a recovery manifest. Use when product or other project raster images need resizing, WebP/AVIF/JPEG/PNG conversion, or metadata stripping; do not use for AI image editing or SVG assets.
---

# Optimize Project Images

Use `scripts/optimize_images.py`. It scans recursively, backs up the complete target folder under `<project-root>/arch/backups/`, converts sequentially, validates every output, and writes a JSON run manifest beside the backup.

## Workflow

1. Inspect the target path and confirm it is the intended folder. Never target the project root or a backup folder.
2. Run a dry run first and review the file count, collisions, backup destination, and estimated space requirement:

   ```bash
   python3 .agents/skills/optimize-project-images/scripts/optimize_images.py /absolute/folder/path --dry-run
   ```

3. Run the real conversion only when image mutation is within the user's request. Defaults are WebP, maximum width 1024 px, quality 82, and removal of each original only after its replacement validates:

   ```bash
   python3 .agents/skills/optimize-project-images/scripts/optimize_images.py /absolute/folder/path
   ```

4. Report the backup and manifest paths, converted/skipped counts, bytes saved, and any failure. Do not delete backups automatically.

## Useful options

- `--output-format webp|avif|jpeg|jpg|png`
- `--max-width PIXELS`
- `--quality 1..100`
- `--keep-originals` to retain source files beside converted outputs
- `--existing error|skip|replace` for pre-existing destination files; keep `error` unless replacement is explicitly intended
- `--no-recursive` for only the target folder
- `--backup-root PATH` to override the project backup location
- `--skip-space-check` only after independently proving capacity

The backup is a browsable directory copy. TIFF and GIF inputs are intentionally converted from their first frame because this workflow targets still product imagery. SVG files and symlinks are not converted.
