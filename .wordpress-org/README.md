# WordPress.org Plugin Assets

Files in this folder are **NOT** part of the plugin you ship to users. The
`10up/action-wordpress-plugin-deploy` action publishes them to the WordPress.org
SVN `assets/` directory (separate from `trunk/`), where they power the public
plugin listing at:

https://wordpress.org/plugins/raybogman-ai-content-orchestrator

All filenames are **lowercase and case-sensitive**. Use **PNG or JPG** (8-bit,
no animation). Drop the files directly in this folder, then push a `v*` tag —
asset changes deploy automatically and do **not** require a version bump.

---

## 1. Plugin icon  (required — shows in search results & the plugin header)

| File                | Size (px)   | Notes                          |
|---------------------|-------------|--------------------------------|
| `icon-128x128.png`  | 128 × 128   | Standard display               |
| `icon-256x256.png`  | 256 × 256   | Retina / high-DPI              |
| `icon.svg`          | vector      | Optional — used instead of PNG if present |

- Square. Keep it legible at 128px (it renders small in search).
- If you provide `icon.svg`, still include `icon-256x256.png` as a fallback.

## 2. Banner  (recommended — the wide header image on the plugin page)

| File                 | Size (px)    | Notes              |
|----------------------|--------------|--------------------|
| `banner-772x250.png` | 772 × 250    | Standard           |
| `banner-1544x500.png`| 1544 × 500   | Retina / high-DPI  |

- Exact dimensions required. Keep important content/text away from the far
  right — the plugin name overlays the lower-left on some themes.

## 3. Screenshots  (recommended)

| File               | Notes                                                |
|--------------------|------------------------------------------------------|
| `screenshot-1.png` | Maps to caption #1 in readme.txt `== Screenshots ==` |
| `screenshot-2.png` | Maps to caption #2, and so on, in order              |

- Numbered sequentially starting at `1`. Each must have a matching caption line
  in the `== Screenshots ==` section of `readme.txt`, in the same order.
- PNG or JPG. ~1280px wide is a good target; keep them sharp but reasonably sized.

---

## Checklist before tagging a release

- [ ] `icon-256x256.png` (and `icon-128x128.png` or `icon.svg`) added
- [ ] `banner-772x250.png` (and ideally `banner-1544x500.png`) added
- [ ] Screenshots numbered `screenshot-1.png`… with matching captions in `readme.txt`
- [ ] All filenames lowercase, correct exact dimensions
