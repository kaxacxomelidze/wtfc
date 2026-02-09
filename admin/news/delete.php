<?php
declare(strict_types=1);
require __DIR__ . '/../../inc/bootstrap.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
  $stmt = db()->prepare("DELETE FROM news_posts WHERE id=?");
  $stmt->execute([$id]);
}
header('Location: index.php');
exit;
