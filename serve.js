const WebSocket = require('ws');
const wss = new WebSocket.Server({ port: 8080 });

console.log('Servidor WebSocket iniciado na porta 8080');

// Armazena todas as conexões ativas
const clients = new Set();

wss.on('connection', function connection(ws) {
    console.log('Nova conexão estabelecida');
    clients.add(ws);

    ws.on('close', () => {
        console.log('Conexão fechada');
        clients.delete(ws);
    });

    ws.on('error', (error) => {
        console.error('Erro na conexão WebSocket:', error);
    });
});

// Função para enviar notificação para todos os clientes conectados
function broadcastNotification(data) {
    clients.forEach(client => {
        if (client.readyState === WebSocket.OPEN) {
            client.send(JSON.stringify(data));
        }
    });
}

// Exporta a função para uso em outros arquivos
module.exports = {
    broadcastNotification
};
