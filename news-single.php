<?php
$pageTitle = "SPG Portal — სიახლე";
include __DIR__ . "/header.php";
require __DIR__ . "/inc/bootstrap.php";

$id = (int)($_GET['id'] ?? 0);
$post = $id ? get_one_news($id) : null;

if (!$post) {
  http_response_code(404);
  echo "<div style='padding:40px;max-width:900px;margin:auto'>Not found</div>";
  include __DIR__ . "/footer.php";
  exit;
}
?>
<section class="section">
  <div class="container" style="max-width:1000px">
    <a href="news.php" style="display:inline-block;margin-bottom:14px">← ყველა სიახლე</a>

    <div class="singleHero" style="border-radius:20px;overflow:hidden;border:1px solid rgba(15,23,42,.12)">
      <img src="<?=h($post['img'])?>" alt="<?=h($post['title'])?>" style="width:100%;display:block;max-height:460px;object-fit:cover">
    </div>

    <div style="margin-top:16px">
      <div style="display:flex;gap:10px;align-items:center;opacity:.85">
        <span class="tag"><?=h($post['cat'])?></span>
        <span>•</span>
        <span><?=h($post['date'])?></span>
      </div>

      <h1 style="margin:10px 0 10px"><?=h($post['title'])?></h1>

      <p style="font-size:18px;opacity:.9"><?=h($post['text'])?></p>

      <?php if(trim($post['content']) !== ''): ?>
        <div class="richText" style="margin-top:14px;line-height:1.7;opacity:.95">
          <?= nl2br(h($post['content'])) ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php include __DIR__ . "/footer.php"; ?>
