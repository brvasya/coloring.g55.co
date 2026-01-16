<?php
// index.php
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

function newest_page($pages) {
  $n = count($pages);
  return $n ? $pages[$n - 1] : null;
}

$index = load_site_index();
$site = $index['site'] ?? [];
$categories = $index['categories'] ?? [];
if (!is_array($categories)) $categories = [];
$categories = sort_categories_alpha($categories);

$cid = isset($_GET['c']) ? $_GET['c'] : '';
$cid = preg_replace('/[^a-z0-9_-]/i', '', (string)$cid);
$isCategory = ($cid !== '');

$title = '';
$metaDesc = $site['description'] ?? '';
$canonical = '/';

$h1 = '';
$desc = '';
$gridItems = []; // each: id,title,image,category

if (!$isCategory) {
  // homepage: 1 newest image per category, categories already sorted alphabetically
  $totalCount = 0;

  foreach ($categories as $c) {
    $catId = $c['id'] ?? '';
    if (!$catId) continue;

    list($_, $pages) = load_category_pages($catId);
    $totalCount += count($pages);

    $newest = newest_page($pages);
    if ($newest && isset($newest['id'], $newest['title'], $newest['image'])) {
      $gridItems[] = [
        'id' => $newest['id'],
        'title' => $newest['title'],
        'image' => $newest['image'],
        'category' => $catId
      ];
    }
  }

  $h1Base = $site['h1'] ?? '';
  $h1 = ($totalCount > 0 ? $totalCount . ' ' : '') . $h1Base;
  $desc = $site['description'] ?? '';

  $title = $h1;
  $metaDesc = $site['description'] ?? '';
  $canonical = '/';
} else {
  $cat = null;
  foreach ($categories as $c) {
    if (($c['id'] ?? '') === $cid) { $cat = $c; break; }
  }

  list($_, $pages) = load_category_pages($cid);
  $pages = array_reverse($pages); // newest to oldest

  foreach ($pages as $p) {
    if (!isset($p['id'], $p['title'], $p['image'])) continue;
    $gridItems[] = [
      'id' => $p['id'],
      'title' => $p['title'],
      'image' => $p['image'],
      'category' => $cid
    ];
  }

  $catName = $cat['name'] ?? 'Category';
  $catDesc = $cat['description'] ?? ($site['description'] ?? '');

  $count = count($gridItems);
  $h1 = ($count > 0 ? $count . ' ' : '') . $catName;
  $desc = $catDesc;

  $siteTitle = $site['title'] ?? '';
  $title = $h1 . ($siteTitle ? ' | ' . $siteTitle : '');

  $metaDesc = $catDesc;
  $canonical = '/?c=' . rawurlencode($cid);
}
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
<table id="title">
<tr>
<td>
<h1><?php echo h($h1); ?></h1>
<p class="description"><?php echo h($desc); ?></p>
</td>
</tr>
</table>
<table id="content">
<tr>
<td class="pages">
<?php foreach ($gridItems as $it): ?>
<a class="thumbnail" href="page.php?id=<?php echo rawurlencode($it['id']); ?>&c=<?php echo rawurlencode($it['category']); ?>" title="<?php echo h($it['title']); ?>" target="_top">
<img loading="lazy" src="<?php echo h($it['image']); ?>" alt="<?php echo h($it['title']); ?>" width="170" height="128">
</a>
<?php endforeach; ?>
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