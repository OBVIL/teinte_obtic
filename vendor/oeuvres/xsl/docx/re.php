<?php
if (!isset($argv[1])) {
    die("Give a file");
}
$file = $argv[1];

$txt = file_get_contents($file);
$txt = preg_replace_callback(
    '/unicode="([^"]+)">/',
    function ($matches) {
        $ret = $matches[0] . mb_chr(hexdec($matches[1]), 'UTF-8');
        return $ret;
    },
    $txt
);
file_put_contents($file, $txt);
