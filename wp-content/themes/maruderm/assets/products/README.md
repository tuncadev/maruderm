# Product assets

This directory stores product images in one flat folder per published WooCommerce SKU/barcode:

```text
assets/products/<13-digit-sku>/
```

The flat layout is intentional: product categories can change, while the SKU remains the stable lookup key. Empty product folders contain `.gitkeep` so their structure survives Git deployments.

Transferred photos retain their source classification:

```text
assets/products/<13-digit-sku>/product/
assets/products/<13-digit-sku>/promotion/
```

`product/` contains catalog-suitable packshots, while `promotion/` contains lifestyle, model, application, grouped-product, flat-lay, texture, and other campaign/supporting images. MP4 videos and source barcode sets without a published WooCommerce product are not stored here.

WordPress resolves browser-safe images in this order: `product/`, files placed directly in the barcode folder, then up to three images from `promotion/`. The first resolved image is used wherever WooCommerce requests a product image, and the resolved set is used by the single-product gallery. When a folder has no supported JPG, JPEG, PNG, WebP, AVIF, or GIF file, the normal WooCommerce product image or placeholder remains active. TIFF files are retained as source assets but are not sent to browsers.
