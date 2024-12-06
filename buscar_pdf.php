<?php
// Captura os parâmetros 'conjunto' e 'equipamento' da URL
$conjunto = isset($_GET['conjunto']) ? $_GET['conjunto'] : null;
$equipamento = isset($_GET['equipamento']) ? $_GET['equipamento'] : null;

// Inicializa o caminho do PDF como vazio
$caminho_pdf = "";

// Função para extrair o número no formato correto
function extrairNumeracao($string) {
    // Tenta encontrar o padrão 000.000.0000
    if (preg_match('/(\d{3}\.\d{3}\.\d{4})/', $string, $matches)) {
        return $matches[1];
    }
    // Tenta encontrar o padrão 000.00.0000
    else if (preg_match('/(\d{3}\.\d{2}\.\d{4})/', $string, $matches)) {
        return $matches[1];
    }
    return null;
}

// Verifica se o parâmetro 'conjunto' foi fornecido
if ($conjunto) {
    $numeracao = extrairNumeracao($conjunto);
    if ($numeracao) {
        // Construir o caminho do PDF com base na numeração extraída
        $caminho_pdf = "\\\\smasrv2\\SMA_PROJETOS\\PCP2\\1-PDFs\\" . $numeracao . ".pdf";
    }
}

// Se 'equipamento' foi fornecido e não 'conjunto', use o equipamento
if (!$conjunto && $equipamento) {
    $numeracao = extrairNumeracao($equipamento);
    if ($numeracao) {
        // Construa o caminho do PDF com base na numeração extraída
        $caminho_pdf = "\\\\smasrv2\\SMA_PROJETOS\\PCP2\\1-PDFs\\" . $numeracao . ".pdf";
    }
}

// Verifica se o PDF existe
if ($caminho_pdf && file_exists($caminho_pdf)) {
    // Exibir o PDF no navegador
    header('Content-type: application/pdf');
    header('Content-Disposition: inline; filename="' . basename($caminho_pdf) . '"');
    readfile($caminho_pdf);
} else {
    echo "Erro: PDF não encontrado.";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="img/icon-SILO.ico" type="image/x-icon">
    <title>Visualizar PDF</title>
</head>
<body>

<!-- Container para o visualizador de PDF -->
<div id="pdf-viewer"></div>

<script>
// Função para extrair a numeração correta
function extrairNumeracao(string) {
    // Tenta encontrar o padrão 000.000.0000 ou 000.00.0000
    const pattern1 = /(\d{3}\.\d{3}\.\d{4})/;
    const pattern2 = /(\d{3}\.\d{2}\.\d{4})/;
    
    let match = string.match(pattern1) || string.match(pattern2);
    return match ? match[1] : null;
}

// Função para carregar o PDF no visualizador
function carregarPDF(url) {
    document.getElementById('pdf-viewer').innerHTML = `<embed src="${url}" width="800" height="600" type="application/pdf">`;
}

// Adiciona evento de clique nos links com classe 'pdf-link'
document.querySelectorAll('.pdf-link').forEach(function(link) {
    link.addEventListener('click', function(event) {
        event.preventDefault(); // Previne o comportamento padrão do link

        // Pega o href do link
        let href = link.getAttribute('href');

        // Checa se o parâmetro 'conjunto' está presente
        if (href.includes('conjunto=')) {
            let conjunto = href.split('conjunto=')[1];
            let numeracao = extrairNumeracao(conjunto);
            if (numeracao) {
                let url = `http://localhost/CADASTRO/exibir_pdf.php?conjunto=${encodeURIComponent(numeracao)}.pdf`;
                carregarPDF(url);
            }
        }
        // Checa se o parâmetro 'equipamento' está presente
        else if (href.includes('equipamento=')) {
            let equipamento = href.split('equipamento=')[1];
            let numeracao = extrairNumeracao(equipamento);
            if (numeracao) {
                let url = `http://localhost/CADASTRO/exibir_pdf.php?equipamento=${encodeURIComponent(numeracao)}.pdf`;
                carregarPDF(url);
            }
        }
    });
});
</script>

</body>
</html>
