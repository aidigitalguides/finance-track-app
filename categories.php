<?php
// api/categories.php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once '../config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

Auth::startSession();
$userId = Auth::requireLogin();

$action = $_GET['action'] ?? (json_decode(file_get_contents('php://input'), true)['action'] ?? '');

switch ($action) {
    case 'list':
        $type = $_GET['type'] ?? '';
        $sql  = 'SELECT id, name, icon, color_hex, type FROM categories
                 WHERE (user_id = ? OR user_id IS NULL) AND is_active = 1';
        $params = [$userId];
        if ($type) { $sql .= ' AND (type = ? OR type = "both")'; $params[] = $type; }
        $sql .= ' ORDER BY is_system DESC, sort_order ASC, name ASC';
        echo json_encode(['success' => true, 'data' => Database::fetchAll($sql, $params)]);
        break;

    case 'add':
        $body  = json_decode(file_get_contents('php://input'), true) ?? [];
        $name  = trim(strip_tags($body['name'] ?? ''));
        $icon  = mb_substr($body['icon'] ?? '📁', 0, 10);
        $color = preg_match('/^#[0-9a-fA-F]{6}$/', $body['color_hex'] ?? '') ? $body['color_hex'] : '#888780';
        $type  = in_array($body['type'] ?? '', ['expense','income','both']) ? $body['type'] : 'expense';
        if (!$name) { echo json_encode(['success'=>false,'message'=>'Name required']); break; }
        $id = Database::insert(
            'INSERT INTO categories (user_id, name, icon, color_hex, type, is_system) VALUES (?,?,?,?,?,0)',
            [$userId, $name, $icon, $color, $type]
        );
        echo json_encode(['success' => true, 'id' => $id]);
        break;

    case 'delete':
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $id   = (int)($body['id'] ?? 0);
        // Only delete user-owned, non-system categories
        $ok = Database::execute(
            'UPDATE categories SET is_active = 0 WHERE id = ? AND user_id = ? AND is_system = 0',
            [$id, $userId]
        );
        echo json_encode(['success' => $ok > 0]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
