<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_vinculo = $_POST['id_vinculo'];
    $link_pastas = $_POST['link_pastas'];
    $obs_detalhes = $_POST['obs_detalhes'];
    $lote = $_POST['lote'];
    $prioridade = $_POST['prioridade'];

    // Verificar se id_vinculo está definido
    if (!empty($id_vinculo)) {
        // Preparar a consulta para atualizar todos os campos
        $sql = "UPDATE cliente_produto SET 
                    link_pastas = ?, 
                    obs_detalhes = ?, 
                    prioridade = ?,
                    lote = ? 
                WHERE id_vinculo = ?";

        $stmt = $conexao->prepare($sql);
        $stmt->bind_param('ssssi', $link_pastas, $obs_detalhes, $prioridade, $lote, $id_vinculo);

        if ($stmt->execute()) {
            echo "Dados salvos com sucesso!";
        } else {
            echo "Erro ao salvar dados: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "ID de vínculo não fornecido.";
    }
}


$conexao->close();

// Redireciona para a página anterior
header("Location: {$_SERVER['HTTP_REFERER']}");
exit;
?>
