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
&#169; <?php echo date('Y'); ?> coloring.g55.co
<a href="/privacy-policy.php">Privacy Policy</a>
<a href="mailto:crazygames888@gmail.com">Contact Us</a>
</footer>
