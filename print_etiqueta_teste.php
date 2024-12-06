<?php
include_once('config.php');
include('protect.php');

// Verificar se a tabela existe e criar se necessário
$check_table = "SHOW TABLES LIKE 'config_impressao'";
$table_exists = $conexao->query($check_table)->num_rows > 0;

if (!$table_exists) {
    $create_table = "CREATE TABLE config_impressao (
        id INT PRIMARY KEY,
        margem_topo DECIMAL(5,2) DEFAULT 4.5,
        margem_esquerda DECIMAL(5,2) DEFAULT 0,
        largura_etiqueta DECIMAL(5,2) DEFAULT 101,
        altura_etiqueta DECIMAL(5,2) DEFAULT 75,
        rotacao INT DEFAULT 0
    )";
    
    $conexao->query($create_table);
    
    // Inserir configuração padrão
    $insert_default = "INSERT INTO config_impressao (id, margem_topo, margem_esquerda, largura_etiqueta, altura_etiqueta, rotacao)
                      VALUES (1, 4.5, 0, 101, 75, 0)";
    $conexao->query($insert_default);
}

// Buscar configurações de impressão
$sql = "SELECT * FROM config_impressao WHERE id = 1";
$result = $conexao->query($sql);
$config = $result->fetch_assoc();

if (!$config) {
    $config = [
        'margem_topo' => 4.5,
        'margem_esquerda' => 0,
        'largura_etiqueta' => 101,
        'altura_etiqueta' => 75,
        'rotacao' => 0
    ];
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Teste de Impressão</title>
    <style>
        @page {
            size: 105mm 84mm;
            margin: 0;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 24px;
            font-weight: bold;
        }
        .etiqueta {
            background: white;
            padding: 5px;
            border: 2px solid black;
            width: <?php echo $config['largura_etiqueta']; ?>mm;
            height: <?php echo $config['altura_etiqueta']; ?>mm;
            margin-top: <?php echo $config['margem_topo']; ?>mm;
            margin-left: <?php echo $config['margem_esquerda']; ?>mm;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            transform: rotate(<?php echo $config['rotacao']; ?>deg);
        }
        .box {
            border: 1px solid black;
            padding: 5px;
            margin-bottom: 2px;
            text-align: center;
        }
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="etiqueta">
        <div class="box">TESTE DE IMPRESSÃO</div>
        <div class="box">Configuração:</div>
        <div class="box">Margem Superior: <?php echo $config['margem_topo']; ?>mm</div>
        <div class="box">Margem Esquerda: <?php echo $config['margem_esquerda']; ?>mm</div>
        <div class="box">Dimensões: <?php echo $config['largura_etiqueta']; ?>x<?php echo $config['altura_etiqueta']; ?>mm</div>
        <div class="box">Rotação: <?php echo $config['rotacao']; ?>°</div>
    </div>
    <div class="no-print" style="position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);">
        <button onclick="window.print()">Imprimir</button>
        <button onclick="window.close()">Fechar</button>
    </div>
</body>
</html>
