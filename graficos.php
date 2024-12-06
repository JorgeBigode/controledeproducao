<?php
include_once('config.php'); 
include('protect.php');
include('menu.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function buscarClientes($conexao) {
    $sql = "SELECT 
                idcliente AS cliente_id,
                pedido AS pedido_cliente,
                cliente AS nome_cliente,           
                endereco AS endereco_cliente,      
                data_entrega AS entrega_cliente
            FROM cliente
            ORDER BY idcliente DESC";
    
    $result = $conexao->query($sql);
    if (!$result) {
        error_log("Erro na consulta de clientes: " . $conexao->error);
        echo "Desculpe, ocorreu um erro ao processar sua solicitação.";
        exit;
    }

    $clientes = [];
    while ($row = $result->fetch_assoc()) {
        $clientes[$row['cliente_id']] = [
            'idcliente' => $row['cliente_id'],
            'pedido' => $row['pedido_cliente'],
            'cliente' => $row['nome_cliente'],
            'endereco' => $row['endereco_cliente'],
            'data_entrega' => $row['entrega_cliente'],
            'pedidos' => [],
            'produtos' => []
        ];
    }
    $result->free();

    return $clientes;
}

function buscarProdutos($conexao, $cliente_id = null) {
    $sql = "SELECT c.idcliente, c.pedido, c.cliente AS cliente, c.endereco, c.data_entrega AS data_entrega, 
             cp.id_vinculo, cp.quantidade_prod, cp.lote, e.equipamento_pai, p.conjunto, 
             pe.status_producao, pe.porcentagem, pe.data_atualizacao 
             FROM cliente AS c 
             LEFT JOIN cliente_produto AS cp ON c.idcliente = cp.id_cliente 
             LEFT JOIN equipamento_produto AS ep ON cp.id_equipamento_produto = ep.id_equipamento_produto 
             LEFT JOIN equipamento AS e ON ep.idequipamento = e.idequipamento 
             LEFT JOIN produto AS p ON ep.idproduto = p.idproduto 
             LEFT JOIN progresso_equipamento AS pe ON cp.id_vinculo = pe.id_vinculo 
             " . ($cliente_id && $cliente_id !== 'all' ? "WHERE cp.id_cliente = " . intval($cliente_id) : "") . " 
             ORDER BY c.idcliente DESC";
    $result = $conexao->query($sql);
    if (!$result) {
      error_log("Erro na consulta de produtos: " . $conexao->error);
      echo "Desculpe, ocorreu um erro ao processar sua solicitação.";
      exit;
    }
    $produtos = [];
    while ($row = $result->fetch_assoc()) {
      $produtos[$row['idcliente']][] = $row;
    }
    $result->free();
    return $produtos;
  }



$clientes = buscarClientes($conexao);
$produtos = buscarProdutos($conexao);
$conexao->close();

foreach ($clientes as $cliente_id => &$cliente_data) {
    // Adicionar pedidos
    if (isset($pedidos[$cliente_id])) {
        $cliente_data['pedidos'] = $pedidos[$cliente_id];
    }

    if (isset($produtos[$cliente_id])) {
        $cliente_data['produtos'] = $produtos[$cliente_id];
    }
}
unset($cliente_data);
$clientes_data = array_values($clientes);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="img/icon-SILO.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/style_grafic.css">
    <link rel="stylesheet" href="css/graf.css">
    <title>GRAFICO</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    .container {
        max-width: 800px;
        margin: 0 auto;
    }

    .dados-cliente {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }

    .dados-cliente h2 {
        margin: 0;
        font-size: 1.1rem;
        color: #333;
        flex: 1 1 200px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .dados-cliente h2:first-child {
        font-size: 1.3rem;
        color: #2c3e50;
        flex: 1 1 100%;
        border-bottom: 2px solid #dee2e6;
        padding-bottom: 10px;
        margin-bottom: 10px;
    }

    .dados-cliente h2 a {
        color: #6c757d;
        font-weight: normal;
        font-size: 0.9em;
    }

    @media (max-width: 768px) {
        .cliente-bloco {
            width: 100%;
            padding: 20px;
            box-sizing: border-box;
        }

        .grafico-container {
            width: 100%;
        }

        .dados-cliente h2 {
            flex: 1 1 100%;
        }
    }

    @media (max-width: 480px) {
        .cliente-bloco {
            padding: 10px;
        }

        .grafico-container {
            padding: 10px;
        }
    }

    .grafico-container {
        width: 100%;
        height: 85%;
        margin: 0 auto;
    }

    .grafico-container canvas {
        width: 100%;
        height: auto;
    }
    </style>
</head>

<body>
    <main style="overflow-y: auto; max-height: calc(100vh - 78px);">
        <?php foreach ($clientes_data as $index => $cliente): ?>
        <div class="cliente-bloco <?php echo $index === 0 ? 'active' : ''; ?>" id="cliente-<?php echo $index; ?>"
            data-client-id="<?php echo htmlspecialchars($cliente['idcliente']); ?>">
            <div class="dados-cliente">
                <h2><?php echo htmlspecialchars($cliente['cliente']); ?></h2>
                <h2><a>Pedido:</a> <?php echo htmlspecialchars($cliente['pedido']); ?></h2>
                <h2><a>Localidade:</a> <?php echo htmlspecialchars($cliente['endereco']); ?></h2>
                <h2><a>Data de Entrega:</a> <?php echo htmlspecialchars($cliente['data_entrega']); ?></h2>
            </div>

            <?php if (count($cliente['produtos']) > 0): ?>
            <div class="grafico-container">
                <canvas id="grafico-produtos-cliente-<?php echo $cliente['idcliente']; ?>"
                    class="grafico-canvas"></canvas>
            </div>
            <?php endif; ?>
        </div>
        </div>
        <?php endforeach; ?>
    </main>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const chartData = <?php echo json_encode($clientes_data); ?>;

        const clientSelect = document.getElementById('clientSelect');
        const clientBlocos = document.querySelectorAll('.cliente-bloco');
        let currentIndex = 0;

        const chartInstances = {};

        function criarGraficoProdutos(cliente) {
            const ctx = document.getElementById(`grafico-produtos-cliente-${cliente.idcliente}`);
            if (ctx) {
                const chartCtx = ctx.getContext('2d');
                const labels = [];
                const dataValues = [];
                const equipamentos = {};

                cliente.produtos.forEach(produto => {
                    const equipamentoPai = produto.equipamento_pai || "N/A";
                    if (!equipamentos[equipamentoPai]) {
                        equipamentos[equipamentoPai] = {
                            porcentagens: [],
                            quantidade: 0
                        };
                    }
                    equipamentos[equipamentoPai].porcentagens.push(parseFloat(produto.porcentagem));
                    equipamentos[equipamentoPai].quantidade++;
                });

                Object.keys(equipamentos).forEach(equipamentoPai => {
                    const mediaPorcentagem = equipamentos[equipamentoPai].porcentagens.reduce((a, b) =>
                        a + b, 0) / equipamentos[equipamentoPai].quantidade;
                    labels.push(equipamentoPai);
                    dataValues.push(mediaPorcentagem);
                });

                console.log('Dados de porcentagem:', dataValues);
                new Chart(chartCtx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Progresso de Produção (%)',
                            data: dataValues,
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100
                            }
                        }
                    }
                });
            }
        }
        // Function to show the client and their corresponding chart
        function showCliente(index) {
            clientBlocos.forEach((bloco, i) => {
                if (i === index) {
                    bloco.style.display = 'block'; // Show selected client
                    if (!chartInstances[bloco.dataset.clientId]) {
                        const clienteData = chartData[i];
                        criarGraficoProdutos(clienteData); // Create chart if not already created
                        chartInstances[bloco.dataset.clientId] = true;
                    }
                } else {
                    bloco.style.display = 'none'; // Hide other clients
                }
            });
        }

        // Set up the client selection dropdown
        clientSelect.addEventListener('change', (e) => {
            const selectedClienteId = e.target.value;
            if (selectedClienteId === 'all') {
                showCliente(0); // Show the first client if 'all' is selected
            } else {
                const clienteIndex = chartData.findIndex(cliente => cliente.idcliente ===
                    selectedClienteId);
                showCliente(clienteIndex);
            }
        });

        // Initial page load - show the first client
        showCliente(currentIndex);
    });
    </script>
</body>

</html>