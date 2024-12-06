<?php
include_once('config.php');

header('Content-Type: application/json');

function responderErro($mensagem) {
    echo json_encode(['erro' => $mensagem]);
    exit;
}

if (!isset($_GET['idequipamento']) || empty($_GET['idequipamento'])) {
    responderErro('ID do equipamento não fornecido');
}

$idequipamento = intval($_GET['idequipamento']);

if ($idequipamento <= 0) {
    responderErro('ID do equipamento inválido');
}

$query = "SELECT m.idmateriais, m.tipo_material, me.quantidade, me.quantidade_total, me.id as id_vinculo 
          FROM materiais_equipamento me 
          JOIN materiais m ON me.idmateriais = m.idmateriais 
          WHERE me.idequipamento = ?";

$stmt = $conexao->prepare($query);
if (!$stmt) {
    responderErro('Erro ao preparar consulta: ' . $conexao->error);
}

$stmt->bind_param("i", $idequipamento);
if (!$stmt->execute()) {
    responderErro('Erro ao executar consulta: ' . $stmt->error);
}

$result = $stmt->get_result();
if (!$result) {
    responderErro('Erro ao obter resultados: ' . $stmt->error);
}

$materiais = array();
while($row = $result->fetch_assoc()) {
    $materiais[] = $row;
}

echo json_encode($materiais);
