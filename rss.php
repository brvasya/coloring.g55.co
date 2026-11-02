<?php
require_once __DIR__ . '/app/common.php';

$index = load_site_index();
$pages = [];
foreach ($index['categories'] as $category) {
  $cid = $category['id'];
  list($_, $categoryPages) = load_category_pages($cid);
  foreach ($categoryPages as $p) {
    $imagePath = __DIR__ . '/categories/' . $cid . '/' . $p['id'] . '.png';
    if (!is_file($imagePath)) continue;

    $mtime = filemtime($imagePath);
    if ($mtime === false) continue;

    $p['cid'] = $cid;
    $p['mtime'] = $mtime;
    $pages[] = $p;
  }
}
usort($pages, function($a, $b) {
  return ($b['mtime'] <=> $a['mtime'])
    ?: strcmp($a['cid'] . '/' . $a['id'], $b['cid'] . '/' . $b['id']);
});
$items = max(1, (int) ($_GET['items'] ?? 100));
$pages = array_slice($pages, 0, $items);
header('Content-Type: application/rss+xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/">
<channel>
<title>Coloring Pages</title>
<link>https://coloring.g55.co/</link>
<description>Latest printable coloring pages from all categories.</description>
<?php foreach ($pages as $p): ?>
<item>
<title><?php echo h($p['title']); ?></title>
<link>https://coloring.g55.co/page.php?id=<?php echo rawurlencode($p['id']); ?>&amp;c=<?php echo rawurlencode($p['cid']); ?></link>
<guid isPermaLink="true">https://coloring.g55.co/page.php?id=<?php echo rawurlencode($p['id']); ?>&amp;c=<?php echo rawurlencode($p['cid']); ?></guid>
<description><?php echo h($p['description']); ?></description>
<media:content url="https://coloring.g55.co/categories/<?php echo rawurlencode($p['cid']); ?>/<?php echo rawurlencode($p['id']); ?>.png" medium="image" type="image/png" />
</item>
<?php endforeach; ?>
</channel>
</rss>
