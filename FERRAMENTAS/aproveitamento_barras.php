<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Otimização de Corte de Material</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="icon-SILO.ico" type="image/x-icon">

    <style>
    .cutting-diagram {
        border: 1px solid #ccc;
        margin: 10px 0;
        padding: 10px;
    }

    .piece {
        background-color: #007bff;
        color: white;
        margin: 2px;
        padding: 5px;
        display: inline-block;
    }

    .waste {
        background-color: #dc3545;
        color: white;
        margin: 2px;
        padding: 5px;
        display: inline-block;
    }
    </style>
</head>

<body>
    <div class="container mt-5">
    <div class="line" style="display: flex; align-items: center; gap: 20px;">
    <h1 class="mb-4">Otimização de Corte de Material</h1>
    <a href="http://localhost/CADASTRO/obra.php"
        style="padding: 10px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none;">
        VOLTAR
    </a>
</div>

        <form method="POST" class="mb-4">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="materialLength" class="form-label">Comprimento do Material (mm)</label>
                        <input type="number" class="form-control" id="materialLength" name="materialLength" required
                            value="<?php echo isset($_POST['materialLength']) ? $_POST['materialLength'] : '6000'; ?>">
                    </div>
                </div>
            </div>

            <div id="piecesContainer">
                <h3>Peças Necessárias</h3>
                <div class="row piece-row mb-2">
                    <div class="col-md-4">
                        <input type="number" class="form-control" name="lengths[]" placeholder="Comprimento (mm)"
                            required>
                    </div>
                    <div class="col-md-4">
                        <input type="number" class="form-control" name="quantities[]" placeholder="Quantidade" required>
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-secondary mb-3" onclick="addPieceRow()">Adicionar Mais Peças</button>
            <button type="submit" class="btn btn-primary mb-3">Calcular Otimização</button>
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $materialLength = $_POST['materialLength'];
            $pieces = [];
            
            // Organizar as peças
            foreach ($_POST['lengths'] as $key => $length) {
                $quantity = $_POST['quantities'][$key];
                for ($i = 0; $i < $quantity; $i++) {
                    $pieces[] = $length;
                }
            }
            
            // Ordenar as peças em ordem decrescente
            rsort($pieces);
            
            $bars = [];
            $currentBar = [];
            $currentLength = 0;
            
            // Algoritmo First-Fit Decreasing
            foreach ($pieces as $piece) {
                if ($currentLength + $piece <= $materialLength) {
                    $currentBar[] = $piece;
                    $currentLength += $piece;
                } else {
                    if (!empty($currentBar)) {
                        $bars[] = [
                            'pieces' => $currentBar,
                            'remaining' => $materialLength - $currentLength
                        ];
                    }
                    $currentBar = [$piece];
                    $currentLength = $piece;
                }
            }
            
            if (!empty($currentBar)) {
                $bars[] = [
                    'pieces' => $currentBar,
                    'remaining' => $materialLength - $currentLength
                ];
            }
            
            // Exibir resultados
            echo "<h3 class='mt-4'>Resultado da Otimização</h3>";
            echo "<p>Barras necessárias: " . count($bars) . "</p>";
            
            $totalWaste = 0;
            foreach ($bars as $index => $bar) {
                $totalWaste += $bar['remaining'];
                echo "<div class='cutting-diagram'>";
                echo "<h4>Barra " . ($index + 1) . "</h4>";
                foreach ($bar['pieces'] as $piece) {
                    echo "<div class='piece'>" . $piece . "mm</div>";
                }
                if ($bar['remaining'] > 0) {
                    echo "<div class='waste'>" . $bar['remaining'] . "mm (sobra)</div>";
                }
                echo "</div>";
            }
            
            $efficiency = 100 - ($totalWaste / ($materialLength * count($bars)) * 100);
            echo "<p>Eficiência total: " . number_format($efficiency, 2) . "%</p>";
            echo "<p>Desperdício total: " . $totalWaste . "mm</p>";
        }
        ?>
    </div>

    <script>
    function addPieceRow() {
        const container = document.getElementById('piecesContainer');
        const newRow = document.createElement('div');
        newRow.className = 'row piece-row mb-2';
        newRow.innerHTML = `
                <div class="col-md-4">
                    <input type="number" class="form-control" name="lengths[]" placeholder="Comprimento (mm)" required>
                </div>
                <div class="col-md-4">
                    <input type="number" class="form-control" name="quantities[]" placeholder="Quantidade" required>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-danger" onclick="this.parentElement.parentElement.remove()">Remover</button>
                </div>
            `;
        container.appendChild(newRow);
    }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>