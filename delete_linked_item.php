<?php
include_once('config.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['idequipamento']) || !isset($data['idmateriais'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Dados incompletos']);
    exit;
}

$idequipamento = $data['idequipamento'];
$idmateriais = $data['idmateriais'];

$query = "DELETE FROM materiais_equipamento 
          WHERE idequipamento = ? AND idmateriais = ?";

$stmt = $conexao->prepare($query);
$stmt->bind_param("ii", $idequipamento, $idmateriais);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao excluir item']);
}
?>
