<?php
// sitemap_pages.php
// Outputs page URLs (page.php?id=...&c=...) and supports pagination by n.
// Default per sitemap: 40000 URLs.
//
// URL examples:
//   sitemap_pages.php?n=1
//   sitemap_pages.php?n=2

header('Content-Type: application/xml; charset=utf-8');

function read_json($path) {
  if (!file_exists($path)) return null;
  $raw = file_get_contents($path);
  if ($raw === false) return null;
  $data = json_decode($raw, true);
  return is_array($data) ? $data : null;
}

function norm_base($base) {
  $base = trim((string)$base);
  if ($base === '') return '';
  return rtrim($base, '/');
}

function xml_e($s) {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function q($s) {
  return rawurlencode((string)$s);
}

function load_category_pages($cid) {
  $cid = preg_replace('/[^a-z0-9_-]/i', '', (string)$cid);
  $path = __DIR__ . DIRECTORY_SEPARATOR . 'categories' . DIRECTORY_SEPARATOR . $cid . '.json';
  $data = read_json($path);
  $pages = ($data && isset($data['pages']) && is_array($data['pages'])) ? $data['pages'] : [];
  return $pages;
}

$index = read_json(__DIR__ . DIRECTORY_SEPARATOR . 'pages.json');
$site = $index && isset($index['site']) ? $index['site'] : [];
$categories = $index && isset($index['categories']) && is_array($index['categories']) ? $index['categories'] : [];

$base = norm_base(isset($site['baseUrl']) ? $site['baseUrl'] : '');
if ($base === '') {
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
  $base = $scheme . '://' . $host;
}

$today = date('Y-m-d');

$perSitemap = 40000;
$n = isset($_GET['n']) ? (int)$_GET['n'] : 1;
if ($n < 1) $n = 1;

$start = ($n - 1) * $perSitemap;
$end = $start + $perSitemap;

$urls = [];
$count = 0;

foreach ($categories as $c) {
  $cid = isset($c['id']) ? $c['id'] : '';
  if ($cid === '') continue;

  $pages = load_category_pages($cid);
  foreach ($pages as $p) {
    $pid = isset($p['id']) ? $p['id'] : '';
    if ($pid === '') continue;

    if ($count >= $start && $count < $end) {
      $urls[] = $base . "/page.php?id=" . q($pid) . "&c=" . q($cid);
    }
    $count++;

    if ($count >= $end) break 2;
  }
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($urls as $loc) {
  echo "  <url>\n";
  echo "    <loc>" . xml_e($loc) . "</loc>\n";
  echo "    <lastmod>" . xml_e($today) . "</lastmod>\n";
  echo "  </url>\n";
}

echo "</urlset>\n";
