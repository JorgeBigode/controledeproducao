<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $pdfPath = $uploadDir . basename($_FILES['pdf']['name']);
        if (move_uploaded_file($_FILES['pdf']['tmp_name'], $pdfPath)) {
            echo "PDF salvo com sucesso em: " . $pdfPath;
        } else {
            echo "Erro ao salvar o PDF.";
        }
    } else {
        echo "Nenhum PDF foi enviado.";
    }
}
