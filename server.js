const WebSocket = require('ws');
const mysql = require('mysql');

// Configuração da conexão com o banco de dados
const db = mysql.createConnection({
  host: 'localhost',
  user: 'root',
  password: '',
  database: 'formulario_cadastro'
});

db.connect((err) => {
  if (err) {
    console.error('Erro ao conectar ao banco de dados:', err);
    return;
  }
  console.log('Conectado ao banco de dados com sucesso!');
});

const wss = new WebSocket.Server({ port: 8080 });

wss.on('connection', (ws) => {
  console.log('Novo cliente conectado');

  ws.on('message', (message) => {
    try {
      const data = JSON.parse(message);
      
      if (data.type === 'openFolder') {
        const { exec } = require('child_process');
        const path = data.path.replace(/"/g, '\\"');  // Escape quotes in path
        
        exec(`explorer "${path}"`, (error, stdout, stderr) => {
          if (error) {
            console.error(`Erro ao abrir pasta: ${error}`);
            ws.send(JSON.stringify({
              type: 'error',
              message: 'Erro ao abrir a pasta'
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
    }
  });

  // Consulta a tabela log_notificacoes para obter os dados
  db.query('SELECT * FROM log_notificacoes', (err, results) => {
    if (err) {
      console.error('Erro ao consultar a tabela log_notificacoes:', err);
      return;
    }

    // Envia os dados para o cliente WebSocket
    ws.send(JSON.stringify(results));
  });

  // Lidar com o fechamento da conexão
  ws.on('close', () => {
    console.log('Cliente desconectado');
  });
});

console.log('Servidor WebSocket rodando em ws://localhost:8080');