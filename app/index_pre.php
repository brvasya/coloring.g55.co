<?php
// app/index_pre.php
require_once 'common.php';

$index = load_site_index();
$site = $index['site'];
$categories = get_categories_sorted($index);

$catMap = [];
foreach ($categories as $c) {
  $catMap[$c['id']] = $c;
}

$hasC = isset($_GET['c']);

if ($hasC) {
  $cid = clean_slug($_GET['c']);
  if ($cid === '' || !isset($catMap[$cid])) {
    header('Location: /', true, 302);
    exit;
  }

  $cat = $catMap[$cid];

  list($_, $pages) = load_category_pages($cid);

  $gridItems = [];
  // CHANGED: removed reverse order loop
  foreach ($pages as $p) {
    $gridItems[] = [
      'id' => $p['id'],
      'title' => $p['title'],
      'image' => '/categories/' . $cid . '/' . $p['id'] . '.png',
      'category' => $cid
    ];
  }

  $count = count($gridItems);
  $h1 = ($count > 0 ? number_format($count) . ' ' : '') . $cat['name'] . ' Coloring Pages';
  $desc = $cat['description'];

  $title = $h1;
  $metaDesc = $desc;
  $canonical = 'https://coloring.g55.co/?c=' . rawurlencode($cid);
} else {
  $totalCount = 0;
  $gridItems = [];

  foreach ($categories as $c) {
    $catId = $c['id'];

    list($_, $pages) = load_category_pages($catId);
    $totalCount += count($pages);

    $newest = newest_page($pages);
    $gridItems[] = [
      'id' => $newest['id'],
      'title' => $newest['title'],
      'image' => '/categories/' . $catId . '/' . $newest['id'] . '.png',
      'category' => $catId
    ];
  }

  $h1 = ($totalCount > 0 ? number_format($totalCount) . ' ' : '') . $site['title'];
  $desc = $site['description'];

  $title = $h1;
  $metaDesc = $desc;
  $canonical = 'https://coloring.g55.co/';
}
