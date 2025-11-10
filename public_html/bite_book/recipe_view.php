<?php
// recipe_view.php — BiteBook
ini_set('display_errors',1); error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/csrf.php';
require_once __DIR__ . '/bitebook-db.php';

if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); exit('Missing id'); }

// seed token for the page
csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();

  if (isset($_POST['add_comment'])) {
    bb_add_comment($id, (int)$_POST['c_user'], trim($_POST['c_body'] ?? ''));
  } elseif (isset($_POST['add_rating'])) {
    bb_rate_recipe($id, (int)$_POST['r_user'], (int)$_POST['r_stars']);
  }
  header('Location: recipe_view.php?id='.$id);
  exit;
}

$recipe = bb_get_recipe($id);
if (!$recipe) { http_response_code(404); exit('Recipe not found'); }
$ingredients = bb_get_ingredients($id);
$steps       = bb_get_steps($id);
$comments    = bb_get_comments($id);
$rating      = bb_rating_summary($id);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo e($recipe['title_recipe_info']); ?> — BiteBook</title>
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Georgia', 'Times New Roman', serif;
      background: linear-gradient(180deg, #f5f0e8 0%, #ebe4d9 100%);
      min-height: 100vh;
      padding: 2rem 1rem;
      color: #3d3934;
    }

    .container {
      max-width: 900px;
      margin: 0 auto;
    }

    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      color: #4a6fa5;
      text-decoration: none;
      font-weight: 600;
      margin-bottom: 2rem;
      transition: gap 0.2s;
      font-family: -apple-system, system-ui, sans-serif;
      font-size: 0.95rem;
    }

    .back-link:hover {
      gap: 0.75rem;
      color: #2d4a6e;
    }

    .recipe-header {
      background: #fffcf7;
      padding: 3rem;
      border-radius: 1rem;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06), 0 4px 16px rgba(0, 0, 0, 0.04);
      margin-bottom: 2rem;
      border: 2px solid #e8dfd0;
      border-top: 5px solid #4a6fa5;
    }

    .recipe-header h1 {
      font-size: 3rem;
      font-weight: 400;
      margin-bottom: 1.25rem;
      line-height: 1.2;
      color: #2d4a6e;
      font-family: 'Georgia', serif;
    }

    .recipe-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 1.75rem;
      color: #6b6158;
      font-size: 1rem;
      margin-bottom: 1.5rem;
      font-family: -apple-system, system-ui, sans-serif;
    }

    .meta-item {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .meta-item strong {
      color: #3d3934;
    }

    .recipe-description {
      color: #4a4540;
      line-height: 1.8;
      font-size: 1.1rem;
      font-style: italic;
    }

    .section {
      background: #fffcf7;
      padding: 2.5rem;
      border-radius: 1rem;
      box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05), 0 2px 8px rgba(0, 0, 0, 0.04);
      margin-bottom: 2rem;
      border: 2px solid #e8dfd0;
    }

    .section h2 {
      font-size: 2rem;
      margin-bottom: 1.5rem;
      color: #2d4a6e;
      font-weight: 400;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      font-family: 'Georgia', serif;
      padding-bottom: 0.75rem;
      border-bottom: 2px solid #e8dfd0;
    }

    .ingredients-list {
      list-style: none;
      display: grid;
      gap: 0.75rem;
    }

    .ingredients-list li {
      padding: 1rem 1.25rem;
      background: #fef9f0;
      border-radius: 0.5rem;
      border-left: 4px solid #4a6fa5;
      display: flex;
      align-items: flex-start;
      gap: 1rem;
      line-height: 1.6;
      font-family: -apple-system, system-ui, sans-serif;
    }

    .ingredients-list li::before {
      content: '□';
      color: #4a6fa5;
      font-weight: normal;
      font-size: 1.3rem;
      flex-shrink: 0;
      margin-top: -0.1rem;
    }

    .steps-list {
      list-style: none;
      counter-reset: step-counter;
      display: grid;
      gap: 1.25rem;
    }

    .steps-list li {
      counter-increment: step-counter;
      padding: 1.5rem 1.5rem 1.5rem 4.5rem;
      background: #fef9f0;
      border-radius: 0.75rem;
      position: relative;
      line-height: 1.7;
      font-family: -apple-system, system-ui, sans-serif;
    }

    .steps-list li::before {
      content: counter(step-counter);
      position: absolute;
      left: 1.25rem;
      top: 1.5rem;
      width: 2.5rem;
      height: 2.5rem;
      background: #4a6fa5;
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 1.1rem;
      font-family: -apple-system, system-ui, sans-serif;
    }

    .rating-summary {
      display: flex;
      align-items: center;
      gap: 1.25rem;
      padding: 1.25rem 1.5rem;
      background: linear-gradient(135deg, #fef9f0 0%, #fdf4e3 100%);
      border-radius: 0.75rem;
      margin-bottom: 2rem;
      border: 2px solid #f3e8d4;
    }

    .rating-summary .stars {
      font-size: 2.5rem;
      color: #d4a574;
    }

    .rating-summary .details {
      color: #5a4a2f;
      font-weight: 600;
      font-family: -apple-system, system-ui, sans-serif;
    }

    .form-card {
      padding: 2rem;
      background: #fef9f0;
      border-radius: 0.75rem;
      border: 2px solid #e8dfd0;
    }

    .form-group {
      margin-bottom: 1.25rem;
    }

    .form-group label {
      display: block;
      font-weight: 600;
      margin-bottom: 0.5rem;
      color: #3d3934;
      font-size: 1rem;
      font-family: -apple-system, system-ui, sans-serif;
    }

    input[type="number"],
    textarea {
      width: 100%;
      padding: 0.875rem 1rem;
      border: 2px solid #d4c4ad;
      border-radius: 0.5rem;
      font-size: 1rem;
      font-family: -apple-system, system-ui, sans-serif;
      transition: border-color 0.2s, box-shadow 0.2s;
      background: #fffcf7;
      color: #3d3934;
    }

    input[type="number"]:focus,
    textarea:focus {
      outline: none;
      border-color: #4a6fa5;
      box-shadow: 0 0 0 3px rgba(74, 111, 165, 0.1);
    }

    textarea {
      resize: vertical;
      min-height: 100px;
      line-height: 1.6;
    }

    .btn {
      padding: 0.875rem 1.75rem;
      border: none;
      border-radius: 0.5rem;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      font-family: -apple-system, system-ui, sans-serif;
    }

    .btn-primary {
      background: #2f5233;
      color: white;
      box-shadow: 0 2px 8px rgba(47, 82, 51, 0.25);
    }

    .btn-primary:hover {
      background: #3d6844;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(47, 82, 51, 0.35);
    }

    .star-rating {
      display: flex;
      gap: 0.5rem;
      margin-bottom: 1rem;
      flex-direction: row-reverse;
      justify-content: flex-end;
    }

    .star-rating input[type="radio"] {
      display: none;
    }

    .star-rating label {
      font-size: 2.25rem;
      cursor: pointer;
      color: #d4c4ad;
      transition: color 0.2s, transform 0.2s;
    }

    .star-rating input[type="radio"]:checked ~ label,
    .star-rating label:hover,
    .star-rating label:hover ~ label {
      color: #d4a574;
    }

    .star-rating label:hover {
      transform: scale(1.15);
    }

    .comment-card {
      padding: 1.5rem;
      background: #fffcf7;
      border-radius: 0.75rem;
      margin-bottom: 1.25rem;
      border: 2px solid #e8dfd0;
    }

    .comment-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 0.875rem;
      color: #8a7d6f;
      font-size: 0.9rem;
      font-family: -apple-system, system-ui, sans-serif;
    }

    .comment-author {
      font-weight: 600;
      color: #2d4a6e;
    }

    .comment-body {
      color: #3d3934;
      line-height: 1.7;
      font-family: -apple-system, system-ui, sans-serif;
    }

    .empty-message {
      text-align: center;
      color: #8a7d6f;
      padding: 3rem;
      font-style: italic;
    }

    @media (max-width: 640px) {
      .recipe-header h1 {
        font-size: 2rem;
      }

      .section,
      .recipe-header {
        padding: 1.75rem;
      }

      .steps-list li {
        padding-left: 3.5rem;
      }

      .steps-list li::before {
        width: 2rem;
        height: 2rem;
        font-size: 0.95rem;
        left: 1rem;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <a href="index.php" class="back-link">← Back to all recipes</a>

    <div class="recipe-header">
      <h1><?php echo e($recipe['title_recipe_info']); ?></h1>
      <div class="recipe-meta">
        <div class="meta-item">
          <span>by</span>
          <strong><?php echo e($recipe['author'] ?? 'Unknown'); ?></strong>
        </div>
        <div class="meta-item">
          <span><strong><?php echo (int)$recipe['servings']; ?></strong> servings</span>
        </div>
        <?php if ($recipe['prep_time']): ?>
        <div class="meta-item">
          <span><strong><?php echo (int)$recipe['prep_time']; ?></strong> min prep</span>
        </div>
        <?php endif; ?>
        <?php if ($recipe['cook_time']): ?>
        <div class="meta-item">
          <span><strong><?php echo (int)$recipe['cook_time']; ?></strong> min cook</span>
        </div>
        <?php endif; ?>
      </div>
      <?php if (!empty($recipe['description_recipe_info'])): ?>
        <p class="recipe-description"><?php echo nl2br(e($recipe['description_recipe_info'])); ?></p>
      <?php endif; ?>
    </div>

    <div class="section">
      <h2><span>🥕</span> Ingredients</h2>
      <ul class="ingredients-list">
        <?php foreach ($ingredients as $ing): ?>
          <li>
            <span><?php
              echo e($ing['name']);
              if ($ing['number_quantity'] !== null) {
                echo ' — ' . e($ing['number_quantity']);
                if ($ing['unit_quantity']) echo ' ' . e($ing['unit_quantity']);
              }
            ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="section">
      <h2><span>📖</span> Instructions</h2>
      <ol class="steps-list">
        <?php foreach ($steps as $s): ?>
          <li><?php echo nl2br(e($s['instruction'])); ?></li>
        <?php endforeach; ?>
      </ol>
    </div>

    <div class="section">
      <h2><span>★</span> Rating</h2>
      <?php
        $avg = $rating['avg_rating'] ?? null;
        $cnt = (int)($rating['cnt'] ?? 0);
      ?>
      <?php if ($cnt > 0): ?>
        <div class="rating-summary">
          <div class="stars">★★★★★</div>
          <div class="details">
            Average: <strong><?php echo number_format($avg, 2); ?></strong> / 5.0 
            (<?php echo $cnt; ?> <?php echo $cnt === 1 ? 'rating' : 'ratings'; ?>)
          </div>
        </div>
      <?php else: ?>
        <p class="empty-message">No ratings yet. Be the first to rate this recipe!</p>
      <?php endif; ?>

      <div class="form-card">
        <form method="post">
          <?php csrf_field(); ?>
          <div class="form-group">
            <label>User ID</label>
            <input type="number" name="r_user" min="1" required placeholder="Enter your user ID">
          </div>
          <div class="form-group">
            <label>Your Rating</label>
            <div class="star-rating">
              <input type="radio" name="r_stars" id="star5" value="5" required>
              <label for="star5">★</label>
              <input type="radio" name="r_stars" id="star4" value="4">
              <label for="star4">★</label>
              <input type="radio" name="r_stars" id="star3" value="3">
              <label for="star3">★</label>
              <input type="radio" name="r_stars" id="star2" value="2">
              <label for="star2">★</label>
              <input type="radio" name="r_stars" id="star1" value="1">
              <label for="star1">★</label>
            </div>
          </div>
          <button type="submit" name="add_rating" value="1" class="btn btn-primary">Submit Rating</button>
        </form>
      </div>
    </div>

    <div class="section">
      <h2><span>💬</span> Comments</h2>
      
      <div class="form-card" style="margin-bottom: 2rem;">
        <form method="post">
          <?php csrf_field(); ?>
          <div class="form-group">
            <label>User ID</label>
            <input type="number" name="c_user" min="1" required placeholder="Enter your user ID">
          </div>
          <div class="form-group">
            <label>Your Comment</label>
            <textarea name="c_body" rows="4" required placeholder="Share your thoughts about this recipe..."></textarea>
          </div>
          <button type="submit" name="add_comment" value="1" class="btn btn-primary">Post Comment</button>
        </form>
      </div>

      <?php if (empty($comments)): ?>
        <p class="empty-message">No comments yet. Be the first to share your thoughts!</p>
      <?php else: ?>
        <?php foreach ($comments as $c): ?>
          <div class="comment-card">
            <div class="comment-header">
              <span class="comment-author"><?php echo e($c['name'] ?? ('User '.$c['user_id'])); ?></span>
              <span><?php echo e(date('M j, Y', strtotime($c['created_at']))); ?></span>
            </div>
            <div class="comment-body"><?php echo nl2br(e($c['body'])); ?></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
