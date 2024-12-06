// server.php
require dirname(__DIR__) . '/vendor/autoload.php'; // Ajuste o caminho conforme necessário

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop\Factory;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

class NotificationServer implements MessageComponentInterface {
    public function onOpen(ConnectionInterface $conn) {
        echo "Nova conexão: ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        echo "Mensagem recebida: $msg\n";
        // Enviar a mensagem para todos os clientes conectados
        foreach ($from->httpRequest->getHeaders() as $header) {
            $from->send("Nova notificação: $msg");
        }
    }

    public function onClose(ConnectionInterface $conn) {
        echo "Conexão fechada: ({$conn->resourceId})\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Erro: {$e->getMessage()}\n";
        $conn->close();
    }
}

$loop = Factory::create();
$server = IoServer::factory(
    new WsServer(
        new NotificationServer()
    ),
    8080, // Porta do servidor WebSocket
    $loop
);

echo "Servidor WebSocket em execução na porta 8080\n";
$server->run();
// server.php
require dirname(__DIR__) . '/vendor/autoload.php'; // Ajuste o caminho conforme necessário

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop\Factory;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

class NotificationServer implements MessageComponentInterface {
    public function onOpen(ConnectionInterface $conn) {
        echo "Nova conexão: ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        echo "Mensagem recebida: $msg\n";
        // Enviar a mensagem para todos os clientes conectados
        foreach ($from->httpRequest->getHeaders() as $header) {
            $from->send("Nova notificação: $msg");
        }
    }

    public function onClose(ConnectionInterface $conn) {
        echo "Conexão fechada: ({$conn->resourceId})\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Erro: {$e->getMessage()}\n";
        $conn->close();
    }
}

$loop = Factory::create();
$server = IoServer::factory(
    new WsServer(
        new NotificationServer()
    ),
    8080, // Porta do servidor WebSocket
    $loop
);

echo "Servidor WebSocket em execução na porta 8080\n";
$server->run();
// server.php
require dirname(__DIR__) . '/vendor/autoload.php'; // Ajuste o caminho conforme necessário

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop\Factory;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

class NotificationServer implements MessageComponentInterface {
    public function onOpen(ConnectionInterface $conn) {
        echo "Nova conexão: ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        echo "Mensagem recebida: $msg\n";
        // Enviar a mensagem para todos os clientes conectados
        foreach ($from->httpRequest->getHeaders() as $header) {
            $from->send("Nova notificação: $msg");
        }
    }

    public function onClose(ConnectionInterface $conn) {
        echo "Conexão fechada: ({$conn->resourceId})\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Erro: {$e->getMessage()}\n";
        $conn->close();
    }
}

$loop = Factory::create();
$server = IoServer::factory(
    new WsServer(
        new NotificationServer()
    ),
    8080, // Porta do servidor WebSocket
    $loop
);

echo "Servidor WebSocket em execução na porta 8080\n";
$server->run();
