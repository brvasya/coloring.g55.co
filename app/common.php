<?php
// app/common.php

function h(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function read_json(string $path): array {
  static $cache = [];

  if (isset($cache[$path])) {
    return $cache[$path];
  }

  $raw = file_get_contents($path);
  if ($raw === false) {
    http_response_code(500);
    echo 'JSON read failed';
    exit;
  }

  $data = json_decode($raw, true);
  if (!is_array($data)) {
    http_response_code(500);
    echo 'Invalid JSON';
    exit;
  }

  $cache[$path] = $data;
  return $data;
}

function load_site_index(): array {
  $path = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'categories.json';
  return read_json($path);
}

function load_category_pages(string $cid): array {
  if ($cid === '' || !preg_match('/^[a-z0-9_-]+$/i', $cid)) {
    http_response_code(400);
    echo 'Invalid category';
    exit;
  }

  $path = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'categories' . DIRECTORY_SEPARATOR . $cid . '.json';
  $data = read_json($path);

  if (!isset($data['pages']) || !is_array($data['pages'])) {
    http_response_code(500);
    echo 'Invalid category schema';
    exit;
  }

  return [$cid, $data['pages']];
}

function sort_categories_alpha(array $cats): array {
  usort($cats, function($a, $b) {
    return strcasecmp($a['name'], $b['name']);
  });
  return $cats;
}

function newest_page(array $pages): array {
  $n = count($pages);
  if ($n === 0) {
    http_response_code(500);
    echo 'Empty category';
    exit;
  }
  return $pages[$n - 1];
}

function get_categories_sorted(array $index): array {
  return sort_categories_alpha($index['categories']);
}

function clean_slug($s): string {
  return preg_replace('/[^a-z0-9_-]/i', '', (string)$s);
}
