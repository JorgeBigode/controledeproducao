<?php
session_start();
include_once('config.php'); 

// Recebe o id_vinculo
$id_vinculo = $_POST['id_vinculo'] ?? null;
$prioridade_alterada = false;

// Verifica se a prioridade foi alterada
if ($id_vinculo) {
    $query = "SELECT id_usuario_modificacao FROM tabela_prioridade WHERE id_vinculo = ?";
    $stmt = $conexao->prepare($query);
    $stmt->bind_param("i", $id_vinculo);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    // Verifica se a prioridade foi alterada por outro usuário
    if ($row && $row['id_usuario_modificacao'] != $_SESSION['id_usuario']) {
        $prioridade_alterada = true;
    }
}

// Retorna a resposta em formato JSON
echo json_encode(['prioridade_alterada' => $prioridade_alterada]);
?>
