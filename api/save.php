<?php
/**
 * Al Bert Hoven — API de posts
 * Solo accesible con sesión admin válida.
 */

session_name('abh_admin');
session_start();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// ── Seguridad ────────────────────────────────────────
if (empty($_SESSION['abh_logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// CSRF
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(403);
    echo json_encode(['error' => 'Petición no válida']);
    exit;
}

$POSTS_FILE = __DIR__ . '/posts.json';

// ── Helpers ──────────────────────────────────────────
function loadPosts(string $file): array {
    if (!file_exists($file)) return [];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function savePosts(string $file, array $posts): bool {
    $fp = fopen($file, 'c+');
    if (!$fp) return false;
    if (flock($fp, LOCK_EX)) {
        ftruncate($fp, 0);
        fwrite($fp, json_encode($posts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        flock($fp, LOCK_UN);
    }
    fclose($fp);
    return true;
}

function slugify(string $text): string {
    $text = mb_strtolower(trim($text));
    $map  = ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n','ç'=>'c'];
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function sanitize(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function uniqueId(): string {
    return (string) time() . rand(100, 999);
}

// ── Router ───────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($method) {

    case 'GET':
        echo json_encode(loadPosts($POSTS_FILE));
        break;

    case 'POST':
        $posts   = loadPosts($POSTS_FILE);
        $title   = sanitize($body['title']   ?? '');
        $excerpt = sanitize($body['excerpt'] ?? '');
        $content = sanitize($body['content'] ?? '');
        $cat     = sanitize($body['category'] ?? 'General');
        $rt      = (int) ($body['readTime'] ?? 3);

        if (empty($title)) {
            http_response_code(400);
            echo json_encode(['error' => 'El título es obligatorio']);
            exit;
        }

        $date = new DateTime();
        $months = ['enero','febrero','marzo','abril','mayo','junio',
                   'julio','agosto','septiembre','octubre','noviembre','diciembre'];
        $formatted = $date->format('j') . ' ' . ucfirst($months[(int)$date->format('n') - 1]) . ' ' . $date->format('Y');

        $post = [
            'id'            => uniqueId(),
            'slug'          => slugify($title),
            'title'         => $title,
            'excerpt'       => $excerpt,
            'content'       => $content,
            'date'          => $date->format('Y-m-d'),
            'dateFormatted' => $formatted,
            'category'      => $cat,
            'readTime'      => (string) $rt,
        ];

        array_unshift($posts, $post);
        savePosts($POSTS_FILE, $posts);
        echo json_encode(['ok' => true, 'post' => $post]);
        break;

    case 'PUT':
        $id    = sanitize($body['id'] ?? '');
        $posts = loadPosts($POSTS_FILE);
        $found = false;

        foreach ($posts as &$p) {
            if ($p['id'] === $id) {
                $p['title']    = sanitize($body['title']    ?? $p['title']);
                $p['excerpt']  = sanitize($body['excerpt']  ?? $p['excerpt']);
                $p['content']  = sanitize($body['content']  ?? $p['content']);
                $p['category'] = sanitize($body['category'] ?? $p['category']);
                $p['readTime'] = (string)(int)($body['readTime'] ?? $p['readTime']);
                $p['slug']     = slugify($p['title']);
                $found = true;
                break;
            }
        }
        unset($p);

        if (!$found) {
            http_response_code(404);
            echo json_encode(['error' => 'Post no encontrado']);
            exit;
        }

        savePosts($POSTS_FILE, $posts);
        echo json_encode(['ok' => true]);
        break;

    case 'DELETE':
        $id    = sanitize($body['id'] ?? '');
        $posts = loadPosts($POSTS_FILE);
        $posts = array_values(array_filter($posts, fn($p) => $p['id'] !== $id));
        savePosts($POSTS_FILE, $posts);
        echo json_encode(['ok' => true]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
}
