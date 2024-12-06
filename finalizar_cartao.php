<?php
include_once('config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_producao = $_POST['id_producao'];

    $sql = "UPDATE producao SET data_hora_fim = NOW() WHERE id_producao = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param('i', $id_producao);
    $stmt->execute();

    echo 'Cartão finalizado com sucesso.';
}
?>
