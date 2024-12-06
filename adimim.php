<?php
include_once('config.php'); 
include('protect.php');
verificarAcesso();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="icon" href="img/icon-SILO.ico" type="image/x-icon">
    <title>Document</title>
</head>
<body>
    <a href="registro_usuario.php">Registro de Usuario</a>
    <a href="gestao_setores.php">Processo de Produção</a>
    <button onclick="window.history.back();">Voltar</button>
</body>
</html>