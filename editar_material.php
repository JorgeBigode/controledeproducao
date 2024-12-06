<?php
include_once('config.php');
include('protect.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);

verificarAcesso();

if (!isset($_GET['id'])) {
    header('Location: inserir_material.php');
    exit();
}

$id = $_GET['id'];

// Buscar dados do material
$sql = "SELECT * FROM materiais WHERE idmateriais = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$material = $resultado->fetch_assoc();

if (!$material) {
    header('Location: inserir_material.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_material = $_POST['tipo_material'];
    $dimensao = $_POST['dimensao'];
    $un_medida = $_POST['un_medida'];
    $quant = str_replace(',', '.', $_POST['quant']); // Converte vírgula para ponto

    $sql = "UPDATE materiais SET tipo_material = ?, dimensão = ?, un_medida = ?, quant = ? WHERE idmateriais = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("sssdi", $tipo_material, $dimensao, $un_medida, $quant, $id); // Mudado para 'd' para decimal

    if ($stmt->execute()) {
        header("Location: inserir_material.php");
        exit();
    } else {
        echo "Erro ao atualizar material: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Material</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 40px auto;
        }
        .form-title {
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
            font-weight: 600;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            font-weight: 500;
            color: #34495e;
        }
        .btn-container {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .btn-submit {
            background-color: #2ecc71;
            color: white;
            flex: 1;
        }
        .btn-submit:hover {
            background-color: #27ae60;
            color: white;
        }
        .btn-cancel {
            background-color: #95a5a6;
            color: white;
        }
        .btn-cancel:hover {
            background-color: #7f8c8d;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="form-title">Editar Material</h2>
        <form method="POST">
            <div class="form-group">
                <label for="tipo_material" class="form-label">Tipo de Material:</label>
                <input type="text" class="form-control" name="tipo_material" id="tipo_material" 
                       value="<?php echo htmlspecialchars($material['tipo_material']); ?>" required>
            </div>

            <div class="form-group">
                <label for="dimensao" class="form-label">Dimensão:</label>
                <input type="text" class="form-control" name="dimensao" id="dimensao" 
                       value="<?php echo htmlspecialchars($material['dimensão']); ?>" required>
            </div>

            <div class="form-group">
                <label for="un_medida" class="form-label">Unidade de Medida:</label>
                <input type="text" class="form-control" name="un_medida" id="un_medida" 
                       value="<?php echo htmlspecialchars($material['un_medida']); ?>" required>
            </div>

            <div class="form-group">
                <label for="quant" class="form-label">Quantidade:</label>
                <input type="number" class="form-control" name="quant" id="quant" step="0.01" min="0" 
                       value="<?php echo htmlspecialchars($material['quant']); ?>" required>
            </div>

            <div class="btn-container">
                <a href="inserir_material.php" class="btn btn-cancel">Cancelar</a>
                <button type="submit" class="btn btn-submit">Salvar</button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
