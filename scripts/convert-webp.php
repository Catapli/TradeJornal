<?php

// Conversión puntual de los logos referenciados en vistas a WebP.
// Uso: php scripts/convert-webp.php

if (!function_exists('imagewebp')) {
    exit("ERROR: GD sin soporte WebP\n");
}

$base = __DIR__ . '/../public/img/';

// [origen, destino, ancho máximo (null = mantener)]
$jobs = [
    ['logo_o.png', 'logo_o.webp', null],
    ['logo_trader_h.png', 'logo_trader_h.webp', null],
    ['detrafic_large_white.png', 'detrafic_large_white.webp', 1200],
];

foreach ($jobs as [$src, $dst, $maxWidth]) {
    $img = imagecreatefrompng($base . $src);

    if ($maxWidth !== null && imagesx($img) > $maxWidth) {
        $ratio = $maxWidth / imagesx($img);
        $img = imagescale($img, $maxWidth, (int) round(imagesy($img) * $ratio), IMG_BICUBIC);
    }

    imagepalettetotruecolor($img);
    imagealphablending($img, false);
    imagesavealpha($img, true);

    imagewebp($img, $base . $dst, 85);
    imagedestroy($img);

    printf(
        "%s (%d KB) -> %s (%d KB)\n",
        $src,
        (int) (filesize($base . $src) / 1024),
        $dst,
        (int) (filesize($base . $dst) / 1024)
    );
}

echo "OK\n";
