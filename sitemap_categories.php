<?php
// sitemap_categories.php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'common.php';

header('Content-Type: application/xml; charset=utf-8');

function xml_e($s): string {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function q($s): string {
  return rawurlencode((string)$s);
}

$base = 'https://coloring.g55.co';

$index = load_site_index();
$categories = get_categories_sorted($index);

$today = date('Y-m-d');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($categories as $c) {
  $cid = $c['id'];
  $loc = $base . "/?c=" . q($cid);

  echo "<url><loc>" . xml_e($loc) . "</loc><lastmod>" . xml_e($today) . "</lastmod></url>\n";
}

echo "</urlset>\n";
