<?php
include_once('config.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function buscarProgressoEquipamento($conexao, $clienteId = null) {
    // Base da consulta
    $sql = "SELECT id_equipamento_produto, porcentagem FROM progresso_equipamento";

    // Se houver um clienteId, adiciona o filtro
    if ($clienteId) {
        $sql .= " WHERE cliente_id = ?";
    }

    // Prepara a consulta
    $stmt = $conexao->prepare($sql);

    // Verifica se a preparação foi bem-sucedida
    if (!$stmt) {
        error_log("Erro ao preparar a consulta: " . $conexao->error);
        throw new Exception("Erro ao preparar a consulta.");
    }

    // Vincula o parâmetro se necessário
    if ($clienteId) {
        $stmt->bind_param("i", $clienteId);
    }

    // Executa a consulta
    if (!$stmt->execute()) {
        error_log("Erro ao executar a consulta: " . $stmt->error);
        throw new Exception("Erro ao executar a consulta.");
    }

    $resultado = $stmt->get_result();

    // Verifica se a consulta retornou um resultado válido
    if (!$resultado) {
        error_log("Erro ao obter o resultado: " . $stmt->error);
        throw new Exception("Erro ao obter o resultado.");
    }

    // Armazena os dados no array
    $dados = [];
    while ($linha = $resultado->fetch_assoc()) {
        $dados[] = $linha;
    }

    // Libera os recursos e retorna os dados
    $stmt->close();
    return $dados;
}

if (isset($_GET['dados_grafico'])) {
    // Captura o cliente_id, se fornecido
    $clienteId = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : null;

    // Define o tipo de retorno como JSON
    header('Content-Type: application/json');

    try {
        // Busca os dados
        $dados = buscarProgressoEquipamento($conexao, $clienteId);

        // Retorna os dados ou mensagem padrão
        if (empty($dados)) {
            echo json_encode(['mensagem' => 'Nenhum dado encontrado.']);
        } else {
            echo json_encode($dados);
        }
    } catch (Exception $e) {
        // Registra o erro no log do servidor
        error_log("Erro ao processar a requisição: " . $e->getMessage());

        // Retorna erro em caso de exceção
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao buscar os dados.']);
    }

    // Finaliza o script
    exit;
}
?>
