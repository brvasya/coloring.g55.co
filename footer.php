<nav>
<h2>Explore All Coloring Page Categories</h2>
<ul class="categories">
<?php foreach ($categories as $c): ?>
<li><a class="tag" href="/?c=<?php echo rawurlencode($c['id']); ?>"><?php echo h($c['name']); ?></a></li>
<?php endforeach; ?>
</ul>
</nav>
<footer>
<a href="/privacy-policy.php">Privacy Policy</a>
</footer>
