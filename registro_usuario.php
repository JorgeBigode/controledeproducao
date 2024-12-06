<?php
include('config.php');
include('protect.php');
verificarAcesso();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash da senha
    $role = $_POST['role'];

    // Verifica se o nome de usuário já existe
    $sql = "SELECT * FROM usuarios WHERE username=?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "Nome de usuário já existe. Escolha outro.";
    } else {
        // Insere o novo usuário
        $sql = "INSERT INTO usuarios (username, password, role) VALUES (?, ?, ?)";
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("sss", $username, $password, $role);
        if ($stmt->execute()) {
            echo "Usuário registrado com sucesso!";
        } else {
            echo "Erro ao registrar usuário.";
        }
    }
}
?>

<link rel="icon" href="img/icon-SILO.ico" type="image/x-icon">
<form method="POST">
    <label for="username">Usuário:</label>
    <input type="text" name="username" required>
    
    <label for="password">Senha:</label>
    <input type="password" name="password" required>
    
    <label for="role">Papel:</label>
    <select name="role">
        <option value="viewer">Viewer</option>
        <option value="editor">Editor</option>
        <option value="admin">Admin</option>
    </select>
    
    <button type="submit">Registrar</button>
</form>
<button onclick="window.history.back();">Voltar</button>