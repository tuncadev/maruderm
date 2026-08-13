import { execFileSync } from "node:child_process";
import { existsSync, readFileSync, readdirSync } from "node:fs";
import { dirname, relative, resolve } from "node:path";
import { fileURLToPath } from "node:url";

process.on("uncaughtException", (error) => {
  process.stderr.write(`${error.message}\n`);
  process.exitCode = 1;
});

const scriptDirectory = dirname(fileURLToPath(import.meta.url));
const themeRoot = resolve(scriptDirectory, "..");
const htmlRoot = resolve(
  process.env.MARUDERM_REFERENCE_ROOT ??
    resolve(themeRoot, "../../../../maruderm.html"),
);
const manifestPath = resolve(htmlRoot, "src/reference-assets.json");
const lockPath = resolve(themeRoot, "assets/reference/reference-assets.lock.json");
const consumerPath = resolve(scriptDirectory, "reference-assets.consumers.json");
const implementationPath = resolve(
  scriptDirectory,
  "reference-implementations.json",
);

const readJson = (path, label) => {
  if (!existsSync(path)) {
    throw new Error(`${label} not found: ${path}`);
  }

  try {
    return JSON.parse(readFileSync(path, "utf8"));
  } catch (error) {
    throw new Error(`${label} is not valid JSON: ${error.message}`);
  }
};

const filesUnder = (directory, suffix) =>
  readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
    const path = resolve(directory, entry.name);

    if (entry.isDirectory()) {
      return filesUnder(path, suffix);
    }

    return entry.isFile() && entry.name.endsWith(suffix) ? [path] : [];
  });

const sorted = (values) => [...values].sort((left, right) => left.localeCompare(right));
const sameValues = (left, right) =>
  JSON.stringify(sorted(left)) === JSON.stringify(sorted(right));

const assertStringArray = (value, label) => {
  if (!Array.isArray(value) || value.some((item) => typeof item !== "string" || item === "")) {
    throw new Error(`${label} must be an array of non-empty strings.`);
  }
};

const assertFiles = (root, paths, label) => {
  for (const path of paths) {
    if (!existsSync(resolve(root, path))) {
      throw new Error(`${label} file not found: ${path}`);
    }
  }
};

const manifest = existsSync(manifestPath)
  ? readJson(manifestPath, "HTML reference manifest")
  : readJson(lockPath, "Synchronized reference lock");
const consumerConfig = readJson(consumerPath, "Reference consumer configuration");
const registry = readJson(implementationPath, "Reference implementation registry");

if (manifest.version !== 1 || !Array.isArray(manifest.styles)) {
  throw new Error("Unsupported reference manifest or lock schema.");
}

if (consumerConfig.version !== 1 || !Array.isArray(consumerConfig.pending)) {
  throw new Error("Unsupported reference consumer configuration schema.");
}

if (registry.version !== 1 || !Array.isArray(registry.implementations)) {
  throw new Error("Unsupported reference implementation registry schema.");
}

const manifestTargets = manifest.styles.map(({ target }) => target);
const registryTargets = registry.implementations.map(({ target }) => target);
const registryIds = registry.implementations.map(({ id }) => id);

if (new Set(registryTargets).size !== registryTargets.length) {
  throw new Error("Reference implementation registry has duplicate targets.");
}

if (new Set(registryIds).size !== registryIds.length) {
  throw new Error("Reference implementation registry has duplicate IDs.");
}

if (!sameValues(manifestTargets, registryTargets)) {
  throw new Error(
    "Reference implementation registry targets must exactly match the HTML manifest.",
  );
}

const javascriptSources = filesUnder(resolve(themeRoot, "assets"), ".js").map(
  (path) => ({
    path: relative(themeRoot, path).replaceAll("\\", "/"),
    content: readFileSync(path, "utf8"),
  }),
);
const pendingTargets = new Set(consumerConfig.pending.map(({ target }) => target));
const implementationsByReferencePath = new Map();

for (const implementation of registry.implementations) {
  const manifestStyle = manifest.styles.find(
    ({ target }) => target === implementation.target,
  );
  const referencePaths = [
    ...(manifestStyle?.source ? [`src/${manifestStyle.source}`] : []),
    ...implementation.reference.markup.map((path) => `src/${path}`),
    ...implementation.reference.javascript.map((path) => `src/${path}`),
  ];

  for (const path of referencePaths) {
    const consumers = implementationsByReferencePath.get(path) ?? [];
    consumers.push(implementation.id);
    implementationsByReferencePath.set(path, consumers);
  }
}

for (const implementation of registry.implementations) {
  const { id, target, kind, reference, wordpress } = implementation;

  if (
    typeof id !== "string" ||
    id === "" ||
    typeof target !== "string" ||
    target === "" ||
    !["foundation", "component", "page"].includes(kind)
  ) {
    throw new Error("Every implementation requires a valid id, target, and kind.");
  }

  assertStringArray(reference?.markup, `${id} reference markup`);
  assertStringArray(reference?.javascript, `${id} reference JavaScript`);
  assertStringArray(wordpress?.entries, `${id} WordPress entries`);
  assertStringArray(wordpress?.renderers, `${id} WordPress renderers`);
  assertStringArray(wordpress?.routes, `${id} WordPress routes`);

  if (existsSync(manifestPath)) {
    assertFiles(resolve(htmlRoot, "src"), reference.markup, `${id} reference markup`);
    assertFiles(
      resolve(htmlRoot, "src"),
      reference.javascript,
      `${id} reference JavaScript`,
    );
  }

  assertFiles(themeRoot, wordpress.entries, `${id} WordPress entry`);
  assertFiles(themeRoot, wordpress.renderers, `${id} WordPress renderer`);
  assertFiles(
    themeRoot,
    wordpress.legacyFiles ?? [],
    `${id} WordPress legacy consumer`,
  );

  const importFragment = `reference/${target}`;
  const actualEntries = javascriptSources
    .filter(({ content }) => content.includes(importFragment))
    .map(({ path }) => path);

  if (!sameValues(actualEntries, wordpress.entries)) {
    throw new Error(
      `${id} registry entries do not match real Vite consumers for ${target}. Expected [${wordpress.entries.join(", ")}], found [${actualEntries.join(", ")}].`,
    );
  }

  if (wordpress.status === "implemented") {
    if (pendingTargets.has(target)) {
      throw new Error(`${id} is implemented but ${target} remains pending.`);
    }

    if (wordpress.entries.length === 0 || wordpress.routes.length === 0) {
      throw new Error(`${id} is implemented without a Vite entry or route.`);
    }

    if (kind !== "foundation" && wordpress.renderers.length === 0) {
      throw new Error(`${id} is implemented without a WordPress renderer.`);
    }
  } else if (wordpress.status === "pending") {
    if (!pendingTargets.has(target)) {
      throw new Error(`${id} is pending but ${target} is absent from the pending configuration.`);
    }

    if (
      wordpress.entries.length > 0 ||
      wordpress.renderers.length > 0 ||
      typeof wordpress.blocker !== "string" ||
      wordpress.blocker.trim() === ""
    ) {
      throw new Error(
        `${id} pending state requires no active entries/renderers and a concrete blocker.`,
      );
    }
  } else {
    throw new Error(`${id} has unsupported WordPress status: ${wordpress.status}`);
  }
}

const registryPendingTargets = registry.implementations
  .filter(({ wordpress }) => wordpress.status === "pending")
  .map(({ target }) => target);

if (!sameValues(registryPendingTargets, pendingTargets)) {
  throw new Error(
    "Pending targets must match between reference-implementations.json and reference-assets.consumers.json.",
  );
}

const required = process.argv.flatMap((argument, index, argumentsList) =>
  argument === "--require" && argumentsList[index + 1]
    ? [argumentsList[index + 1]]
    : [],
);

for (const requested of required) {
  const implementation = registry.implementations.find(
    ({ id, target }) => id === requested || target === requested,
  );

  if (!implementation) {
    throw new Error(`Requested reference implementation is not registered: ${requested}`);
  }

  if (implementation.wordpress.status !== "implemented") {
    throw new Error(
      `Requested reference implementation is still pending: ${implementation.id} (${implementation.target}). ${implementation.wordpress.blocker}`,
    );
  }
}

const labelWidth = Math.max(
  "Implementation".length,
  ...registry.implementations.map(({ id }) => id.length),
);
process.stdout.write(
  `${"Implementation".padEnd(labelWidth)}  Status       Target\n`,
);
process.stdout.write(`${"-".repeat(labelWidth)}  -----------  --------------------------\n`);

for (const implementation of registry.implementations) {
  process.stdout.write(
    `${implementation.id.padEnd(labelWidth)}  ${implementation.wordpress.status.padEnd(11)}  ${implementation.target}\n`,
  );
}

process.stdout.write(
  `\nReference handoff registry valid: ${registry.implementations.length} targets, ${registryPendingTargets.length} pending.\n`,
);

if (existsSync(resolve(htmlRoot, ".git"))) {
  const head = execFileSync(
    "git",
    ["-C", htmlRoot, "log", "-1", "--format=%h %s"],
    { encoding: "utf8" },
  ).trim();
  const status = execFileSync(
    "git",
    ["-C", htmlRoot, "status", "--porcelain=v1", "--untracked-files=all"],
    { encoding: "utf8" },
  ).trim();
  const dirtySourcePaths = status === ""
    ? []
    : status
        .split("\n")
        .map((line) => line.slice(3).trim().split(" -> ").at(-1))
        .filter((path) => path.startsWith("src/"));
  const changedImplementations = new Set();
  const unmappedSourcePaths = [];

  for (const path of dirtySourcePaths) {
    if (path === "src/reference-assets.json") {
      continue;
    }

    const implementations = implementationsByReferencePath.get(path);

    if (!implementations) {
      unmappedSourcePaths.push(path);
      continue;
    }

    implementations.forEach((id) => changedImplementations.add(id));
  }

  process.stdout.write(`HTML source: ${head || "no commits"}.\n`);
  process.stdout.write(
    `Dirty HTML source files: ${dirtySourcePaths.length}; mapped implementations: ${sorted(changedImplementations).join(", ") || "none"}.\n`,
  );

  if (unmappedSourcePaths.length > 0) {
    process.stdout.write(
      `Unmapped dirty HTML source files requiring manual intake: ${sorted(unmappedSourcePaths).join(", ")}.\n`,
    );
  }
}
