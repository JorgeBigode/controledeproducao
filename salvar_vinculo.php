<?php
include_once('config.php');

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente = isset($_POST['cliente']) ? intval($_POST['cliente']) : null;
    $modelo = isset($_POST['modelo']) ? $_POST['modelo'] : null;

    // Log received data
    error_log("Received data - Cliente: " . print_r($cliente, true) . ", Modelo: " . print_r($modelo, true));

    if (!$cliente || !$modelo) {
        echo json_encode(['success' => false, 'error' => 'Dados incompletos']);
        exit;
    }

    // First, check if the cliente exists
    $stmt = $conexao->prepare("SELECT idcliente FROM cliente WHERE idcliente = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Erro ao preparar verificação do cliente: ' . $conexao->error]);
        exit;
    }
    
    $stmt->bind_param("i", $cliente);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Cliente não encontrado']);
        exit;
    }
    $stmt->close();

    // Insert into pedidos_tr table
    $stmt = $conexao->prepare("INSERT INTO pedidos_tr (idtr_pedido, modelo) VALUES (?, ?)");
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Erro ao preparar inserção: ' . $conexao->error]);
        exit;
    }

    $stmt->bind_param("is", $cliente, $modelo);
    
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'error' => 'Erro ao inserir vínculo: ' . $stmt->error]);
        exit;
    }
    
    $stmt->close();
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Método inválido']);
}
?>
