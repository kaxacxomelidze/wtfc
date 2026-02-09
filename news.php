<?php
require __DIR__ . "/inc/bootstrap.php";
$pageTitle = "SPG Portal — სიახლეები";
include __DIR__ . "/header.php";

$posts = get_news_posts(200);
?>
<section class="section">
  <div class="container">
    <h2 class="secTitle">სიახლეები</h2>

    <div class="newsGrid">
      <?php foreach($posts as $p): ?>
        <a class="newsCard" href="news-single.php?id=<?=(int)$p['id']?>">
          <div class="newsImg"><img src="<?=h($p['img'])?>" alt="<?=h($p['title'])?>"></div>
          <div class="newsBody">
            <div class="newsMeta">
              <span class="tag"><?=h($p['cat'])?></span>
              <span class="dot">•</span>
              <span class="date"><?=h($p['date'])?></span>
            </div>
            <div class="newsTitle"><?=h($p['title'])?></div>
            <div class="newsText"><?=h($p['text'])?></div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php include __DIR__ . "/footer.php"; ?>
