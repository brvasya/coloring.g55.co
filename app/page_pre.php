<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'common.php';

$index = load_site_index();
$site = $index['site'];
$categories = get_categories_sorted($index);

$id  = clean_slug($_GET['id']);
$cid = clean_slug($_GET['c']);

$page = null;
$cat  = null;

if (!$cid || !$id) {
  http_response_code(404);
  exit;
}

foreach ($categories as $c) {
  if ($c['id'] === $cid) {
    $cat = $c;
    break;
  }
}

if (!$cat) {
  http_response_code(404);
  exit;
}

list($_, $pages) = load_category_pages($cid);

foreach ($pages as $p) {
  if ($p['id'] === $id) {
    $page = $p;
    break;
  }
}

if (!$page) {
  http_response_code(404);
  exit;
}

$pageTitle = $page['title'];
$title = $pageTitle;
$metaDesc = $page['description'];

$canonical = 'https://coloring.g55.co/page.php?id='
  . rawurlencode($id)
  . '&c='
  . rawurlencode($cid);

$imageSrc = $page['image'];

$h1 = $pageTitle;
$desc = $page['description'];

$pagesAllRev = array_reverse($pages);
$similar = [];

foreach ($pagesAllRev as $p) {
  if ($p['id'] === $id) continue;
  $similar[] = $p;
  if (count($similar) >= 5) break;
}

$moreText = 'More ' . $cat['name'];
$moreHref = '/?c=' . rawurlencode($cid);
$moreTitle = 'Similar Free Printable ' . $cat['name'] . ' You May Like';
