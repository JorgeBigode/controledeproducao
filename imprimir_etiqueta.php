<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

include_once('config.php');

$id_vinculo = $_GET['id'] ?? null;
$id_cliente = $_GET['id_cliente'] ?? null;
$equipamento_pai = $_GET['equipamento_pai'] ?? null;

if (!$id_vinculo || !$id_cliente || !$equipamento_pai) {
    die("Parâmetros insuficientes.");
}

// Consultar os dados com todos os conjuntos relacionados
$query = "SELECT 
    c.idcliente, 
    c.pedido, 
    c.cliente, 
    c.endereco,
    c.data_entrega,
    cp.id_vinculo,  
    cp.quantidade_prod,
    cp.tag,
    cp.lote,
    cp.obs_detalhes,
    cp.data_engenharia,
    cp.data_prog_fim,
    cp.data_programacao,
    e.equipamento_pai,     
    p.conjunto
FROM 
    cliente c
INNER JOIN cliente_produto cp
    ON c.idcliente = cp.id_cliente
INNER JOIN equipamento_produto ep 
    ON cp.id_equipamento_produto = ep.id_equipamento_produto
INNER JOIN equipamento e
    ON ep.idequipamento = e.idequipamento
INNER JOIN produto p
    ON ep.idproduto = p.idproduto
WHERE 
    c.idcliente = ? 
    AND e.equipamento_pai = ?
ORDER BY 
    p.conjunto";

$stmt = $conexao->prepare($query);
$stmt->bind_param("is", $id_cliente, $equipamento_pai);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Criar uma nova planilha
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Definir cabeçalhos
    $headers = [
        'A1' => 'Pedido',
        'B1' => 'Cliente',
        'C1' => 'Endereço',
        'D1' => 'Data Entrega',
        'E1' => 'Equipamento Pai',
        'F1' => 'Conjunto',
        'G1' => 'Tag',
        'H1' => 'Lote',
        'I1' => 'Quantidade',
        'J1' => 'Observações',
        'K1' => 'Data Engenharia',
        'L1' => 'Data Início Prog.',
        'M1' => 'Data Fim Prog.',
        'N1' => 'Qtd. Impressão'
    ];

    // Aplicar cabeçalhos
    foreach ($headers as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }

    // Estilo para cabeçalhos
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '4CAF50'],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
            ],
        ],
    ];

    $sheet->getStyle('A1:N1')->applyFromArray($headerStyle);

    // Preencher dados
    $row = 2;
    while ($data = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $row, $data['pedido']);
        $sheet->setCellValue('B' . $row, $data['cliente']);
        $sheet->setCellValue('C' . $row, $data['endereco']);
        $sheet->setCellValue('D' . $row, date('d/m/Y', strtotime($data['data_entrega'])));
        $sheet->setCellValue('E' . $row, $data['equipamento_pai']);
        $sheet->setCellValue('F' . $row, $data['conjunto']);
        $sheet->setCellValue('G' . $row, $data['tag']);
        $sheet->setCellValue('H' . $row, $data['lote']);
        $sheet->setCellValue('I' . $row, $data['quantidade_prod']);
        $sheet->setCellValue('J' . $row, $data['obs_detalhes']);
        $sheet->setCellValue('K' . $row, $data['data_engenharia'] ? date('d/m/Y H:i', strtotime($data['data_engenharia'])) : '');
        $sheet->setCellValue('L' . $row, $data['data_prog_fim'] ? date('d/m/Y H:i', strtotime($data['data_prog_fim'])) : '');
        $sheet->setCellValue('M' . $row, $data['data_programacao'] ? date('d/m/Y H:i', strtotime($data['data_programacao'])) : '');
        $sheet->setCellValue('N' . $row, '1');
        $row++;
    }

    // Ajustar largura das colunas automaticamente
    foreach (range('A', 'N') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Estilo para as células de dados
    $dataStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
            ],
        ],
        'alignment' => [
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
    ];
    $sheet->getStyle('A2:N' . ($row - 1))->applyFromArray($dataStyle);

    // Criar o arquivo Excel
    $writer = new Xlsx($spreadsheet);
    $filename = 'dados_produto.xlsx';
    
    // Remove o arquivo existente se houver
    if (file_exists($filename)) {
        unlink($filename);
    }
    
    $writer->save($filename);

    // Exibir mensagem para o usuário
    echo "Arquivo Excel atualizado com sucesso! Agora, iniciando a impressão...";

    // Caminho do layout da etiqueta no BarTender
    $layoutPath = "C:\\xampp\\htdocs\\CADASTRO\\ETIQUETAS\\ETIQUETA_KANBAN_PRODUCAO.btw";

    // Comando para executar a impressão via BarTender
    $cmd = "start /D \"C:\\Program Files (x86)\\Seagull\\BarTender\" Bartend.exe \"$layoutPath\" /P";
    exec($cmd, $output, $return_var);

    // Verificar se houve erro na impressão
    if ($return_var !== 0) {
        echo "Erro ao imprimir a etiqueta.";
    } else {
        echo "Etiqueta enviada para a impressora com sucesso.";
    }

} else {
    die("Dados não encontrados.");
}
?>
