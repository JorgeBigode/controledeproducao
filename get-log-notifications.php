<?php
include_once('config.php'); 

// Configura o cabeçalho para JSON e habilita CORS
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// Verifica se o ID da última notificação foi passado
$lastNotificationId = isset($_GET['lastNotificationId']) ? (int)$_GET['lastNotificationId'] : 0;

// Consulta para buscar notificações com ID maior que o último ID
$sql = "SELECT * FROM log_notificacoes WHERE id_log > ? ORDER BY data_alteracao DESC LIMIT 10";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $lastNotificationId); // Previne SQL Injection
$stmt->execute();
$result = $stmt->get_result();

$notificacoes = [];

if ($result->num_rows > 0) {
    while ($linha = $result->fetch_assoc()) {
        $notificacoes[] = $linha;
    }
}

// Retorna as notificações como JSON
echo json_encode($notificacoes);
?>
