import { createHash } from "node:crypto";
import {
  existsSync,
  mkdirSync,
  readdirSync,
  readFileSync,
  writeFileSync,
} from "node:fs";
import { dirname, relative, resolve, sep } from "node:path";
import { fileURLToPath, pathToFileURL } from "node:url";

const scriptDirectory = dirname(fileURLToPath(import.meta.url));

export const themeRoot = resolve(scriptDirectory, "..");
export const referenceRoot = resolve(
  process.env.MARUDERM_REFERENCE_ROOT ??
    resolve(themeRoot, "../../../../maruderm.html"),
);
export const referenceSourceRoot = resolve(referenceRoot, "src");
export const referenceManifestPath = resolve(
  referenceSourceRoot,
  "reference-assets.json",
);
export const synchronizedAssetRoot = resolve(themeRoot, "assets/reference");
export const synchronizedLockPath = resolve(
  synchronizedAssetRoot,
  "reference-assets.lock.json",
);

const digest = (content) =>
  createHash("sha256").update(content).digest("hex");

const javascriptFiles = (directory) =>
  readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
    const path = resolve(directory, entry.name);

    if (entry.isDirectory()) {
      return javascriptFiles(path);
    }

    return entry.isFile() && entry.name.endsWith(".js") ? [path] : [];
  });

const verifyStyleConsumers = (styles) => {
  const sources = javascriptFiles(resolve(themeRoot, "assets")).map((path) => ({
    path,
    content: readFileSync(path, "utf8"),
  }));

  for (const style of styles) {
    const importFragment = `reference/${style.target.replaceAll("\\", "/")}`;
    const consumers = sources.filter(({ content }) =>
      content.includes(importFragment),
    );

    if (consumers.length === 0) {
      throw new Error(
        `Synchronized reference style has no WordPress Vite consumer: ${style.target}`,
      );
    }
  }
};

const assertInside = (root, candidate, label) => {
  const path = relative(root, candidate);

  if (path === "" || (!path.startsWith(`..${sep}`) && path !== "..")) {
    return;
  }

  throw new Error(`${label} escapes its allowed root: ${candidate}`);
};

const readManifest = () => {
  if (!existsSync(referenceManifestPath)) {
    throw new Error(
      `Reference manifest not found: ${referenceManifestPath}. Set MARUDERM_REFERENCE_ROOT when the HTML repository is elsewhere.`,
    );
  }

  const content = readFileSync(referenceManifestPath, "utf8");
  const manifest = JSON.parse(content);

  if (manifest.version !== 1 || !Array.isArray(manifest.styles)) {
    throw new Error("Unsupported or invalid reference-assets.json manifest.");
  }

  return { content, manifest };
};

const verifySynchronizedSnapshot = ({ quiet = false } = {}) => {
  if (!existsSync(synchronizedLockPath)) {
    throw new Error(
      `Reference source and synchronized lock are both unavailable: ${referenceManifestPath}`,
    );
  }

  const lock = JSON.parse(readFileSync(synchronizedLockPath, "utf8"));

  if (lock.version !== 1 || !Array.isArray(lock.styles)) {
    throw new Error("Invalid synchronized reference asset lock.");
  }

  for (const entry of lock.styles) {
    const target = resolve(synchronizedAssetRoot, entry.target);
    assertInside(synchronizedAssetRoot, target, "Synchronized target");

    if (!existsSync(target) || digest(readFileSync(target)) !== entry.sha256) {
      throw new Error(`Synchronized snapshot is invalid: ${entry.target}`);
    }
  }

  verifyStyleConsumers(lock.styles);

  if (!quiet) {
    process.stdout.write(
      `Reference source unavailable; verified ${lock.styles.length} checked-in snapshot assets.\n`,
    );
  }

  return { manifest: null, records: [], synchronized: [] };
};

export const referenceAssetRecords = () => {
  const { manifest } = readManifest();
  const targets = new Set();

  return manifest.styles.map((entry) => {
    if (
      typeof entry?.source !== "string" ||
      entry.source === "" ||
      typeof entry?.target !== "string" ||
      entry.target === ""
    ) {
      throw new Error("Every reference style requires source and target paths.");
    }

    const source = resolve(referenceSourceRoot, entry.source);
    const target = resolve(synchronizedAssetRoot, entry.target);
    assertInside(referenceSourceRoot, source, "Reference source");
    assertInside(synchronizedAssetRoot, target, "Synchronized target");

    if (!existsSync(source)) {
      throw new Error(`Reference source not found: ${source}`);
    }

    if (targets.has(target)) {
      throw new Error(`Duplicate synchronized target: ${entry.target}`);
    }

    targets.add(target);

    return { ...entry, sourcePath: source, targetPath: target };
  });
};

export const referenceAssetWatchPaths = () =>
  existsSync(referenceManifestPath)
    ? [
        referenceManifestPath,
        ...referenceAssetRecords().map(({ sourcePath }) => sourcePath),
      ]
    : [];

export const syncReferenceAssets = ({ check = false, quiet = false } = {}) => {
  if (!existsSync(referenceManifestPath)) {
    return verifySynchronizedSnapshot({ quiet });
  }

  const { content: manifestContent, manifest } = readManifest();
  const records = referenceAssetRecords();
  const synchronized = [];
  const lock = {
    version: 1,
    manifest: "src/reference-assets.json",
    manifestSha256: digest(manifestContent),
    sourceRepository: "maruderm.html",
    styles: [],
  };

  for (const record of records) {
    const sourceContent = readFileSync(record.sourcePath);
    const targetContent = existsSync(record.targetPath)
      ? readFileSync(record.targetPath)
      : null;
    const matches =
      targetContent !== null && sourceContent.equals(targetContent);

    if (check && !matches) {
      throw new Error(`Synchronized asset is stale: ${record.target}`);
    }

    if (!check && !matches) {
      mkdirSync(dirname(record.targetPath), { recursive: true });
      writeFileSync(record.targetPath, sourceContent);
      synchronized.push(record.target);
    }

    lock.styles.push({
      source: record.source,
      target: record.target,
      sha256: digest(sourceContent),
    });
  }

  const lockContent = `${JSON.stringify(lock, null, 2)}\n`;
  const currentLock = existsSync(synchronizedLockPath)
    ? readFileSync(synchronizedLockPath, "utf8")
    : null;

  if (check && currentLock !== lockContent) {
    throw new Error("Synchronized reference asset lock is stale.");
  }

  if (!check && currentLock !== lockContent) {
    mkdirSync(synchronizedAssetRoot, { recursive: true });
    writeFileSync(synchronizedLockPath, lockContent, "utf8");
    synchronized.push("reference-assets.lock.json");
  }

  verifyStyleConsumers(lock.styles);

  if (!quiet) {
    const state = check
      ? `verified ${records.length}`
      : synchronized.length > 0
        ? `updated ${synchronized.length}`
        : `already current (${records.length})`;
    process.stdout.write(`Reference assets: ${state}.\n`);
  }

  return { manifest, records, synchronized };
};

const invokedDirectly =
  process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href;

if (invokedDirectly) {
  try {
    syncReferenceAssets({ check: process.argv.includes("--check") });
  } catch (error) {
    process.stderr.write(`${error.message}\n`);
    process.exitCode = 1;
  }
}
