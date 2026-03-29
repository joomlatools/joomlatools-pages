# extension-joomla

Site-local override of the Joomlatools Pages `ext:joomla` extension. Changes here shadow the bundle copy.

**Bundle copy (source of truth for sync):** `~/Documents/waz-repos/com_pages/extensions/extension-joomla/`
**Sync destinations:** wellfoundation site (here), wfcs site (`/Users/waseem/Workspace/www/sites/wfcs/sites/wfcs/extensions/extension-joomla/`), com_pages bundle (above)

---

## model/entity/article.php

### Custom additions vs upstream

#### 1. Dual image access — intro and full (added 2026-03-28)

The upstream `setPropertyImage` collapsed Joomla's two article images (intro + fulltext) into a single `$article->image` property. We extended the entity to expose both separately.

**New properties:**

| Expression | Returns |
|---|---|
| `$article->image->url` | Primary image (fulltext takes priority, intro fallback) — unchanged, backward compat |
| `$article->intro->image->url` | Intro image URL (`KHttpUrl`) — empty string if not set |
| `$article->intro->image->alt` | Intro image alt text |
| `$article->intro->image->caption` | Intro image caption |
| `$article->full->image->url` | Fulltext image URL (`KHttpUrl`) — empty string if not set |
| `$article->full->image->alt` | Fulltext image alt text |
| `$article->full->image->caption` | Fulltext image caption |

**How it works:**

- `setPropertyImage` now stores the raw decoded JSON in `$this->_images = []` (class property) before the existing logic
- URL normalization extracted to `private _normalizeImageUrl($url)` — strips `#joomlaImage://...` fragment via `Joomla\CMS\HTML\HTMLHelper::cleanImageURL()`, converts remaining relative paths to absolute `https://` URLs using `request->getBaseUrl()`, wraps in `KHttpUrl`
- `getPropertyIntro()` reads `$this->_images['image_intro*']` and returns `ComPagesObjectConfig(['image' => [...]])`
- `getPropertyFull()` reads `$this->_images['image_fulltext*']` and returns the same structure

**Image URL stripping (Joomla 6 media manager):**

Fragment stripping is handled internally by `_normalizeImageUrl()` — all three URL properties already return clean absolute `https://` URLs. No template-level stripping is needed.

```php
// Correct — entity already returns a clean absolute URL
$image = (string) $article->intro->image->url ?: 'images://fallback.jpg';
$image = (string) $article->full->image->url;
```
