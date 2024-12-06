<?php
include_once('config.php');
header('Content-Type: application/json');

// Consulta para buscar os dados mais recentes
$sql_lista_pedidos = "
    SELECT c.idcliente, 
           c.pedido, 
           c.cliente,
           c.data_pedido,
           c.prazo_entrega,
           c.status_producao,
           c.observacao,
           GROUP_CONCAT(DISTINCT m.modelo SEPARATOR ', ') as modelos
    FROM cliente c
    LEFT JOIN cliente_produto cp ON c.idcliente = cp.cliente_id
    LEFT JOIN modelo m ON cp.modelo_id = m.id
    GROUP BY c.idcliente
    ORDER BY c.idcliente DESC";

$result_lista_pedidos = $conexao->query($sql_lista_pedidos);

if (!$result_lista_pedidos) {
    echo json_encode(['error' => 'Erro ao buscar pedidos: ' . $conexao->error]);
    exit;
}

$pedidos = [];
while ($row = $result_lista_pedidos->fetch_assoc()) {
    $pedidos[] = [
        'idcliente' => $row['idcliente'],
        'pedido' => $row['pedido'],
        'cliente' => $row['cliente'],
        'data_pedido' => $row['data_pedido'],
        'prazo_entrega' => $row['prazo_entrega'],
        'status_producao' => $row['status_producao'],
        'observacao' => $row['observacao'],
        'modelos' => $row['modelos']
    ];
}

echo json_encode(['pedidos' => $pedidos]);
