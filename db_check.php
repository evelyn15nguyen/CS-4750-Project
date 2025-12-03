<?php
require __DIR__ . '/../config.php';
header('Content-Type: text/plain; charset=utf-8');

echo "DB: " . $db->query('SELECT DATABASE()')->fetchColumn() . "\n";
$tables = ['Users','Recipes','Ingredients','Steps','comments','rates'];
foreach ($tables as $t) {
  $stmt = $db->prepare("SHOW TABLES LIKE ?");
  $stmt->execute([$t]);
  echo str_pad($t, 12) . ': ' . ($stmt->fetch() ? 'YES' : 'NO') . "\n";
}
