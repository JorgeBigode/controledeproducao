<?php
include_once('config.php');
include('protect.php');
include('menu.php');
verificarAcesso();

if (isset($_POST['submit'])) {
    // Coleta dos dados para inserção
    $pedido = $_POST['pedido'] ?? null;
    $cliente = $_POST['client'] ?? null;
    $endereco = $_POST['local'] ?? null;
    $data_entrega = $_POST['data_entrega'] ?? null;
    $produtos = $_POST['produtos'] ?? [];

    if ($pedido && $cliente && $endereco && $data_entrega) {
        $sql_cliente = "INSERT INTO cliente (pedido, cliente, endereco, data_entrega) VALUES (?, ?, ?, ?)";
        $stmt_cliente = $conexao->prepare($sql_cliente);

        if (!$stmt_cliente) {
            die("Erro na preparação da consulta cliente: " . $conexao->error);
        }

        $stmt_cliente->bind_param("ssss", $pedido, $cliente, $endereco, $data_entrega);

        if ($stmt_cliente->execute()) {
            // Redireciona para index.php após a inserção
            echo "<script>location.href='pedido.php?status=success';</script>";
            exit(); // Importante sair após o redirecionamento para evitar execução posterior
        } else {
            echo "Erro ao inserir dados do cliente: " . $stmt_cliente->error . "<br>";
        }

        $stmt_cliente->close();
    }   
}

    $sql_cliente_query = "SELECT * FROM cliente ORDER BY idcliente DESC";
    $result_cliente = $conexao->query($sql_cliente_query);

    if (isset($_POST['submit_edit'])) {
        $pedido_id = $_POST['pedido_id'] ?? null;
        $cliente = $_POST['edit_cliente'] ?? null;
        $local = $_POST['edit_local'] ?? null;
        $data_entrega = $_POST['edit_data_entrega'] ?? null;
      
        if ($pedido_id && $cliente && $local && $data_entrega) {
          $sql_update = "UPDATE cliente SET cliente = ?, endereco = ?, data_entrega = ? WHERE pedido = ?";
          $stmt_update = $conexao->prepare($sql_update);
      
          if (!$stmt_update) {
            die("Erro na preparação da consulta: " . $conexao->error);
          }
      
          $stmt_update->bind_param("ssss", $cliente, $local, $data_entrega, $pedido_id);
      
          if ($stmt_update->execute()) {
            if (isset($_FILES['pdf'])) {
              $pdf = $_FILES['pdf'];
              $pdf_name = $pdf['name'];
              $pdf_tmp_name = $pdf['tmp_name'];
              $pdf_size = $pdf['size'];
              $pdf_error = $pdf['error'];
        
              if ($pdf_error === 0) {
                $pdf_dst = 'pdfs/' . $pdf_name;
                move_uploaded_file($pdf_tmp_name, $pdf_dst);
        
                // Armazena o caminho do arquivo PDF no banco de dados
                $sql = "UPDATE cliente SET pdf = '$pdf_dst' WHERE pedido = $_POST[pedido_id]";
                $conexao->query($sql);
              }
            }
      
            echo "<script>location.href='pedido.php?status=success';</script>";
            exit();
          } else {
            echo "Erro ao atualizar dados do cliente: " . $stmt_update->error . "<br>";
          }
      
          $stmt_update->close();
        }
      }
      

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCP</title>
    <style>
    .table thead th {
        background-color: #f2f2f2;
        color: #333;
        font-weight: bold;
        padding: 5px 15px;
        text-align: center;
        text-transform: uppercase;
    }

    tr {
        text-align: center;
        text-transform: uppercase;
    }

    tr:nth-child(even) {
        background-color: #888;
        color: black;
    }
    </style>
</head>

<body>
    <main>
        <div class="m-6">
            <table class="table text-white table-bg">
                <thead>
                    <tr>
                        <th scope="col">Pedido</th>
                        <th scope="col">Cliente</th>
                        <th scope="col">Localidade</th>
                        <th scope="col">Data</th>
                        <th scope="col">PDF</th>
                        <th scope="col">...</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
    if ($result_cliente && $result_cliente->num_rows > 0) {
        while ($user_data = $result_cliente->fetch_assoc()) {
            echo "<tr class='pedido-detalhe'>";  // Adicione a classe pedido-detalhe aqui
            echo "<td>" . htmlspecialchars($user_data['pedido']) . "</td>";
            echo "<td>" . htmlspecialchars($user_data['cliente']) . "</td>";
            echo "<td>" . htmlspecialchars($user_data['endereco']) . "</td>";
            $dataOriginal = $user_data['data_entrega'];
            $data = new DateTime($dataOriginal);
            $dataFormatada = $data->format('d/m/Y');
            echo "<td>" . htmlspecialchars($dataFormatada) . "</td>";
            echo "<td><a href='baixar_pdf.php?id=" . $user_data['idcliente'] . "' target='_blank'><svg xmlns='(link unavailable)' width='16' height='16' fill='currentColor' class='bi bi-filetype-pdf' viewBox='0 0 16 16'> <path fill-rule='evenodd' d='M14 4.5V14a2 2 0 0 1-2 2h-1v-1h1a1 1 0 0 0 1-1V4.5h-2A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v9H2V2a2 2 0 0 1 2-2h5.5zM1.6 11.85H0v3.999h.791v-1.342h.803q.43 0 .732-.173.305-.175.463-.474a1.4 1.4 0 0 0 .161-.677q0-.375-.158-.677a1.2 1.2 0 0 0-.46-.477q-.3-.18-.732-.179m.545 1.333a.8.8 0 0 1-.085.38.57.57 0 0 1-.238.241.8.8 0 0 1-.375.082H.788V12.48h.66q.327 0 .512.181.185.183.185.522m1.217-1.333v3.999h1.46q.602 0 .998-.237a1.45 1.45 0 0 0 .595-.689q.196-.45.196-1.084 0-.63-.196-1.075a1.43 1.43 0 0 0-.589-.68q-.396-.234-1.005-.234zm.791.645h.563q.371 0 .609.152a.9.9 0 0 1 .354.454q.118.302.118.753a2.3 2.3 0 0 1-.068.592 1.1 1.1 0 0 1-.196.422.8.8 0 0 1-.334.252 1.3 1.3 0 0 1-.483.082h-.563zm3.743 1.763v1.591h-.79V11.85h2.548v.653H7.896v1.117h1.606v.638z'/> Visualizar PDF</svg></a></td>";
            echo "<td>
                <a class='btn btn-sm btn-primary btn-edit' data-id='" . htmlspecialchars($user_data['pedido']) . "' 
                data-cliente='" . htmlspecialchars($user_data['cliente']) . "' 
                data-local='" . htmlspecialchars($user_data['endereco']) . "' 
                data-data='" . htmlspecialchars($user_data['data_entrega']) . "'>
                <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-pencil-square' viewBox='0 0 16 16'>
                    <path d='M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z'/>
                    <path fill-rule='evenodd' d='M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z'/>
                </svg>
                </a>
            </td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='5'>Nenhum cliente encontrado para este pedido.</td></tr>";
    }
    ?>
                </tbody>
            </table>
        </div>
    </main>
    <div class="modal fade" id="pedidoModal" tabindex="-1" aria-labelledby="pedidoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pedidoModalLabel">Novo Pedido</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="pedido.php" method="POST">
                        <fieldset>
                            <div class="form-group">
                                <label for="pedido" class="form-label">PEDIDO</label>
                                <input type="text" name="pedido" id="pedido" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label for="client" class="form-label">NOME DO CLIENTE</label>
                                <input type="text" name="client" id="client" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label for="local" class="form-label">LOCALIDADE</label>
                                <input type="text" name="local" id="local" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label for="data_entrega" class="form-label">DATA DE ENTREGA</label>
                                <input type="date" name="data_entrega" id="data_entrega" class="form-control" required>
                            </div>
                        </fieldset>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                            <button type="submit" name="submit" id="submit" class="btn btn-primary">Salvar</button>
                        </div>
                    </form>
                </div>


            </div>
        </div>
    </div>
    <!-- Modal de Edição -->
    <div class="modal fade" id="editPedidoModal" tabindex="-1" aria-labelledby="editPedidoModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPedidoModalLabel">Editar Pedido</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="pedido.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="pedido_id" id="edit_pedido_id">
                        <div class="form-group">
                            <label for="edit_cliente" class="form-label">NOME DO CLIENTE</label>
                            <input type="text" name="edit_cliente" id="edit_cliente" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_local" class="form-label">LOCALIDADE</label>
                            <input type="text" name="edit_local" id="edit_local" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_data_entrega" class="form-label">DATA DE ENTREGA</label>
                            <input type="date" name="edit_data_entrega" id="edit_data_entrega" class="form-control"
                                required>
                        </div>
                        <div class="form-group">
                            <label for="pdf" class="form-label">Vincular PDF</label>
                            <input type="file" name="pdf" id="pdf" class="form-control">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                            <button type="submit" name="submit_edit" class="btn btn-primary">Salvar mudanças</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    const buscarBox = document.querySelector('.buscar-box');
    const inputBuscar = document.querySelector('.input-buscar');
    const btnFechar = document.querySelector('.btn-fechar');
    const lupaBuscar = document.querySelector('.lupa-buscar');

    function ativarBuscar() {
        buscarBox.classList.add('ativar');
        inputBuscar.style.display = 'flex';
        btnFechar.style.display = 'flex';

        const circuloContainers = document.querySelectorAll('.circulo-container');
        circuloContainers.forEach(container => {
            const span = container.querySelector('span');
            span.style.display = 'none';
        });
    }

    function desativarBuscar() {
        buscarBox.classList.remove('ativar');
        inputBuscar.style.display = 'none';
        btnFechar.style.display = 'none';

        const circuloContainers = document.querySelectorAll('.circulo-container');
        circuloContainers.forEach(container => {
            const span = container.querySelector('span');
            span.style.display = 'inline';
        });
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
        const rows = document.querySelectorAll(".pedido-detalhe");
        rows.forEach(row => {
            const textContent = row.textContent.toLowerCase();
            if (textContent.includes(searchValue)) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });
    }

    lupaBuscar.addEventListener('click', ativarBuscar);
    btnFechar.addEventListener('click', desativarBuscar);

    document.addEventListener('DOMContentLoaded', function() {
        const editButtons = document.querySelectorAll('.btn-edit');

        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Preenche os campos do modal de edição com os dados do cliente
                document.getElementById('edit_pedido_id').value = this.getAttribute('data-id');
                document.getElementById('edit_cliente').value = this.getAttribute(
                    'data-cliente');
                document.getElementById('edit_local').value = this.getAttribute('data-local');
                document.getElementById('edit_data_entrega').value = this.getAttribute(
                    'data-data');

                // Abre o modal
                $('#editPedidoModal').modal('show');
            });
        });
    });
    </script>
</body>

</html>