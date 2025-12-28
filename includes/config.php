<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$BASE_URL = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($BASE_URL === '/') $BASE_URL = '';

$dbPath = __DIR__ . '/../database.sqlite';
$pdo = new PDO('sqlite:' . $dbPath, null, null, [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$pdo->exec('PRAGMA foreign_keys = ON;');