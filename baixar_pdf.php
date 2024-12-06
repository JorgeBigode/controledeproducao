<?php
include_once('config.php');
// Verifica se o ID do cliente foi passado como parâmetro
if (isset($_GET['id'])) {
    $id_cliente = $_GET['id'];
  
    // Consulta ao banco de dados para obter o caminho do arquivo PDF
    $sql = "SELECT pdf FROM cliente WHERE idcliente = $id_cliente";
    $resultado = $conexao->query($sql);
  
    // Verifica se o resultado da consulta foi obtido com sucesso
    if ($resultado->num_rows > 0) {
      $linha = $resultado->fetch_assoc();
      $caminho_pdf = $linha['pdf'];
  
      // Verifica se o arquivo PDF existe no servidor
      if (file_exists($caminho_pdf)) {
        // Exibe o PDF no navegador
        header("Content-Type: application/pdf");
        readfile($caminho_pdf);
      } else {
        echo "Erro: O arquivo PDF não existe no servidor.";
      }
    } else {
      echo "Erro: Não foi possível obter o caminho do arquivo PDF.";
    }
  } else {
    echo "Erro: O ID do cliente não foi passado como parâmetro.";
  }
  
  // Fecha a conexão com o banco de dados
  $conexao->close();
  ?>