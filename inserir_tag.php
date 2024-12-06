<?php
include_once('config.php');  // Certifique-se de ajustar para o caminho correto

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_vinculo'], $_POST['tag'])) {
    $id_vinculo = $_POST['id_vinculo'];
    $tag = $_POST['tag'];

    // Obtenha o equipamento_pai do id_vinculo
    $query_equipamento = "SELECT eq.equipamento_pai 
                          FROM cliente_produto cp
                          JOIN equipamento_produto ep ON cp.id_equipamento_produto = ep.id_equipamento_produto
                          JOIN equipamento eq ON ep.idequipamento = eq.idequipamento
                          WHERE cp.id_vinculo = ?";
    $stmt_equipamento = $conexao->prepare($query_equipamento);
    $stmt_equipamento->bind_param("i", $id_vinculo);
    $stmt_equipamento->execute();
    $result_equipamento = $stmt_equipamento->get_result();

    if ($result_equipamento->num_rows > 0) {
        $equipamento = $result_equipamento->fetch_assoc();
        $equipamento_pai = $equipamento['equipamento_pai'];

        // Atualize a tag para todos os registros com o mesmo equipamento_pai
        $query_update = "UPDATE cliente_produto cp
                         JOIN equipamento_produto ep ON cp.id_equipamento_produto = ep.id_equipamento_produto
                         JOIN equipamento eq ON ep.idequipamento = eq.idequipamento
                         SET cp.tag = ?
                         WHERE eq.equipamento_pai = ?";
        $stmt_update = $conexao->prepare($query_update);
        $stmt_update->bind_param("ss", $tag, $equipamento_pai);
        $stmt_update->execute();

        if ($stmt_update->affected_rows > 0) {
            echo "Tag atualizada com sucesso para todos os registros com o mesmo equipamento.";
        } else {
            echo "Nenhum registro atualizado.";
        }
    } else {
        echo "Equipamento não encontrado.";
    }

    $stmt_equipamento->close();
    $stmt_update->close();
}
$conexao->close();
header("Location: obra.php"); // Redireciona para a página desejada
exit;
?>
