<?php
$dir = __DIR__ . '/vendor/landrok/activitypub/src';

if (!is_dir($dir)) {
    die("Directory not found: $dir\n");
}

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

foreach ($rii as $file) {
    if ($file->isDir()) continue;
    if ($file->getExtension() === 'php') {
        echo $file->getPathname() . "\n";
    }
}
