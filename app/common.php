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
    exit;
  }

  $data = json_decode($raw, true);
  if (!is_array($data)) {
    http_response_code(500);
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
    exit;
  }

  $path = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'categories' . DIRECTORY_SEPARATOR . $cid . '.json';
  $data = read_json($path);

  if (!isset($data['pages']) || !is_array($data['pages'])) {
    http_response_code(500);
    exit;
  }

  return [$cid, $data['pages']];
}

function sort_categories_alpha(array $cats): array {
  usort($cats, function($a, $b) {
    return strcasecmp($a['id'], $b['id']);
  });
  return $cats;
}

function newest_page(array $pages): array {
  $n = count($pages);
  if ($n === 0) {
    http_response_code(500);
    exit;
  }

  return $pages[0];
}

function get_categories_sorted(array $index): array {
  return sort_categories_alpha($index['categories']);
}

function clean_slug($s): string {
  return preg_replace('/[^a-z0-9_-]/i', '', (string)$s);
}

function makeImageAlt(string $id): string {
  $imageAlt = str_replace('-', ' ', $id);
  $imageAlt = str_replace(['coloring page', 'free', 'printable'], ['line art', '', ''], $imageAlt);
  return preg_replace('/\s+/', ' ', trim($imageAlt));
}

function build_category_clusters(array $categories): array {
    $categories = sort_categories_alpha($categories);
    $map = [];
    $order = [];

    foreach ($categories as $cat) {
        if (!is_array($cat) || empty($cat['id']) || empty($cat['name'])) {
            continue;
        }

        $id = clean_slug($cat['id']);
        if ($id === '') {
            continue;
        }

        $map[$id] = $cat;
        $order[] = $id;
    }

    $parentIds = [];

    foreach ($order as $id) {
        $candidate = $id;

        while (($separator = strrpos($candidate, '-')) !== false) {
            $candidate = substr($candidate, 0, $separator);

            if (isset($map[$candidate])) {
                $parentIds[$candidate] = true;
            }
        }
    }

    $clustersByParent = [];
    $browseMore = [];

    foreach ($order as $id) {
        $parentId = '';

        if (isset($parentIds[$id])) {
            $parentId = $id;
        } else {
            $candidate = $id;

            while (($separator = strrpos($candidate, '-')) !== false) {
                $candidate = substr($candidate, 0, $separator);

                if (isset($parentIds[$candidate])) {
                    $parentId = $candidate;
                    break;
                }
            }
        }

        if ($parentId === '') {
            $browseMore[] = $map[$id];
            continue;
        }

        $clustersByParent[$parentId][] = $map[$id];
    }

    $clusters = [];

    foreach ($order as $id) {
        if (isset($clustersByParent[$id])) {
            $clusters[] = $clustersByParent[$id];
        }
    }

    return [
        'clusters' => $clusters,
        'browse_more' => $browseMore,
    ];
}

function get_categories_clustered(array $index): array {
    return build_category_clusters($index['categories'] ?? []);
}

function find_cluster_for_category(array $clustered, string $categoryId): array {
    foreach ($clustered['clusters'] as $cluster) {
        foreach ($cluster as $cat) {
            if ($cat['id'] === $categoryId) {
                return $cluster;
            }
        }
    }
    return [];
}
