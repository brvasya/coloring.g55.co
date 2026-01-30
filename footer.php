<nav>
<h2>Browse More Coloring Pages</h2>
<ul class="categories">
<?php foreach ($categories as $c): ?>
<li><a class="tag" href="/?c=<?php echo rawurlencode($c['id']); ?>"><?php echo h($c['name']); ?></a></li>
<?php endforeach; ?>
</ul>
</nav>
</main>
<footer>
<a href="/privacy-policy.php">Privacy Policy</a>
</footer>
