# storage:variants

```bash
vendor/bin/laminas storage:variants [--backend=r2] [--prefix=asset/news] [--limit=500] [--generate] [-v]
```

Walks the backend recursively from `--prefix` and, for every image original
(keys containing `__` are variant siblings and skipped):

- without `--generate`, reports the variant keys it lacks through
  `MissingVariantsReporterInterface` (the backend must implement it);
- with `--generate`, creates them through `regenerateMissingVariants()`.

It prints a table of originals, complete originals, missing or generated keys
and errors; `-v` lists every key and a progress line every 100 originals. An
original deleted during the run is skipped, not counted as an error. The exit
code is 1 when any original failed or the backend is unknown or cannot report.

`--backend` defaults to the primary backend. Which variants an original is
owed comes from `storage.variants` and `storage.paths`, applied by the backend.
