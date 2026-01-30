<?php
// app/page_pre.php
require_once 'common.php';

$index = load_site_index();
$site = $index['site'];
$categories = get_categories_sorted($index);

if (!isset($_GET['id'], $_GET['c'])) {
  header('Location: /', true, 302);
  exit;
}

$id = clean_slug($_GET['id']);
$cid = clean_slug($_GET['c']);

if ($id === '' || $cid === '') {
  header('Location: /', true, 302);
  exit;
}

$cat = null;
foreach ($categories as $c) {
  if ($c['id'] === $cid) {
    $cat = $c;
    break;
  }
}

if ($cat === null) {
  header('Location: /', true, 302);
  exit;
}

list($_, $pages) = load_category_pages($cid);

$page = null;
foreach ($pages as $p) {
  if ($p['id'] === $id) {
    $page = $p;
    break;
  }
}

if ($page === null) {
  header('Location: /', true, 302);
  exit;
}

$pageTitle = $page['title'];
$title = $pageTitle;

$metaDesc = $page['description'];
$canonical = 'https://coloring.g55.co/page.php?id=' . rawurlencode($id) . '&c=' . rawurlencode($cid);
$imageSrc = '/categories/' . $cid . '/' . $page['id'] . '.png';
$imageAlt = str_replace('-', ' ', $page['id']);
$imageAlt = str_replace(['coloring page', 'free', 'printable'], ['line art', '', ''], $imageAlt);
$imageAlt = preg_replace('/\s+/', ' ', trim($imageAlt));

$h1 = $pageTitle;
$desc = $page['description'];

$pool = [];
foreach ($pages as $p) {
  if ($p['id'] === $id) continue;
  $pool[] = $p;
}

$limit = min(4, count($pool));
$similar = [];

if ($limit > 0) {
  $keys = array_rand($pool, $limit);
  if (!is_array($keys)) $keys = [$keys];
  foreach ($keys as $k) {
    $similar[] = $pool[$k];
  }
}

$moreText = 'More ' . $cat['name'] . ' Coloring Pages';
$moreHref = '/?c=' . rawurlencode($cid);
$moreTitle = 'Similar ' . $cat['name'] . ' Coloring Pages';
