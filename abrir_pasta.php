<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once('protect.php');

try {
    // Verifica se o usuário está logado
    if (!isset($_SESSION['id'])) {
        throw new Exception('Você deve estar logado para acessar esta página.');
    }

    // Verifica se o usuário tem permissão
    $role = $_SESSION['role'] ?? 'guest';
    if (!in_array($role, ['admin', 'editor'])) {
        throw new Exception('Você não tem permissão para acessar esta funcionalidade.');
    }

    if (!isset($_GET['path'])) {
        throw new Exception('Caminho não especificado');
    }

    $caminho = trim(urldecode($_GET['path']));
    
    // Registra informações de diagnóstico
    error_log("=== Diagnóstico de Acesso a Pasta ===");
    error_log("Usuário: " . $_SESSION['id'] . " (Role: " . $role . ")");
    error_log("Caminho recebido: " . $caminho);

    // Normaliza o caminho mantendo caracteres especiais necessários
    $caminho = str_replace('/', '\\', $caminho);
    
    // Remove caracteres perigosos mas mantém os necessários
    $caminho = str_replace(['<', '>', '"', "'", '|', '?', '*'], '', $caminho);
    
    // Verifica se é um caminho UNC válido
    if (!preg_match('/^\\\\\\\\[^\\\\]+.*$/', $caminho)) {
        $caminho = '\\\\' . ltrim($caminho, '\\');
    }

    error_log("Caminho final: " . $caminho);

    // Redireciona para o caminho UNC
    header("Location: " . $caminho);
    exit;

} catch (Exception $e) {
    error_log("Erro em abrir_pasta.php: " . $e->getMessage());
    error_log("Caminho recebido: " . ($caminho ?? 'não definido'));
    
    $statusCode = 500;
    if (strpos($e->getMessage(), 'logado') !== false) {
        $statusCode = 401;
    } else if (strpos($e->getMessage(), 'permissão') !== false) {
        $statusCode = 403;
    }
    
    http_response_code($statusCode);
    echo $e->getMessage();
}
