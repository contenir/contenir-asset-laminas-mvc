# Upgrading from 0.x to 2.0

2.0 keeps the 0.6 API. The platform requirement changes and a few internals
are tightened.

| | 0.x | 2.0 |
| --- | --- | --- |
| PHP | ^8.1 | 8.3, 8.4 or 8.5 |
| contenir/storage | ^0.6.1 | ^0.6.1 or ^2.0 |
| psr/container | ^1.0 \|\| ^2.0 | ^1.1 \|\| ^2.0 |
| laminas/laminas-mvc | ^3.0 | ^3.8 (first release clean under PHP 8.4) |
| symfony/console | via laminas-cli | ^6.4.10 \|\| ^7.1.3, declared directly (used by `VariantsCommand`) |

```bash
composer require contenir/contenir-asset-laminas-mvc:^2.0
```

Projects that must stay on PHP 8.1 or 8.2 can keep using `^0.6`, maintained
on the `0.x` branch.

## Typed class constants

```php
// 0.x
public const BACKEND_LOCAL = 'local';
public const PREVIEW_VARIANT = 'admin-thumb';

// 2.0
public const string BACKEND_LOCAL = 'local';
public const string PREVIEW_VARIANT = 'admin-thumb';
```

All classes were already `final`, so nothing can redeclare them.

## AssetVariantController returns its response

```php
// 0.x: headers sent with header(), body with readfile(), then exit.
// 2.0: a Laminas\Http\Response with the body and headers, returned normally.
```

Listeners on `MvcEvent::EVENT_FINISH` now run for variant requests. The
`Cache-Control` value is serialised by laminas-http as
`max-age=31536000, public`.

## Folders with `..` are refused

`/asset/%2e%2e/…/_variant/<name>/<file>` used to resolve outside the asset
directory. It now returns 404.

## Factories check service types

A container entry of the wrong type (for example `StorageManager::class`
registered as something else) raises `UnexpectedValueException` naming the
service, instead of a `TypeError`.

## Empty backend options count as unset

An empty `root_path` now falls back to `public`, and an empty `binary` to
auto-discovery, instead of being used as-is.
