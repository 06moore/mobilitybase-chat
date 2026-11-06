<?php
// Router for PHP's built-in server so pretty permalinks work locally.
$root = $_SERVER['DOCUMENT_ROOT'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && file_exists($root . $path) && !is_dir($root . $path)) return false;
if (is_dir($root . $path) && file_exists($root . rtrim($path, '/') . '/index.php')) { require $root . rtrim($path, '/') . '/index.php'; return; }
$_SERVER['SCRIPT_NAME'] = '/index.php';
require $root . '/index.php';
