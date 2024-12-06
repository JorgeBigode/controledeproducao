<?php
include_once('config.php'); 
include('protect.php');
verificarAcesso();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$status = $_GET['status'] ?? 'all';
$dataMin = $_GET['dataMin'] ?? '';

if (empty($dataMin)) {
    echo "Por favor, selecione uma data mínima.";
    exit;
}

echo "Data a partir de: " . htmlspecialchars(date("d/m/y", strtotime($dataMin)));

$sql_lista_pedidos = "
    SELECT c.idcliente, 
           c.pedido, 
           c.cliente AS cliente, 
           c.endereco, 
           GROUP_CONCAT(cp.id_vinculo SEPARATOR '||') AS id_vinculos,  
           GROUP_CONCAT(
               CONCAT_WS(
                   '||', 
                   COALESCE(cp.quantidade_prod, 'N/A'),
                   COALESCE(cp.lote, 'N/A'),
                   COALESCE(cp.data_engenharia, 'N/A'), 
                   COALESCE(cp.data_programacao, 'N/A'), 
                   COALESCE(cp.data_pcp, 'N/A'),
                   COALESCE(cp.data_producao, 'N/A'),
                   COALESCE(cp.data_qualidade, 'N/A'),
                   COALESCE(cp.status_producao, 'N/A'),  -- Adicione a coluna status_producao
                   COALESCE(e.equipamento_pai, 'N/A'), 
                   COALESCE(p.conjunto, 'N/A') 
               ) SEPARATOR '%%'
           ) AS produtos
    FROM cliente AS c
    LEFT JOIN cliente_produto AS cp ON c.idcliente = cp.id_cliente
    LEFT JOIN equipamento_produto AS ep ON cp.id_equipamento_produto = ep.id_equipamento_produto
    LEFT JOIN equipamento AS e ON ep.idequipamento = e.idequipamento
    LEFT JOIN produto AS p ON ep.idproduto = p.idproduto
    WHERE 1=1
";

// Condições para o filtro baseado em status
if ($status !== 'all') {
    switch ($status) {
        case 'engenharia':
            $sql_lista_pedidos .= " AND cp.status_producao = 'Aguardando Programação'";
            break;
        case 'programacao':
            $sql_lista_pedidos .= " AND cp.status_producao = 'Aguardando PCP'";
            break;
        case 'pcp':
            $sql_lista_pedidos .= " AND cp.status_producao = 'Em Produção'";
            break;
        case 'producao':
            $sql_lista_pedidos .= " AND cp.status_producao = 'Produção Finalizada'";
            break;
        case 'qualidade':
            $sql_lista_pedidos .= " AND cp.status_producao = 'Liberado para Expedição'";
            break;
    }
}

$sql_lista_pedidos .= " AND (
    cp.data_engenharia >= ? OR
    cp.data_programacao >= ? OR 
    cp.data_pcp >= ? OR 
    cp.data_producao >= ? OR
    cp.data_qualidade >= ?
)";

$sql_lista_pedidos .= " GROUP BY c.idcliente ORDER BY c.idcliente DESC";

// Executar a consulta
$stmt_pedidos = $conexao->prepare($sql_lista_pedidos);
$stmt_pedidos->bind_param('sssss', $dataMin, $dataMin, $dataMin, $dataMin, $dataMin); // Binding da data mínima para as 3 condições
$stmt_pedidos->execute();
$result_lista_pedidos = $stmt_pedidos->get_result();

// Verifique os resultados
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="icon" href="img/icon-SILO.ico" type="image/x-icon">
    <title>Relatório de Status</title>
    <style>
    .linha-engenharia {
        background-color: #F44343 !important;
        /* Vermelho */
    }

    .linha-programacao {
        background-color: #007BFF !important;
        /* Azul */
    }

    .linha-pcp {
        background-color: #FFFF00 !important;
        /* Amarelo */
    }

    .linha-producao {
        background-color: #F0912B !important;
        /* Verde */
    }

    .linha-qualidade {
        background-color: #008000 !important;
        /* Verde */
    }

    .pedido-modulo {
        position: fixed;
        top: 100px;
        left: 0;
        width: 100%;
        background-color: #f5f5f5;
        padding: 15px;
        margin-bottom: 10px;
        border-radius: 8px;
        z-index: 1000;
    }

    .pedido-detalhe {
        width: 100%;
        background-color: #ffffff;
        padding: 15px;
        margin-bottom: 10px;
        border-radius: 8px;
        border: 1px solid #ddd;
        text-transform: uppercase;
    }

    .pedido-modulo table,
    .pedido-detalhe table {
        width: 100%;
        border-collapse: collapse;
        text-transform: uppercase;
        text-align: center;
    }

    .pedido-modulo table th,
    .pedido-modulo table td,
    .pedido-detalhe table th,
    .pedido-detalhe table td {
        border: 1px solid #ddd;
        padding: 8px;
    }


    a {
        background: #009879;
        color: black;
        border-radius: 8px;
        padding: 5px;
    }

    h2 {
        text-align: center;
    }

    .pedido-detalhe-group {
        display: flex;
        flex-direction: column;
        margin-bottom: 20px;
    }

    .bloco-cliente:nth-of-type(3n) {
        page-break-after: always;
    }

    @media print {
        #printButton {
            display: none;
        }

        a {
            display: none;
        }
    }
    </style>
</head>

<body>
    <div class="container">

        <h2>Relatório de Status</h2>
        <a href="obra.php">Voltar</a>
        <button id="printButton" onclick="imprimirBlocos()">Imprimir</button>

        <?php if ($result_lista_pedidos->num_rows > 0): ?>
        <?php while ($cliente = $result_lista_pedidos->fetch_assoc()): ?>
        <div class="card mb-3 bloco-cliente">
            <div class="card-header">
                <?php echo htmlspecialchars($cliente['cliente']); ?> -
                <?php echo htmlspecialchars($cliente['pedido']); ?>
            </div>
            <div class="card-body">
    <p><strong>Endereço:</strong> <?php echo htmlspecialchars($cliente['endereco']); ?></p>
    <h4>Produtos por Equipamento Pai:</h4>
    
    <?php
        $produtos = explode('%%', $cliente['produtos']);
        $count = 0; // Inicialização da variável para contar os blocos de produtos
        
        foreach ($produtos as $produto) {
            // Início de um grupo de três blocos
            if ($count % 3 == 0) {
                if ($count > 0) {
                    // Se não for o primeiro grupo, adiciona quebra de página antes de continuar
                    echo '<div style="page-break-after: always;"></div>';
                }
                echo '<div class="pedido-detalhe-group">';
            }
            
            list($quantidade, $lote, $data_engenharia, $data_programacao, $data_pcp, $data_producao, $data_qualidade, $status_producao, $equipamento, $conjunto) = explode('||', $produto);
            
            // Formatação de datas e aplicação das classes conforme o status
            $data_engenharia = ($data_engenharia == '0000-00-00 00:00:00' || $data_engenharia == 'N/A') ? 'N/A' : date("d/m/y", strtotime($data_engenharia));
            $data_programacao = ($data_programacao == '0000-00-00 00:00:00' || $data_programacao == 'N/A') ? 'N/A' : date("d/m/y", strtotime($data_programacao));
            $data_pcp = ($data_pcp == '0000-00-00 00:00:00' || $data_pcp == 'N/A') ? 'N/A' : date("d/m/y", strtotime($data_pcp));
            $data_producao = ($data_producao == '0000-00-00 00:00:00' || $data_producao == 'N/A') ? 'N/A' : date("d/m/y", strtotime($data_producao));
            $data_qualidade = ($data_qualidade == '0000-00-00 00:00:00' || $data_qualidade == 'N/A') ? 'N/A' : date("d/m/y", strtotime($data_qualidade));

            switch ($status_producao) {
                case 'Aguardando Programação': $classe_linha = "linha-engenharia"; break;
                case 'Aguardando PCP': $classe_linha = "linha-programacao"; break;
                case 'Aguardando Produção': $classe_linha = "linha-pcp"; break;
                case 'Produção Finalizada': $classe_linha = "linha-producao"; break;
                case 'Liberado para Expedição': $classe_linha = "linha-qualidade"; break;
                default: $classe_linha = "";
            }
    ?>
    <div class="pedido-detalhe <?php echo $classe_linha; ?>">
        <p><strong>Status:</strong> <?php echo htmlspecialchars($status_producao); ?></p>
        <p><strong>Equipamento Pai:</strong> <?php echo htmlspecialchars($equipamento); ?></p>
        <ul>
            <li><strong>Quantidade:</strong> <?php echo htmlspecialchars($quantidade); ?></li>
            <li><strong>Lote:</strong> <?php echo htmlspecialchars($lote); ?></li>
            <li><strong>Conjunto:</strong> <?php echo htmlspecialchars($conjunto); ?></li>
            <li><strong>Data Engenharia:</strong> <?php echo htmlspecialchars($data_engenharia); ?></li>
            <li><strong>Data Programação:</strong> <?php echo htmlspecialchars($data_programacao); ?></li>
            <li><strong>Data PCP:</strong> <?php echo htmlspecialchars($data_pcp); ?></li>
            <li><strong>Data Produção:</strong> <?php echo htmlspecialchars($data_producao); ?></li>
            <li><strong>Data Qualidade:</strong> <?php echo htmlspecialchars($data_qualidade); ?></li>
        </ul>
    </div>
    <?php
            $count++;
            // Fim de um grupo de três blocos
            if ($count % 3 == 0) echo '</div>'; // Fecha o grupo de blocos
        }
        // Fecha o último grupo caso não seja múltiplo de três
        if ($count % 3 != 0) echo '</div>';
    ?>
</div>

        <?php endwhile; // Fim do while ?>
        <?php else: ?>
        <p>Nenhum pedido encontrado para o status selecionado.</p>
        <?php endif; ?>
    </div>
    <script>
    let blocoIndex = 0;

    function imprimirBlocos() {
        // Seleciona todos os blocos
        const blocos = document.querySelectorAll('.bloco-cliente');

        // Esconde todos os blocos
        blocos.forEach(bloco => bloco.style.display = 'none');

        // Mostra apenas os próximos três blocos para impressão
        const limite = blocoIndex + 3;
        for (let i = blocoIndex; i < limite && i < blocos.length; i++) {
            blocos[i].style.display = 'block';
        }

        // Atualiza o índice para o próximo conjunto de blocos
        blocoIndex += 3;

        // Realiza a impressão
        window.print();

        // Restaura a visibilidade dos blocos após a impressão
        blocos.forEach(bloco => bloco.style.display = 'block');
    }
    </script>
</body>

</html>