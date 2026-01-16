<?php
// page.php
header('Content-Type: text/html; charset=utf-8');

function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

function read_json($path) {
  if (!file_exists($path)) return null;
  $raw = file_get_contents($path);
  if ($raw === false) return null;
  $data = json_decode($raw, true);
  return is_array($data) ? $data : null;
}

function load_site_index() {
  return read_json(__DIR__ . DIRECTORY_SEPARATOR . 'pages.json');
}

function load_category_pages($cid) {
  $cid = preg_replace('/[^a-z0-9_-]/i', '', (string)$cid);
  $path = __DIR__ . DIRECTORY_SEPARATOR . 'categories' . DIRECTORY_SEPARATOR . $cid . '.json';
  $data = read_json($path);
  $pages = ($data && isset($data['pages']) && is_array($data['pages'])) ? $data['pages'] : [];
  return [$cid, $pages];
}

function sort_categories_alpha($cats) {
  usort($cats, function($a, $b) {
    return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
  });
  return $cats;
}

$index = load_site_index();
$site = $index['site'] ?? [];
$categories = $index['categories'] ?? [];
if (!is_array($categories)) $categories = [];
$categories = sort_categories_alpha($categories);

$id = isset($_GET['id']) ? $_GET['id'] : '';
$cid = isset($_GET['c']) ? $_GET['c'] : '';

$id = preg_replace('/[^a-z0-9_-]/i', '', (string)$id);
$cid = preg_replace('/[^a-z0-9_-]/i', '', (string)$cid);

$page = null;
$cat = null;

if ($cid) {
  list($_, $pages) = load_category_pages($cid);
  foreach ($pages as $p) {
    if (($p['id'] ?? '') === $id) { $page = $p; break; }
  }
}

// If category param missing or wrong, find page by scanning categories
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
  http_response_code(404);
  echo 'Not found';
  exit;
}

$pageTitle = $page['title'] ?? '';
$siteTitle = $site['title'] ?? '';
$title = $pageTitle . ($siteTitle ? ' | ' . $siteTitle : '');

$metaDesc = $page['description'] ?? $pageTitle;
$canonical = 'page.php?id=' . rawurlencode($id) . '&c=' . rawurlencode($cid);
$imageSrc = $page['image'] ?? '';

$h1 = $pageTitle;
$desc = $page['description'] ?? ($cat['description'] ?? ($site['description'] ?? ''));

list($_, $pagesAll) = load_category_pages($cid);
$pagesAllRev = array_reverse($pagesAll); // newest to oldest

$similar = [];
foreach ($pagesAllRev as $p) {
  if (($p['id'] ?? '') === $id) continue;
  if (!isset($p['id'], $p['title'], $p['image'])) continue;
  $similar[] = $p;
  if (count($similar) >= 8) break;
}

$moreText = $cat ? ('More ' . ($cat['name'] ?? '')) : '';
$moreHref = '/?c=' . rawurlencode($cid);
$moreTitle = $cat ? ('Similar Free Printable ' . ($cat['name'] ?? '') . ' You May Like') : 'Similar Pages';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo h($title); ?></title>
<meta name="description" content="<?php echo h($metaDesc); ?>">
<link rel="canonical" href="<?php echo h($canonical); ?>">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
<link rel="stylesheet" href="style.css">
<script src="colors.js"></script>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6180203036822393" crossorigin="anonymous"></script>
<script async src="https://www.googletagmanager.com/gtag/js?id=G-6SLYYXXV9H"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', 'G-6SLYYXXV9H');
</script>
<script>
(function() {
  var cx = 'partner-pub-6180203036822393:4728109199';
  var gcse = document.createElement('script');
  gcse.type = 'text/javascript';
  gcse.async = true;
  gcse.src = 'https://cse.google.com/cse.js?cx=' + cx;
  var s = document.getElementsByTagName('script')[0];
  s.parentNode.insertBefore(gcse, s);
})();
</script>
</head>

<body>
<table id="header">
<tr>
<td id="header-left"><a id="logo" href="/" title="" target="_top"></a></td>
<td id="header-right"><div class="gcse-searchbox-only"></div></td>
</tr>
</table>
<table id="content">
<tr>
<td>
<div id="container">
<img class="page" id="printable" onclick="document.querySelector('#printable') && document.querySelector('#printable').requestFullscreen && document.querySelector('#printable').requestFullscreen();" src="<?php echo h($imageSrc); ?>" alt="<?php echo h($desc); ?>">
<div class="tower_r">
<h1><?php echo h($h1); ?></h1>
<p><?php echo h($desc); ?></p>
<a class="tag" id="print" href="#printable" onclick="window.print();">Print</a>
<a class="tag" id="download" href="<?php echo h($imageSrc); ?>" download>Download</a>
<?php if ($cat): ?>
<a class="tag" id="more" href="<?php echo h($moreHref); ?>"><?php echo h($moreText); ?></a>
<?php endif; ?>
</div>
</div>
</td>
</tr>
</table>
<table id="more-pages">
<tr>
<td>
<h2><?php echo h($moreTitle); ?></h2>
<ul class="more-pages" id="more-pages-list">
<?php foreach ($similar as $p): ?>
<a class="thumbnail" href="page.php?id=<?php echo rawurlencode($p['id']); ?>&c=<?php echo rawurlencode($cid); ?>" title="<?php echo h($p['title']); ?>" target="_top">
<img loading="lazy" src="<?php echo h($p['image']); ?>" alt="<?php echo h($p['title']); ?>" width="170" height="128">
</a>
<?php endforeach; ?>
</ul>
</td>
</tr>
</table>
<table id="menu">
<tr>
<td>
<h3>Discover More Free Printable Coloring Pages</h3>
<ul class="menu" id="category-menu">
<?php foreach ($categories as $c): ?>
<li><a class="tag" href="/?c=<?php echo rawurlencode($c['id'] ?? ''); ?>" title="<?php echo h($c['name'] ?? ''); ?>" target="_top"><?php echo h($c['name'] ?? ''); ?></a></li>
<?php endforeach; ?>
</ul>
</td>
</tr>
</table>
<table id="footer">
<tr>
<td>
<a href="privacy-policy.php" title="Privacy Policy" target="_top">Privacy Policy</a>
</td>
</tr>
</table>
</body>
</html>