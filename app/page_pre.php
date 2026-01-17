<?php
// app/page_pre.php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'common.php';

$index = load_site_index();
$site = $index['site'] ?? [];
$categories = get_categories_sorted($index);

$id = isset($_GET['id']) ? $_GET['id'] : '';
$cid = isset($_GET['c']) ? $_GET['c'] : '';

$id = clean_slug($id);
$cid = clean_slug($cid);

$page = null;
$cat = null;

if ($cid) {
  list($_, $pages) = load_category_pages($cid);
  foreach ($pages as $p) {
    if (($p['id'] ?? '') === $id) { $page = $p; break; }
  }
}

if (!$page && $id) {
  foreach ($categories as $c) {
    $tryCid = $c['id'] ?? '';
    if (!$tryCid) continue;
    list($_, $pages) = load_category_pages($tryCid);
    foreach ($pages as $p) {
      if (($p['id'] ?? '') === $id) { $page = $p; $cid = $tryCid; break 2; }
    }
  }
}

foreach ($categories as $c) {
  if (($c['id'] ?? '') === $cid) { $cat = $c; break; }
}

if (!$page) {
  exit;
}

$pageTitle = $page['title'] ?? '';
$title = $pageTitle;

$metaDesc = $page['description'] ?? $pageTitle;
$canonical = 'https://coloring.g55.co/page.php?id=' . rawurlencode($id) . '&c=' . rawurlencode($cid);
$imageSrc = $page['image'] ?? '';

$h1 = $pageTitle;
$desc = $page['description'] ?? ($cat['description'] ?? ($site['description'] ?? ''));

list($_, $pagesAll) = load_category_pages($cid);
$pagesAllRev = array_reverse($pagesAll);

$similar = [];
foreach ($pagesAllRev as $p) {
  if (($p['id'] ?? '') === $id) continue;
  if (!isset($p['id'], $p['title'], $p['image'])) continue;
  $similar[] = $p;
  if (count($similar) >= 5) break;
}

$moreText = $cat ? ('More ' . ($cat['name'] ?? '')) : '';
$moreHref = '/?c=' . rawurlencode($cid);
$moreTitle = $cat ? ('Similar Free Printable ' . ($cat['name'] ?? '') . ' You May Like') : 'Similar Pages';
