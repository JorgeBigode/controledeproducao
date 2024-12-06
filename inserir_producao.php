<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_vinculo = $_POST['id_vinculo'];
    $setoresSelecionados = $_POST['id_setor']; // Array de setores selecionados

    // Verifica se algum setor foi selecionado
    if (!empty($setoresSelecionados) && is_array($setoresSelecionados)) {
        foreach ($setoresSelecionados as $id_setor) {
            // Consulta para inserir os dados na tabela producao para cada setor selecionado
            $sql = "INSERT INTO producao (id_vinculo, id_setor) VALUES ('$id_vinculo', '$id_setor')";
            
            if (!mysqli_query($conexao, $sql)) {
                echo "Erro ao inserir dados: " . mysqli_error($conexao);
                break; // Para o loop em caso de erro
            }
        }
        echo "<script>
        alert('Dados inseridos com sucesso.');
        window.history.back();
      </script>";
} else {
echo "<script>
        alert('Nenhum setor foi selecionado.');
        window.history.back();
      </script>";
}

mysqli_close($conexao);
}
?>

