<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include_once('config.php');

$data = json_decode(file_get_contents("php://input"), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

$prioridade = $data['prioridade'];
$id_vinculo = $data['id_vinculo'];
$response = ['success' => false];

$sql = "UPDATE cliente_produto SET prioridade = ? WHERE id_vinculo = ?";
$stmt = $conexao->prepare($sql);

if (!$stmt) {
    echo json_encode(['error' => 'SQL prepare failed']);
    exit;
}

$stmt->bind_param("ii", $prioridade, $id_vinculo);

if ($stmt->execute()) {
    $response['success'] = true;
} else {
    echo json_encode(['error' => 'SQL execution failed']);
    exit;
}

$stmt->close();
$conexao->close();

header('Content-Type: application/json');
echo json_encode($response);
