<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Detecta automaticamente /php/public
$BASE_URL = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

// Se for só "/", fica vazio
if ($BASE_URL === '/') {
  $BASE_URL = '';
}
