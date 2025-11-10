<?php
// lib/validation.php
function str($key) {
  return trim((string)($_POST[$key] ?? ''));
}
function intv($key) {
  return filter_var($_POST[$key] ?? null, FILTER_VALIDATE_INT);
}
function dec($key) {
  $v = $_POST[$key] ?? null;
  if ($v === null || $v === '') return null;
  return filter_var($v, FILTER_VALIDATE_FLOAT);
}
function e($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
