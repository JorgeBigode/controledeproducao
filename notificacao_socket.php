<?php
// notificacao_socket.php

set_time_limit(0);  // Para o script continuar executando

// Inclua o arquivo de configuração com a conexão ao banco de dados
require_once 'config.php';  // Certifique-se de que o caminho para o config.php está correto

// Verifique se a conexão foi estabelecida
if (!$conexao) {
    error_log("Erro ao conectar ao banco de dados.");
    die("Erro ao conectar ao banco de dados."); // Interrompe a execução caso a conexão falhe
}

// Defina o endereço e a porta do servidor WebSocket
$host = 'localhost';
$port = 8080;

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_bind($socket, $host, $port);
socket_listen($socket);

echo "Servidor WebSocket iniciado. Aguardando conexões...\n";

// Função para enviar notificações para todos os clientes conectados
function enviarNotificacao($clientes, $mensagem) {
    foreach ($clientes as $cliente) {
        socket_write($cliente, $mensagem, strlen($mensagem));
    }
}

$clientes = [];  // Array para armazenar os sockets dos clientes

// Função para buscar novas notificações no banco usando MySQLi
function buscarNovasNotificacoes() {
    global $conexao;  // Variável de conexão vinda do config.php

    // Buscar notificações não enviadas (status = 'pendente') da tabela log_notificacoes
    $query = "SELECT id_log, tipo, tabela, id_registro, data_alteracao, status_producao FROM log_notificacoes WHERE status_producao = 'pendente'";
    $stmt = $conexao->prepare($query);

    if (!$stmt) {
        error_log("Erro ao preparar a consulta: " . $conexao->error);
        return [];
    }

    $stmt->execute();
    $stmt->bind_result($id_log, $tipo, $tabela, $id_registro, $data_alteracao, $status_producao);

    $notificacoes = [];
    while ($stmt->fetch()) {
        $notificacoes[] = [
            'id_log' => $id_log,
            'tipo' => $tipo,
            'tabela' => $tabela,
            'id_registro' => $id_registro,
            'data_alteracao' => $data_alteracao,
            'status_producao' => $status_producao
        ];
    }

    $stmt->close();  // Feche o statement após o uso

    return $notificacoes;
}

while (true) {
    // Aceitar novas conexões
    $novo_cliente = socket_accept($socket);
    $clientes[] = $novo_cliente;

    // Ler dados dos clientes conectados
    $dados = socket_read($novo_cliente, 1024);

    // Verificar se a leitura tem dados
    if ($dados) {
        // Se houver dados, podemos enviar notificações
        $notificacoes = buscarNovasNotificacoes();

        foreach ($notificacoes as $notificacao) {
            $mensagem = json_encode($notificacao);

            // Enviar a notificação para todos os clientes conectados
            enviarNotificacao($clientes, $mensagem);
        }

        // Após enviar as notificações, atualize o status para 'enviado'
        $updateQuery = "UPDATE log_notificacoes SET status_producao = 'enviado' WHERE status_producao = 'pendente'";
        $updateStmt = $conexao->prepare($updateQuery);
        $updateStmt->execute();
    }

    // Fechar a conexão com o cliente se ele desconectar
    foreach ($clientes as $key => $cliente) {
        if (!@socket_read($cliente, 1024)) {
            unset($clientes[$key]);
            socket_close($cliente);
        }
    }
}

// Fechar o servidor quando o script for encerrado
socket_close($socket);
?>
