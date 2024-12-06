<?php
require 'vendor/autoload.php';
include_once('config.php');
include('protect.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

verificarAcesso();

if (isset($_POST['multiplicador'])) {
    $multiplicador = floatval($_POST['multiplicador']);
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Cabeçalhos
    $sheet->setCellValue('A1', 'Material');
    $sheet->setCellValue('B1', 'Quantidade');
    $sheet->setCellValue('C1', 'Quantidade Total');
    $sheet->setCellValue('D1', 'Quantidade Multiplicada');
    
    // Estilo para cabeçalhos
    $headerStyle = [
        'font' => ['bold' => true],
        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => 'CCCCCC']],
    ];
    $sheet->getStyle('A1:D1')->applyFromArray($headerStyle);
    
    // Buscar dados
    $idequipamento = isset($_POST['idequipamento']) ? intval($_POST['idequipamento']) : 0;
    $sql = "SELECT * FROM materiais_equipamento WHERE idequipamento = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $idequipamento);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $row = 2;
    while ($material = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $row, $material['tipo_material']);
        $sheet->setCellValue('B' . $row, $material['quantidade']);
        $sheet->setCellValue('C' . $row, $material['quantidade_total']);
        $sheet->setCellValue('D' . $row, $material['quantidade_total'] * $multiplicador);
        $row++;
    }
    
    // Auto-size colunas
    foreach (range('A', 'D') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Criar arquivo Excel
    $writer = new Xlsx($spreadsheet);
    
    // Configurar headers para download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="lista_materiais_equipamento.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer->save('php://output');
    exit;
}

// Redirecionar de volta se não houver multiplicador
header('Location: material.php');
exit;
?>
