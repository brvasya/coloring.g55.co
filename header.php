<header>
<a class="logo" href="/"></a>
<div class="gcse-searchbox-only"></div>
<button class="tag install">Install app</button>
</header>
<nav>
<ul class="categories">
<?php foreach (array_keys($homepageLetters) as $letter): ?>
<li><a class="tag" href="#<?php echo $letter; ?>"><?php echo $letter; ?></a></li>
<?php endforeach; ?>
</ul>
<ul class="categories">
<?php foreach ($grouped['clusters'] as $cluster): $c = $cluster[0]; ?>
<li><a class="tag" href="/?c=<?php echo rawurlencode($c['id']); ?>"><?php echo h($c['name']); ?></a></li>
<?php endforeach; ?>
</ul>
</nav>
