<?php
include_once('config.php');
include('protect.php');
include('menu.php');
verificarAcesso();


if (isset($_POST['submitequip'])) {
    $equipamento_pai = $_POST['equip'] ?? null;

    // Verificar se já existe um equipamento com o mesmo equipamento_pai
    $sql_check = "SELECT COUNT(*) FROM equipamento WHERE equipamento_pai = ?";
    if ($stmt_check = $conexao->prepare($sql_check)) {
        $stmt_check->bind_param("s", $equipamento_pai);
        $stmt_check->execute();
        $stmt_check->bind_result($count);
        $stmt_check->fetch();
        $stmt_check->close();

        if ($count > 0) {
            echo "Erro: Este equipamento já está cadastrado.";
        } else {
            // Inserir novo equipamento
            $sql_insert = "INSERT INTO equipamento (equipamento_pai) VALUES (?)";
            if ($stmt = $conexao->prepare($sql_insert)) {
                $stmt->bind_param("s", $equipamento_pai);
                if ($stmt->execute()) {
                    echo "<script>location.href='cadastro.php?status=success';</script>";
                    exit();
                } else {
                    echo "Erro ao executar a consulta: " . $stmt->error;
                }
                $stmt->close();
            } else {
                echo "Erro na preparação da consulta: " . $conexao->error;
            }
        }
    } else {
        echo "Erro na verificação de equipamento existente: " . $conexao->error;
    }
}

if (isset($_POST['submit'])) {
    $conjunto = $_POST['conj'] ?? null;

    // Verificar se já existe um produto com o mesmo conjunto
    $sql_check = "SELECT COUNT(*) FROM produto WHERE conjunto = ?";
    if ($stmt_check = $conexao->prepare($sql_check)) {
        $stmt_check->bind_param("s", $conjunto);
        $stmt_check->execute();
        $stmt_check->bind_result($count);
        $stmt_check->fetch();
        $stmt_check->close();

        if ($count > 0) {
            echo "Erro: Este conjunto já está cadastrado.";
        } else {
            // Inserir novo produto
            $sql_insert = "INSERT INTO produto (conjunto) VALUES (?)";
            if ($stmt = $conexao->prepare($sql_insert)) {
                $stmt->bind_param("s", $conjunto);
                if ($stmt->execute()) {
                    echo "<script>location.href='cadastro.php?status=success';</script>";
                    exit();
                } else {
                    echo "Erro ao executar a consulta: " . $stmt->error;
                }
                $stmt->close();
            } else {
                echo "Erro na preparação da consulta: " . $conexao->error;
            }
        }
    } else {
        echo "Erro na verificação de produto existente: " . $conexao->error;
    }
}

    if (isset($_POST['submitEdit'])) {
        $idproduto = $_POST['idproduto'] ?? null;
        $conjunto = $_POST['conj'] ?? null;
    
        // Validação dos dados aqui...
    
        $sql_update = "UPDATE produto SET conjunto = ? WHERE idproduto = ?";
        
        // Prepare a consulta
        if ($stmt = $conexao->prepare($sql_update)) {
            // Bind dos parâmetros
            $stmt->bind_param("si", $conjunto, $idproduto);
            
            // Executa a consulta
            if ($stmt->execute()) {
                // Redireciona para a mesma página sem dados POST
                header("Location: cadastro.php?status=success");
                exit();
            } else {
                echo "Erro ao executar a consulta: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Erro na preparação da consulta: " . $conexao->error;
        }
    }
    
    if (isset($_POST['submitEditEquip'])) {
        $idequipamento = $_POST['idequipamento'] ?? null;
        $equipamento_pai = $_POST['equip'] ?? null;
    
        // Validação dos dados aqui...
    
        $sql_update = "UPDATE equipamento SET equipamento_pai = ? WHERE idequipamento = ?";
        
        // Prepare a consulta
        if ($stmt = $conexao->prepare($sql_update)) {
            // Bind dos parâmetros
            $stmt->bind_param("si", $equipamento_pai, $idequipamento);
            
            // Executa a consulta
            if ($stmt->execute()) {
                // Redireciona para a mesma página sem dados POST
                header("Location: cadastro.php?status=success");
                exit();
            } else {
                echo "Erro ao executar a consulta: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Erro na preparação da consulta: " . $conexao->error;
        }
    }
    
// Consulta para exibir equipamentos
$sql_equipamento_query = "SELECT * FROM equipamento ORDER BY idequipamento DESC";
$result_equipamento = $conexao->query($sql_equipamento_query);

$sql_produto_query = "SELECT * FROM produto ORDER BY idproduto DESC";
$result_produto = $conexao->query($sql_produto_query);


$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <link rel="icon" href="img/icon-SILO.ico" type="image/x-icon">
    <link rel="stylesheet" href="./css/style_cadas.css">
    <title>Cadastro</title>
</head>

<body>
    <div class="modal fade" id="modalEquipamento" tabindex="-1" aria-labelledby="modalEquipamentoLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEquipamentoLabel">REGISTRO DE EQUIPAMENTO PAI</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="cadastro.php" method="POST">
                        <div class="form-group">
                            <label for="conj">EQUIPAMENTO PAI</label>
                            <input type="text" name="equip" id="equip" class="form-control">
                        </div>
                        <button type="submit" name="submitequip" class="btn btn-primary">Salvar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modalConjunto" tabindex="-1" aria-labelledby="modalConjuntoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalConjuntoLabel">REGISTRO DE CONJUNTO</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="cadastro.php" method="POST">
                        <div class="form-group">
                            <label for="conj">CONJUNTO</label>
                            <input type="text" name="conj" id="conj" class="form-control">
                        </div>
                        <button type="submit" name="submit" class="btn btn-primary">Salvar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <main>
        <div class="tabelas-container">
            <table class="table text-white table-bg">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">EQUIPAMENTO</th>
                        <th scope="col">...</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
    while ($user_data = mysqli_fetch_assoc($result_equipamento)) {
        echo "<tr data-id='" . htmlspecialchars($user_data['idequipamento']) . "'>";
        echo "<td>" . htmlspecialchars($user_data['idequipamento']) . "</td>";
        echo "<td class='equipamento'>" . htmlspecialchars($user_data['equipamento_pai']) . "</td>";
        echo "<td>
        <button class='btn btn-primary btn-sm' data-toggle='modal' data-target='#modalEditEquipamento' onclick='editarEquipamento(" . htmlspecialchars($user_data['idequipamento']) . ")'>
            <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-pencil-square'>
                <path d='M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z'/>
                <path fill-rule='evenodd' d='M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z'/>
            </svg>
        </button>
        </td>";
        echo "</tr>";
    }
    ?>
                </tbody>

            </table>
            <table class="table text-white table-bg">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">CONJUNTO</th>
                        <th scope="col">...</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
    while ($user_data = mysqli_fetch_assoc($result_produto)) {
        echo "<tr data-id='" . htmlspecialchars($user_data['idproduto']) . "'>";
        echo "<td>" . htmlspecialchars($user_data['idproduto']) . "</td>";
        echo "<td class='conjunto'>" . htmlspecialchars($user_data['conjunto']) . "</td>";
        echo "<td>
        <button class='btn btn-primary btn-sm' data-toggle='modal' data-target='#modalEditProduto' onclick='editarProduto(" . htmlspecialchars($user_data['idproduto']) . ")'>
            <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-pencil-square'>
                <path d='M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z'/>
                <path fill-rule='evenodd' d='M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z'/>
            </svg>
        </button>
        </td>";
        echo "</tr>";
    }
    ?>
                </tbody>

            </table>
        </div>

        <!-- Modal para Editar Equipamento -->
        <div class="modal fade" id="modalEditEquipamento" tabindex="-1" aria-labelledby="modalEditEquipamentoLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEditEquipamentoLabel">EDITAR EQUIPAMENTO</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="formEditEquipamento" method="POST">
                            <input type="hidden" name="idequipamento" id="idequipamentoField">
                            <div class="form-group">
                                <label for="editEquip">EQUIPAMENTO</label>
                                <input type="text" name="equip" id="equipamento" class="form-control">
                            </div>
                            <button type="submit" name="submitEditEquip" class="btn btn-primary">Salvar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para Editar Conjunto -->
        <div class="modal fade" id="modalEditProduto" tabindex="-1" aria-labelledby="modalEditProdutoLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEditProdutoLabel">EDITAR CONJUNTO</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="formEditProduto" method="POST">
                            <input type="hidden" name="idproduto" id="editProdutoId">
                            <div class="form-group">
                                <label for="editConj">CONJUNTO</label>
                                <input type="text" name="conj" id="editConj" class="form-control">
                            </div>
                            <button type="submit" name="submitEdit" class="btn btn-primary">Salvar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <script>
    const buscarBox = document.querySelector('.buscar-box');
    const inputBuscar = document.querySelector('.input-buscar');
    const btnFechar = document.querySelector('.btn-fechar');
    const lupaBuscar = document.querySelector('.lupa-buscar');

    function ativarBuscar() {
        buscarBox.classList.add('ativar');
        inputBuscar.style.display = 'flex';
        btnFechar.style.display = 'flex';
    }

    function desativarBuscar() {
        buscarBox.classList.remove('ativar');
        inputBuscar.style.display = 'none';
        btnFechar.style.display = 'none';
    }

    function clearSearch() {
        const searchInput = document.getElementById("searchInput");
        searchInput.value = "";
        filterTable("");
    }

    document.getElementById("searchInput").addEventListener("input", function() {
        const searchValue = this.value.toLowerCase();
        filterTable(searchValue);
    });

    document.querySelector('.btn-fechar').addEventListener('click', function() {
        const searchInput = document.getElementById("searchInput");
        searchInput.value = "";
        filterTable("");
    });

    function filterTable(searchValue) {
        const rows = document.querySelectorAll("table tbody tr");
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            let found = false;
            
            cells.forEach(cell => {
                if (cell.textContent.toLowerCase().includes(searchValue)) {
                    found = true;
                }
            });
            
            row.style.display = found ? "" : "none";
        });
    }

    lupaBuscar.addEventListener('click', ativarBuscar);
    btnFechar.addEventListener('click', desativarBuscar);

    function editarProduto(id) {
        const linha = document.querySelector(`tr[data-id="${id}"]`);
        const conjunto = linha.querySelector(".conjunto").textContent;
        document.getElementById("idprodutoField").value = id;
        document.getElementById("editConj").value = conjunto;
    }

    function editarEquipamento(id) {
        const linha = document.querySelector(`tr[data-id="${id}"]`);
        const equipamento = linha.querySelector(".equipamento").textContent;
        document.getElementById("idequipamentoField").value = id;
        document.getElementById("equipamento_pai").value = equipamento;
    }
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>