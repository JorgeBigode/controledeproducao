<?php
include_once('config.php');
include('protect.php');

// Verificar se a tabela existe e criar se necessário
$check_table = "SHOW TABLES LIKE 'config_impressao'";
$table_exists = $conexao->query($check_table)->num_rows > 0;

if (!$table_exists) {
    $create_table = "CREATE TABLE config_impressao (
        id INT PRIMARY KEY,
        margem_topo DECIMAL(5,2) DEFAULT 4.5,
        margem_esquerda DECIMAL(5,2) DEFAULT 0,
        largura_etiqueta DECIMAL(5,2) DEFAULT 101,
        altura_etiqueta DECIMAL(5,2) DEFAULT 75,
        rotacao INT DEFAULT 0,
        temperatura INT DEFAULT 10,
        velocidade INT DEFAULT 3,
        densidade INT DEFAULT 7
    )";
    
    $conexao->query($create_table);
    
    // Inserir configuração padrão
    $insert_default = "INSERT INTO config_impressao (id, margem_topo, margem_esquerda, largura_etiqueta, altura_etiqueta, rotacao, temperatura, velocidade, densidade)
                      VALUES (1, 4.5, 0, 101, 75, 0, 10, 3, 7)";
    $conexao->query($insert_default);
} else {
    // Verificar se a coluna rotacao existe
    $check_column = "SHOW COLUMNS FROM config_impressao LIKE 'rotacao'";
    $column_exists = $conexao->query($check_column)->num_rows > 0;
    
    if (!$column_exists) {
        // Adicionar a coluna rotacao
        $add_column = "ALTER TABLE config_impressao ADD COLUMN rotacao INT DEFAULT 0";
        $conexao->query($add_column);
    }
    
    // Verificar se a coluna temperatura existe
    $check_column = "SHOW COLUMNS FROM config_impressao LIKE 'temperatura'";
    $column_exists = $conexao->query($check_column)->num_rows > 0;
    
    if (!$column_exists) {
        // Adicionar a coluna temperatura
        $add_column = "ALTER TABLE config_impressao ADD COLUMN temperatura INT DEFAULT 10";
        $conexao->query($add_column);
    }
    
    // Verificar se a coluna velocidade existe
    $check_column = "SHOW COLUMNS FROM config_impressao LIKE 'velocidade'";
    $column_exists = $conexao->query($check_column)->num_rows > 0;
    
    if (!$column_exists) {
        // Adicionar a coluna velocidade
        $add_column = "ALTER TABLE config_impressao ADD COLUMN velocidade INT DEFAULT 3";
        $conexao->query($add_column);
    }
    
    // Verificar se a coluna densidade existe
    $check_column = "SHOW COLUMNS FROM config_impressao LIKE 'densidade'";
    $column_exists = $conexao->query($check_column)->num_rows > 0;
    
    if (!$column_exists) {
        // Adicionar a coluna densidade
        $add_column = "ALTER TABLE config_impressao ADD COLUMN densidade INT DEFAULT 7";
        $conexao->query($add_column);
    }
}

// Se foi enviado um POST, atualizar as configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $margem_topo = $_POST['margem_topo'];
    $margem_esquerda = $_POST['margem_esquerda'];
    $largura_etiqueta = $_POST['largura_etiqueta'];
    $altura_etiqueta = $_POST['altura_etiqueta'];
    $rotacao = $_POST['rotacao'];
    $temperatura = $_POST['temperatura'];
    $velocidade = $_POST['velocidade'];
    $densidade = $_POST['densidade'];

    $sql = "UPDATE config_impressao SET 
            margem_topo = ?, 
            margem_esquerda = ?, 
            largura_etiqueta = ?, 
            altura_etiqueta = ?,
            rotacao = ?,
            temperatura = ?,
            velocidade = ?,
            densidade = ?
            WHERE id = 1";
            
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("ddddiiii", 
        $margem_topo, 
        $margem_esquerda, 
        $largura_etiqueta, 
        $altura_etiqueta,
        $rotacao,
        $temperatura,
        $velocidade,
        $densidade
    );
    $stmt->execute();
    
    header("Location: config_impressao.php");
    exit();
}

// Buscar configurações atuais
$sql = "SELECT * FROM config_impressao WHERE id = 1";
$result = $conexao->query($sql);
$config = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Configurar Impressão</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .config-form {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .preview {
            border: 1px solid #ccc;
            padding: 20px;
            margin-top: 20px;
            position: relative;
            width: 210mm;
            height: 297mm;
            background: white;
            transform: scale(0.3);
            transform-origin: top left;
            margin-bottom: -210mm;
        }
        .preview-etiqueta {
            position: absolute;
            border: 2px solid black;
            background: white;
            padding: 5px;
            box-sizing: border-box;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: inline-block;
            width: 150px;
        }
        input[type="number"] {
            width: 100px;
        }
        .buttons {
            margin-top: 20px;
        }
        button {
            padding: 10px 20px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <h1>Configurar Impressão</h1>
    
    <form method="post" class="config-form">
        <div class="form-group">
            <label>Margem Superior (mm):</label>
            <input type="number" step="0.1" name="margem_topo" value="<?php echo $config['margem_topo']; ?>" oninput="updatePreview()">
        </div>
        
        <div class="form-group">
            <label>Margem Esquerda (mm):</label>
            <input type="number" step="0.1" name="margem_esquerda" value="<?php echo $config['margem_esquerda']; ?>" oninput="updatePreview()">
        </div>
        
        <div class="form-group">
            <label>Largura da Etiqueta (mm):</label>
            <input type="number" step="0.1" name="largura_etiqueta" value="<?php echo $config['largura_etiqueta']; ?>" oninput="updatePreview()">
        </div>
        
        <div class="form-group">
            <label>Altura da Etiqueta (mm):</label>
            <input type="number" step="0.1" name="altura_etiqueta" value="<?php echo $config['altura_etiqueta']; ?>" oninput="updatePreview()">
        </div>
        
        <div class="form-group">
            <label>Rotação (graus):</label>
            <input type="number" step="90" name="rotacao" value="<?php echo $config['rotacao']; ?>" oninput="updatePreview()">
        </div>
        
        <div class="form-group">
            <label>Temperatura (1-15):</label>
            <input type="number" name="temperatura" min="1" max="15" value="<?php echo $config['temperatura']; ?>">
            <small>Recomendado: 10 - Ajuste maior para impressão mais escura</small>
        </div>
        
        <div class="form-group">
            <label>Velocidade (1-4):</label>
            <input type="number" name="velocidade" min="1" max="4" value="<?php echo $config['velocidade']; ?>">
            <small>Recomendado: 3 - Velocidade de impressão (1 mais lento, 4 mais rápido)</small>
        </div>
        
        <div class="form-group">
            <label>Densidade (1-15):</label>
            <input type="number" name="densidade" min="1" max="15" value="<?php echo $config['densidade']; ?>">
            <small>Recomendado: 7 - Ajuste a densidade da impressão</small>
        </div>
        
        <div class="buttons">
            <button type="submit">Salvar</button>
            <button type="button" onclick="window.open('print_etiqueta_teste.php', '_blank')">Teste de Impressão</button>
            <button type="button" onclick="window.close()">Fechar</button>
        </div>
    </form>
    
    <div class="preview">
        <div class="preview-etiqueta">
            PREVIEW DA ETIQUETA
        </div>
    </div>
    
    <script>
        function updatePreview() {
            const etiqueta = document.querySelector('.preview-etiqueta');
            const margemTopo = document.querySelector('input[name="margem_topo"]').value;
            const margemEsquerda = document.querySelector('input[name="margem_esquerda"]').value;
            const largura = document.querySelector('input[name="largura_etiqueta"]').value;
            const altura = document.querySelector('input[name="altura_etiqueta"]').value;
            const rotacao = document.querySelector('input[name="rotacao"]').value;
            
            etiqueta.style.width = largura + 'mm';
            etiqueta.style.height = altura + 'mm';
            etiqueta.style.marginTop = margemTopo + 'mm';
            etiqueta.style.marginLeft = margemEsquerda + 'mm';
            etiqueta.style.transform = `rotate(${rotacao}deg)`;
        }
        
        // Inicializar preview
        updatePreview();
    </script>
</body>
</html>
