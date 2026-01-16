<?php
// app.php
header('Content-Type: text/html; charset=utf-8');

/* helpers */

function h($s) {
  return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function read_json($path) {
  if (!file_exists($path)) return null;
  $raw = file_get_contents($path);
  if ($raw === false) return null;
  $data = json_decode($raw, true);
  return is_array($data) ? $data : null;
}

function site_index() {
  return read_json(__DIR__ . '/pages.json');
}

function load_category_pages($cid) {
  $cid = preg_replace('/[^a-z0-9_-]/i', '', (string)$cid);
  $path = __DIR__ . '/categories/' . $cid . '.json';
  $data = read_json($path);
  return ($data && isset($data['pages']) && is_array($data['pages'])) ? $data['pages'] : [];
}

function sort_categories_alpha($cats) {
  usort($cats, function($a, $b) {
    return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
  });
  return $cats;
}

function newest_page($pages) {
  return count($pages) ? $pages[count($pages) - 1] : null;
}

function title_with_count($count, $title) {
  return $count > 0 ? $count . ' ' . $title : $title;
}

function find_page_global($categories, $id) {
  foreach ($categories as $c) {
    $cid = $c['id'] ?? '';
    if (!$cid) continue;
    foreach (load_category_pages($cid) as $p) {
      if (($p['id'] ?? '') === $id) {
        return [$p, $cid];
      }
    }
  }
  return [null, null];
}

/* ROUTER */

$view = basename($_SERVER['SCRIPT_NAME']);

$index = site_index();
$site = $index['site'] ?? [];
$categories = sort_categories_alpha($index['categories'] ?? []);

$data = [
  'site' => $site,
  'categories' => $categories
];

/* INDEX PAGE */

if ($view === '/') {

  $cid = preg_replace('/[^a-z0-9_-]/i', '', $_GET['c'] ?? '');
  $isCategory = $cid !== '';

  $grid = [];
  $totalCount = 0;

  if (!$isCategory) {
    foreach ($categories as $c) {
      $pages = load_category_pages($c['id']);
      $totalCount += count($pages);
      $newest = newest_page($pages);
      if ($newest) {
        $grid[] = [
          'id' => $newest['id'],
          'title' => $newest['title'],
          'image' => $newest['image'],
          'category' => $c['id']
        ];
      }
    }

    $h1 = title_with_count($totalCount, $site['h1'] ?? '');
    $desc = $site['description'] ?? '';
    $title = $h1;
    $canonical = '/';
  } else {
    $cat = null;
    foreach ($categories as $c) {
      if ($c['id'] === $cid) { $cat = $c; break; }
    }

    $pages = array_reverse(load_category_pages($cid));
    foreach ($pages as $p) {
      $grid[] = [
        'id' => $p['id'],
        'title' => $p['title'],
        'image' => $p['image'],
        'category' => $cid
      ];
    }

    $count = count($grid);
    $h1 = title_with_count($count, $cat['name'] ?? 'Category');
    $desc = $cat['description'] ?? ($site['description'] ?? '');
    $title = $h1 . (!empty($site['title']) ? ' | ' . $site['title'] : '');
    $canonical = '/?c=' . rawurlencode($cid);
  }

  $data += compact('grid','h1','desc','title','canonical');
}

/* PAGE VIEW */

if ($view === 'page.php') {

  $id = preg_replace('/[^a-z0-9_-]/i', '', $_GET['id'] ?? '');
  $cid = preg_replace('/[^a-z0-9_-]/i', '', $_GET['c'] ?? '');

  $page = null;

  if ($cid) {
    foreach (load_category_pages($cid) as $p) {
      if ($p['id'] === $id) { $page = $p; break; }
    }
  }

  if (!$page) {
    list($page, $cid) = find_page_global($categories, $id);
  }

  if (!$page) {
    http_response_code(404);
    $data['notFound'] = true;
    return;
  }

  $cat = null;
  foreach ($categories as $c) {
    if ($c['id'] === $cid) { $cat = $c; break; }
  }

  $title = $page['title'] . (!empty($site['title']) ? ' | ' . $site['title'] : '');
  $canonical = 'page.php?id=' . rawurlencode($id) . '&c=' . rawurlencode($cid);

  $similar = [];
  foreach (array_reverse(load_category_pages($cid)) as $p) {
    if ($p['id'] === $id) continue;
    $similar[] = $p;
    if (count($similar) >= 8) break;
  }

  $data += compact('page','cat','cid','title','canonical','similar');
}
