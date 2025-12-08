<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/bitebook-db.php';

if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
function param_str($k,$d=''){ return isset($_POST[$k]) ? trim((string)$_POST[$k]) : $d; }
function param_int_or_null($k){
  if (!isset($_POST[$k]) || $_POST[$k]==='') return null;
  return is_numeric($_POST[$k]) ? (int)$_POST[$k] : false;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title       = param_str('title');
  $description = param_str('description');
  $difficulty  = param_str('difficulty','');
  $prep_time   = param_int_or_null('prep_time');
  $cook_time   = param_int_or_null('cook_time');
  $servings    = param_int_or_null('servings');
  $user_id     = param_int_or_null('user_id');

  if ($title === '')        $errors[] = 'Title is required.';
  if ($user_id === null)    $errors[] = 'User ID is required.';
  if ($user_id === false)   $errors[] = 'User ID must be numeric.';
  if ($servings === null)   $errors[] = 'Servings is required.';
  if ($servings === false)  $errors[] = 'Servings must be numeric.';
  if ($prep_time === false) $errors[] = 'Prep time must be numeric if provided.';
  if ($cook_time === false) $errors[] = 'Cook time must be numeric if provided.';

  $ingredients = [];
  $names = $_POST['ing_name'] ?? [];
  $qtys  = $_POST['ing_qty']  ?? [];
  $units = $_POST['ing_unit'] ?? [];
  $N = max(count($names), count($qtys), count($units));
  for ($i=0; $i<$N; $i++) {
    $name = trim($names[$i] ?? '');
    if ($name === '') continue;
    $qty  = ($qtys[$i] !== '' ? (float)$qtys[$i] : null);
    $unit = trim($units[$i] ?? '');
    $ingredients[] = ['name'=>$name,'qty'=>$qty,'unit'=>$unit ?: null];
  }

  $steps = [];
  foreach (($_POST['step_text'] ?? []) as $s) {
    $t = trim((string)$s);
    if ($t !== '') $steps[] = $t;
  }

  if (!$errors) {
    try {
      $rid = bb_create_recipe(
        (int)$user_id, $title, $description, $difficulty ?: null,
        $prep_time, $cook_time, (int)$servings, $ingredients, $steps
      );
      header('Location: recipe_view.php?id='.$rid);
      exit;
    } catch (Throwable $e) {
      $errors[] = 'Failed to save: '.$e->getMessage();
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Create a Recipe — BiteBook</title>
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

    .page-header {
      text-align: center;
      margin-bottom: 3rem;
      padding: 2rem 1rem;
    }

    .page-header h1 {
      font-size: 3rem;
      font-weight: 400;
      color: #2d4a6e;
      margin-bottom: 0.75rem;
      font-family: 'Georgia', serif;
    }

    .page-header p {
      color: #5a5550;
      font-size: 1.15rem;
      font-style: italic;
    }

    .form-container {
      background: #fffcf7;
      padding: 3rem;
      border-radius: 1rem;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06), 0 4px 16px rgba(0, 0, 0, 0.04);
      border: 2px solid #e8dfd0;
    }

    .error-box {
      background: linear-gradient(135deg, #fef5f5 0%, #fed7d7 100%);
      border: 2px solid #fc8181;
      color: #742a2a;
      padding: 1.5rem 2rem;
      border-radius: 0.75rem;
      margin-bottom: 2.5rem;
    }

    .error-box strong {
      display: block;
      margin-bottom: 0.75rem;
      font-size: 1.1rem;
      font-family: -apple-system, system-ui, sans-serif;
    }

    .error-box ul {
      margin-left: 1.5rem;
      font-family: -apple-system, system-ui, sans-serif;
    }

    .form-group {
      margin-bottom: 1.75rem;
    }

    .form-group label {
      display: block;
      font-weight: 600;
      margin-bottom: 0.625rem;
      color: #2d4a6e;
      font-size: 1.05rem;
      font-family: -apple-system, system-ui, sans-serif;
    }

    .form-group .label-hint {
      font-weight: 400;
      color: #8a7d6f;
      font-size: 0.9rem;
    }

    input[type="text"],
    input[type="number"],
    textarea {
      width: 100%;
      padding: 1rem 1.25rem;
      border: 2px solid #d4c4ad;
      border-radius: 0.5rem;
      font-size: 1rem;
      font-family: -apple-system, system-ui, sans-serif;
      transition: border-color 0.2s, box-shadow 0.2s;
      background: #fffcf7;
      color: #3d3934;
    }

    input[type="text"]:focus,
    input[type="number"]:focus,
    textarea:focus {
      outline: none;
      border-color: #4a6fa5;
      box-shadow: 0 0 0 3px rgba(74, 111, 165, 0.1);
    }

    textarea {
      resize: vertical;
      line-height: 1.6;
    }

    .grid-3 {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1.25rem;
    }

    .section-header {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      margin: 2.5rem 0 1.5rem;
      padding-bottom: 0.875rem;
      border-bottom: 2px solid #e8dfd0;
    }

    .section-header h2 {
      font-size: 1.75rem;
      font-weight: 400;
      color: #2d4a6e;
      font-family: 'Georgia', serif;
    }

    .ingredient-row {
      display: grid;
      grid-template-columns: 2fr 1fr 1fr;
      gap: 0.875rem;
      margin-bottom: 0.875rem;
    }

    .step-item {
      margin-bottom: 0.875rem;
    }

    .btn {
      padding: 0.875rem 1.75rem;
      border: none;
      border-radius: 0.5rem;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-block;
      font-family: -apple-system, system-ui, sans-serif;
    }

    .btn-secondary {
      background: #fef9f0;
      color: #5a5550;
      border: 2px solid #d4c4ad;
    }

    .btn-secondary:hover {
      background: #fdf4e3;
      border-color: #c8bfb3;
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

    .form-actions {
      display: flex;
      gap: 1.25rem;
      margin-top: 3rem;
      padding-top: 2.5rem;
      border-top: 2px solid #e8dfd0;
    }

    .add-button-container {
      margin: 1.25rem 0;
    }

    @media (max-width: 768px) {
      .grid-3 {
        grid-template-columns: 1fr;
      }

      .ingredient-row {
        grid-template-columns: 1fr;
      }

      .form-container {
        padding: 2rem;
      }

      .page-header h1 {
        font-size: 2.25rem;
      }
    }
  </style>
  <script>
    function addIng(){
      const c=document.getElementById('ing');
      const r=document.createElement('div');
      r.className='ingredient-row';
      r.innerHTML='<input type="text" name="ing_name[]" placeholder="Ingredient name" required>\
                   <input type="number" name="ing_qty[]" step="0.01" placeholder="Quantity">\
                   <input type="text" name="ing_unit[]" placeholder="Unit (e.g., cups)">';
      c.appendChild(r);
    }
    
    function addStep(){
      const c=document.getElementById('steps');
      const wrapper=document.createElement('div');
      wrapper.className='step-item';
      const t=document.createElement('textarea');
      t.name='step_text[]';
      t.rows=3;
      t.placeholder='Describe this step in detail...';
      t.required=true;
      wrapper.appendChild(t);
      c.appendChild(wrapper);
    }
  </script>
</head>
<body>
  <div class="container">
    <a href="index.php" class="back-link">← Cancel and go back</a>

    <div class="page-header">
      <h1>Add a New Recipe</h1>
      <p>Share your culinary creation with others</p>
    </div>

    <div class="form-container">
      <?php if ($errors): ?>
        <div class="error-box">
          <strong>Please fix the following errors:</strong>
          <ul>
            <?php foreach($errors as $e): ?>
              <li><?php echo e($e); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post" action="recipe_create.php" novalidate>
        <div class="form-group">
          <label>Recipe Title *</label>
          <input type="text" name="title" required value="<?php echo e($_POST['title'] ?? ''); ?>" placeholder="e.g., Grandmother's Apple Pie">
        </div>

        <div class="form-group">
          <label>Description <span class="label-hint">(optional)</span></label>
          <textarea name="description" rows="4" placeholder="Share the story behind this recipe..."><?php echo e($_POST['description'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
          <label>Difficulty <span class="label-hint">(optional)</span></label>
          <input type="text" name="difficulty" placeholder="e.g., Easy, Medium, Hard" value="<?php echo e($_POST['difficulty'] ?? ''); ?>">
        </div>

        <div class="grid-3">
          <div class="form-group">
            <label>Prep Time <span class="label-hint">(minutes)</span></label>
            <input type="number" name="prep_time" min="0" value="<?php echo e($_POST['prep_time'] ?? ''); ?>" placeholder="15">
          </div>
          <div class="form-group">
            <label>Cook Time <span class="label-hint">(minutes)</span></label>
            <input type="number" name="cook_time" min="0" value="<?php echo e($_POST['cook_time'] ?? ''); ?>" placeholder="30">
          </div>
          <div class="form-group">
            <label>Servings *</label>
            <input type="number" name="servings" min="1" required value="<?php echo e($_POST['servings'] ?? ''); ?>" placeholder="4">
          </div>
        </div>

        <!-- <div class="form-group">
          <label>User ID * </label>
          <input type="number" name="user_id" min="1" required value="<?php echo e($_POST['user_id'] ?? ''); ?>" placeholder="1">
        </div> -->

        <div class="section-header">
          <span>🥕</span>
          <h2>Ingredients</h2>
        </div>

        <div id="ing">
          <?php
            $names = $_POST['ing_name'] ?? [''];
            $qtys  = $_POST['ing_qty']  ?? [''];
            $units = $_POST['ing_unit'] ?? [''];
            $M = max(count($names), count($qtys), count($units), 1);
            for ($i=0; $i<$M; $i++):
          ?>
            <div class="ingredient-row">
              <input type="text" name="ing_name[]" placeholder="Ingredient name" value="<?php echo e($names[$i] ?? ''); ?>">
              <input type="number" name="ing_qty[]" step="0.01" placeholder="Quantity" value="<?php echo e($qtys[$i] ?? ''); ?>">
              <input type="text" name="ing_unit[]" placeholder="Unit" value="<?php echo e($units[$i] ?? ''); ?>">
            </div>
          <?php endfor; ?>
        </div>
        
        <div class="add-button-container">
          <button type="button" onclick="addIng()" class="btn btn-secondary">+ Add Another Ingredient</button>
        </div>

        <div class="section-header">
          <span>📖</span>
          <h2>Instructions</h2>
        </div>

        <div id="steps">
          <?php
            $stepTexts = $_POST['step_text'] ?? [''];
            foreach ($stepTexts as $s):
          ?>
            <div class="step-item">
              <textarea name="step_text[]" rows="3" placeholder="Describe this step..."><?php echo e($s); ?></textarea>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="add-button-container">
          <button type="button" onclick="addStep()" class="btn btn-secondary">+ Add Another Step</button>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Create Recipe</button>
          <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
