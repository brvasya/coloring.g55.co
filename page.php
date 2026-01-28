<?php require_once __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'page_pre.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo h($title); ?></title>
<meta name="description" content="<?php echo h($metaDesc); ?>">
<link rel="canonical" href="<?php echo h($canonical); ?>">
<link rel="image_src" href="<?php echo h($imageSrc); ?>">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
<link rel="stylesheet" href="/style.css">
<script defer src="/colors.js"></script>
<script async src="https://cse.google.com/cse.js?cx=d0a39b24af4ab40a8"></script>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6180203036822393" crossorigin="anonymous"></script>
<script async src="https://www.googletagmanager.com/gtag/js?id=G-F24J0X7PDM"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', 'G-F24J0X7PDM');
</script>
</head>

<body>
<table id="header">
<tr>
<td id="header-left"><a id="logo" href="/"></a></td>
<td id="header-right"><div class="gcse-searchbox-only"></div></td>
</tr>
</table>
<table id="content">
<tr>
<td id="container">
<div class="tower_r">
<h1><?php echo h($h1); ?></h1>
<p><?php echo h($desc); ?></p>
<a class="tag" id="save" href="https://www.pinterest.com/pin/create/button/?url=<?php echo rawurlencode($canonical); ?>&description=<?php echo rawurlencode($desc); ?>" target="_blank">Save to Pinterest</a>
<button class="tag" id="print" onclick="window.print();">Print</button>
<a class="tag" id="download" href="<?php echo h($imageSrc); ?>" download>Download</a>
<a class="tag" id="more" href="<?php echo h($moreHref); ?>"><?php echo h($moreText); ?></a>
</div>
<img class="page" id="printable" onclick="this.requestFullscreen();" src="<?php echo h($imageSrc); ?>" alt="<?php echo h($imageAlt); ?>">
</td>
</tr>
</table>
<table id="more-pages">
<tr>
<td>
<h2><?php echo h($moreTitle); ?></h2>
<div class="pages">
<?php foreach ($similar as $p): ?>
<a class="thumbnail" style="background-image: url(<?php echo h('/categories/' . $cid . '/' . $p['id'] . '.png'); ?>);" href="/page.php?id=<?php echo rawurlencode($p['id']); ?>&c=<?php echo rawurlencode($cid); ?>"><span><?php echo h($p['title']); ?></span></a>
<?php endforeach; ?>
</div>
</td>
</tr>
</table>
<table id="menu">
<tr>
<td>
<h3>Browse More Free Printable Coloring Pages</h3>
<ul class="menu">
<?php foreach ($categories as $c): ?>
<li><a class="tag" href="/?c=<?php echo rawurlencode($c['id']); ?>"><?php echo h($c['name']); ?></a></li>
<?php endforeach; ?>
</ul>
</td>
</tr>
</table>
<table id="footer">
<tr>
<td>
<a href="/privacy-policy.php">Privacy Policy</a>
</td>
</tr>
</table>
</body>
</html>
