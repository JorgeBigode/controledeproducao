<?php
require_once('protect.php');

header('Content-Type: application/json');

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comando'])) {
        $comando = $_POST['comando'];
        
        // Verifica se é um comando explorer válido
        if (preg_match('/^explorer\s+"([^"]+)"$/', $comando, $matches)) {
            $caminho = $matches[1];
            
            // Remove o /select para abrir a pasta diretamente
            $comando = 'explorer "' . $caminho . '"';
            
            // Executa o comando
            exec($comando . " 2>&1", $output, $return_var);
            
            if ($return_var === 0) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Erro ao executar comando',
                    'details' => implode("\n", $output)
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Comando inválido'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Requisição inválida'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Acesso não permitido'
    ]);
}
?>
