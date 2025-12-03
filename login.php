<?php
ini_set('display_errors', 1); error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/bitebook-db.php';

if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        try {
            $stmt = $db->prepare("SELECT user_id, name, password FROM Users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && $password === $user['password']) {
                $_SESSION['user_id'] = (int)$user['user_id'];
                $_SESSION['username'] = $user['name'];

                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            $error = 'Database error.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>BiteBook — Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body {
      font-family: -apple-system, system-ui, sans-serif;
      background: #f5f0e8;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      margin: 0;
    }
    .card {
      background: #fffcf7;
      padding: 2rem 2.5rem;
      border-radius: 1rem;
      box-shadow: 0 4px 14px rgba(0,0,0,0.08);
      border: 2px solid #e8dfd0;
      max-width: 380px;
      width: 100%;
    }
    h1 {
      margin: 0 0 1.5rem;
      font-size: 1.8rem;
      color: #2d4a6e;
      text-align: center;
      font-family: Georgia, serif;
    }
    label {
      display: block;
      margin-bottom: 0.25rem;
      font-size: 0.9rem;
      color: #5a5550;
    }
    input {
      width: 100%;
      padding: 0.75rem;
      margin-bottom: 1rem;
      border-radius: 0.5rem;
      border: 2px solid #d4c4ad;
      font-size: 0.95rem;
      background: #fef9f0;
    }
    input:focus {
      outline: none;
      border-color: #4a6fa5;
      box-shadow: 0 0 0 3px rgba(74,111,165,0.12);
      background: #fffcf7;
    }
    .btn {
      width: 100%;
      padding: 0.85rem;
      border: none;
      border-radius: 0.5rem;
      background: #4a6fa5;
      color: #fff;
      font-weight: 600;
      cursor: pointer;
      font-size: 1rem;
      box-shadow: 0 3px 10px rgba(74,111,165,0.3);
    }
    .btn:hover {
      background: #2d4a6e;
    }
    .error {
      background: #ffe3e3;
      color: #8b2222;
      padding: 0.6rem 0.8rem;
      border-radius: 0.5rem;
      margin-bottom: 1rem;
      font-size: 0.9rem;
    }
  </style>
</head>
<body>
<div class="card">
  <h1>Log in to BiteBook</h1>

  <?php if ($error): ?>
    <div class="error"><?php echo e($error); ?></div>
  <?php endif; ?>

  <form method="post" action="login.php">
    <label for="email">Email</label>
    <input type="email" name="email" id="email" required>

    <label for="password">Password</label>
    <input type="password" name="password" id="password" required>

    <button type="submit" class="btn">Log In</button>
  </form>
</div>
</body>
</html>
