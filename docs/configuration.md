# Configuration

The module reads only the `storage` block, through `contenir/storage`'s
`StorageConfig::primaryBackendConfig()`:

| Key on the primary backend | Used by | Default |
| --- | --- | --- |
| `type` | URL scheme: `local` gives `_variant/<name>/<file>`, anything else sibling keys | `local` |
| `public_path` | URL prefix for a local backend | `''` |
| `publicUrl` / `public_base_url` | CDN base for an S3/R2 backend (`public_base_url` wins) | `''` |
| `root_path` | Web root holding `asset/…` originals for on-demand generation | `public` |
| `binary` | ImageMagick CLI path; auto-discovered when unset | — |
| `generate_secret` | Shared secret for `/asset-variant/generate`; empty disables it | `''` |

`storage.variants` feeds `ProfileProviderService`:

- a `dimensions` ladder becomes a responsive profile whose variants are
  `<name>-<width>`; `sizes` and `formats` are read from the same entry;
  `'role' => 'preview'` registers the variants but no profile;
- a legacy `variants` map (`'tile' => ['variants' => ['tile-320' => […]], 'sizes' => …]`)
  is still accepted; `admin-thumb` inside it is never part of the srcset;
- a flat variant (`width`/`height`) is registered for lookup only.

Variant names must be unique across profiles: the request URL carries only
the name.

The module also registers `storage.asset` defaults (`root_path`,
`public_path`) for backwards compatibility; the factories read the primary
backend instead.

`StorageManager` must be registered by the application (for example with a
factory calling `StorageConfig::fromArray()`) for the CLI command and the
edge generation endpoint.
