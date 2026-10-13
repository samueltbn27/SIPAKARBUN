<?php

// Deterministic, locally drawn test fixtures. No external photos or fonts.
// The seeder uses the committed PNG files and does not require GD at runtime.
if (PHP_SAPI !== 'cli' || ! extension_loaded('gd')) {
    throw new RuntimeException('Jalankan generator fixture melalui PHP CLI dengan extension GD.');
}

foreach (['awal' => 'PENGAMATAN AWAL', 'akhir' => 'EVALUASI AKHIR'] as $stage => $label) {
    $canvas = imagecreatetruecolor(920, 600);
    $background = imagecolorallocate($canvas, 242, 248, 244);
    $green = imagecolorallocate($canvas, 23, 107, 69);
    $leaf = imagecolorallocate($canvas, 155, 199, 166);
    $muted = imagecolorallocate($canvas, 82, 97, 89);
    $white = imagecolorallocate($canvas, 255, 255, 255);
    imagefill($canvas, 0, 0, $background);
    imagefilledrectangle($canvas, 0, 0, 919, 105, $green);
    $title = imagecreatetruecolor(260, 20);
    imagefill($title, 0, 0, $green);
    imagestring($title, 5, 0, 1, 'DATA DEMO SIPAKARBUN', $white);
    imagecopyresized($canvas, $title, 75, 25, 0, 0, 780, 60, 260, 20);
    unset($title);
    imagefilledellipse($canvas, 460, 290, 300, 210, $leaf);
    imagesetthickness($canvas, 7);
    imageline($canvas, 320, 390, 570, 205, $green);
    imageline($canvas, 415, 320, 397, 250, $green);
    imageline($canvas, 470, 280, 540, 300, $green);
    imagestring($canvas, 5, 365, 435, $label, $green);
    imagestring($canvas, 5, 180, 485, 'ILUSTRASI PLACEHOLDER - BUKAN FOTO KASUS NYATA', $muted);
    imagestring($canvas, 5, 220, 520, 'Fixture lokal untuk pengujian laporan akhir', $muted);
    imagepng($canvas, __DIR__.'/demo-status-'.$stage.'.png');
    unset($canvas);
}
