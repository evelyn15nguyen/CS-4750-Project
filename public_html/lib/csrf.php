<?php
// bite_book/lib/csrf.php

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

function csrf_token(): string {
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf'];
}

function csrf_field(): void {
  $t = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
  echo '<input type="hidden" name="csrf" value="'.$t.'">';
}

function csrf_verify(): void {
  // Only check on POST
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ok = isset($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf']);
    if (!$ok) {
      http_response_code(400);
      echo 'Invalid CSRF token';
      exit;
    }
  }
}
