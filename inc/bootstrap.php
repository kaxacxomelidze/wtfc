<?php
declare(strict_types=1);

// IMPORTANT: no spaces/BOM before <?php

$config = require __DIR__ . '/config.php';

/**
 * Detect base URL automatically:
 * - If project is in C:\xampp\htdocs\sspm  -> base_url = /sspm
 * - If project is in C:\xampp\htdocs      -> base_url = (empty)
 */
function detect_base_url(): string {
  $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
  $projectRoot = realpath(__DIR__ . '/..') ?: '';

  $docRoot = str_replace('\\', '/', $docRoot);
  $projectRoot = str_replace('\\', '/', $projectRoot);

  if ($docRoot !== '' && $projectRoot !== '' && str_starts_with($projectRoot, $docRoot)) {
    $rel = substr($projectRoot, strlen($docRoot)); // like "/sspm"
    $rel = str_replace('\\', '/', $rel);
    $rel = rtrim($rel, '/');
    return $rel; // "" or "/sspm"
  }
  return ''; // fallback
}

define('BASE_URL', rtrim((string)($config['app']['base_url'] ?? ''), '/'));
if (BASE_URL === '') {
  define('AUTO_BASE_URL', detect_base_url());
} else {
  define('AUTO_BASE_URL', BASE_URL);
}

/** Build absolute URL within this project */
function url(string $path = ''): string {
  $base = AUTO_BASE_URL;
  $path = '/' . ltrim($path, '/');
  if ($base === '' || $base === '/') return $path;
  return $base . $path;
}

/** Escape HTML */
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

/** CSRF */
function csrf_token(): string {
  if (empty($_SESSION['_csrf'])) {
    $_SESSION['_csrf'] = bin2hex(random_bytes(16));
  }
  return $_SESSION['_csrf'];
}
function csrf_verify(): void {
  $ok = isset($_POST['_csrf'], $_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], (string)$_POST['_csrf']);
  if (!$ok) {
    http_response_code(419);
    exit('CSRF token mismatch');
  }
}

/** Start session BEFORE any output */
if (session_status() !== PHP_SESSION_ACTIVE) {
  if (headers_sent($file, $line)) {
    http_response_code(500);
    exit("Headers already sent in: {$file} on line {$line}. Make sure every page loads bootstrap.php BEFORE header.php.");
  }
  session_name($config['app']['session_name'] ?? 'SPGSESSID');
  session_start();
}

/** DB */
function db(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;

  $cfg = require __DIR__ . '/config.php';
  $db = $cfg['db'];

  $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";
  $pdo = new PDO($dsn, $db['user'], $db['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  return $pdo;
}

/** Admin auth */
function is_admin(): bool {
  return !empty($_SESSION['admin_id']);
}
function require_admin(): void {
  if (!is_admin()) {
    header('Location: ' . url('admin/login.php'));
    exit;
  }
}

/** Helpers for news */
function fmt_date_dmY(string $datetime): string {
  $t = strtotime($datetime);
  return $t ? date('d.m.Y', $t) : '';
}

function get_news_posts(int $limit = 50): array {
  $stmt = db()->prepare("
    SELECT id, category, title, excerpt, image_path, published_at
    FROM news_posts
    WHERE is_published=1
    ORDER BY published_at DESC, id DESC
    LIMIT :lim
  ");
  $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
  $stmt->execute();

  $rows = $stmt->fetchAll();
  $out = [];
  foreach ($rows as $r) {
    $out[] = [
      'id' => (int)$r['id'],
      'cat' => (string)$r['category'],
      'date' => fmt_date_dmY((string)$r['published_at']),
      'title' => (string)$r['title'],
      'text' => (string)$r['excerpt'],
      'img' => (string)$r['image_path'],
    ];
  }
  return $out;
}

function get_one_news(int $id): ?array {
  $stmt = db()->prepare("SELECT * FROM news_posts WHERE id=? AND is_published=1 LIMIT 1");
  $stmt->execute([$id]);
  $r = $stmt->fetch();
  if (!$r) return null;

  return [
    'id' => (int)$r['id'],
    'cat' => (string)$r['category'],
    'date' => fmt_date_dmY((string)$r['published_at']),
    'title' => (string)$r['title'],
    'text' => (string)$r['excerpt'],
    'content' => (string)($r['content'] ?? ''),
    'img' => (string)$r['image_path'],
    'published_at' => (string)$r['published_at'],
  ];
}
