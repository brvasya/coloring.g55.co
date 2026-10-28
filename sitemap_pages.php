<?php
// sitemap_pages.php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'common.php';

header('Content-Type: application/xml; charset=utf-8');

function xml_e($s): string {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function q($s): string {
  return rawurlencode((string)$s);
}

$base = 'https://coloring.g55.co';

$n = (int)$_GET['n'];

$categories = get_categories_sorted(load_site_index());

$perSitemap = 10000;
$start = ($n - 1) * $perSitemap;
$end = $start + $perSitemap;

$today = date('Y-m-d');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

$count = 0;

foreach ($categories as $c) {
  $cid = $c['id'];
  list($_, $pages) = load_category_pages($cid);

  foreach ($pages as $p) {
    $pid = $p['id'];

    if ($count >= $start && $count < $end) {
      $loc = $base . "/page.php?id=" . q($pid) . "&c=" . q($cid);
      $imageLoc = $base . "/categories/" . q($cid) . "/" . q($pid) . ".png";

      echo "<url><loc>" . xml_e($loc) . "</loc><lastmod>" . xml_e($today) . "</lastmod>";
      echo "<image:image><image:loc>" . xml_e($imageLoc) . "</image:loc></image:image>";
      echo "</url>\n";
    }

    $count++;
    if ($count >= $end) break 2;
  }
}

echo "</urlset>\n";
