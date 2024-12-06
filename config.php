<?php
require_once __DIR__ . '/env_loader.php';

// Função para log de erros personalizado
if (!function_exists('logError')) {
    function logError($message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message";
        
        if (!empty($context)) {
            $logMessage .= " Context: " . json_encode($context);
        }
        
        error_log($logMessage . PHP_EOL, 3, __DIR__ . '/logs/error.log');
    }
}

try {
    // Obtém as credenciais do ambiente
    $dbHost = getenv('DB_HOST');
    $dbUsername = getenv('DB_USERNAME');
    $dbPassword = getenv('DB_PASSWORD');
    $dbName = getenv('DB_NAME');
    
    // Verifica se todas as variáveis necessárias estão definidas
    if (!$dbHost || !$dbUsername || !$dbName) {
        throw new Exception("Configurações de banco de dados incompletas");
    }
    
    // Cria a conexão com o banco de dados
    $conexao = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
    
    // Verifica erros de conexão
    if ($conexao->connect_error) {
        throw new Exception("Erro de conexão com o banco de dados: " . $conexao->connect_error);
    }
    
    // Define o charset para utf8
    if (!$conexao->set_charset("utf8")) {
        throw new Exception("Erro ao configurar charset: " . $conexao->error);
    }
    
} catch (Exception $e) {
    // Log do erro
    logError($e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    // Em ambiente de desenvolvimento, mostra mensagem detalhada
    if (getenv('APP_DEBUG') === 'true') {
        die("Erro: " . $e->getMessage());
    } else {
        // Em produção, mostra mensagem genérica
        die("Erro interno do sistema. Por favor, contate o administrador.");
    }
}
