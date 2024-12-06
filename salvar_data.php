<?php
include 'config.php';

date_default_timezone_set('America/Sao_Paulo'); // Define o fuso horário para Brasília

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_vinculo = $_POST['id_vinculo'];
    $acao = $_POST['acao'];
    $data_atual = date('Y-m-d H:i:s'); // Data e hora atuais no formato 'YYYY-MM-DD HH:MM:SS'

    if ($acao == "iniciar") {
        // Atualizar data_prog_fim com a data e hora atual
        $sql = "UPDATE cliente_produto SET data_prog_fim = ? WHERE id_vinculo = ?";
    } elseif ($acao == "finalizar") {
        // Atualizar data_programacao com a data e hora atual
        $sql = "UPDATE cliente_produto SET data_programacao = ? WHERE id_vinculo = ?";
    }

    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("si", $data_atual, $id_vinculo);

    if ($stmt->execute()) {
        echo "Data atualizada com sucesso!";
    } else {
        echo "Erro ao atualizar data: " . $stmt->error;
    }

    $stmt->close();
    $conexao->close();
    header("Location: programacao.php");
    exit();
}
?>

