<?php
/**
 * ECO // BANCO POPULAR - DEDICATED IP BLACKLIST MANAGER
 */
require_once __DIR__ . '/../config.php';
checkAdminSession();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PANEL ADMIN // LISTA NEGRA DE IPS</title>
    <link rel="icon" type="image/png" href="../assets/logos/principalmd.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        :root {
            --bg-base: #06090e;
            --bg-card: rgba(18, 26, 44, 0.65);
            --border-subtle: rgba(255, 255, 255, 0.08);
            --teal-brand: #105163;
            --cyan-accent: #00d4ff;
            --cyan-glow: rgba(0, 212, 255, 0.25);
            --emerald: #10b981;
            --rose: #f43f5e;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --text-tertiary: #64748b;
            --font-sans: 'Plus Jakarta Sans', system-ui, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: var(--font-sans);
        }

        body {
            background-color: var(--bg-base);
            background-image: 
                radial-gradient(circle at 10% 15%, rgba(16, 81, 99, 0.2) 0%, transparent 40%),
                radial-gradient(circle at 90% 85%, rgba(244, 63, 94, 0.1) 0%, transparent 40%);
            color: var(--text-primary);
            min-height: 100vh;
            padding: 40px 24px;
        }

        .ambient-top-line {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 2.5px;
            background: linear-gradient(90deg, #105163, #00d4ff, #10b981, #f43f5e, #105163);
            background-size: 300% 100%;
            z-index: 9999;
        }

        .container {
            max-width: 1060px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-subtle);
        }

        .title {
            font-size: 20px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: -0.01em;
        }

        .title i {
            color: var(--rose);
            font-size: 24px;
        }

        .btn-back {
            background: var(--bg-card);
            color: var(--text-primary);
            padding: 9px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-back:hover {
            border-color: var(--cyan-accent);
            box-shadow: 0 0 12px var(--cyan-glow);
            transform: translateY(-1px);
        }

        .card {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-subtle);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.5);
        }

        .manual-ban-bar {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-subtle);
            display: flex;
            gap: 12px;
            background: rgba(255, 255, 255, 0.015);
        }

        .manual-ban-input {
            flex: 1;
            height: 40px;
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid var(--border-subtle);
            border-radius: 8px;
            padding: 0 14px;
            color: #fff;
            font-family: var(--font-mono);
            font-size: 13px;
            outline: none;
            transition: border-color 0.2s;
        }

        .manual-ban-input:focus {
            border-color: var(--rose);
        }

        .btn-ban {
            background: rgba(244, 63, 94, 0.15);
            color: #fda4af;
            border: 1px solid rgba(244, 63, 94, 0.3);
            padding: 0 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn-ban:hover {
            background: rgba(244, 63, 94, 0.3);
            color: #ffffff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 14px 20px;
            background: rgba(0, 0, 0, 0.25);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-tertiary);
            font-weight: 700;
            border-bottom: 1px solid var(--border-subtle);
        }

        td {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            font-size: 13px;
            vertical-align: middle;
        }

        tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .ip-mono {
            font-family: var(--font-mono);
            color: #fda4af;
            font-weight: 700;
        }

        .reason-badge {
            background: rgba(244, 63, 94, 0.1);
            color: #fda4af;
            border: 1px solid rgba(244, 63, 94, 0.2);
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
        }

        .ts-mono {
            font-family: var(--font-mono);
            color: var(--text-tertiary);
            font-size: 11.5px;
        }

        .btn-unblock {
            background: rgba(16, 185, 129, 0.12);
            color: var(--emerald);
            border: 1px solid rgba(16, 185, 129, 0.3);
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-unblock:hover {
            background: rgba(16, 185, 129, 0.25);
            color: #ffffff;
        }

        .empty-state {
            padding: 50px;
            text-align: center;
            color: var(--text-tertiary);
        }
    </style>
</head>
<body>
    <div class="ambient-top-line"></div>
    <div class="container">
        <header class="header">
            <div class="title">
                <i class="ri-shield-cross-line"></i>
                <span>PANEL ADMIN // LISTA NEGRA DE IPS</span>
            </div>
            <a href="index.php" class="btn-back">
                <i class="ri-arrow-left-line"></i> Volver a Telemetría
            </a>
        </header>

        <div class="card">
            <!-- Manual Ban Bar -->
            <div class="manual-ban-bar">
                <input type="text" id="manual-ip" class="manual-ban-input" placeholder="Dirección IP a bloquear manualmente (ej: 181.54.21.9)">
                <input type="text" id="manual-reason" class="manual-ban-input" style="flex: 0.8;" placeholder="Motivo (opcional)">
                <button class="btn-ban" onclick="submitManualBan()">
                    <i class="ri-forbid-line"></i> Bloquear IP
                </button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Dirección IP</th>
                        <th>Razón de Bloqueo</th>
                        <th>Fecha de Registro</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="blacklist-table">
                    <tr>
                        <td colspan="4" class="empty-state">
                            <i class="ri-loader-4-line ri-spin" style="font-size: 24px; display: block; margin-bottom: 8px;"></i>
                            Cargando registros...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        async function loadBlacklist() {
            try {
                const fd = new FormData();
                fd.append('action', 'get_blacklist');
                const res = await fetch('actions.php', { method: 'POST', body: fd });
                const data = await res.json();
                const tbody = document.getElementById('blacklist-table');

                if (data.status === 'success' && data.blacklist && data.blacklist.length > 0) {
                    tbody.innerHTML = data.blacklist.map(item => `
                        <tr>
                            <td class="ip-mono">${escapeHtml(item.ip)}</td>
                            <td><span class="reason-badge">${escapeHtml(item.reason)}</span></td>
                            <td class="ts-mono">${escapeHtml(item.created_at)}</td>
                            <td style="text-align: right;">
                                <button onclick="unblockIp('${escapeHtml(item.ip)}')" class="btn-unblock">
                                    <i class="ri-lock-unlock-line"></i> Desbloquear
                                </button>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="4" class="empty-state">No hay direcciones IP bloqueadas actualmente.</td></tr>';
                }
            } catch (err) {
                console.error(err);
            }
        }

        async function unblockIp(ip) {
            if (!confirm(`¿Estás seguro de desbloquear la IP ${ip}?`)) return;

            const fd = new FormData();
            fd.append('action', 'unblock');
            fd.append('ip', ip);
            const res = await fetch('actions.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.status === 'success') {
                loadBlacklist();
            }
        }

        async function submitManualBan() {
            const ip = (document.getElementById('manual-ip').value || '').trim();
            const reason = (document.getElementById('manual-reason').value || '').trim() || 'Bloqueo manual';
            if (!ip) return;

            const fd = new FormData();
            fd.append('action', 'blacklist');
            fd.append('ip', ip);
            fd.append('reason', reason);
            const res = await fetch('actions.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.status === 'success') {
                document.getElementById('manual-ip').value = '';
                document.getElementById('manual-reason').value = '';
                loadBlacklist();
            } else {
                alert('Dirección IP no válida');
            }
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        loadBlacklist();
    </script>
</body>
</html>
