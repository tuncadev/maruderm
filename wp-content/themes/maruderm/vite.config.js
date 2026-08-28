import { rmSync, writeFileSync } from "node:fs";
import { dirname, resolve } from "node:path";
import { fileURLToPath } from "node:url";
import { defineConfig } from "vite";
import tailwindcss from "@tailwindcss/vite";
import {
  referenceAssetWatchPaths,
  syncReferenceAssets,
} from "./scripts/reference-assets.mjs";

const __dirname = dirname(fileURLToPath(import.meta.url));
const devServerOrigin =
  process.env.MARUDERM_VITE_DEV_SERVER_URL ?? "http://localhost:5173";
const devServerPort = Number(new URL(devServerOrigin).port || 5173);
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

function marudermReferenceAssets() {
  return {
    name: "maruderm-reference-assets",
    buildStart() {
      syncReferenceAssets({ quiet: true });
    },
    configureServer(server) {
      const watch = () => server.watcher.add(referenceAssetWatchPaths());
      watch();

      server.watcher.on("change", (changedPath) => {
        if (!referenceAssetWatchPaths().includes(resolve(changedPath))) {
          return;
        }

        syncReferenceAssets();
        watch();
        server.ws.send({ type: "full-reload", path: "*" });
      });
    },
  };
}

export default defineConfig({
  base: "",
  plugins: [tailwindcss(), marudermReferenceAssets(), marudermWordPress()],
  server: {
    host: true,
    port: devServerPort,
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
        catalog: resolve(__dirname, "assets/catalog/index.js"),
        product: resolve(__dirname, "assets/product/index.js"),
        cart: resolve(__dirname, "assets/cart/index.js"),
        delivery: resolve(__dirname, "assets/delivery/index.js"),
        payment: resolve(__dirname, "assets/payment/index.js"),
        "bank-transfer": resolve(__dirname, "assets/bank-transfer/index.js"),
        "checkout-result": resolve(__dirname, "assets/checkout-result/index.js"),
        "landing-page": resolve(__dirname, "assets/landing-page/index.js"),
        home: resolve(__dirname, "assets/home/index.js"),
        footer: resolve(__dirname, "assets/footer/index.js"),
        "campaign-popup": resolve(__dirname, "assets/campaign-popup/index.js"),
        "legal-document": resolve(__dirname, "assets/legal-document/index.js"),
        "hair-analysis": resolve(__dirname, "assets/hair-analysis/index.js"),
        login: resolve(__dirname, "assets/login/index.js"),
        account: resolve(__dirname, "assets/account/index.js"),
      },
    },
  },
});
