<?php
// Conexao com o banco de dados
include('config.php');

// Verifica se o ID foi passado
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Consulta para buscar os dados existentes
    $sql = "SELECT * FROM pedidos_tr WHERE idpedidos_tr = $id";
    $resultado = mysqli_query($conexao, $sql);
    
    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $dados = mysqli_fetch_assoc($resultado);
    } else {
        echo "Registro nao encontrado.";
        exit;
    }
} else {
    echo "ID nao fornecido.";
    exit;
}

// Atualizar os dados se o formulario for enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $modelo = $_POST['modelo'];
    $montagem = $_POST['montagem'];
    $frete = $_POST['frete'];
    $status = $_POST['status'];
    $frequencia = $_POST['frequencia'];
    $bica = $_POST['bica'];
    $n_serie = $_POST['n_serie'];
    $observacao = $_POST['observacao'];
    
    // Atualiza os dados na tabela
    $sql = "UPDATE pedidos_tr SET
            modelo = '$modelo',
            montagem = '$montagem',
            frete = '$frete',
            status = '$status',
            frequencia = '$frequencia',
            bica = '$bica',
            n_serie = '$n_serie',
            observacao = '$observacao'
            WHERE idpedidos_tr = $id";
    
    if (mysqli_query($conexao, $sql)) {
        echo "Dados atualizados com sucesso.";
        header("Location: trilhadeira.php"); // Redireciona de volta para a pagina principal
        exit;
    } else {
        echo "Erro ao atualizar os dados: " . mysqli_error($conexao);
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="img/icon-SILO.ico" type="image/x-icon">
    <title>Editar Pedido</title>
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
            max-width: 800px;
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
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            transition: border-color 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52,152,219,0.25);
        }

        .btn-container {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn-submit {
            background-color: #2ecc71;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            flex: 1;
            transition: background-color 0.3s ease;
        }

        .btn-submit:hover {
            background-color: #27ae60;
        }

        .btn-voltar {
            background-color: #3498db;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            flex: 1;
            transition: background-color 0.3s ease;
        }

        .btn-voltar:hover {
            background-color: #2980b9;
            color: white;
            text-decoration: none;
        }

        .observacao-field {
            min-height: 100px;
            resize: vertical;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin: 20px;
            }

            .btn-container {
                flex-direction: column;
            }

            .btn-submit, .btn-voltar {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="form-title">Editar Pedido</h2>
        <form method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Modelo:</label>
                        <input type="text" class="form-control" name="modelo" 
                               value="<?php echo htmlspecialchars($dados['modelo']); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Nº de Série:</label>
                        <input type="text" class="form-control" name="n_serie" 
                               value="<?php echo htmlspecialchars($dados['n_serie']); ?>">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Montagem:</label>
                        <select class="form-select" name="montagem">
                            <option value="">Selecione uma montagem</option>
                            <option value="SMA" <?php echo ($dados['montagem'] == 'SMA') ? 'selected' : ''; ?>>SMA</option>
                            <option value="CLIENTE" <?php echo ($dados['montagem'] == 'CLIENTE') ? 'selected' : ''; ?>>CLIENTE</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Frete:</label>
                        <select class="form-select" name="frete">
                            <option value="">Selecione um frete</option>
                            <option value="SMA" <?php echo ($dados['frete'] == 'SMA') ? 'selected' : ''; ?>>SMA</option>
                            <option value="CLIENTE" <?php echo ($dados['frete'] == 'CLIENTE') ? 'selected' : ''; ?>>CLIENTE</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Status:</label>
                        <select class="form-select" name="status">
                            <option value="">Selecione um status</option>
                            <option value="AGUARDANDO" <?php echo ($dados['status'] == 'AGUARDANDO') ? 'selected' : ''; ?>>AGUARDANDO</option>
                            <option value="PROGRAMACAO" <?php echo ($dados['status'] == 'PROGRAMACAO') ? 'selected' : ''; ?>>PROGRAMAÇÃO</option>
                            <option value="PRODUCAO" <?php echo ($dados['status'] == 'PRODUCAO') ? 'selected' : ''; ?>>PRODUÇÃO</option>
                            <option value="PATIO" <?php echo ($dados['status'] == 'PATIO') ? 'selected' : ''; ?>>PÁTIO</option>
                            <option value="ENTREGUE" <?php echo ($dados['status'] == 'ENTREGUE') ? 'selected' : ''; ?>>ENTREGUE</option>
                            <option value="CANCELADA" <?php echo ($dados['status'] == 'CANCELADA') ? 'selected' : ''; ?>>CANCELADA</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Frequência:</label>
                        <select class="form-select" name="frequencia">
                            <option value="">Selecione uma frequência</option>
                            <option value="50Hz" <?php echo ($dados['frequencia'] == '50Hz') ? 'selected' : ''; ?>>50Hz</option>
                            <option value="60Hz" <?php echo ($dados['frequencia'] == '60Hz') ? 'selected' : ''; ?>>60Hz</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Bica:</label>
                <select class="form-select" name="bica">
                    <option value="">Selecione uma bica</option>
                    <option value="BICA SIMPLES" <?php echo ($dados['bica'] == 'BICA SIMPLES') ? 'selected' : ''; ?>>BICA SIMPLES</option>
                    <option value="BICA DUPLA" <?php echo ($dados['bica'] == 'BICA DUPLA') ? 'selected' : ''; ?>>BICA DUPLA</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Observação:</label>
                <textarea class="form-control observacao-field" name="observacao"><?php echo htmlspecialchars($dados['observacao']); ?></textarea>
            </div>

            <div class="btn-container">
                <a href="trilhadeira.php" class="btn-voltar">Voltar</a>
                <button type="submit" class="btn-submit">Salvar Alterações</button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
