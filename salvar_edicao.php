<?php
include_once('config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pedidoId = $_POST['pedidoId'];
    $observacao = $_POST['observacao'];
    $previsaoEntrega = $_POST['previsaoEntrega'];

    $stmt = $conexao->prepare("UPDATE cliente_produto SET obs_producao = ?, data_previsao = ? WHERE id_vinculo = ?");
    $stmt->bind_param("ssi", $observacao, $previsaoEntrega, $pedidoId);

    if ($stmt->execute()) {
        echo "Edição salva com sucesso!";
    } else {
        echo "Erro ao salvar a edição: " . $conexao->error;
    }

    $stmt->close();
}
?>
