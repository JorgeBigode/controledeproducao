<?php
header('Content-Type: application/json');
include_once('config.php');
include('protect.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Receber e decodificar os dados JSON
    $dados = json_decode(file_get_contents('php://input'), true);
    
    if (!$dados) {
        throw new Exception("Dados não recebidos corretamente");
    }

    // Extrair os dados
    $id = $dados['id'];
    $idmateriais = $dados['idmateriais'];
    $quantidade = $dados['quantidade'];
    $operacao = $dados['operacao'];

    // Buscar o valor de 'quant' na tabela materiais
    $query_material = "SELECT quant FROM materiais WHERE idmateriais = ?";
    $stmt = $conexao->prepare($query_material);
    $stmt->bind_param("i", $idmateriais);
    $stmt->execute();
    $result = $stmt->get_result();
    $material = $result->fetch_assoc();

    if (!$material) {
        throw new Exception("Material não encontrado");
    }

    $quant = str_replace(',', '.', $material['quant']);
    
    // Converte para float para garantir cálculo preciso
    $quantidade_float = floatval($quantidade);
    $quant_float = floatval($quant);

    // Calcula baseado na operação selecionada
    if ($operacao === 'multiplicar') {
        $resultado = $quant_float * $quantidade_float;
        $quantidade_final = $quantidade_float;
        $quantidade_total = $resultado;
    } else {
        // Verifica divisão por zero
        if ($quant_float == 0) {
            throw new Exception("Erro: Divisão por zero não é permitida");
        }
        // Para divisão, o resultado vai para quantidade e o valor inserido vai para total
        $resultado = $quantidade_float / $quant_float;
        $quantidade_final = $resultado;
        $quantidade_total = $quantidade_float;
    }

    // Atualizar os valores no banco
    $query_update = "UPDATE formulario_cadastro.materiais_equipamento 
                    SET quantidade = ?, quantidade_total = ? 
                    WHERE id = ?";
    $stmt_update = $conexao->prepare($query_update);
    $stmt_update->bind_param("ddi", $quantidade_final, $quantidade_total, $id);

    if (!$stmt_update->execute()) {
        throw new Exception("Erro ao atualizar no banco de dados: " . $stmt_update->error);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
