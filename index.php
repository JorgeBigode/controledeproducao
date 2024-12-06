<?php
session_start();
include('config.php');

// Obter cookies, se existentes
$usernameCookie = isset($_COOKIE['username']) ? $_COOKIE['username'] : '';
$passwordCookie = isset($_COOKIE['password']) ? $_COOKIE['password'] : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verifica se os campos foram preenchidos
    $username = isset($_POST['usuario']) ? $_POST['usuario'] : '';
    $password = isset($_POST['senha']) ? $_POST['senha'] : '';

    if (empty($username)) {
        echo "<div class='error-message'>Preencha seu usuário.</div>";
    } elseif (empty($password)) {
        echo "<div class='error-message'>Preencha sua senha.</div>";
    } else {
        $sql = "SELECT * FROM usuarios WHERE username=?";
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // Verifica a senha
            if (password_verify($password, $row['password'])) {
                // Armazena as informações do usuário na sessão após o login ser bem-sucedido
                $_SESSION['id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];

                // Configura cookies, se a opção "lembrar" estiver marcada
                if (isset($_POST['lembrar'])) {
                    setcookie('username', $username, time() + (86400 * 30), "/");
                    setcookie('password', $password, time() + (86400 * 30), "/");
                } else {
                    setcookie('username', '', time() - 3600, "/");
                    setcookie('password', '', time() - 3600, "/");
                }

                // Verifica e redireciona conforme o papel do usuário
                if ($_SESSION['role'] === 'viewer') {
                    header('Location: gestao_setores.php');
                } else {
                    header('Location: inicio.php');
                }
                exit;
            } else {
                echo "<div class='error-message'>Senha inválida.</div>";
            }
        } else {
            echo "<div class='error-message'>Usuário não encontrado.</div>";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="icon" href="img/icon-SILO.ico" type="image/x-icon">
    <link rel="stylesheet" href="./css/login.css">
    <style>
    .error-message {
        color: red;
        font-weight: bold;
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        padding: 10px;
        margin: 10px 0;
        border-radius: 5px;
    }
    </style>
    <title>LOGIN</title>
</head>
<body>
    <form action="" method="POST">
        <div class="main-login">
            <div class="left-login"></div>

            <div class="right-login">
                <div class="card-login">
                    <h1>LOGIN</h1>

                    <div class="textfield">
                        <label for="usuario">Usuário</label>
                        <input type="text" id="usuario" name="usuario"
                            value="<?php echo htmlspecialchars($usernameCookie); ?>" required>
                    </div>
                    <div class="textfield">
                        <label for="senha">Senha</label>
                        <input type="password" id="senha" name="senha"
                            value="<?php echo htmlspecialchars($passwordCookie); ?>" required>
                    </div>
                    <div>
                        <input type="checkbox" name="lembrar" id="lembrar"
                            <?php echo isset($_COOKIE['username']) ? 'checked' : ''; ?>>
                        <label for="lembrar">Lembrar-me</label>
                    </div>
                    <div>
                        <button type="submit" class="btn-login">Entrar</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</body>
</html>
