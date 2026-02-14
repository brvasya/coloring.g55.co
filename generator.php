<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$base_dir = __DIR__;
$app_dir = $base_dir . DIRECTORY_SEPARATOR . 'app';
$categories_dir = $base_dir . DIRECTORY_SEPARATOR . 'categories';

$list_names = ['characters', 'actions', 'environments'];

$pool_files = [
  'intro' => $app_dir . DIRECTORY_SEPARATOR . 'intro_pool.txt',
  'usage' => $app_dir . DIRECTORY_SEPARATOR . 'usage_pool.txt',
  'ease' => $app_dir . DIRECTORY_SEPARATOR . 'ease_pool.txt',
  'benefit' => $app_dir . DIRECTORY_SEPARATOR . 'benefit_pool.txt',
];

function qs(string $k, string $default = ''): string {
  return isset($_GET[$k]) ? trim((string)$_GET[$k]) : $default;
}
function qi(string $k, int $default = 0): int {
  return isset($_GET[$k]) ? max(0, (int)$_GET[$k]) : $default;
}

function load_lines(string $path): array {
  if (!is_file($path)) return [];
  $lines = file($path, FILE_IGNORE_NEW_LINES);
  if (!is_array($lines)) return [];
  $out = [];
  foreach ($lines as $line) {
    $s = trim((string)$line);
    if ($s === '') continue;
    if (str_starts_with($s, '#')) continue;
    $out[] = $s;
  }
  return $out;
}

function load_style(string $style_file): string {
  $lines = load_lines($style_file);
  $s = trim(implode(' ', $lines));
  $s = preg_replace('/\s{2,}/', ' ', $s ?? '') ?? '';
  return trim($s);
}

function strip_leading_article(string $s): string {
  $s = trim($s);
  $s = preg_replace('/^(a|an|the)\s+/i', '', $s) ?? $s;
  return trim($s);
}

function format_title(string $s): string {
  $s = trim($s);
  $s = preg_replace('/\s{2,}/', ' ', $s ?? '') ?? $s;
  return ucwords(strtolower($s));
}

function slugify(string $s): string {
  $s = strtolower(trim($s));
  $s = preg_replace('/[^\p{L}\p{N}]+/u', '-', $s) ?? $s;
  $s = preg_replace('/-+/', '-', $s) ?? $s;
  $s = trim($s, '-');
  return $s !== '' ? $s : 'item';
}

function build_h1(array $parts): string {
  $character = strip_leading_article((string)$parts['character']);
  $action = trim((string)$parts['action']);
  $env = trim((string)$parts['environment']);
  $base = trim(preg_replace('/\s{2,}/', ' ', "$character $action $env") ?? '');
  return 'Free Printable ' . format_title($base) . ' Coloring Page for Kids';
}

function build_seo_base_for_slug(array $parts): string {
  $character = strip_leading_article((string)$parts['character']);
  $action = trim((string)$parts['action']);
  $env = trim((string)$parts['environment']);
  $base = trim(preg_replace('/\s{2,}/', ' ', "$character $action $env coloring page") ?? '');
  return $base;
}

function build_id(array $parts): string {
  return slugify(build_seo_base_for_slug($parts));
}

function build_prompt(array $parts, string $style): string {
  $core = trim(preg_replace('/\s{2,}/', ' ', $parts['character'] . ' ' . $parts['action'] . ' ' . $parts['environment']) ?? '');
  return 'Coloring page on white background, ' . $core . ', ' . rtrim($style, '.') . '.';
}

function clean_sentence(string $s): string {
  $s = trim(preg_replace('/\s{2,}/', ' ', trim($s)) ?? '');
  $s = trim($s, " ,");
  if ($s === '') return '';
  if (!str_ends_with($s, '.')) $s .= '.';
  return $s;
}

function render_template(string $line, string $scene): string {
  return str_replace('{scene}', $scene, $line);
}

function build_page_description(array $parts, array $pools): string {
  $scene = trim(preg_replace('/\s{2,}/', ' ', $parts['character'] . ' ' . $parts['action'] . ' ' . $parts['environment']) ?? '');

  foreach (['intro','usage','ease','benefit'] as $k) {
    if (empty($pools[$k])) return 'Missing pool files.';
  }

  $patterns = [
    ['intro', 'usage', 'ease', 'benefit'],
    ['intro', 'usage', 'benefit', 'ease'],
    ['intro', 'ease', 'usage', 'benefit'],
    ['intro', 'ease', 'benefit', 'usage'],
    ['intro', 'benefit', 'usage', 'ease'],
    ['intro', 'benefit', 'ease', 'usage'],
  ];

  $pattern = $patterns[array_rand($patterns)];
  $sentences = [];

  foreach ($pattern as $k) {
    $line = $pools[$k][array_rand($pools[$k])];
    $sentences[] = clean_sentence(render_template($line, $scene));
  }

  return trim(implode(' ', array_filter($sentences)));
}

function ensure_dir(string $dir): void {
  if (is_dir($dir)) return;
  @mkdir($dir, 0755, true);
}

function sanitize_filename(string $name): string {
  $name = trim($name);
  $name = preg_replace('/[^a-zA-Z0-9\.\_\-]+/', '_', $name) ?? $name;
  $name = preg_replace('/_+/', '_', $name) ?? $name;
  $name = trim($name, '_');
  return $name !== '' ? $name : 'image';
}

function gemini_generate_image(string $api_key, string $prompt, string $out_path, string $aspect_ratio = '2:3', string $model = 'gemini-2.5-flash-image'): array {
  $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent';

  $payload = [
    'contents' => [
      [
        'parts' => [
          ['text' => $prompt]
        ]
      ]
    ],
    'generationConfig' => [
      'responseModalities' => ['Image'],
      'imageConfig' => [
        'aspectRatio' => $aspect_ratio
      ]
    ]
  ];

  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
      'Content-Type: application/json',
      'x-goog-api-key: ' . $api_key
    ],
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    CURLOPT_TIMEOUT => 120,
  ]);

  $raw = curl_exec($ch);
  $err = curl_error($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($raw === false) {
    return ['ok' => false, 'error' => 'curl_error', 'detail' => $err];
  }
  if ($code < 200 || $code >= 300) {
    return ['ok' => false, 'error' => 'http_error', 'status' => $code, 'detail' => $raw];
  }

  $json = json_decode($raw, true);
  if (!is_array($json)) {
    return ['ok' => false, 'error' => 'bad_json', 'detail' => $raw];
  }

  $image_b64 = null;
  $parts = $json['candidates'][0]['content']['parts'] ?? [];
  foreach ($parts as $p) {
    if (isset($p['inlineData']['data'])) {
      $image_b64 = (string)$p['inlineData']['data'];
      break;
    }
    if (isset($p['inline_data']['data'])) {
      $image_b64 = (string)$p['inline_data']['data'];
      break;
    }
  }

  if (!$image_b64) {
    return ['ok' => false, 'error' => 'no_image_in_response', 'detail' => $json];
  }

  $bytes = base64_decode($image_b64, true);
  if ($bytes === false) {
    return ['ok' => false, 'error' => 'base64_decode_failed'];
  }

  ensure_dir(dirname($out_path));
  $ok = file_put_contents($out_path, $bytes);
  if ($ok === false) {
    return ['ok' => false, 'error' => 'write_failed', 'path' => $out_path];
  }

  return ['ok' => true, 'path' => $out_path];
}

function category_json_path(string $categories_dir, string $category_name): string {
  return $categories_dir . DIRECTORY_SEPARATOR . $category_name . '.json';
}

function prepend_pages_to_category_json(string $path, array $pages_to_add): array {
  if (!is_file($path)) {
    return ['ok' => false, 'error' => 'missing_category_json', 'path' => $path];
  }

  $fp = fopen($path, 'c+');
  if (!$fp) return ['ok' => false, 'error' => 'open_failed', 'path' => $path];

  if (!flock($fp, LOCK_EX)) {
    fclose($fp);
    return ['ok' => false, 'error' => 'lock_failed', 'path' => $path];
  }

  $raw = stream_get_contents($fp);
  $data = json_decode($raw ?: '{}', true);
  if (!is_array($data)) $data = [];

  if (!isset($data['pages']) || !is_array($data['pages'])) $data['pages'] = [];
  $data['pages'] = array_values(array_merge($pages_to_add, $data['pages']));

  ftruncate($fp, 0);
  rewind($fp);
  fwrite($fp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
  fflush($fp);
  flock($fp, LOCK_UN);
  fclose($fp);

  return ['ok' => true, 'count' => count($pages_to_add)];
}

$category = qs('c', '');
$count = max(1, min(20, qi('n', 1)));
$do_img = qi('img', 0) === 1;
$dry = qi('dry', 0) === 1;

$aspect_ratio = qs('ar', '2:3');
$model = qs('model', 'gemini-2.5-flash-image');

$api_key = qs('key', '');
if ($api_key === '') {
  $api_key = (string)getenv('GEMINI_API_KEY');
}

if ($category === '') {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'missing_c_param']);
  exit;
}

$cat_dir = $categories_dir . DIRECTORY_SEPARATOR . $category;
if (!is_dir($cat_dir)) {
  http_response_code(404);
  echo json_encode(['ok' => false, 'error' => 'missing_category_dir', 'category' => $category]);
  exit;
}

$data = [];
foreach ($list_names as $k) {
  $data[$k] = load_lines($cat_dir . DIRECTORY_SEPARATOR . $k . '.txt');
}
$style = load_style($categories_dir . DIRECTORY_SEPARATOR . 'style.txt');

$pools = [];
foreach ($pool_files as $k => $p) {
  $pools[$k] = load_lines($p);
}

$missing = [];
foreach ($list_names as $k) if (empty($data[$k])) $missing[] = $k;
foreach (['intro','usage','ease','benefit'] as $k) if (empty($pools[$k])) $missing[] = 'pool_' . $k;
if ($style === '') $missing[] = 'style';

if (!empty($missing)) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'missing_inputs', 'missing' => $missing], JSON_UNESCAPED_UNICODE);
  exit;
}

$generated = [];
for ($i = 0; $i < $count; $i++) {
  $parts = [
    'character' => $data['characters'][array_rand($data['characters'])],
    'action' => $data['actions'][array_rand($data['actions'])],
    'environment' => $data['environments'][array_rand($data['environments'])],
  ];

  $page = [
    'parts' => $parts,
    'h1' => build_h1($parts),
    'id' => build_id($parts),
    'prompt' => build_prompt($parts, $style),
    'page_description' => build_page_description($parts, $pools),
  ];

  if ($do_img) {
    if ($api_key === '') {
      $page['image_error'] = 'missing_api_key';
    } else {
      $img_name = sanitize_filename($page['id']) . '.png';
      $out_path = $categories_dir . DIRECTORY_SEPARATOR . $category . DIRECTORY_SEPARATOR . $img_name;
      $img_res = gemini_generate_image($api_key, $page['prompt'], $out_path, $aspect_ratio, $model);
      if (!($img_res['ok'] ?? false)) {
        $page['image_error'] = $img_res;
      } else {
        $page['image_path'] = $out_path;
      }
    }
  }

  $generated[] = $page;
}

$write_res = null;
if (!$dry) {
  $json_path = category_json_path($categories_dir, $category);
  $write_res = prepend_pages_to_category_json($json_path, $generated);
}

echo json_encode([
  'ok' => true,
  'category' => $category,
  'count' => $count,
  'wrote' => $dry ? false : true,
  'write_result' => $write_res,
  'items' => $generated
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);