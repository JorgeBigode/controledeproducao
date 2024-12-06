<?php
error_reporting(0);
session_start();
require_once('config.php');
require_once('protect.php');

header('Content-Type: application/json');

try {
    // Verifica se o usuário está logado
    if (!isset($_SESSION['id'])) {
        throw new Exception('Você deve estar logado para realizar esta ação.');
    }

    // Verifica se recebeu os dados necessários
    if (!isset($_POST['idcliente']) || !isset($_POST['caminho'])) {
        throw new Exception('Dados incompletos.');
    }

    $idcliente = intval($_POST['idcliente']);
    $caminho = trim($_POST['caminho']);

    // Atualiza o caminho no banco de dados
    $stmt = $conexao->prepare("UPDATE clientes SET link_pastas = ? WHERE idcliente = ?");
    if (!$stmt) {
        throw new Exception('Erro ao preparar a consulta: ' . $conexao->error);
    }

    $stmt->bind_param("si", $caminho, $idcliente);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Erro ao salvar o caminho no banco de dados: ' . $stmt->error);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
