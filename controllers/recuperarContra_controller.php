<?php
require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . '/../models/DBAbstract.php';
require_once __DIR__ . "/assets/tpl_engine/tpl_engine.php";
require_once __DIR__ . '/../libraries/phpmailer/mail_engine.php';

$tpl = loadTPL("recuperarContra", "Recuperar contraseña");
$msg_error = "";
$msg_exito = "";

if (isset($_GET['volver'])) {
    unset($_SESSION['reset_pendiente']);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (isset($_POST['txt_codigo'])) {
        $email  = $_SESSION['reset_pendiente'] ?? '';
        $codigo = trim($_POST['txt_codigo'] ?? '');
        $db = dbConnect();

        $stmt = mysqli_prepare($db, "SELECT id, codigo_hash FROM password_resets
            WHERE email = ? AND usado = 0 AND expira_en > NOW() AND intentos < 5");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $reg = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($reg && preg_match('/^\d{6}$/', $codigo) && password_verify($codigo, $reg['codigo_hash'])) {
            $stmt = mysqli_prepare($db, "UPDATE password_resets SET usado = 1 WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $reg['id']);
            mysqli_stmt_execute($stmt);

            $_SESSION['reset_email'] = $email;
            unset($_SESSION['reset_pendiente']);
            header("Location: index.php?slug=nuevaContra");
            exit;
        }

        if ($reg) {
            $stmt = mysqli_prepare($db, "UPDATE password_resets SET intentos = intentos + 1 WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $reg['id']);
            mysqli_stmt_execute($stmt);
        }
        $msg_error = "Código incorrecto o vencido.";
    }

    else {
        $email = trim($_POST["txt_email"] ?? "");

        if ($email === "") {
            $msg_error = "Por favor, ingresa tu correo electrónico.";
        } else {
            $usuario = getUserByEmailOrUsername($email);

            if (!$usuario) {
                $msg_error = "No existe una cuenta registrada con ese correo.";
            } elseif (($usuario["estado"] ?? "activo") === "desactivado") {
                $msg_error = "Tu cuenta ha sido desactivada. No es posible recuperar la contraseña.";
            } else {
                $destino = $usuario["email"] ?? $email; // ajustá al nombre real de la columna
                $codigo  = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $hash    = password_hash($codigo, PASSWORD_DEFAULT);

                $db = dbConnect();
                $stmt = mysqli_prepare($db, "DELETE FROM password_resets WHERE email = ?");
                mysqli_stmt_bind_param($stmt, "s", $destino);
                mysqli_stmt_execute($stmt);

                $stmt = mysqli_prepare($db, "INSERT INTO password_resets (email, codigo_hash, expira_en)
                    VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE))");
                mysqli_stmt_bind_param($stmt, "ss", $destino, $hash);
                mysqli_stmt_execute($stmt);

                $asunto = "Tu código de recuperación - EventNow";
                $htmlMensaje = '
                <div style="font-family: Arial, sans-serif; color: #1A1A1A; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #E8E3DD; border-radius: 12px; background: #FAF8F5;">
                    <h2 style="color: #B25942; font-family: Georgia, serif;">Recuperá tu acceso a EventNow</h2>
                    <p>Usá este código para restablecer tu contraseña. Vence en 5 minutos.</p>
                    <div style="text-align: center; margin: 30px 0; font-size: 36px; font-weight: bold; letter-spacing: 10px; color: #B25942;">' . $codigo . '</div>
                    <p style="font-size: 0.85rem; color: #737373;">Si no solicitaste este cambio, ignorá este mensaje.</p>
                </div>';

                if (enviarCorreoGmail($destino, $asunto, $htmlMensaje)) {
                    $_SESSION['reset_pendiente'] = $destino;
                    $msg_exito = "Te enviamos un código de 6 dígitos. Vence en 5 minutos.";
                } else {
                    $msg_error = "Hubo un error al intentar enviar el correo. Inténtalo más tarde.";
                }
            }
        }
    }
}

$paso2 = isset($_SESSION['reset_pendiente']);
$tpl = setVarTPL("display_email",  $paso2 ? "none"  : "block", $tpl);
$tpl = setVarTPL("display_codigo", $paso2 ? "block" : "none",  $tpl);
$tpl = setVarTPL("msg_error", $msg_error, $tpl);
$tpl = setVarTPL("msg_exito", $msg_exito, $tpl);
printTPL($tpl);
?>