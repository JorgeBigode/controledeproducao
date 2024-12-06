<?php
include_once('config.php');

$sql = "CREATE TABLE IF NOT EXISTS config_impressao (
    id INT PRIMARY KEY,
    margem_topo DECIMAL(5,2) DEFAULT 4.5,
    margem_esquerda DECIMAL(5,2) DEFAULT 0,
    largura_etiqueta DECIMAL(5,2) DEFAULT 101,
    altura_etiqueta DECIMAL(5,2) DEFAULT 75
)";

if ($conexao->query($sql) === TRUE) {
    // Verificar se já existe um registro
    $check = "SELECT * FROM config_impressao WHERE id = 1";
    $result = $conexao->query($check);
    
    if ($result->num_rows == 0) {
        // Inserir configuração padrão
        $insert = "INSERT INTO config_impressao (id, margem_topo, margem_esquerda, largura_etiqueta, altura_etiqueta)
                  VALUES (1, 4.5, 0, 101, 75)";
        $conexao->query($insert);
    }
    
    echo "Tabela de configuração criada com sucesso!";
} else {
    echo "Erro ao criar tabela: " . $conexao->error;
}
?>
