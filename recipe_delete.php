<?php
session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/bitebook-db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$user_id   = (int)$_SESSION['user_id'];
$recipe_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
  bb_delete_recipe($recipe_id, $user_id);
} catch (Throwable $e) {
  die("Error deleting recipe: " . $e->getMessage());
}

header("Location: profile.php");
exit;
?>
