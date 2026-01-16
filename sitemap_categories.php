<?php
// sitemap_categories.php
// Outputs URLs for home + all category pages.
//
// URL:
//   sitemap_categories.php

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

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

$home = $base . "/";
echo "  <url>\n";
echo "    <loc>" . xml_e($home) . "</loc>\n";
echo "    <lastmod>" . xml_e($today) . "</lastmod>\n";
echo "  </url>\n";

foreach ($categories as $c) {
  $cid = isset($c['id']) ? $c['id'] : '';
  if ($cid === '') continue;

  $loc = $base . "/?c=" . q($cid);
  echo "  <url>\n";
  echo "    <loc>" . xml_e($loc) . "</loc>\n";
  echo "    <lastmod>" . xml_e($today) . "</lastmod>\n";
  echo "  </url>\n";
}

echo "</urlset>\n";
