const WebSocket = require('ws');
const { exec } = require('child_process');

// Criar um servidor WebSocket na porta 8080
const wss = new WebSocket.Server({ port: 8080 });

console.log('Servidor WebSocket iniciado na porta 8080');

// Armazenar todas as conexões ativas
const clients = new Set();

wss.on('connection', function connection(ws) {
    console.log('Nova conexão estabelecida');
    clients.add(ws);

    // Quando receber uma mensagem
    ws.on('message', function incoming(message) {
        try {
            const data = JSON.parse(message);
            console.log('Mensagem recebida:', data);

            if (data.type === 'openFolder') {
                const path = data.path;
                console.log('Abrindo pasta:', path);
                
                // Comando para abrir a pasta no Windows
                const command = `explorer "${path}"`;
                
                exec(command, (error, stdout, stderr) => {
                    if (error) {
                        console.error(`Erro ao executar comando: ${error}`);
                        ws.send(JSON.stringify({
                            type: 'error',
                            message: `Erro ao abrir pasta: ${error.message}`
                        }));
                        return;
                    }
                    ws.send(JSON.stringify({
                        type: 'success',
                        message: 'Pasta aberta com sucesso'
                    }));
                });
            }
        } catch (error) {
            console.error('Erro ao processar mensagem:', error);
            ws.send(JSON.stringify({
                type: 'error',
                message: `Erro ao processar mensagem: ${error.message}`
            }));
        }
    });

    // Quando a conexão for fechada
    ws.on('close', function close() {
        console.log('Cliente desconectado');
        clients.delete(ws);
    });

    // Tratar erros
    ws.on('error', function error(err) {
        console.error('Erro na conexão WebSocket:', err);
        clients.delete(ws);
    });
});
