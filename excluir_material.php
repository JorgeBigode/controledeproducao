<?php
include_once('config.php');
include('protect.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

verificarAcesso();

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Deleta o material pelo ID
    $sql = "DELETE FROM materiais WHERE idmateriais = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "Material excluído com sucesso!";
        header("Location: inserir_material.php"); // Redireciona para a lista
        exit;
    } else {
        echo "Erro ao excluir o material.";
    }
} else {
    echo "ID do material não informado.";
    exit;
}
?>
