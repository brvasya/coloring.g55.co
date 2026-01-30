<?php require_once 'app/index_pre.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo h($title); ?></title>
<meta name="description" content="<?php echo h($metaDesc); ?>">
<link rel="canonical" href="<?php echo h($canonical); ?>">
<?php include 'head.php'; ?>
</head>

<body>
<?php include 'header.php'; ?>
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