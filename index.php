<?php
session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/bitebook-db.php';

if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$recipes = bb_get_all_recipes($search);

$logged_in = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? 'Guest';
$user_id = $_SESSION['user_id'] ?? null;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>BiteBook — Your Recipe Collection</title>
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
      position: relative;
    }

    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-image: 
        repeating-linear-gradient(0deg, transparent, transparent 35px, rgba(74, 111, 165, 0.02) 35px, rgba(74, 111, 165, 0.02) 36px);
      pointer-events: none;
      z-index: 0;
    }

    .container {
      max-width: 1100px;
      margin: 0 auto;
      position: relative;
      z-index: 1;
    }

    .top-nav {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
      padding: 1rem;
      background: #fffcf7;
      border: 2px solid #e8dfd0;
      border-radius: 1rem;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }

    .user-info {
      display: flex;
      align-items: center;
      gap: 1rem;
      font-family: -apple-system, system-ui, sans-serif;
      color: #5a5550;
    }

    .user-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: #4a6fa5;
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      font-size: 1.1rem;
    }

    .logout-link {
      color: #4a6fa5;
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 600;
      transition: color 0.2s;
    }

    .logout-link:hover {
      color: #2d4a6e;
    }

    .header {
      text-align: center;
      margin-bottom: 3rem;
      padding: 2rem 1rem;
    }

    .header h1 {
      font-size: 3.5rem;
      color: #2d4a6e;
      margin-bottom: 0.75rem;
      font-weight: 400;
      letter-spacing: 0.02em;
      font-family: 'Georgia', serif;
    }

    .header p {
      color: #5a5550;
      font-size: 1.15rem;
      font-style: italic;
      font-family: 'Georgia', serif;
    }

    .search-section {
      background: #fffcf7;
      padding: 2rem;
      border-radius: 1rem;
      border: 2px solid #e8dfd0;
      margin-bottom: 2.5rem;
      box-shadow: 
        0 2px 4px rgba(0, 0, 0, 0.04),
        inset 0 -1px 0 rgba(0, 0, 0, 0.02);
    }

    .search-form {
      display: flex;
      gap: 1rem;
      align-items: center;
    }

    .search-input-wrapper {
      flex: 1;
      position: relative;
    }

    .search-input {
      width: 100%;
      padding: 1rem 1rem 1rem 3rem;
      border: 2px solid #d4c4ad;
      border-radius: 0.5rem;
      font-size: 1rem;
      font-family: -apple-system, system-ui, sans-serif;
      transition: all 0.2s;
      background: #fef9f0;
      color: #3d3934;
    }

    .search-input:focus {
      outline: none;
      border-color: #4a6fa5;
      box-shadow: 0 0 0 3px rgba(74, 111, 165, 0.1);
      background: #fffcf7;
    }

    .search-icon {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      color: #8a7d6f;
      font-size: 1.25rem;
    }

    .btn-search {
      padding: 1rem 2rem;
      background: #4a6fa5;
      color: white;
      border: none;
      border-radius: 0.5rem;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      font-family: -apple-system, system-ui, sans-serif;
      white-space: nowrap;
      box-shadow: 0 2px 8px rgba(74, 111, 165, 0.25);
    }

    .btn-search:hover {
      background: #2d4a6e;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(74, 111, 165, 0.35);
    }

    .actions {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2.5rem;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .recipe-count {
      color: #5a5550;
      font-size: 1.05rem;
      font-family: -apple-system, system-ui, sans-serif;
    }

    .recipe-count strong {
      color: #2d4a6e;
      font-size: 1.25rem;
    }

    .btn-primary {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: #2f5233;
      color: #fff;
      text-decoration: none;
      padding: 0.875rem 1.75rem;
      border-radius: 0.5rem;
      font-weight: 600;
      font-size: 1rem;
      transition: all 0.3s ease;
      box-shadow: 0 2px 8px rgba(47, 82, 51, 0.25);
      font-family: -apple-system, system-ui, sans-serif;
    }

    .btn-primary:hover {
      background: #3d6844;
      transform: translateY(-2px);
      box-shadow: 0 4px 16px rgba(47, 82, 51, 0.35);
    }

    .recipe-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
      gap: 2rem;
    }

    .recipe-card {
      background: #fffcf7;
      border-radius: 0.75rem;
      padding: 2rem;
      box-shadow: 
        0 1px 3px rgba(0, 0, 0, 0.08), 
        0 4px 12px rgba(0, 0, 0, 0.06),
        inset 0 1px 0 rgba(255, 255, 255, 0.5);
      transition: all 0.3s ease;
      border: 2px solid #e8dfd0;
      position: relative;
    }

    .recipe-card::before {
      content: '';
      position: absolute;
      top: -2px;
      left: 1.5rem;
      width: 80px;
      height: 20px;
      background: #4a6fa5;
      border-radius: 0.5rem 0.5rem 0 0;
      opacity: 0.8;
    }

    .recipe-card::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #4a6fa5 0%, #6b8cb9 100%);
      border-radius: 0 0 0.75rem 0.75rem;
      opacity: 0.15;
    }

    .recipe-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12), 0 2px 6px rgba(0, 0, 0, 0.08);
      border-color: #d4c4ad;
    }

    .recipe-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 1rem;
      margin-bottom: 1rem;
    }

    .recipe-title {
      flex: 1;
    }

    .recipe-title h2 {
      font-size: 1.5rem;
      margin-bottom: 0.5rem;
      font-weight: 600;
      line-height: 1.3;
      font-family: 'Georgia', serif;
    }

    .recipe-title a {
      color: #2d4a6e;
      text-decoration: none;
      transition: color 0.2s;
    }

    .recipe-title a:hover {
      color: #4a6fa5;
    }

    .recipe-rating {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      background: #fef9f0;
      padding: 0.625rem 0.875rem;
      border-radius: 0.5rem;
      white-space: nowrap;
      border: 1px solid #f3e8d4;
    }

    .rating-stars {
      font-size: 1.15rem;
      color: #d4a574;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 0.25rem;
      font-family: -apple-system, system-ui, sans-serif;
    }

    .rating-count {
      font-size: 0.75rem;
      color: #8a7d6f;
      margin-top: 0.125rem;
      font-family: -apple-system, system-ui, sans-serif;
    }

    .recipe-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 0.875rem;
      color: #6b6158;
      font-size: 0.95rem;
      font-family: -apple-system, system-ui, sans-serif;
    }

    .meta-item {
      display: flex;
      align-items: center;
      gap: 0.375rem;
    }

    .meta-divider {
      color: #c8bfb3;
    }

    .empty-state {
      text-align: center;
      padding: 5rem 2rem;
      background: #fffcf7;
      border-radius: 1rem;
      border: 2px dashed #d4c4ad;
    }

    .empty-state h3 {
      color: #2d4a6e;
      font-size: 1.75rem;
      margin-bottom: 0.75rem;
      font-family: 'Georgia', serif;
    }

    .empty-state p {
      color: #6b6158;
      margin-bottom: 2rem;
      font-size: 1.05rem;
    }

    @media (max-width: 640px) {
      .header h1 {
        font-size: 2.5rem;
      }

      .recipe-grid {
        grid-template-columns: 1fr;
      }

      .actions {
        flex-direction: column;
        align-items: stretch;
      }

      .btn-primary {
        justify-content: center;
      }

      .search-form {
        flex-direction: column;
      }

      .btn-search {
        width: 100%;
      }

      .top-nav {
        flex-direction: column;
        gap: 1rem;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <?php if ($logged_in): ?>
      <div class="top-nav">
        <div class="user-info">
          <div class="user-avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
          <span>Welcome, <strong><?php echo e($username); ?></strong></span>
        </div>
        <div class="nav-links">
          <a href="profile.php" class="logout-link">My Recipes</a>
          <a href="logout.php" class="logout-link">Log Out</a>
        </div>
      </div>
    <?php endif; ?>


    <div class="header">
      <h1>BiteBook</h1>
      <p>A collection of cherished recipes</p>
    </div>

    <div class="search-section">
      <form method="get" class="search-form">
        <div class="search-input-wrapper">
          <span class="search-icon">🔍</span>
          <input 
            type="text" 
            name="search" 
            class="search-input" 
            placeholder="Search recipes by title, author, or ingredient..." 
            value="<?php echo e($search); ?>"
          >
        </div>
        <button type="submit" class="btn-search">Search</button>
        <?php if ($search): ?>
          <a href="index.php" class="btn-search" style="background: #8a7d6f; text-decoration: none; display: inline-block;">Clear</a>
        <?php endif; ?>
      </form>
    </div>

    <div class="actions">
      <div class="recipe-count">
        <?php if ($search): ?>
          <strong><?php echo count($recipes); ?></strong> <?php echo count($recipes) === 1 ? 'result' : 'results'; ?> for "<?php echo e($search); ?>"
        <?php else: ?>
          <strong><?php echo count($recipes); ?></strong> <?php echo count($recipes) === 1 ? 'recipe' : 'recipes'; ?>
        <?php endif; ?>
      </div>
      <a href="recipe_create.php" class="btn-primary">
        <span>+</span>
        <span>Add New Recipe</span>
      </a>
    </div>

    <?php if (empty($recipes)): ?>
      <div class="empty-state">
        <?php if ($search): ?>
          <h3>No recipes found</h3>
          <p>Try adjusting your search terms</p>
          <a href="index.php" class="btn-primary">View All Recipes</a>
        <?php else: ?>
          <h3>Your recipe book awaits</h3>
          <p>Begin your culinary journey by adding your first recipe</p>
          <a href="recipe_create.php" class="btn-primary">Create Your First Recipe</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="recipe-grid">
        <?php foreach ($recipes as $r): ?>
          <div class="recipe-card">
            <div class="recipe-header">
              <div class="recipe-title">
                <h2>
                  <a href="recipe_view.php?id=<?php echo (int)$r['recipe_id']; ?>">
                    <?php echo e($r['title']); ?>
                  </a>
                </h2>
              </div>
              <div class="recipe-rating">
                <?php if ($r['rating_count']): ?>
                  <div class="rating-stars">
                    <?php echo number_format((float)$r['avg_rating'], 1); ?> ★
                  </div>
                  <div class="rating-count">
                    <?php echo (int)$r['rating_count']; ?> <?php echo $r['rating_count'] == 1 ? 'rating' : 'ratings'; ?>
                  </div>
                <?php else: ?>
                  <div class="rating-stars" style="color: #c8bfb3;">—</div>
                  <div class="rating-count">Not rated</div>
                <?php endif; ?>
              </div>
            </div>
            <div class="recipe-meta">
              <div class="meta-item">
                <span>by</span>
                <strong><?php echo e($r['author'] ?? 'Unknown'); ?></strong>
              </div>
              <span class="meta-divider">•</span>
              <div class="meta-item">
                <span><?php echo (int)$r['servings']; ?> servings</span>
              </div>
              <span class="meta-divider">•</span>
              <div class="meta-item">
                <span><?php echo e(date('M j, Y', strtotime($r['created_at']))); ?></span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
