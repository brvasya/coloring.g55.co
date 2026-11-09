<?php
$navByLetter = [];
foreach (($grouped['clusters'] ?? []) as $cluster) {
    $category = $cluster[0] ?? null;
    if (!is_array($category) || empty($category['id']) || empty($category['name'])) {
        continue;
    }
    $letter = strtoupper(substr($category['name'], 0, 1));
    $navByLetter[$letter][] = $category;
}
ksort($navByLetter);
?>
<header>
<a class="logo" href="/"></a>
<div class="gcse-searchbox-only"></div>
<button class="tag install">Install app</button>
</header>
<nav class="category-nav" aria-label="Browse coloring page categories">
<button class="tag categories-toggle" type="button" aria-expanded="false" aria-controls="category-letters">Browse categories</button>
<ul class="categories category-letters" id="category-letters">
<?php foreach (array_keys($navByLetter) as $letter): ?>
<li><button class="tag category-letter" type="button" aria-controls="category-panel" aria-expanded="false" aria-label="Browse <?php echo h($letter); ?> categories" data-letter="<?php echo h($letter); ?>"><?php echo h($letter); ?></button></li>
<?php endforeach; ?>
</ul>
<div class="category-panel" id="category-panel" hidden>
<?php foreach ($navByLetter as $letter => $categories): ?>
<ul class="categories category-links" data-letter="<?php echo h($letter); ?>" hidden>
<?php foreach ($categories as $category): ?>
<li><a class="tag" href="/?c=<?php echo rawurlencode($category['id']); ?>"><?php echo h($category['name']); ?></a></li>
<?php endforeach; ?>
</ul>
<?php endforeach; ?>
</div>
</nav>
