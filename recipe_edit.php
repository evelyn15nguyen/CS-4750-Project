<?php
session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/bitebook-db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

if (!function_exists('e')) { function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); } }


// Only the logged-in user can view their own profile
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

$user_id   = (int)$_SESSION['user_id'];
$username  = $_SESSION['username'] ?? 'Me';
$logged_in = true;

$recipe_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id   = (int)$_SESSION['user_id'];

$recipe = bb_get_recipe($recipe_id);
if (!$recipe || $recipe['user_id'] != $user_id) {
  die("You are not allowed to edit this recipe.");
}

$ingredients = bb_get_ingredients($recipe_id);
$steps       = bb_get_steps($recipe_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $title       = trim($_POST['title']);
  $description = trim($_POST['description']);
  $difficulty  = trim($_POST['difficulty']);
  $prep_time   = $_POST['prep_time'] !== '' ? (int)$_POST['prep_time'] : null;
  $cook_time   = $_POST['cook_time'] !== '' ? (int)$_POST['cook_time'] : null;
  $servings    = (int)$_POST['servings'];

  $newIngredients = [];
  foreach ($_POST['ing_name'] as $i => $name) {
    $name = trim($name);
    if ($name === '') continue;
    $qty  = $_POST['ing_qty'][$i] !== '' ? (float)$_POST['ing_qty'][$i] : null;
    $unit = trim($_POST['ing_unit'][$i] ?? '');
    $newIngredients[] = [
      'name' => $name,
      'qty'  => $qty,
      'unit' => $unit ?: null
    ];
  }

  $newSteps = [];
  foreach ($_POST['step_text'] as $s) {
    $txt = trim($s);
    if ($txt !== '') $newSteps[] = $txt;
  }

  bb_update_recipe(
    $recipe_id,
    $user_id,
    $title,
    $description,
    $difficulty,
    $prep_time,
    $cook_time,
    $servings,
    $newIngredients,
    $newSteps
  );

  header("Location: profile.php");
  exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Edit Recipe — BiteBook</title>

  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Georgia', 'Times New Roman', serif;
      background: linear-gradient(180deg, #f5f0e8 0%, #ebe4d9 100%);
      padding: 2rem 1rem;
      color: #3d3934;
      min-height: 100vh;
    }

    .container { max-width: 900px; margin: 0 auto; }

    .back-link {
      display: inline-flex; align-items: center; gap: 0.5rem;
      color: #4a6fa5; text-decoration: none; font-weight: 600;
      margin-bottom: 2rem;
      font-family: -apple-system, system-ui;
    }
    .back-link:hover { gap: 0.75rem; color: #2d4a6e; }

    .page-header { text-align:center; margin-bottom: 3rem; padding: 2rem 1rem; }
    .page-header h1 {
      font-size: 3rem; font-weight:400; color:#2d4a6e;
      margin-bottom: 0.75rem;
    }
    .page-header p {
      font-size:1.15rem; font-style:italic; color:#5a5550;
    }

    .form-container {
      background:#fffcf7; padding:3rem; border-radius:1rem;
      border:2px solid #e8dfd0;
      box-shadow:0 2px 8px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.04);
    }

    .form-group { margin-bottom:1.75rem; }
    .form-group label {
      display:block; font-weight:600; margin-bottom:0.625rem;
      color:#2d4a6e; font-family:-apple-system, system-ui;
    }

    input[type="text"], input[type="number"], textarea {
      width:100%; padding:1rem 1.25rem; border:2px solid #d4c4ad;
      border-radius:0.5rem; background:#fffcf7;
      font-size:1rem; font-family:-apple-system,system-ui;
      transition: all 0.2s;
    }
    input:focus, textarea:focus {
      outline:none; border-color:#4a6fa5;
      box-shadow:0 0 0 3px rgba(74,111,165,0.1);
    }

    textarea { resize:vertical; line-height:1.6; }

    .grid-3 {
      display:grid;
      grid-template-columns:repeat(3,1fr);
      gap:1.25rem;
    }

    .section-header {
      display:flex; gap:0.75rem; align-items:center;
      margin:2.5rem 0 1.5rem; padding-bottom:0.875rem;
      border-bottom:2px solid #e8dfd0;
    }

    .section-header h2 {
      font-size:1.75rem; font-weight:400; color:#2d4a6e;
    }

    .ingredient-row {
      display:grid;
      grid-template-columns:2fr 1fr 1fr;
      gap:0.875rem;
      margin-bottom:0.875rem;
    }

    .step-item { margin-bottom:0.875rem; }

    .btn {
      padding:0.875rem 1.75rem; border:none; border-radius:0.5rem;
      font-size:1rem; font-weight:600; cursor:pointer;
      font-family:-apple-system, system-ui;
      transition:all 0.3s ease;
      text-decoration:none;
      display:inline-block;
    }

    .btn-secondary {
      background:#fef9f0; color:#5a5550; border:2px solid #d4c4ad;
    }
    .btn-secondary:hover {
      background:#fdf4e3; border-color:#c8bfb3;
    }

    .btn-primary {
      background:#2f5233; color:#fff;
      box-shadow:0 2px 8px rgba(47,82,51,0.25);
    }
    .btn-primary:hover {
      background:#3d6844;
      transform:translateY(-2px);
      box-shadow:0 4px 12px rgba(47,82,51,0.35);
    }

    .form-actions {
      display:flex; gap:1.25rem; margin-top:3rem;
      padding-top:2.5rem; border-top:2px solid #e8dfd0;
    }

    @media (max-width:768px){
      .grid-3 { grid-template-columns:1fr; }
      .ingredient-row { grid-template-columns:1fr; }
      .form-container { padding:2rem; }
    }
  </style>

  <script>
    function addIng(){
      const c=document.getElementById('ing');
      const r=document.createElement('div');
      r.className='ingredient-row';
      r.innerHTML =
        '<input type="text" name="ing_name[]" placeholder="Ingredient name">' +
        '<input type="number" step="0.01" name="ing_qty[]" placeholder="Qty">' +
        '<input type="text" name="ing_unit[]" placeholder="Unit">';
      c.appendChild(r);
    }

    function addStep(){
      const c=document.getElementById('steps');
      const w=document.createElement('div');
      w.className='step-item';
      w.innerHTML='<textarea name="step_text[]" rows="3" placeholder="Describe this step..."></textarea>';
      c.appendChild(w);
    }
  </script>
</head>

<body>
    <div class="container">
        <a href="profile.php" class="back-link">← Cancel and go back</a>
        <div class="page-header">
            <h1>Edit Recipe</h1>
            <p>Update your recipe details below</p>
        </div>
        <div class="form-container">
            <form method="post">
            <div class="form-group">
                <label>Recipe Title *</label>
                <input type="text" name="title" required value="<?php echo e($recipe['title_recipe_info']); ?>">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="4"><?php echo e($recipe['description_recipe_info']); ?></textarea>
            </div>
            <div class="form-group">
                <label>Difficulty</label>
                <input type="text" name="difficulty" value="<?php echo e($recipe['difficulty_recipe_info']); ?>">
            </div>
            <div class="grid-3">
                <div class="form-group">
                    <label>Prep Time (minutes)</label>
                    <input type="number" name="prep_time" value="<?php echo e($recipe['prep_time']); ?>">
                </div>
                <div class="form-group">
                    <label>Cook Time (minutes)</label>
                    <input type="number" name="cook_time" value="<?php echo e($recipe['cook_time']); ?>">
                </div>
                <div class="form-group">
                    <label>Servings *</label>
                    <input type="number" name="servings" required value="<?php echo e($recipe['servings']); ?>">
                </div>
            </div>
            <div class="section-header">
                <span>🥕</span>
                <h2>Ingredients</h2>
            </div>
            <div id="ing">
                <?php foreach ($ingredients as $i): ?>
                <div class="ingredient-row">
                <input type="text" name="ing_name[]" value="<?php echo e($i['name']); ?>">
                <input type="number" step="0.01" name="ing_qty[]" value="<?php echo e($i['number_quantity']); ?>">
                <input type="text" name="ing_unit[]" value="<?php echo e($i['unit_quantity']); ?>">
                </div>
                <?php endforeach; ?>
            </div>
            <div class="add-button-container">
                <button type="button" onclick="addIng()" class="btn btn-secondary">+ Add Ingredient</button>
            </div>
            <div class="section-header">
                <span>📖</span>
                <h2>Instructions</h2>
            </div>
            <div id="steps">
                <?php foreach ($steps as $s): ?>
                <div class="step-item">
                    <textarea name="step_text[]" rows="3"><?php echo e($s['instruction']); ?></textarea>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="add-button-container">
                <button type="button" onclick="addStep()" class="btn btn-secondary">+ Add Step</button>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="profile.php" class="btn btn-secondary">Cancel</a>
            </div>
            </form>
        </div>
    </div>
</body>
</html>
