<?php
include_once('config.php');

if (isset($_POST['id_equipamento_produto'])) {
    $idEquipamentoProduto = $_POST['id_equipamento_produto'];

    // Prepara a consulta para excluir o vínculo
    $sql = "DELETE FROM cliente_produto WHERE id_equipamento_produto = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $idEquipamentoProduto);

    if ($stmt->execute()) {
        echo "Vínculo excluído com sucesso.";
    } else {
        echo "Erro ao excluir o vínculo: " . $stmt->error;
    }

    $stmt->close();
    // Redireciona para a página original ou exibe uma mensagem de confirmação
    header("Location: vinculo_cliente.php");
    exit();
}
?>
