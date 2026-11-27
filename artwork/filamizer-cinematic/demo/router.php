<?php
$public = __DIR__.'/host/public';
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = realpath($public.$path);
if ($file && str_starts_with($file, realpath($public).DIRECTORY_SEPARATOR) && is_file($file)) {
    $types = ['css'=>'text/css','js'=>'application/javascript','woff2'=>'font/woff2','svg'=>'image/svg+xml','png'=>'image/png'];
    header('Content-Type: '.($types[pathinfo($file, PATHINFO_EXTENSION)] ?? 'application/octet-stream'));
    readfile($file); return;
}
$app = require __DIR__.'/bootstrap.php';
$app->handleRequest(\Illuminate\Http\Request::capture());
