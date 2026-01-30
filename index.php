<?php require_once __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'index_pre.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo h($title); ?></title>
<meta name="description" content="<?php echo h($metaDesc); ?>">
<link rel="canonical" href="<?php echo h($canonical); ?>">
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
<a class="thumbnail" style="background-image: url(<?php echo h($it['image']); ?>);" href="/page.php?id=<?php echo rawurlencode($it['id']); ?>&c=<?php echo rawurlencode($it['category']); ?>"><span><?php echo h($it['title']); ?></span></a>
<?php endforeach; ?>
</td>
</tr>
</table>
<?php include 'footer.php'; ?>
</body>
</html>