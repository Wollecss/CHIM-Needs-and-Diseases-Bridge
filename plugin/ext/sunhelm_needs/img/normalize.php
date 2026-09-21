<?php
/**
 * Normalises header artwork so its backdrop matches the WebUI panel exactly.
 *
 *   php normalize.php            # normalise every header-* image in this folder
 *   php normalize.php --panel=1e1a16
 *
 * Why this exists
 * ---------------
 * The banners are line art on a flat dark backdrop, and each one arrives a slightly different
 * shade - measured between #241f1a and #2f2720 against a #1e1a16 panel. Feathering the edges hides
 * a mismatch but does not remove it: anything lighter than the page still reads as a soft rectangle
 * sitting on top of it.
 *
 * The transform is a per-channel translation:
 *
 *     out = panel + (in - backdrop)
 *
 * which lands the backdrop precisely on the panel colour while preserving every relative difference
 * in the artwork. A brightness filter would have scaled those differences and flattened the line
 * work; this shifts them instead, so the drawing keeps exactly the contrast the artist gave it.
 *
 * Output is PNG - lossless, so re-normalising never compounds JPEG artefacts, and config.php
 * prefers .png over .jpg when both exist.
 */

$panelHex = '1e1a16';
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--panel=#?([0-9a-fA-F]{6})$/', $arg, $m)) {
        $panelHex = strtolower($m[1]);
    }
}
$panel = [
    hexdec(substr($panelHex, 0, 2)),
    hexdec(substr($panelHex, 2, 2)),
    hexdec(substr($panelHex, 4, 2)),
];

$dir = __DIR__;
$sources = glob($dir . '/header-*.{jpg,jpeg,png,webp}', GLOB_BRACE);
if (!$sources) {
    fwrite(STDERR, "No header-* images found in {$dir}\n");
    exit(1);
}

foreach ($sources as $path) {
    $name = basename($path);
    // Skip anything already produced by a previous run.
    if (str_ends_with($name, '.norm.png')) {
        continue;
    }

    $im = @imagecreatefromstring(file_get_contents($path));
    if (!$im) {
        fwrite(STDERR, "  skipped {$name}: unreadable\n");
        continue;
    }
    imagepalettetotruecolor($im);
    $w = imagesx($im);
    $h = imagesy($im);

    // Artwork with a transparent backdrop is already perfect - the page shows through it, at any
    // panel colour, with no shifting required. Bail out rather than process it: this routine writes
    // through a truecolour canvas with no alpha channel, so running it on a transparent image would
    // silently flatten the backdrop to solid and destroy the very thing that made it blend.
    if ((imagecolorat($im, 2, 2) >> 24) & 0x7F) {
        printf("  %-24s transparent backdrop - already blends, left alone\n", $name);
        imagedestroy($im);
        continue;
    }

    // Backdrop sampled from the edges, where the figure is not. The median of each channel rather
    // than the mean, so one corner clipped by artwork cannot drag the estimate.
    $samples = [[2, 2], [$w - 3, 2], [2, $h - 3], [$w - 3, $h - 3],
                [2, intdiv($h, 2)], [$w - 3, intdiv($h, 2)],
                [intdiv($w, 2), 2], [intdiv($w, 2), $h - 3]];
    $chan = [[], [], []];
    foreach ($samples as [$x, $y]) {
        $c = imagecolorat($im, $x, $y);
        $chan[0][] = ($c >> 16) & 0xFF;
        $chan[1][] = ($c >> 8) & 0xFF;
        $chan[2][] = $c & 0xFF;
    }
    $backdrop = [];
    foreach ($chan as $values) {
        sort($values);
        $backdrop[] = (int) round(($values[intdiv(count($values) - 1, 2)] + $values[intdiv(count($values), 2)]) / 2);
    }

    $shift = [$panel[0] - $backdrop[0], $panel[1] - $backdrop[1], $panel[2] - $backdrop[2]];

    // Lookup tables rather than per-pixel arithmetic: 256 clamps instead of millions.
    $lut = [[], [], []];
    for ($i = 0; $i < 256; $i++) {
        for ($k = 0; $k < 3; $k++) {
            $lut[$k][$i] = max(0, min(255, $i + $shift[$k]));
        }
    }

    $out = imagecreatetruecolor($w, $h);
    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $c = imagecolorat($im, $x, $y);
            imagesetpixel($out, $x, $y, imagecolorallocate(
                $out,
                $lut[0][($c >> 16) & 0xFF],
                $lut[1][($c >> 8) & 0xFF],
                $lut[2][$c & 0xFF]
            ));
        }
    }

    $target = $dir . '/' . preg_replace('/\.(jpe?g|png|webp)$/i', '', $name) . '.png';
    imagepng($out, $target, 9);
    imagedestroy($im);
    imagedestroy($out);

    printf("  %-24s backdrop #%02x%02x%02x -> #%s  (shift %+d %+d %+d)  wrote %s\n",
        $name, $backdrop[0], $backdrop[1], $backdrop[2], $panelHex,
        $shift[0], $shift[1], $shift[2], basename($target));
}
