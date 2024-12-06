<?php
include_once('config.php');

$id_cliente = $_POST['id_cliente'];
$data_engenharia = $_POST['data_engenharia'];
$data_programacao = $_POST['data_programacao'];
$data_pcp = $_POST['data_pcp'];
$data_producao = $_POST['data_producao'];
$lote = $_POST['lote'];
$quantidade_prod = $_POST['quantidade_prod'];
$id_equipamentos = isset($_POST['id_equipamento_produto']) ? $_POST['id_equipamento_produto'] : [];

if (is_array($id_equipamentos) && !empty($id_equipamentos)) {
    foreach ($id_equipamentos as $id_equipamento) {
        $sql_insert = "
            INSERT INTO cliente_produto (id_cliente, id_equipamento_produto, data_engenharia, data_programacao, data_pcp, data_producao, lote, quantidade_prod)
            VALUES ('$id_cliente', '$id_equipamento', '$data_engenharia', '$data_programacao', '$data_pcp', '$data_producao', '$lote', '$quantidade_prod')
        ";
        
        if (!$conexao->query($sql_insert)) {
            echo "Erro ao vincular o equipamento: " . $conexao->error;
            exit;
        }
    }
    echo "Equipamento(s) vinculado(s) com sucesso!";
} else {
    echo "Nenhum equipamento selecionado ou erro ao processar os dados.";
}

$conexao->close();

?>
