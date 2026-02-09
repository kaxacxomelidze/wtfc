<?php
declare(strict_types=1);
require __DIR__ . '/../../inc/bootstrap.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare("SELECT * FROM news_posts WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) { http_response_code(404); exit('Not found'); }

$data = [
  'category' => $row['category'],
  'title' => $row['title'],
  'excerpt' => $row['excerpt'],
  'content' => $row['content'] ?? '',
  'image_path' => $row['image_path'],
  'published_at' => date('Y-m-d\TH:i', strtotime($row['published_at'])),
  'is_published' => (int)$row['is_published'],
];

function handle_upload(): ?string {
  if (empty($_FILES['image_file']) || $_FILES['image_file']['error'] === UPLOAD_ERR_NO_FILE) return null;
  if ($_FILES['image_file']['error'] !== UPLOAD_ERR_OK) return null;

  $tmp = $_FILES['image_file']['tmp_name'];
  $name = $_FILES['image_file']['name'];
  $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) return null;

  $dir = __DIR__ . '/../../assets/news/uploads';
  if (!is_dir($dir)) mkdir($dir, 0775, true);

  $new = 'news_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
  $dest = $dir . '/' . $new;

  if (!move_uploaded_file($tmp, $dest)) return null;
  return '/assets/news/uploads/' . $new;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();

  $data['category'] = trim((string)($_POST['category'] ?? ''));
  $data['title'] = trim((string)($_POST['title'] ?? ''));
  $data['excerpt'] = trim((string)($_POST['excerpt'] ?? ''));
  $data['content'] = trim((string)($_POST['content'] ?? ''));
  $data['image_path'] = trim((string)($_POST['image_path'] ?? ''));
  $data['published_at'] = trim((string)($_POST['published_at'] ?? ''));
  $data['is_published'] = isset($_POST['is_published']) ? 1 : 0;

  $uploaded = handle_upload();
  if ($uploaded) $data['image_path'] = $uploaded;

  if ($data['category'] === '') $errors[] = 'Category is required';
  if ($data['title'] === '') $errors[] = 'Title is required';
  if ($data['excerpt'] === '') $errors[] = 'Short text (excerpt) is required';
  if ($data['image_path'] === '') $errors[] = 'Image path or upload is required';
  if ($data['published_at'] === '') $errors[] = 'Publish date is required';

  if (!$errors) {
    $stmt = db()->prepare("
      UPDATE news_posts
      SET category=?, title=?, excerpt=?, content=?, image_path=?, published_at=?, is_published=?
      WHERE id=?
    ");
    $stmt->execute([
      $data['category'],
      $data['title'],
      $data['excerpt'],
      $data['content'],
      $data['image_path'],
      date('Y-m-d H:i:s', strtotime($data['published_at'])),
      $data['is_published'],
      $id,
    ]);
    header('Location: /admin/news/index.php');
    exit;
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Edit News</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:system-ui;background:#0b1220;color:#e5e7eb;margin:0}
    .wrap{max-width:860px;margin:22px auto;padding:0 14px}
    input,textarea{width:100%;padding:12px;border-radius:12px;border:1px solid rgba(255,255,255,.18);background:#111a2e;color:#fff}
    textarea{min-height:120px}
    .row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .btn{padding:12px 14px;border-radius:12px;border:0;background:#2563eb;color:#fff;font-weight:800;cursor:pointer}
    .card{background:#111a2e;border:1px solid rgba(255,255,255,.12);border-radius:16px;padding:16px}
    .err{color:#fca5a5;margin:10px 0}
  </style>
</head>
<body>
  <div class="wrap">
    <h2 style="margin:0 0 12px">Edit News #<?= (int)$id ?></h2>
    <p><a href="index.php">← Back</a></p>

    <div class="card">
      <?php if($errors): ?>
        <div class="err"><?php foreach($errors as $e) echo '<div>'.h($e).'</div>'; ?></div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">

        <div class="row">
          <div>
            <label>Category</label>
            <input name="category" value="<?=h($data['category'])?>">
          </div>
          <div>
            <label>Publish date</label>
            <input type="datetime-local" name="published_at" value="<?=h($data['published_at'])?>">
          </div>
        </div>

        <div style="margin-top:12px">
          <label>Title</label>
          <input name="title" value="<?=h($data['title'])?>">
        </div>

        <div style="margin-top:12px">
          <label>Short text (card text)</label>
          <textarea name="excerpt"><?=h($data['excerpt'])?></textarea>
        </div>

        <div style="margin-top:12px">
          <label>Full content</label>
          <textarea name="content" style="min-height:180px"><?=h($data['content'])?></textarea>
        </div>

        <div style="margin-top:12px" class="row">
          <div>
            <label>Image path</label>
            <input name="image_path" value="<?=h($data['image_path'])?>">
          </div>
          <div>
            <label>OR Upload new image</label>
            <input type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp">
          </div>
        </div>

        <div style="margin-top:12px">
          <label>
            <input type="checkbox" name="is_published" <?= $data['is_published'] ? 'checked' : '' ?>>
            Published
          </label>
        </div>

        <div style="margin-top:14px">
          <button class="btn" type="submit">Update</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
