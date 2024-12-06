<?php
include_once('config.php'); 
include('protect.php');
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

function exportarParaExcel($clientes_data) {
    if (ob_get_length()) ob_end_clean();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF']
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '4CAF50']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ];

    $dataStyle = [
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true // Permite quebras de linha
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ];

    $sheet->setCellValue('B1', 'Pedido');
    $sheet->setCellValue('C1', 'Cliente');
    $sheet->setCellValue('D1', 'Localidade');
    $sheet->setCellValue('E1', 'Data Entrega');
    $sheet->setCellValue('F1', 'Produto');
    $sheet->setCellValue('G1', 'Status');
    $sheet->setCellValue('H1', 'Observação');
    $sheet->setCellValue('I1', 'Previsão de Entrega');

    $sheet->getStyle('B1:I1')->applyFromArray($headerStyle);

    foreach (range('B', 'I') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $row = 2;

    foreach ($clientes_data as $cliente) {
        // Agrupar produtos iguais
        $produtosAgrupados = [];
        foreach ($cliente['produtos'] as $produto) {
            $chave = $produto['nome_equipamento'] . '|' . 
                    $produto['status_producao'] . '|' . 
                    $produto['obs_producao'] . '|' . 
                    (!empty($produto['data_previsao']) ? $produto['data_previsao'] : 'N/A');
            
            if (!isset($produtosAgrupados[$chave])) {
                $produtosAgrupados[$chave] = [
                    'nome' => $produto['nome_equipamento'],
                    'status' => $produto['status_producao'],
                    'obs' => $produto['obs_producao'],
                    'previsao' => $produto['data_previsao'],
                    'quantidade' => 1
                ];
            } else {
                $produtosAgrupados[$chave]['quantidade']++;
            }
        }

        // Criar uma linha para cada combinação única de produto/status/obs/previsão
        foreach ($produtosAgrupados as $produto) {
            $sheet->setCellValue('B' . $row, $cliente['pedido']);
            $sheet->setCellValue('C' . $row, $cliente['cliente']);
            $sheet->setCellValue('D' . $row, $cliente['endereco']);
            $sheet->setCellValue('E' . $row, date('d/m/Y', strtotime($cliente['data_entrega'])));
            
            // Se houver mais de um produto igual, mostrar a quantidade
            $nomeProduto = $produto['quantidade'] > 1 ? 
                          $produto['nome'] . ' (' . $produto['quantidade'] . ')' : 
                          $produto['nome'];
            
            $sheet->setCellValue('F' . $row, $nomeProduto);
            $sheet->setCellValue('G' . $row, $produto['status']);
            $sheet->setCellValue('H' . $row, $produto['obs']);
            $sheet->setCellValue('I' . $row, !empty($produto['previsao']) ? date('d/m/Y', strtotime($produto['previsao'])) : 'N/A');

            $sheet->getStyle('B' . $row . ':I' . $row)->applyFromArray($dataStyle);
            $row++;
        }
    }

    $fileName = "relatorio_clientes_" . date('Y-m-d_H-i-s') . ".xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}


$cliente_id = isset($_GET['cliente_id']) ? $_GET['cliente_id'] : null;

if ($cliente_id) {
    $query = "SELECT * FROM cliente WHERE idcliente = ?";
    $stmt = $conexao->prepare($query);
    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $cliente = $result->fetch_assoc();
        // Gerar o relatório aqui
        echo "<h1>Relatório para o Cliente: " . htmlspecialchars($cliente['cliente']) . "</h1>";
    } else {
        echo "Cliente não encontrado.";
    }
}

function buscarClientesComProdutos($conexao, $clienteId) {
    $whereClause = ($clienteId !== 'todos' && $clienteId !== null) ? "WHERE c.idcliente = $clienteId" : "";
    if ($whereClause) {
        $whereClause .= " AND cp.id_cliente IS NOT NULL";
    } else {
        $whereClause = "WHERE cp.id_cliente IS NOT NULL";
    }

    $sql = "
        SELECT 
            c.idcliente AS cliente_id,
            c.pedido AS pedido_cliente,
            c.cliente AS nome_cliente,
            c.endereco AS endereco_cliente,
            c.data_entrega AS entrega_cliente,
            cp.quantidade_prod,
            cp.data_engenharia,
            cp.status_producao,
            cp.obs_producao,
            cp.data_previsao,
            e.equipamento_pai AS nome_equipamento,
            pr.conjunto
        FROM cliente c
        INNER JOIN cliente_produto cp ON c.idcliente = cp.id_cliente
        LEFT JOIN equipamento_produto ep ON cp.id_equipamento_produto = ep.id_equipamento_produto
        LEFT JOIN equipamento e ON ep.idequipamento = e.idequipamento
        LEFT JOIN produto pr ON ep.idproduto = pr.idproduto
        $whereClause
        ORDER BY c.idcliente, e.equipamento_pai;
    ";

    $result = $conexao->query($sql);
    if (!$result) {
        echo "Erro na consulta: " . $conexao->error;
        exit;
    }

    $clientes = [];
    while ($row = $result->fetch_assoc()) {
        $cliente_id = $row['cliente_id'];
        if (!isset($clientes[$cliente_id])) {
            $clientes[$cliente_id] = [
                'idcliente' => $row['cliente_id'],
                'pedido' => $row['pedido_cliente'],
                'cliente' => $row['nome_cliente'],
                'endereco' => $row['endereco_cliente'],
                'data_entrega' => $row['entrega_cliente'],
                'produtos' => []
            ];
        }

        $clientes[$cliente_id]['produtos'][] = [
            'conjunto' => $row['conjunto'], // Certifique-se de que 'conjunto' está aqui
            'quantidade_prod' => $row['quantidade_prod'],
            'data_engenharia' => $row['data_engenharia'],
            'status_producao' => $row['status_producao'],
            'obs_producao' => $row['obs_producao'],
            'data_previsao' => $row['data_previsao'],
            'nome_equipamento' => $row['nome_equipamento']
        ];
    }

    return array_values($clientes);
}

$clientes_data = buscarClientesComProdutos($conexao, $cliente_id);

if (isset($_POST['exportar'])) {
    exportarParaExcel($clientes_data);
}
?>
<!DOCTYPE html>
<html lang="ptbr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link rel="icon" href="img/icon-SILO.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/relatorio.css">
    <link rel="stylesheet" href="css/obra.css">
    <title>Relatório</title>
</head>

<body>
    <form method="POST">
        <button type="submit" name="exportar" class="btn btn-success">Exportar para Excel</button>
        <a href='obra.php'        class=" btn btn-success">
            VOLTAR
</a>
    </form>

    <main style="overflow-y: auto; max-height: calc(120vh - 50px);">
        <?php foreach ($clientes_data as $index => $cliente): ?>
        <div class="cliente-bloco <?php echo $index === 0 ? 'active' : ''; ?>"
            id="cliente-<?php echo htmlspecialchars($cliente['idcliente']); ?>"
            data-client-id="<?php echo htmlspecialchars($cliente['idcliente']); ?>">
            <div class="seta-cliente">
                <div class="dados-cliente">
                    <h2><?php echo htmlspecialchars($cliente['cliente']); ?></h2>
                    <h2><a>Pedido:</a> <?php echo htmlspecialchars($cliente['pedido']); ?></h2>
                    <h2><a>Localidade:</a> <?php echo htmlspecialchars($cliente['endereco']); ?></h2>
                    <h2><a>Prazo de Entrega:</a> <?php echo date('d/m/Y', strtotime($cliente['data_entrega'])); ?></h2>
                </div>
            </div>

            <?php
        $query_produtos = "SELECT cp.id_vinculo, cp.quantidade_prod, cp.data_engenharia, cp.status_producao, cp.obs_producao, cp.data_previsao, eq.equipamento_pai AS nome_equipamento, pr.conjunto
                           FROM cliente_produto cp
                           JOIN equipamento_produto ep ON cp.id_equipamento_produto = ep.id_equipamento_produto
                           JOIN equipamento eq ON ep.idequipamento = eq.idequipamento
                           JOIN produto pr ON ep.idproduto = pr.idproduto
                           WHERE cp.id_cliente = ? 
                           ORDER BY eq.equipamento_pai, cp.data_engenharia ASC";
        $stmt_produtos = $conexao->prepare($query_produtos);
        $stmt_produtos->bind_param("i", $cliente['idcliente']);
        $stmt_produtos->execute();
        $result_produtos = $stmt_produtos->get_result();
        $equipamento_pai_anterior = "";

        if ($result_produtos->num_rows > 0):
            while ($produto = $result_produtos->fetch_assoc()):
                if ($produto['nome_equipamento'] !== $equipamento_pai_anterior):
                    if ($equipamento_pai_anterior !== "") {
                        echo "</tbody></table></div>";
                    }

                    // Atualiza o valor de equipamento_pai_anterior para o próximo bloco
                    $equipamento_pai_anterior = $produto['nome_equipamento'];
        ?>
            <div class='equipamento-block'>
                <h5>Equipamento Pai:
                    <a href='buscar_pdf.php?equipamento=<?php echo urlencode($produto['nome_equipamento']); ?>'
                        target='_blank'>
                        <?php echo htmlspecialchars($produto['nome_equipamento']); ?>
                    </a>
                </h5>
                <table class='table table-striped'>
                    <thead>
                        <tr>
                            <th>Conjunto</th>
                            <th>Qtd</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Observação</th>
                            <th>Previsão de Entrega</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                endif;
        ?>
                        <tr>
                            <td class="single-line" title="<?php echo htmlspecialchars($produto['conjunto'] ?? ''); ?>">
                                <a href="buscar_pdf.php?conjunto=<?php echo urlencode($produto['conjunto'] ?? ''); ?>"
                                    target="_blank">
                                    <?php echo htmlspecialchars($produto['conjunto'] ?? ''); ?>
                                </a>
                            </td>
                            <td class="single-line"
                                title="<?php echo htmlspecialchars($produto['quantidade_prod'] ?? ''); ?>">
                                <?php echo htmlspecialchars($produto['quantidade_prod'] ?? ''); ?>
                            </td>
                            <td class="single-line"
                                title="<?php echo !empty($produto['data_engenharia']) && $produto['data_engenharia'] !== '0000-00-00' ? date('d/m/Y', strtotime($produto['data_engenharia'])) : 'N/A'; ?>">
                                <?php echo !empty($produto['data_engenharia']) && $produto['data_engenharia'] !== '0000-00-00' ? date('d/m/Y', strtotime($produto['data_engenharia'])) : 'N/A'; ?>
                            </td>
                            <td class="single-line"
                                title="<?php echo htmlspecialchars($produto['status_producao'] ?? ''); ?>">
                                <?php echo htmlspecialchars($produto['status_producao'] ?? ''); ?>
                            </td>
                            <td class="single-line"
                                title="<?php echo htmlspecialchars($produto['obs_producao'] ?? ''); ?>">
                                <?php echo htmlspecialchars($produto['obs_producao'] ?? ''); ?>
                            </td>
                            <td class="single-line"
                                title="<?php echo !empty($produto['data_previsao']) && $produto['data_previsao'] !== '0000-00-00' ? date('d/m/Y', strtotime($produto['data_previsao'])) : 'N/A'; ?>">
                                <?php echo !empty($produto['data_previsao']) && $produto['data_previsao'] !== '0000-00-00' ? date('d/m/Y', strtotime($produto['data_previsao'])) : 'N/A'; ?>
                            </td>
                            <td>
                                <button class="btn btn-primary" onclick="abrirModalEditar(
                <?php echo $produto['id_vinculo']; ?>, 
                '<?php echo isset($produto['obs_producao']) ? addslashes($produto['obs_producao']) : ''; ?>', 
                '<?php echo isset($produto['data_previsao']) ? addslashes($produto['data_previsao']) : ''; ?>'
            )">
                                    Editar
                                </button>
                            </td>
                        </tr>
                        <?php
            endwhile;
            echo "</tbody></table></div>"; // Fecha o último bloco de equipamento e tabela
        endif;
        ?>
            </div>
            <?php endforeach; ?>
    </main>

    <div class="modal fade" id="modalEditar" tabindex="-1" role="dialog" aria-labelledby="tituloModalEditar"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloModalEditar">Editar Produto</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formEditarProduto">
                        <input type="hidden" id="pedidoId" name="pedidoId">
                        <div class="form-group">
                            <label for="observacao">Observação</label>
                            <input type="text" class="form-control" id="observacao" name="observacao">
                        </div>
                        <div class="form-group">
                            <label for="previsaoEntrega">Previsão de Entrega</label>
                            <input type="date" class="form-control" id="previsaoEntrega" name="previsaoEntrega">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="salvarEdicao()">Salvar</button>
                </div>
            </div>
        </div>
    </div>
    <script>
    function atualizarBlocos() {
        clientBlocos.forEach(bloco => {
            const blocoClientId = bloco.getAttribute('data-client-id');
            if (selectedClient === 'all' || String(blocoClientId) === String(selectedClient)) {
                bloco.style.display = 'block';
                const cliente = chartData.find(c => String(c.idcliente) === String(blocoClientId));
                if (cliente) {
                    if (chartInstances[`pedidos-${cliente.idcliente}`]) {
                        chartInstances[`pedidos-${cliente.idcliente}`].destroy();
                        delete chartInstances[`pedidos-${cliente.idcliente}`];
                    }
                    if (chartInstances[`produtos-${cliente.idcliente}`]) {
                        chartInstances[`produtos-${cliente.idcliente}`].destroy();
                        delete chartInstances[`produtos-${cliente.idcliente}`];
                    }
                    criarGraficoPedidos(cliente);
                    criarGraficoProdutos(cliente);
                }
            } else {
                bloco.style.display = 'none';
                const cliente = chartData.find(c => String(c.idcliente) === String(blocoClientId));
                if (cliente) {
                    if (chartInstances[`pedidos-${cliente.idcliente}`]) {
                        chartInstances[`pedidos-${cliente.idcliente}`].destroy();
                        delete chartInstances[`pedidos-${cliente.idcliente}`];
                    }
                    if (chartInstances[`produtos-${cliente.idcliente}`]) {
                        chartInstances[`produtos-${cliente.idcliente}`].destroy();
                        delete chartInstances[`produtos-${cliente.idcliente}`];
                    }
                }
            }
        });
    }

    const clientBlocos = document.querySelectorAll('.cliente-bloco');

    if (clientBlocos.length > 0) {
        const primeiroVisivel = Array.from(clientBlocos).findIndex(bloco => bloco.style.display === 'block');
        if (primeiroVisivel !== -1) {
            currentIndex = primeiroVisivel;
            showCliente(currentIndex);
        } else {
            currentIndex = -1; // Nenhum cliente visível
        }
    } else {
        console.warn("Nenhum elemento encontrado com a classe 'cliente-bloco'");
    }

    window.onload = function() {
        var clienteId = localStorage.getItem('clienteSelecionado');
        if (clienteId) {
            $('#clienteSelect').val(clienteId);
            selecionarCliente();
        }
    };

    function selecionarCliente() {
        var clienteId = $('#clienteSelect').val();
        $('.cliente-bloco').hide();

        if (clienteId === 'all') {
            $('.cliente-bloco').show();
        } else {
            $('#cliente-' + clienteId).show();
        }

        localStorage.setItem('clienteSelecionado', clienteId);
    }

    function abrirModalEditar(id, observacao, previsaoEntrega) {
        // Verifique se os valores estão corretos
        console.log(id, observacao, previsaoEntrega);

        // Preencher os campos do modal
        document.getElementById('pedidoId').value = id;
        document.getElementById('observacao').value = observacao || '';
        document.getElementById('previsaoEntrega').value = previsaoEntrega || '';

        // Mostrar o modal
        $('#modalEditar').modal('show');
    }

    $('#modalEditar').on('hidden.bs.modal', function() {
        $(this).find('form').trigger('reset');
    });

    function salvarEdicao() {
        const pedidoId = document.getElementById('pedidoId').value;
        const observacao = document.getElementById('observacao').value;
        const previsaoEntrega = document.getElementById('previsaoEntrega').value;

        $.ajax({
            url: 'salvar_detalhes_pedido.php', // Atualize o caminho conforme necessário
            method: 'POST',
            data: {
                pedidoId: pedidoId,
                observacao: observacao,
                previsaoEntrega: previsaoEntrega
            },
            success: function(response) {
                // Feche o modal após salvar
                $('#modalEditar').modal('hide');
                // Atualize a interface ou execute outras ações necessárias
                console.log("Edição salva com sucesso");
            },
            error: function(error) {
                console.error("Erro ao salvar a edição", error);
            }
        });
    }
    </script>

</body>

</html>