# Header banners

Optional artwork shown above the title, one per tab, cross-faded when you switch tabs.

| File | Tab |
| --- | --- |
| `header-needs.*`    | Tab 1 — Survival Needs |
| `header-diseases.*` | Tab 2 — Diseases & Medicine |
| `header-mods.*`     | Tab 3 — Mod Compatibility |

`webp`, `png`, `jpg`, `jpeg` and `gif` all work; the first extension found wins, so nothing needs
converting.

## Shape and size

**Any proportion works.** Images are scaled to fit rather than cropped, so nothing is cut off —
square, portrait and wide artwork all sit correctly. The frame is as tall as `34vw`, capped between
260 and 460 pixels, so a portrait image is not reduced to a narrow column.

Something around 700–1400 px on the long edge is plenty. Larger only costs load time.

## Making artwork blend

**A transparent PNG is the best option and needs nothing else.** The page shows through it at any
panel colour, it survives a palette change, and the edge feather is switched off automatically so
hair or a cloak reaching the frame edge is not faded away.

**For opaque artwork**, run `normalize.php` after adding or replacing a banner:

```sh
php normalize.php                 # normalise every header-* image here
php normalize.php --panel=1e1a16  # or target a different panel colour
```

It measures the backdrop from the image edges and shifts the whole picture so that backdrop lands
exactly on the WebUI panel colour, writing a lossless `.png` beside the original. The transform is a
per-channel translation — `out = panel + (in - backdrop)` — so every relative difference in the
artwork is preserved and the line work keeps precisely the contrast it was drawn with. A brightness
filter would have scaled those differences instead and flattened the drawing.

Transparent images are detected and skipped: the script writes through a canvas with no alpha
channel, so processing one would flatten its backdrop to solid and destroy the very thing making it
blend.

The edge feather in `config.php` is a finishing touch for opaque art, **not** what makes images
blend. Skip normalising and a backdrop lighter than the panel will still read as a soft rectangle.

If you change the palette in `config.php`, re-run this with the new `--panel`. Translations compose,
so normalising an already-normalised image is safe.

## Behaviour when files are missing

Each banner is independent. A tab with no image keeps showing the previous one rather than blanking,
and if none are present the frame is not rendered at all — the page is complete without any of them.

Cache-busting is automatic: the URL carries the file's modification time, so replacing artwork shows
up on a normal reload without a hard refresh.
