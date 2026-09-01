#!/usr/bin/env node

import { readFile, rename, writeFile } from "node:fs/promises";
import process from "node:process";

class RussianProductTranslationGenerator {
  constructor({ apiKey, inputPath, outputPath, model, batchSize = 6 }) {
    this.apiKey = apiKey;
    this.inputPath = inputPath;
    this.outputPath = outputPath;
    this.partialPath = `${outputPath}.partial`;
    this.model = model;
    this.batchSize = batchSize;
  }

  async run() {
    if (!this.apiKey) {
      throw new Error("GEMINI_API is required.");
    }

    const source = JSON.parse(await readFile(this.inputPath, "utf8"));
    if (!Array.isArray(source)) {
      throw new Error("Input must be a JSON array.");
    }

    const translations = [];

    for (let offset = 0; offset < source.length; offset += this.batchSize) {
      const batch = source.slice(offset, offset + this.batchSize);
      const translatedBatch = await this.translateBatch(batch);
      translations.push(...translatedBatch);
      await this.persist(translations, false);
      process.stdout.write(
        `translated ${translations.length}/${source.length} products\n`,
      );
    }

    await this.persist(translations, true);

    return {
      status: "ok",
      model: this.model,
      count: translations.length,
      outputPath: this.outputPath,
    };
  }

  async translateBatch(batch) {
    const endpoint = `https://generativelanguage.googleapis.com/v1beta/models/${this.model}:generateContent?key=${encodeURIComponent(this.apiKey)}`;
    const response = await fetch(endpoint, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        systemInstruction: {
          parts: [
            {
              text: [
                "Translate Ukrainian ecommerce product content into natural Russian.",
                "Return every input SKU exactly once and never change SKUs.",
                "Preserve Maruderm, trademarked names, INCI/ingredient names, percentages, numbers, units, shade codes, and factual product claims.",
                "Translate only title_uk and description_uk into title_ru and description_ru.",
                "Preserve safe HTML tags and paragraph/list structure when HTML exists; do not add markdown or commentary.",
                "Do not invent benefits, warnings, ingredients, medical claims, or missing information.",
              ].join(" "),
            },
          ],
        },
        contents: [
          {
            role: "user",
            parts: [
              {
                text: JSON.stringify(
                  batch.map(({ sku, title_uk, description_uk }) => ({
                    sku,
                    title_uk,
                    description_uk,
                  })),
                ),
              },
            ],
          },
        ],
        generationConfig: {
          temperature: 0.2,
          responseMimeType: "application/json",
          responseJsonSchema: {
            type: "array",
            items: {
              type: "object",
              required: ["sku", "title_ru", "description_ru"],
              properties: {
                sku: { type: "string" },
                title_ru: { type: "string" },
                description_ru: { type: "string" },
              },
              additionalProperties: false,
            },
          },
        },
      }),
    });

    if (!response.ok) {
      throw new Error(
        `Gemini request failed (${response.status}): ${await response.text()}`,
      );
    }

    const payload = await response.json();
    const text = payload?.candidates?.[0]?.content?.parts
      ?.map((part) => part.text ?? "")
      .join("");
    const translations = JSON.parse(text || "null");

    this.validateBatch(batch, translations);
    return translations;
  }

  validateBatch(source, translations) {
    if (!Array.isArray(translations) || translations.length !== source.length) {
      throw new Error("Translation batch cardinality does not match its source.");
    }

    const expectedSkus = new Set(source.map(({ sku }) => String(sku)));
    const returnedSkus = new Set();

    for (const translation of translations) {
      const sku = String(translation?.sku ?? "").trim();
      const title = String(translation?.title_ru ?? "").trim();
      const description = String(translation?.description_ru ?? "").trim();

      if (!expectedSkus.has(sku) || returnedSkus.has(sku)) {
        throw new Error(`Unexpected or duplicate translated SKU: ${sku}`);
      }
      if (!title || !description || !/[А-Яа-яЁё]/u.test(`${title} ${description}`)) {
        throw new Error(`Translation is empty or lacks Russian Cyrillic: ${sku}`);
      }

      returnedSkus.add(sku);
      translation.sku = sku;
      translation.title_ru = title;
      translation.description_ru = description;
    }
  }

  async persist(products, complete) {
    const artifact = {
      generated_at_utc: new Date().toISOString(),
      source: "Local published WooCommerce Ukrainian title and description",
      target_language: "ru",
      translation_method: `${this.model} with constrained preservation instructions and deterministic SKU validation`,
      complete,
      products,
    };

    await writeFile(this.partialPath, `${JSON.stringify(artifact, null, 2)}\n`, {
      mode: 0o600,
    });

    if (complete) {
      await rename(this.partialPath, this.outputPath);
    }
  }
}

const [, , inputPath, outputPath] = process.argv;
if (!inputPath || !outputPath) {
  throw new Error(
    "Usage: generate-russian-product-translations.mjs <input.json> <output.json>",
  );
}

const generator = new RussianProductTranslationGenerator({
  apiKey: process.env.GEMINI_API,
  inputPath,
  outputPath,
  model: process.env.GEMINI_MODEL || "gemini-3.1-flash-lite",
});

const result = await generator.run();
process.stdout.write(`${JSON.stringify(result)}\n`);
