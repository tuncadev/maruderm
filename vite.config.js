import { rmSync, writeFileSync } from "node:fs";
import { dirname, resolve } from "node:path";
import { fileURLToPath } from "node:url";
import { defineConfig } from "vite";
import tailwindcss from "@tailwindcss/vite";

const __dirname = dirname(fileURLToPath(import.meta.url));
const devServerOrigin =
  process.env.MARUDERM_VITE_DEV_SERVER_URL ?? "http://localhost:5173";
const hotFilePath = resolve(__dirname, "vite.hot");

function marudermWordPress() {
  const cleanupHotFile = () => rmSync(hotFilePath, { force: true });

  return {
    name: "maruderm-wordpress",
    buildStart() {
      cleanupHotFile();
    },
    configureServer(server) {
      const writeHotFile = () => {
        writeFileSync(
          hotFilePath,
          `${server.config.server.origin ?? devServerOrigin}\n`,
          "utf8",
        );
      };

      server.httpServer?.once("listening", writeHotFile);
      server.httpServer?.once("close", cleanupHotFile);

      process.once("exit", cleanupHotFile);
      process.once("SIGINT", cleanupHotFile);
      process.once("SIGTERM", cleanupHotFile);
    },
  };
}

export default defineConfig({
  base: "",
  plugins: [tailwindcss(), marudermWordPress()],
  server: {
    host: true,
    port: 5173,
    strictPort: true,
    origin: devServerOrigin,
  },
  build: {
    outDir: "dist",
    manifest: "manifest.json",
    emptyOutDir: true,
    cssCodeSplit: true,
    rollupOptions: {
      input: {
        globals: resolve(__dirname, "assets/globals/index.js"),
        frontend: resolve(__dirname, "assets/frontend/index.js"),
      },
    },
  },
});
