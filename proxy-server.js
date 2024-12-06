import express from 'express';
import fetch from 'node-fetch';  
import http from 'http';
import { WebSocketServer } from 'ws';  // Importando a classe WebSocketServer

const app = express();
const server = http.createServer(app);

// Inicializando o WebSocket com a nova forma de instanciá-lo
const wss = new WebSocketServer({ server });

const PORT = 3000;

app.get('/proxy/features', async (req, res) => {
    try {
        const response = await fetch('https://d2iv4x4t7ozxv2.cloudfront.net/features.json');
        const data = await response.json();
        res.set('Access-Control-Allow-Origin', '*');
        res.json(data);
    } catch (error) {
        res.status(500).send('Erro ao buscar dados');
    }
});

wss.on('connection', (ws) => {
    console.log('WebSocket conectado');
    ws.on('message', (message) => {
        console.log('Mensagem recebida: ', message);
        ws.send('Resposta do servidor WebSocket');
    });
});

server.listen(PORT, () => {
    console.log(`Proxy ativo em http://localhost:${PORT}`);
});
