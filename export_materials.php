<?php
// Limpa qualquer saída anterior
ob_clean();
ob_start();

require 'vendor/autoload.php';
include_once('config.php');
include('protect.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

try {
    // Verifica se foi fornecido um ID de equipamento
    if (!isset($_GET['equipment_id'])) {
        throw new Exception('ID do equipamento não fornecido');
    }

    $equipment_id = intval($_GET['equipment_id']);

    // Array com nomes dos equipamentos
    $equipment_names = [
        2 => 'TR-80',
        22 => 'TR-100',
        21 => 'TR-60'
    ];

    // Nome do equipamento
    $equipment_name = $equipment_names[$equipment_id] ?? 'Equipamento ' . $equipment_id;

    // Cria uma nova planilha
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Define o título das colunas
    $sheet->setCellValue('A1', 'Material');
    $sheet->setCellValue('B1', 'Quantidade');
    $sheet->setCellValue('C1', 'UN/KG');

    // Estilo para o cabeçalho
    $sheet->getStyle('A1:C1')->getFont()->setBold(true);
    $sheet->getStyle('A1:C1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    // Busca os materiais
    $query = "SELECT * FROM materiais_equipamento WHERE idequipamento = ? ORDER BY tipo_material ASC";
    $stmt = $conexao->prepare($query);
    $stmt->bind_param("i", $equipment_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Linha atual
    $row = 2;

    // Adiciona os dados
    while ($data = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $row, $data['tipo_material']);
        $sheet->setCellValue('B' . $row, $data['quantidade']);
        $sheet->setCellValue('C' . $row, $data['quantidade_total']);
        
        // Centraliza as células de quantidade
        $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        $row++;
    }

    // Ajusta a largura das colunas
    $sheet->getColumnDimension('A')->setWidth(30);
    $sheet->getColumnDimension('B')->setWidth(15);
    $sheet->getColumnDimension('C')->setWidth(15);

    // Cria um nome de arquivo temporário
    $temp_file = tempnam(sys_get_temp_dir(), 'excel_');
    
    // Salva o arquivo
    $writer = new Xlsx($spreadsheet);
    $writer->save($temp_file);

    // Verifica se o arquivo foi criado
    if (!file_exists($temp_file)) {
        throw new Exception('Erro ao criar arquivo temporário');
    }

    // Limpa qualquer saída anterior
    ob_clean();
    ob_start();

    // Headers para download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="lista_materiais_' . $equipment_name . '.xlsx"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Lê e envia o arquivo
    readfile($temp_file);
    
    // Remove o arquivo temporário
    unlink($temp_file);
    
    exit();

} catch (Exception $e) {
    // Em caso de erro, exibe uma mensagem amigável
    header('Content-Type: text/html; charset=utf-8');
    echo "Erro ao gerar o arquivo Excel: " . $e->getMessage();
    exit();
}
