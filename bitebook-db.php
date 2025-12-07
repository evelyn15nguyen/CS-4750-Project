<?php

require_once __DIR__ . '/../config.php';


//
// ---------- Recipes ----------
//

// Create a recipe WITH ingredients & steps in a single transaction.
function bb_create_recipe($user_id, $title, $desc, $difficulty, $prep, $cook, $servings, $ingredients = [], $steps = []) {
  global $db;

  $synthesizeId = true;

  try {
    $db->beginTransaction();

    if ($synthesizeId) {
      $rid = (int)$db->query("SELECT COALESCE(MAX(recipe_id), 100) + 1 AS next_id FROM Recipes")
                     ->fetch()['next_id'];

      $sql = "INSERT INTO Recipes
              (recipe_id, user_id, title_recipe_info, description_recipe_info,
               difficulty_recipe_info, prep_time, cook_time, servings)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
      $db->prepare($sql)->execute([
        $rid, $user_id, $title, $desc, $difficulty ?: null,
        $prep !== '' ? $prep : null,
        $cook !== '' ? $cook : null,
        $servings
      ]);
      $recipe_id = $rid;
    } else {
      $sql = "INSERT INTO Recipes
              (user_id, title_recipe_info, description_recipe_info,
               difficulty_recipe_info, prep_time, cook_time, servings)
              VALUES (?, ?, ?, ?, ?, ?, ?)";
      $db->prepare($sql)->execute([
        $user_id, $title, $desc, $difficulty ?: null,
        $prep !== '' ? $prep : null,
        $cook !== '' ? $cook : null,
        $servings
      ]);
      $recipe_id = (int)$db->lastInsertId();
    }

    // Ingredients (ingredient_id is per-recipe running index 1..N)
    if (!empty($ingredients)) {
      $insIng = $db->prepare(
        "INSERT INTO Ingredients (recipe_id, ingredient_id, name, number_quantity, unit_quantity)
         VALUES (?, ?, ?, ?, ?)"
      );
      $i = 1;
      foreach ($ingredients as $ing) {
        $name = trim((string)($ing['name'] ?? ''));
        if ($name === '') continue;
        $qty  = isset($ing['qty']) && $ing['qty'] !== '' ? (float)$ing['qty'] : null;
        $unit = isset($ing['unit']) ? trim((string)$ing['unit']) : null;
        $insIng->execute([$recipe_id, $i++, $name, $qty, $unit ?: null]);
      }
    }

    // Steps (step_no 1..N)
    if (!empty($steps)) {
      $insStep = $db->prepare(
        "INSERT INTO Steps (recipe_id, step_no, instruction) VALUES (?, ?, ?)"
      );
      $s = 1;
      foreach ($steps as $text) {
        $txt = trim((string)$text);
        if ($txt === '') continue;
        $insStep->execute([$recipe_id, $s++, $txt]);
      }
    }

    $db->commit();
    return $recipe_id;

  } catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    throw $e; // Let caller display an error like in your POTD
  }
}

// List recent recipes (minimal fields for cards)
function bb_list_recipes($limit = 50) {
  global $db;
  $sql = "SELECT recipe_id, title_recipe_info, description_recipe_info, servings
          FROM Recipes
          ORDER BY created_at DESC
          LIMIT ?";
  $stmt = $db->prepare($sql);
  $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
  $stmt->execute();
  return $stmt->fetchAll();
}

// Fetch one recipe with author name
function bb_get_recipe($recipe_id) {
  global $db;
  $sql = "SELECT R.*, U.name AS author
          FROM Recipes R
          JOIN Users   U ON U.user_id = R.user_id
          WHERE R.recipe_id = ?";
  $stmt = $db->prepare($sql);
  $stmt->execute([$recipe_id]);
  return $stmt->fetch();
}

// List this user's recipes (with optional search), newest/highest-rated first
function bb_get_recipes_by_user(int $user_id, string $q = ''): array {
  global $db;

  $base = "
    SELECT
      R.recipe_id,
      R.user_id,
      R.title_recipe_info       AS title,
      R.description_recipe_info AS description,
      R.servings,
      COALESCE(R.created_at, NOW()) AS created_at,
      U.name                    AS author,
      AVG(T.stars)              AS avg_rating,
      COUNT(T.user_id)          AS rating_count
    FROM Recipes R
    JOIN Users U           ON U.user_id = R.user_id
    LEFT JOIN rates T      ON T.recipe_id = R.recipe_id
    LEFT JOIN Ingredients I ON I.recipe_id = R.recipe_id
    WHERE R.user_id = ?
  ";

  $params = [$user_id];

  if ($q !== '') {
    $tokens = preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($tokens as $t) {
      $like = '%' . $t . '%';
      $base .= " AND (R.title_recipe_info LIKE ? OR R.description_recipe_info LIKE ? OR I.name LIKE ?)";
      array_push($params, $like, $like, $like);
    }
  }

  $sql = $base . "
    GROUP BY R.recipe_id
    ORDER BY COALESCE(AVG(T.stars),0) DESC, R.recipe_id DESC
    LIMIT 500
  ";

  $stmt = $db->prepare($sql);
  $stmt->execute($params);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// Fetch all recipes
function bb_get_all_recipes(string $q = ''): array {
  global $db;

  $base = "
    SELECT
      R.recipe_id,
      R.user_id,
      R.title_recipe_info       AS title,
      R.description_recipe_info AS description,
      R.servings,
      COALESCE(R.created_at, NOW()) AS created_at,
      U.name                    AS author,
      AVG(T.stars)              AS avg_rating,
      COUNT(T.user_id)          AS rating_count
    FROM Recipes R
    JOIN Users U          ON U.user_id = R.user_id
    LEFT JOIN rates T     ON T.recipe_id = R.recipe_id
    LEFT JOIN Ingredients I ON I.recipe_id = R.recipe_id
  ";

  $where = "";
  $params = [];

  if ($q !== '') {
    $tokens = preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY);
    $clauses = [];
    foreach ($tokens as $t) {
      $like = '%' . $t . '%';
      $clauses[] = "(R.title_recipe_info LIKE ? OR R.description_recipe_info LIKE ? OR U.name LIKE ? OR I.name LIKE ?)";
      array_push($params, $like, $like, $like, $like);
    }
    if ($clauses) {
      $where = "WHERE " . implode(" AND ", $clauses);
    }
  }

  $sql = $base . "
    $where
    GROUP BY R.recipe_id
    ORDER BY COALESCE(AVG(T.stars),0) DESC, R.recipe_id DESC
    LIMIT 500
  ";

  $stmt = $db->prepare($sql);
  $stmt->execute($params);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Ingredients for a recipe
function bb_get_ingredients($recipe_id) {
  global $db;
  $sql = "SELECT ingredient_id, name, number_quantity, unit_quantity
          FROM Ingredients
          WHERE recipe_id = ?
          ORDER BY ingredient_id";
  $stmt = $db->prepare($sql);
  $stmt->execute([$recipe_id]);
  return $stmt->fetchAll();
}

// Steps for a recipe
function bb_get_steps($recipe_id) {
  global $db;
  $sql = "SELECT step_no, instruction
          FROM Steps
          WHERE recipe_id = ?
          ORDER BY step_no";
  $stmt = $db->prepare($sql);
  $stmt->execute([$recipe_id]);
  return $stmt->fetchAll();
}

//
// ---------- Comments & Ratings ----------
//


function bb_add_comment($recipe_id, $user_id, $body) {
  global $db;
  $update = $db->prepare("UPDATE comments SET body=?, created_at=NOW()
                          WHERE recipe_id=? AND user_id=?");
  $update->execute([$body, $recipe_id, $user_id]);

  if ($update->rowCount() === 0) {
    $insert = $db->prepare(
      "INSERT INTO comments (recipe_id, user_id, body) VALUES (?, ?, ?)"
    );
    $insert->execute([$recipe_id, $user_id, $body]);
  }
}

// Read comments newest-first
function bb_get_comments($recipe_id) {
  global $db;
  $sql = "SELECT C.user_id, U.name, C.body, C.created_at
          FROM comments C
          JOIN Users U ON U.user_id = C.user_id
          WHERE C.recipe_id = ?
          ORDER BY C.created_at DESC";
  $stmt = $db->prepare($sql);
  $stmt->execute([$recipe_id]);
  return $stmt->fetchAll();
}

// Insert/Upsert a rating (stars 1..5)
function bb_rate_recipe($recipe_id, $user_id, $stars) {
  global $db;
  $stars = (int)$stars;
  if ($stars < 1 || $stars > 5) throw new InvalidArgumentException("stars must be 1..5");

  $update = $db->prepare("UPDATE rates SET stars=? WHERE recipe_id=? AND user_id=?");
  $update->execute([$stars, $recipe_id, $user_id]);

  if ($update->rowCount() === 0) {
    $insert = $db->prepare("INSERT INTO rates (recipe_id, user_id, stars) VALUES (?, ?, ?)");
    $insert->execute([$recipe_id, $user_id, $stars]);
  }
}

// Average rating + count for a recipe
function bb_rating_summary($recipe_id) {
  global $db;
  $sql = "SELECT AVG(stars) AS avg_rating, COUNT(*) AS cnt
          FROM rates WHERE recipe_id = ?";
  $stmt = $db->prepare($sql);
  $stmt->execute([$recipe_id]);
  return $stmt->fetch();
}

//
//

function bb_upsert_user($user_id, $name) {
  global $db;
  $upd = $db->prepare("UPDATE Users SET name=? WHERE user_id=?");
  $upd->execute([$name, $user_id]);
  if ($upd->rowCount() === 0) {
    $ins = $db->prepare("INSERT INTO Users (user_id, name) VALUES (?, ?)");
    $ins->execute([$user_id, $name]);
  }
}
