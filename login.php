<?php
session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/csrf.php';
require_once __DIR__ . '/helpers/Mailer.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();

    $login    = strtolower(trim($_POST['login'] ?? ''));
    $password = trim($_POST['password'] ?? '');
    definirCookieMfa('mfa_token', '', time() - 3600);

    if (empty($login) || empty($password)) {
        $erro = 'Preenche todos os campos.';
    } else {
        try {
            
            $stmt = $pdo->prepare("SELECT * FROM utilizador WHERE login = ? AND ativo = 1");
            $stmt->execute([$login]);
            $utilizador = $stmt->fetch();

            
            
            if ($utilizador && password_verify($password, $utilizador['password'])) {
                $emailMfa = trim((string) ($utilizador['email'] ?? ''));

                if (!filter_var($emailMfa, FILTER_VALIDATE_EMAIL)) {
                    $erro = 'O utilizador não tem um email válido registado para receber o código MFA.';
                    $utilizador = null;
                }

                if ($utilizador && $utilizador['perfil'] === 'administrador') {

                    
                    $mfaCookieName = 'mfa_token_' . (int) $utilizador['id'];
                    $mfaToken = $_COOKIE[$mfaCookieName] ?? '';
                    if ($mfaToken) {
                        try {
                            $pdo->prepare(
                                "DELETE FROM mfa_dispositivo
                                 WHERE utilizador_id = ? AND expira_em <= NOW()"
                            )->execute([$utilizador['id']]);
                            $stmtDev = $pdo->prepare(
                                "SELECT id FROM mfa_dispositivo
                                 WHERE utilizador_id = ? AND token = ? AND expira_em > NOW()"
                            );
                            $stmtDev->execute([$utilizador['id'], $mfaToken]);
                            if ($stmtDev->fetch()) {
                                iniciarSessaoAutenticada($utilizador);
                                header('Location: dashboard.php');
                                exit;
                            }
                        } catch (PDOException $devError) {
                            
                        }
                    }

                    $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    
                    
                    try {
                        $pdo->prepare(
                            "INSERT INTO mfa_codigo (utilizador_id, codigo, expira_em)
                             VALUES (?,?,DATE_ADD(NOW(),INTERVAL 10 MINUTE))"
                        )->execute([$utilizador['id'], $codigo]);
                    } catch (PDOException $mfaError) {
                        error_log('Falha ao registar código MFA: ' . $mfaError->getMessage());
                        $erro = 'Não foi possível iniciar a verificação MFA. Tente novamente.';
                        $utilizador = null;
                    }

                    if ($utilizador) {
                        $_SESSION['mfa_utilizador_id'] = $utilizador['id'];
                        $_SESSION['mfa_nome']          = $utilizador['nome'];
                        $_SESSION['mfa_email']         = $emailMfa;
                        $_SESSION['mfa_login']         = $utilizador['login'];
                        unset($_SESSION['mfa_email_enviado'], $_SESSION['mfa_email_erro']);

                        try {
                            Mailer::enviarMFA($emailMfa, $utilizador['nome'], $codigo);
                            $_SESSION['mfa_email_enviado'] = true;
                        } catch (Throwable $mailError) {
                            error_log('Falha ao enviar email MFA: ' . $mailError->getMessage());
                            $_SESSION['mfa_email_enviado'] = false;
                            $_SESSION['mfa_email_erro'] = 'Não foi possível enviar o email. Utilize "Reenviar código".';
                        }

                        header('Location: mfa.php');
                        exit;
                    }
                } elseif ($utilizador) {
                    iniciarSessaoAutenticada($utilizador);
                    header('Location: dashboard.php');
                    exit;
                }
            } else {
                $erro = 'Credenciais inválidas ou conta desativada.';
            }
        } catch (PDOException $e) {
            error_log('Erro no login: ' . $e->getMessage());
            $erro = 'Ocorreu um erro. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login — PCU Campus</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:"Inter",sans-serif;min-height:100vh;background:#0f2744;display:flex;align-items:center;justify-content:center}
    .card{background:#1a2e4a;border:1px solid rgba(255,255,255,0.07);border-radius:12px;padding:40px;width:100%;max-width:400px}
    .logo{text-align:center;margin-bottom:32px}
    .logo-icon{width:52px;height:52px;background:#243d5e;border:1px solid #2e5080;border-radius:10px;display:inline-grid;place-items:center;margin-bottom:14px}
    .logo-icon svg{width:26px;height:26px;stroke:#a0b8d8}
    .logo h1{font-size:19px;font-weight:700;color:#e8f0f8}
    .logo p{font-size:13px;color:#6a8aaa;margin-top:4px}
    .error{background:rgba(192,57,43,0.15);border:1px solid rgba(192,57,43,0.4);border-radius:7px;padding:10px 14px;color:#e88080;font-size:13px;margin-bottom:16px;word-break:break-word}
    .form-group{margin-bottom:16px}
    label{display:block;font-size:13px;font-weight:500;color:#a0b8d8;margin-bottom:6px}
    input{width:100%;padding:10px 14px;background:#0f2744;border:1px solid #2e5080;border-radius:7px;color:#e8f0f8;font-size:14px;outline:none}
    .btn{width:100%;padding:11px;background:#2563a8;border:none;border-radius:7px;color:#fff;font-size:14px;font-weight:600;cursor:pointer;margin-top:8px}
    .link{display:block;text-align:center;margin-top:16px;font-size:13px;color:#6a8aaa;text-decoration:none}
  </style>
</head>
<body>
<div class="card">
  <div class="logo">
    <div class="logo-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke-width="1.6">
        <rect x="2" y="7" width="20" height="14" rx="1.5"/>
        <path d="M7 21V11h10v10"/><path d="M12 3l9 4H3l9-4z"/><path d="M9 21v-4h6v4"/>
      </svg>
    </div>
    <h1>PCU Campus</h1>

  </div>

  <?php if ($erro): ?>
    <div class="error"><?= htmlspecialchars($erro) ?></div>
  <?php endif; ?>

  <form method="POST">
    <?= csrfField() ?>
    <div class="form-group">
      <label>Email Institucional</label>
      <input type="email" name="login" placeholder="utilizador@upt.pt" value="<?= htmlspecialchars($_POST['login'] ?? '') ?>" required>
    </div>
    <div class="form-group">
      <label>Palavra-passe</label>
      <input type="password" name="password" placeholder="••••••••" required>
    </div>
    <button type="submit" class="btn">Entrar</button>
  </form>
  <a href="pagina-inicial.php" class="link">← Voltar à página inicial</a>
</div>
</body>
</html>
