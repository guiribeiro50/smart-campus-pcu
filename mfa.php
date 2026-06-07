<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/csrf.php';
require_once __DIR__ . '/helpers/Mailer.php';

if (!isset($_SESSION['mfa_utilizador_id'])) {
    header('Location: login.php');
    exit;
}

$erro = '';
$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();

    $uid      = $_SESSION['mfa_utilizador_id'];

    if (($_POST['acao'] ?? '') === 'reenviar_codigo') {
        $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $pdo->prepare(
            "INSERT INTO mfa_codigo (utilizador_id, codigo, expira_em)
             VALUES (?,?,DATE_ADD(NOW(),INTERVAL 10 MINUTE))"
        )->execute([$uid, $codigo]);

        try {
            Mailer::enviarMFA(
                $_SESSION['mfa_email'],
                $_SESSION['mfa_nome'] ?? 'Utilizador',
                $codigo
            );
            $_SESSION['mfa_email_enviado'] = true;
            unset($_SESSION['mfa_email_erro']);
            $mensagem = 'Novo código enviado para o email.';
        } catch (Throwable $mailError) {
            error_log('Falha ao reenviar email MFA: ' . $mailError->getMessage());
            $_SESSION['mfa_email_enviado'] = false;
            $_SESSION['mfa_email_erro'] = 'Não foi possível enviar o email. Tente novamente.';
            $erro = 'Não foi possível enviar o email. Tente novamente.';
        }
    } else {
        $codigo = trim($_POST['codigo'] ?? '');

        $stmt = $pdo->prepare(
            "SELECT * FROM mfa_codigo
             WHERE utilizador_id = ? AND codigo = ?
             AND expira_em > NOW() AND usado = 0
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([$uid, $codigo]);
        $mfa = $stmt->fetch();

        if ($mfa) {
            $pdo->prepare(
                "UPDATE mfa_codigo SET usado = 1 WHERE id = ?"
            )->execute([$mfa['id']]);

            $u = $pdo->prepare(
                "SELECT * FROM utilizador WHERE id = ?"
            );
            $u->execute([$uid]);
            $utilizador = $u->fetch();

            iniciarSessaoAutenticada($utilizador);

            unset(
                $_SESSION['mfa_utilizador_id'],
                $_SESSION['mfa_nome'],
                $_SESSION['mfa_email'],
                $_SESSION['mfa_login'],
                $_SESSION['mfa_email_enviado'],
                $_SESSION['mfa_email_erro']
            );

            try {
                $token = bin2hex(random_bytes(32));
                $pdo->prepare(
                    "INSERT INTO mfa_dispositivo (utilizador_id, token, expira_em)
                     VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))"
                )->execute([$utilizador['id'], $token]);
                definirCookieMfa('mfa_token_' . (int) $utilizador['id'], $token, time() + (30 * 24 * 3600));
                definirCookieMfa('mfa_token', '', time() - 3600);
            } catch (PDOException $devError) {
                error_log('Falha ao registar dispositivo MFA: ' . $devError->getMessage());
            }

            header('Location: dashboard.php');
            exit;
        } else {
            $erro = 'Código inválido ou expirado.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Verificação MFA — PCU</title>
  <link rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:"Inter",sans-serif;min-height:100vh;
         background:#0f2744;display:flex;
         align-items:center;justify-content:center}
    .card{background:#1a2e4a;border:1px solid rgba(255,255,255,0.07);
          border-radius:12px;padding:40px;width:100%;max-width:400px}
    .logo{text-align:center;margin-bottom:28px}
    .logo-icon{width:52px;height:52px;background:#243d5e;
               border:1px solid #2e5080;border-radius:10px;
               display:inline-grid;place-items:center;margin-bottom:14px}
    .logo-icon svg{width:26px;height:26px;stroke:#a0b8d8}
    .logo h1{font-size:18px;font-weight:700;color:#e8f0f8}
    .logo p{font-size:13px;color:#6a8aaa;margin-top:4px}
    .info{background:rgba(37,99,168,0.15);
          border:1px solid rgba(37,99,168,0.4);
          border-radius:7px;padding:10px 14px;
          color:#80b8e8;font-size:13px;margin-bottom:20px;
          text-align:center}
    .error{background:rgba(192,57,43,0.15);
           border:1px solid rgba(192,57,43,0.4);
           border-radius:7px;padding:10px 14px;
           color:#e88080;font-size:13px;margin-bottom:16px;
           text-align:center}
    .success{background:rgba(22,163,74,0.15);
             border:1px solid rgba(22,163,74,0.4);
             border-radius:7px;padding:10px 14px;
             color:#8ee8b0;font-size:13px;margin-bottom:16px;
             text-align:center}
    .debug-code{font-size:18px;font-weight:700;letter-spacing:4px;color:#fff}
    .mfa-boxes{display:flex;gap:10px;justify-content:center;margin:20px 0}
    .mfa-box{width:46px;height:54px;background:#0f2744;
             border:1px solid #2e5080;border-radius:8px;
             text-align:center;font-size:22px;font-weight:700;
             color:#e8f0f8;font-family:"Inter",sans-serif;
             outline:none;transition:border-color .15s}
    .mfa-box:focus{border-color:#4a90d9}
    .btn{width:100%;padding:11px;background:#2563a8;border:none;
         border-radius:7px;color:#fff;font-size:14px;font-weight:600;
         font-family:"Inter",sans-serif;cursor:pointer;
         transition:background .15s}
    .btn:hover{background:#1d4f8a}
    .btn.secondary{background:#243d5e;margin-top:10px}
    .btn.secondary:hover{background:#2e5080}
    .timer{text-align:center;font-size:13px;color:#6a8aaa;margin:12px 0}
    .link{display:block;text-align:center;margin-top:14px;
          font-size:13px;color:#6a8aaa;text-decoration:none;
          transition:color .15s}
    .link:hover{color:#a0b8d8}
  </style>
</head>
<body>
<div class="card">
  <div class="logo">
    <div class="logo-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke-width="1.6">
        <rect x="3" y="11" width="18" height="11" rx="2"/>
        <path d="M7 11V7a5 5 0 0110 0v4"/>
      </svg>
    </div>
    <h1>Verificação MFA</h1>
    <p>Insira o código de 6 dígitos enviado para o seu email</p>
  </div>

  <div class="info">
    Código enviado para 
    <strong><?= htmlspecialchars($_SESSION['mfa_email']) ?></strong>
  </div>

  <?php if ($mensagem): ?>
    <div class="success"><?= htmlspecialchars($mensagem) ?></div>
  <?php elseif (($_SESSION['mfa_email_enviado'] ?? null) === true): ?>
    <div class="success">Código enviado com sucesso para o email.</div>
  <?php endif; ?>

  <?php if ($erro): ?>
    <div class="error"><?= htmlspecialchars($erro) ?></div>
  <?php endif; ?>

  <?php if (($_SESSION['mfa_email_enviado'] ?? null) === false): ?>
    <div class="error">
      <?= htmlspecialchars($_SESSION['mfa_email_erro'] ?? 'Não foi possível enviar o email. Utilize "Reenviar código".') ?>
    </div>
  <?php endif; ?>

  <form method="POST" id="mfa-form">
    <?= csrfField() ?>
    <input type="hidden" name="codigo" id="codigo-hidden">
    <div class="mfa-boxes">
      <input class="mfa-box" type="text" maxlength="1" 
             inputmode="numeric" pattern="[0-9]">
      <input class="mfa-box" type="text" maxlength="1" 
             inputmode="numeric" pattern="[0-9]">
      <input class="mfa-box" type="text" maxlength="1" 
             inputmode="numeric" pattern="[0-9]">
      <input class="mfa-box" type="text" maxlength="1" 
             inputmode="numeric" pattern="[0-9]">
      <input class="mfa-box" type="text" maxlength="1" 
             inputmode="numeric" pattern="[0-9]">
      <input class="mfa-box" type="text" maxlength="1" 
             inputmode="numeric" pattern="[0-9]">
    </div>
    <div class="timer" id="timer">Reenviar código em 10:00</div>
    <button type="submit" class="btn">Verificar Código</button>
  </form>

  <form method="POST">
    <?= csrfField() ?>
    <input type="hidden" name="acao" value="reenviar_codigo">
    <button type="submit" class="btn secondary">Reenviar código</button>
  </form>

  <a href="login.php" class="link">Cancelar e voltar ao login</a>
</div>

<script>
// Auto-avança entre caixas
const boxes = document.querySelectorAll('.mfa-box');
boxes.forEach((box, i) => {
  box.addEventListener('input', () => {
    if (box.value && i < boxes.length - 1) boxes[i+1].focus();
  });
  box.addEventListener('keydown', e => {
    if (e.key === 'Backspace' && !box.value && i > 0) 
      boxes[i-1].focus();
  });
});

// Junta os 6 dígitos no hidden input antes de submeter
document.getElementById('mfa-form').addEventListener('submit', e => {
  const codigo = [...boxes].map(b => b.value).join('');
  document.getElementById('codigo-hidden').value = codigo;
  if (codigo.length < 6) {
    e.preventDefault();
    alert('Insere os 6 dígitos do código.');
  }
});

// Timer de 10 minutos
let seconds = 600;
const timerEl = document.getElementById('timer');
const interval = setInterval(() => {
  seconds--;
  const m = String(Math.floor(seconds/60)).padStart(2,'0');
  const s = String(seconds%60).padStart(2,'0');
  timerEl.textContent = `Reenviar código em ${m}:${s}`;
  if (seconds <= 0) {
    clearInterval(interval);
    timerEl.textContent = 'Código expirado. Volta ao login.';
  }
}, 1000);
</script>
</body>
</html>
