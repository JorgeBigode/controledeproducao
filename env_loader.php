<?php

function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception("O arquivo .env não foi encontrado");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
        }
    }
}

// Carrega as variáveis de ambiente
try {
    loadEnv(__DIR__ . '/.env');
} catch (Exception $e) {
    error_log("Erro ao carregar arquivo .env: " . $e->getMessage());
    die("Erro na configuração do sistema. Por favor, contate o administrador.");
}
