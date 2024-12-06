<?php
// salvar_detalhes_pedido.php

// Configurações de erro
ini_set('log_errors', 1);
ini_set('error_log', 'php-error.log'); // Substitua pelo caminho real do seu log
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Definir cabeçalho JSON
header('Content-Type: application/json');

// Incluir arquivos necessários
include_once('config.php');
include('protect.php');

// Função para enviar resposta JSON e encerrar a execução
function sendResponse($status, $message) {
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

// Obter os dados recebidos via JSON
$input = json_decode(file_get_contents('php://input'), true);

// Validar se os dados foram recebidos corretamente
if (!$input) {
    sendResponse('error', 'Dados inválidos.');
}

// Extrair e validar os campos necessários
$idtr_pedido = isset($input['idtr_pedido']) ? intval($input['idtr_pedido']) : 0;
$modelo = isset($input['modelo']) ? trim($input['modelo']) : '';

if ($idtr_pedido <= 0 || empty($modelo)) {
    sendResponse('error', 'ID do pedido ou modelo inválido.');
}

// Preparar a inserção no banco de dados
$stmt = $conexao->prepare("INSERT INTO pedidos_tr (idtr_pedido, modelo, montagem, frete, status, frequencia, bica, n_serie, observacao) VALUES (?, ?, 'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A')");

if (!$stmt) {
    sendResponse('error', 'Erro na preparação da consulta: ' . $conexao->error);
}

$stmt->bind_param("is", $idtr_pedido, $modelo);

// Executar a consulta
if ($stmt->execute()) {
    sendResponse('success', 'Modelo adicionado com sucesso.');
} else {
    sendResponse('error', 'Erro ao adicionar modelo: ' . $stmt->error);
}

// Fechar a conexão
$stmt->close();
$conexao->close();
?>
