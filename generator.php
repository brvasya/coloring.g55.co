<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$BASE_DIR = __DIR__;
$APP_DIR = $BASE_DIR . DIRECTORY_SEPARATOR . 'app';
$CATEGORIES_DIR = $BASE_DIR . DIRECTORY_SEPARATOR . 'categories';

function qs(string $k, string $default = ''): string {
  return isset($_GET[$k]) ? trim((string)$_GET[$k]) : $default;
}
function qi(string $k, int $default = 0): int {
  return isset($_GET[$k]) ? (int)$_GET[$k] : $default;
}
function qb(string $k, bool $default = false): bool {
  if (!isset($_GET[$k])) return $default;
  $v = strtolower(trim((string)$_GET[$k]));
  return in_array($v, ['1','true','yes','on'], true);
}

function json_out(array $payload, int $code = 200): void {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
  exit;
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

function build_title(array $parts): string {
  $character = strip_leading_article((string)$parts['character']);
  $action = trim((string)$parts['action']);
  $env = trim((string)$parts['environment']);
  $base = trim(preg_replace('/\s{2,}/', ' ', "$character $action $env") ?? '');
  return 'Free Printable ' . format_title($base) . ' Coloring Page for Kids';
}

function build_id(array $parts): string {
  $character = strip_leading_article((string)$parts['character']);
  $action = trim((string)$parts['action']);
  $env = trim((string)$parts['environment']);
  $base = trim(preg_replace('/\s{2,}/', ' ', "$character $action $env coloring page") ?? '');
  return slugify($base);
}

function build_prompt(array $parts, string $style): string {
  $core = trim(preg_replace('/\s{2,}/', ' ', $parts['character'] . ' ' . $parts['action'] . ' ' . $parts['environment']) ?? '');
  return 'Coloring page on white background, ' . $core . ', ' . rtrim($style, '.') . '.';
}

function build_description(array $parts, array $pools): string {
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

function gemini_generate_image(
  string $api_key,
  string $prompt,
  string $out_path,
  string $aspect_ratio = '2:3',
  string $model = 'gemini-2.5-flash-image'
): array {
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
    CURLOPT_TIMEOUT => 180,
  ]);

  $raw = curl_exec($ch);
  $err = curl_error($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($raw === false) return ['ok' => false, 'error' => 'curl_error', 'detail' => $err];
  if ($code < 200 || $code >= 300) return ['ok' => false, 'error' => 'http_error', 'status' => $code, 'detail' => $raw];

  $json = json_decode($raw, true);
  if (!is_array($json)) return ['ok' => false, 'error' => 'bad_json', 'detail' => $raw];

  $image_b64 = null;
  $parts = $json['candidates'][0]['content']['parts'] ?? [];
  foreach ($parts as $p) {
    if (isset($p['inlineData']['data'])) { $image_b64 = (string)$p['inlineData']['data']; break; }
    if (isset($p['inline_data']['data'])) { $image_b64 = (string)$p['inline_data']['data']; break; }
  }
  if (!$image_b64) return ['ok' => false, 'error' => 'no_image_in_response'];

  $bytes = base64_decode($image_b64, true);
  if ($bytes === false) return ['ok' => false, 'error' => 'base64_decode_failed'];

  ensure_dir(dirname($out_path));
  $ok = file_put_contents($out_path, $bytes);
  if ($ok === false) return ['ok' => false, 'error' => 'write_failed', 'path' => $out_path];

  return ['ok' => true];
}

function imagick_is_bilevel(Imagick $img): bool {
  try {
    $type = $img->getImageType();
    if ($type === Imagick::IMGTYPE_BILEVEL) return true;
  } catch (Exception $e) {
  }
  try {
    $depth = $img->getImageDepth();
    if ((int)$depth === 1) return true;
  } catch (Exception $e) {
  }
  return false;
}

function convert_png_to_1bit(string $path): array {
  if (!extension_loaded('imagick')) return ['ok' => false, 'error' => 'imagick_not_loaded'];
  if (!is_file($path)) return ['ok' => false, 'error' => 'file_missing'];

  try {
    $img = new Imagick($path);

    if (imagick_is_bilevel($img)) {
      $img->clear();
      $img->destroy();
      return ['ok' => true, 'skipped' => true];
    }

    $img->setImageColorspace(Imagick::COLORSPACE_GRAY);

    $img->quantizeImage(
      2,
      Imagick::COLORSPACE_GRAY,
      0,
      false,
      false
    );

    $quantum = Imagick::getQuantum();
    $img->thresholdImage(0.5 * $quantum);

    $img->setImageType(Imagick::IMGTYPE_BILEVEL);
    $img->setImageFormat('png');

    $img->writeImage($path);
    $img->clear();
    $img->destroy();

    return ['ok' => true, 'skipped' => false];
  } catch (Exception $e) {
    return ['ok' => false, 'error' => 'imagick_exception', 'detail' => $e->getMessage()];
  }
}

function category_json_path(string $categories_dir, string $category_name): string {
  return $categories_dir . DIRECTORY_SEPARATOR . $category_name . '.json';
}

function read_category_json_locked($fp): array {
  rewind($fp);
  $raw = stream_get_contents($fp);
  $data = json_decode($raw ?: '{}', true);
  if (!is_array($data)) $data = [];
  if (!isset($data['pages']) || !is_array($data['pages'])) $data['pages'] = [];
  return $data;
}

function write_category_json_locked($fp, array $data): void {
  ftruncate($fp, 0);
  rewind($fp);
  fwrite($fp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
  fflush($fp);
}

function prepend_unique_pages(string $path, array $pages_to_add): array {
  if (!is_file($path)) return ['ok' => false, 'error' => 'missing_category_json', 'path' => $path];

  $fp = fopen($path, 'c+');
  if (!$fp) return ['ok' => false, 'error' => 'open_failed', 'path' => $path];

  if (!flock($fp, LOCK_EX)) { fclose($fp); return ['ok' => false, 'error' => 'lock_failed', 'path' => $path]; }

  $data = read_category_json_locked($fp);

  $existing_ids = [];
  foreach ($data['pages'] as $p) {
    if (is_array($p) && isset($p['id'])) $existing_ids[(string)$p['id']] = true;
  }

  $clean_add = [];
  foreach ($pages_to_add as $p) {
    $id = (string)($p['id'] ?? '');
    if ($id === '') continue;
    if (isset($existing_ids[$id])) continue;
    $existing_ids[$id] = true;

    $clean_add[] = [
      'id' => $id,
      'title' => (string)($p['title'] ?? ''),
      'description' => (string)($p['description'] ?? ''),
    ];
  }

  $data['pages'] = array_values(array_merge($clean_add, $data['pages']));

  write_category_json_locked($fp, $data);

  flock($fp, LOCK_UN);
  fclose($fp);

  return ['ok' => true, 'added' => count($clean_add)];
}

$category = qs('c', '');
if ($category === '') json_out(['ok' => false, 'error' => 'missing_c_param'], 400);

$count = qi('n', 1);
if ($count < 1) $count = 1;
if ($count > 20) $count = 20;

$do_img = qb('img', false);
$dry = qb('dry', false);

$aspect_ratio = qs('ar', '2:3');
$model = qs('model', 'gemini-2.5-flash-image');

$skip_existing_img = qb('skip_img_existing', true);
$convert_1bit = qb('onebit', true);
$skip_existing_1bit = qb('skip_onebit_existing', true);

$api_key = qs('key', '');
if ($api_key === '') $api_key = (string)getenv('GEMINI_API_KEY');

$cat_dir = $CATEGORIES_DIR . DIRECTORY_SEPARATOR . $category;
if (!is_dir($cat_dir)) json_out(['ok' => false, 'error' => 'missing_category_dir', 'category' => $category], 404);

$characters = load_lines($cat_dir . DIRECTORY_SEPARATOR . 'characters.txt');
$actions = load_lines($cat_dir . DIRECTORY_SEPARATOR . 'actions.txt');
$environments = load_lines($cat_dir . DIRECTORY_SEPARATOR . 'environments.txt');

$style = load_style($CATEGORIES_DIR . DIRECTORY_SEPARATOR . 'style.txt');

$pools = [
  'intro' => load_lines($APP_DIR . DIRECTORY_SEPARATOR . 'intro_pool.txt'),
  'usage' => load_lines($APP_DIR . DIRECTORY_SEPARATOR . 'usage_pool.txt'),
  'ease' => load_lines($APP_DIR . DIRECTORY_SEPARATOR . 'ease_pool.txt'),
  'benefit' => load_lines($APP_DIR . DIRECTORY_SEPARATOR . 'benefit_pool.txt'),
];

$missing = [];
if (empty($characters)) $missing[] = 'characters';
if (empty($actions)) $missing[] = 'actions';
if (empty($environments)) $missing[] = 'environments';
foreach (['intro','usage','ease','benefit'] as $k) if (empty($pools[$k])) $missing[] = 'pool_' . $k;
if ($style === '') $missing[] = 'style';

if (!empty($missing)) json_out(['ok' => false, 'error' => 'missing_inputs', 'missing' => $missing], 500);

$items = [];
$errors = [];

for ($i = 0; $i < $count; $i++) {
  $parts = [
    'character' => $characters[array_rand($characters)],
    'action' => $actions[array_rand($actions)],
    'environment' => $environments[array_rand($environments)],
  ];

  $id = build_id($parts);
  $title = build_title($parts);
  $description = build_description($parts, $pools);
  $prompt = build_prompt($parts, $style);

  $items[] = [
    'id' => $id,
    'title' => $title,
    'description' => $description,
  ];

  if ($do_img) {
    if ($api_key === '') {
      $errors[] = ['id' => $id, 'error' => 'missing_api_key'];
      continue;
    }

    $img_name = sanitize_filename($id) . '.png';
    $out_path = $CATEGORIES_DIR . DIRECTORY_SEPARATOR . $category . DIRECTORY_SEPARATOR . $img_name;

    if ($skip_existing_img && is_file($out_path)) {
      if ($convert_1bit && !$dry) {
        if (!$skip_existing_1bit) {
          $c = convert_png_to_1bit($out_path);
          if (!($c['ok'] ?? false)) $errors[] = ['id' => $id, 'error' => ['onebit' => $c]];
        } else {
          if (extension_loaded('imagick')) {
            try {
              $tmp = new Imagick($out_path);
              $is1 = imagick_is_bilevel($tmp);
              $tmp->clear();
              $tmp->destroy();
              if (!$is1) {
                $c = convert_png_to_1bit($out_path);
                if (!($c['ok'] ?? false)) $errors[] = ['id' => $id, 'error' => ['onebit' => $c]];
              }
            } catch (Exception $e) {
              $c = convert_png_to_1bit($out_path);
              if (!($c['ok'] ?? false)) $errors[] = ['id' => $id, 'error' => ['onebit' => $c]];
            }
          }
        }
      }
      continue;
    }

    $img_res = gemini_generate_image($api_key, $prompt, $out_path, $aspect_ratio, $model);
    if (!($img_res['ok'] ?? false)) {
      $errors[] = ['id' => $id, 'error' => $img_res];
      continue;
    }

    if ($convert_1bit && !$dry) {
      $c = convert_png_to_1bit($out_path);
      if (!($c['ok'] ?? false)) $errors[] = ['id' => $id, 'error' => ['onebit' => $c]];
    }
  }
}

$write_result = null;
if (!$dry) {
  $json_path = category_json_path($CATEGORIES_DIR, $category);
  $write_result = prepend_unique_pages($json_path, $items);
  if (!($write_result['ok'] ?? false)) {
    json_out(['ok' => false, 'error' => 'write_failed', 'detail' => $write_result], 500);
  }
}

json_out([
  'ok' => true,
  'category' => $category,
  'count_requested' => $count,
  'count_generated' => count($items),
  'dry' => $dry,
  'write_result' => $write_result,
  'items' => $items,
  'errors' => $errors,
  'onebit_enabled' => $convert_1bit,
  'imagick_loaded' => extension_loaded('imagick')
]);