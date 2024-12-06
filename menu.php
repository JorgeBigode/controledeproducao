<?php
include('config.php');

$paginaAtual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link rel="icon" href="img/icon-SILO.ico" type="image/x-icon">
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        background-image: linear-gradient(to right, rgb(20, 147, 22), rgb(17, 54, 7));
    }

    header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: #333;
        padding: 10px;
        color: white;
    }

    a {
        color: inherit;
    }


    #logo img {
        max-width: 100px;
    }

    #openMenu {
        font-size: 24px;
        background: none;
        border: none;
        color: white;
        cursor: pointer;
    }

    #menu {
        height: 100%;
        width: 250px;
        position: fixed;
        top: 0;
        left: -250px;
        background-color: #111;
        overflow-x: hidden;
        transition: 0.3s;
        padding-top: 60px;
        z-index: 2000;
    }

    #menu a {
        padding: 10px 15px;
        text-decoration: none;
        font-size: 18px;
        color: white;
        display: block;
        transition: 0.3s;
    }

    #menu a:hover {
        background-color: #575757;
    }

    #closeMenu {
        position: absolute;
        top: 0;
        right: 15px;
        font-size: 36px;
        background: none;
        border: none;
        color: white;
        cursor: pointer;
    }

    .submenu {
        padding: 10px;
    }

    .submenu-content {
        padding-left: 20px;
    }

    .buscar-box.ativar {
        width: 300px;
        height: 30px;
        margin-right: 5px;

    }

    .buscar-box.ativar .lupa-buscar {
        height: 30px;
    }

    .buscar-box .lupa-buscar {
        min-width: 5px;
        height: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .buscar-box .input-buscar {
        position: relative;
        width: calc(100% - 120px);
        height: 100%;
    }

    .buscar-box .input-buscar input {
        border: 0;
        outline: 0;
        font-size: 20px;
        height: 100%;
        width: 100%;
        padding-right: 40px;
    }

    .btn-fechar {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        display: none;
    }

    .buscar-box.ativar .btn-fechar {
        display: flex;
    }



    .buscar-box {
        width: 80px;
        height: 80px;
        overflow: hidden;
        display: flex;
        justify-content: space-between;
        position: relative;
        transition: width 0.5s;
    }
    </style>
</head>

<body>
    <header id="header-expandable" class="collapsed">
        <a href="inicio.php" id="logo"><img src="./img/logoSMA.png"></a>
        <?php if ($paginaAtual === 'material.php') : ?>
        <a href="inserir_material.php">Inserir</a>
        <a href="vincular_material_equipamento.php">Vincular</a>
        <?php endif; ?>
        <?php if ($paginaAtual === 'pedido.php') : ?>
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#pedidoModal">
            Adicionar Pedido
        </button>
        <?php endif; ?>
        <?php if ($paginaAtual === 'cadastro.php') : ?>
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalEquipamento">
            Adicionar Equip
        </button>
        <button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#modalConjunto">
            Adicionar Conj
        </button>
        <?php endif; ?>
        <?php if ($paginaAtual === 'pedido.php' || $paginaAtual === 'trilhadeira.php' || $paginaAtual === 'cadastro.php') : ?>
        <div class="buscar-box">
            <div class="lupa-buscar">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-search"
                    viewBox="0 0 16 16">
                    <path
                        d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0" />
                </svg>
            </div>

            <div class="input-buscar">
                <input type="text" id="searchInput" placeholder="Faça uma busca">
            </div>

            <div class="btn-fechar" onclick="clearSearch()">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="black" class="bi bi-x-circle"
                    viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />
                    <path
                        d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708" />
                </svg>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($paginaAtual === 'graficos.php') :?>
        <?php $clientes = buscarClientes($conexao); ?>
        <form action="graficos.php" method="GET">
            <div class="client-filter" style="margin: 20px;">
                <label for="clientSelect"></label>
                <select id="clientSelect">
                    <option value="all">Selecione o cliente</option>
                    <?php foreach ($clientes as $cliente) : ?>
                    <option value="<?php echo htmlspecialchars($cliente['idcliente']); ?>">
                        <?php echo htmlspecialchars($cliente['pedido']) . " - " . htmlspecialchars($cliente['cliente']) . " - " . htmlspecialchars($cliente['endereco']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
        <script>
        const chartData = <?php echo json_encode($clientes); ?>;
        const chartDataArray = Object.values(chartData);
        clientSelect.addEventListener('change', (e) => {
            const selectedClienteId = e.target.value;
            if (selectedClienteId === 'all') {
                showCliente(0); // Show the first client if 'all' is selected
            } else {
                const clienteIndex = chartDataArray.findIndex(cliente => cliente.idcliente ===
                    selectedClienteId);
                showCliente(clienteIndex);
            }
        });
        </script>
        <?php endif; ?>

        <?php if ($paginaAtual === 'trilhadeira.php') :?>
        <?php
                $statusSelecionado = isset($_GET['status']) ? $_GET['status'] : 'todos';

                $statusCountsQuery = "
                    SELECT 
                        LOWER(
                            REPLACE(
                                REPLACE(
                                    REPLACE(
                                        REPLACE(status, 'á', 'a'), 
                                        'ç', 'c'
                                    ), 
                                    'ã', 'a'
                                ), 
                                'õ', 'o'
                            )
                        ) as status, 
                        COUNT(*) as count 
                    FROM pedidos_tr 
                    GROUP BY status
                ";
                    $resultStatusCounts = $conexao->query($statusCountsQuery);
                
                    $statusCounts = [
                        'aguardando' => 0,
                        'programacao' => 0,
                        'producao' => 0,
                        'patio' => 0,
                        'entregue' => 0,
                        'cancelada' => 0
                    ];
                    
                    // Atualiza as contagens com os valores da consulta
                    if ($resultStatusCounts->num_rows > 0) {
                        while ($row = $resultStatusCounts->fetch_assoc()) {
                            $status = $row['status'];
                    
                            if (isset($statusCounts[$status])) {
                                $statusCounts[$status] = $row['count'];
                            }
                        }
                    }
                     ?>
        <div class="seta-toggle">
            <button id="toggleButton">⇨</button>
        </div>
        <div style="margin: 20px;">
        <form method="GET" style="display: inline-block;">
            <select name="ano" id="ano" style="padding: 5px; margin-right: 10px;">
                <option value="">Todos os Anos</option>
                <?php
                $anos_query = "SELECT DISTINCT YEAR(data_insercao) as ano FROM cliente ORDER BY ano DESC";
                $anos_result = $conexao->query($anos_query);
                while($ano = $anos_result->fetch_assoc()) {
                    $selected = (isset($_GET['ano']) && $_GET['ano'] == $ano['ano']) ? 'selected' : '';
                    echo "<option value='".$ano['ano']."' $selected>".$ano['ano']."</option>";
                }
                ?>
            </select>
            <button type="submit" style="padding: 5px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 3px; cursor: pointer;">Filtrar</button>
            <?php if(isset($_GET['ano']) && $_GET['ano'] != ''): ?>
                <a href="trilhadeira.php" style="padding: 5px 15px; background-color: #666; color: white; text-decoration: none; border-radius: 3px;">Limpar</a>
            <?php endif; ?>
        </form>
    </div>
        <div class="buttons-e-circulos">
            <button type='button' class='gerar-relatorio-btn' onclick='abrirModalAdicionar("")'>Pedido Tr</button>

            <button class="circulo-container" data-full-text="Aguardando" data-status="aguardando"
                style="color: white;">
                <div class="circulo-1">
                    <span class="count"><?= $statusCounts['aguardando']; ?></span>
                </div>
                <span>AGUARDANDO</span>
            </button>
            <button class="circulo-container" data-full-text="Programacao" data-status="programacao"
                style="color: white;">
                <div class="circulo-2">
                    <span class="count"><?= $statusCounts['programacao']; ?></span>
                </div>
                <span>PROGRAMAÇÃO</span>
            </button>
            <button class="circulo-container" data-full-text="Producao" data-status="producao" style="color: white;">
                <div class="circulo-3">
                    <span class="count"><?= $statusCounts['producao']; ?></span>
                </div>
                <span>PRODUÇÃO</span>
            </button>
            <button class="circulo-container" data-full-text="Patio" data-status="patio" style="color: white;">
                <div class="circulo-4">
                    <span class="count"><?= $statusCounts['patio']; ?></span>
                </div>
                <span>PATIO</span>
            </button>
            <button class="circulo-container" data-full-text="Entregue" data-status="entregue" style="color: white;">
                <div class="circulo-5">
                    <span class="count"><?= $statusCounts['entregue']; ?></span>
                </div>
                <span>ENTREGUE</span>
            </button>
            <button class="circulo-container" data-full-text="Cancelada" data-status="cancelada" style="color: white;">
                <div class="circulo-6">
                    <span class="count"><?= $statusCounts['cancelada']; ?></span>
                </div>
                <span>CANCELADA</span>
            </button>
        </div>
        <script>
        function inserirPedido() {
            const pedidoSelect = document.getElementById('pedidoSelect');
            const idCliente = pedidoSelect.value;

            if (!idCliente) {
                alert("Por favor, selecione um cliente.");
                return;
            }

            const data = {
                id_cliente: idCliente
            };

            fetch('salvar_detalhes_pedido.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Pedido inserido com sucesso!');

                        location.reload();
                    } else {
                        alert('Erro ao inserir pedido: ' + (data.error || data.message));
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao inserir pedido: ' + error.message);
                });
        }

        function abrirModalAdicionar(idCliente) {
            console.log("Abrindo modal para o cliente:", idCliente);
            document.getElementById("idtr_pedido_adicionar").value = idCliente;
            document.getElementById("modal-overlay").style.display = "flex";
        }

        function fecharModal() {
            console.log("Fechando modal");
            document.getElementById("modal-overlay").style.display = "none";
        }

        function salvarDetalhes() {
            const idtr_pedido = document.getElementById("idtr_pedido_adicionar").value;
            const modelo = document.getElementById("modelo").value;

            if (!idtr_pedido || !modelo) {
                alert("Erro: Todos os campos devem ser preenchidos.");
                return;
            }

            fetch('salvar_detalhes_pedido.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        idtr_pedido,
                        modelo
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message);
                        adicionarModeloNaLista(idtr_pedido, modelo);
                        fecharModal();
                    } else {
                        alert('Erro ao salvar detalhes: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao salvar detalhes: ' + error.message);
                });
        }

        function salvarVinculo() {
            const idCliente = document.getElementById("clienteSelect").value;
            const modelo = document.getElementById("modeloSelect").value;

            if (!idCliente || !modelo) {
                alert("Por favor, selecione um cliente e um modelo.");
                return;
            }

            // Log dos valores
            console.log('ID Cliente:', idCliente, 'Modelo:', modelo);

            fetch('salvar_vinculo.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        idCliente,
                        modelo
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        adicionarLinhaAoCliente(idCliente, modelo);
                        fecharModal();
                        location.reload(); // Recarrega a página após salvar o vínculo
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                });
        }

        function adicionarLinhaAoCliente(idCliente, modelo) {
            const tabelaCliente = document.getElementById(`tabela-produtos-${idCliente}`);



            console.log(`Adicionando modelo ${modelo} à tabela ${tabelaCliente.id}`);

            const novaLinha = document.createElement("tr");
            novaLinha.innerHTML = `
        <td>${modelo}</td>
        <td>N/A</td>
        <td>N/A</td>
        <td>N/A</td>
        <td>N/A</td>
        <td>N/A</td>
        <td>N/A</td>
        <td>N/A</td>
        <td>
            <button onclick="abrirModalEditar(
                '${modelo.replace(/'/g, "\\'")}',
                'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A', '${idCliente}'
            )">Editar</button>
        </td>
    `;

            tabelaCliente.querySelector('tbody').appendChild(novaLinha);
        }


        document.addEventListener("DOMContentLoaded", function() {
            const statusClasses = {
                'AGUARDANDO': 'status-aguardando',
                'PROGRAMACAO': 'status-programacao',
                'PRODUCAO': 'status-producao',
                'PATIO': 'status-patio',
                'ENTREGUE': 'status-entregue',
                'CANCELADA': 'status-cancelada'
            };

            // Selecionar todas as linhas que têm um 'data-idpedidos_tr'
            document.querySelectorAll('tr[data-idpedidos_tr]').forEach(function(row) {
                const status = row.querySelector('td:nth-child(4)').textContent
                    .trim(); // A coluna de status
                const classeStatus = statusClasses[status];

                if (classeStatus) {
                    row.classList.add(classeStatus); // Adiciona a classe CSS apropriada
                }
            });
        });

        function filtrarPorStatus(status) {
            // Obtém todos os pedidos exibidos na página
            const pedidos = document.querySelectorAll(".pedido-detalhe");

            // Para cada pedido, verifica o atributo data-status e exibe apenas os que correspondem ao status filtrado
            pedidos.forEach(pedido => {
                if (status === "" || pedido.getAttribute("data-status") === status) {
                    pedido.style.display = "block"; // Exibe o pedido se o status corresponder
                } else {
                    pedido.style.display = "none"; // Oculta o pedido se o status não corresponder
                }
            });
        }

        // Adiciona os eventos de clique nos botões de status para ativar o filtro
        document.querySelectorAll(".circulo-container").forEach(button => {
            button.addEventListener("click", () => {
                // Obtém o status do botão clicado
                const status = button.getAttribute("data-status");
                filtrarPorStatus(status);
            });
        });

        document.getElementById('toggleButton').addEventListener('click', function() {
            var buttons = document.querySelector('.buttons-e-circulos');
            if (buttons.style.display === 'none' || buttons.style.display === '') {
                buttons.style.display = 'block'; // Exibe os botões
                this.textContent = '⇩'; // Muda a seta para baixo
            } else {
                buttons.style.display = 'none'; // Esconde os botões
                this.textContent = '⇨'; // Muda a seta para a direita
            }
        });
        </script>
        <?php endif; ?>
        <button id="openMenu">&#9776;</button>
    </header>

    <nav id="menu">
        <button id="closeMenu">X</button>
        <div class="submenu">
            <a href="inicio.php">INICIO</a>
        </div>
        <div>
            <a href="status_producao.php">STATUS PRODUÇÃO</a>
        </div>
        <div class="submenu">
            <a href="pedido.php">PEDIDOS</a>
            <div class="submenu-content">
                <a href="graficos.php">GRÁFICOS</a>
            </div>
        </div>
        <div class="submenu">
            <a href="trilhadeira.php">TRILHADEIRA</a>
            <div class="submenu-content">
                <a href="material.php">MATERIAL</a>
            </div>
        </div>
        <div class="submenu">
            <a href="cadastro.php">CADASTRO</a>
        </div>
        <div class="submenu">
            <a href="obra.php">OBRAS</a>
        </div>

        <div class="submenu">
            <a href="producao.php">PRODUÇÃO</a>
            <div class="submenu">
                <a href="programacao.php">PROGRAMAÇÃO</a>
            </div>
        </div>
        <?php if ($paginaAtual === 'cadastro.php') : ?>
        <a href="vincular.php">VINCULO ENTRE EQUIPAMENTO E CONJUNTO</a>
        <?php endif; ?>
        <div class="submenu">
            <a href="logout.php">SAIR</a>
        </div>
        <?php if ($paginaAtual === 'producao.php' || $paginaAtual === 'obra.php' || $paginaAtual === 'menu.php') : ?>
        <form method="GET" action="relatorio_status.php">
            <label for="dataMin">Data a partir de:</label>
            <input type="date" name="dataMin" id="dataMin" class="form-control" required>
            <label for="statusFilter">Selecione o Status:</label>
            <select name="status" id="statusFilter" class="form-control">
                <option value="all">Todos</option>
                <option value="engenharia">Engenharia</option>
                <option value="programacao">Programação</option>
                <option value="pcp">PCP</option>
                <option value="producao">Produção</option>
                <option value="qualidade">Qualidade</option>
            </select>
            <button type="submit">STATUS</button>
        </form>
        <button type="button" class="btn btn-primary" onclick="abrirModalRelatorio()">RELATORIO</button>

        <div class="modal fade" id="modalRelatorio" tabindex="-1" role="dialog" aria-labelledby="modalRelatorioLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalRelatorioLabel">Selecione o Cliente</h5>
                        <!-- Botão de Fechar -->
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="formRelatorio">
                            <div class="form-group">
                                <label for="clienteSelect" class="form-label">Cliente</label>
                                <select class="form-control" id="clienteSelectModal" name="cliente"
                                    aria-label="Selecione um cliente">
                                    <option value="todos">Todos</option>
                                    <!-- Opções de clientes -->
                                    <?php
                                $clientes = mysqli_query($conexao, "SELECT idcliente, cliente FROM cliente");
                                if (mysqli_num_rows($clientes) > 0) {
                                    while ($cliente = mysqli_fetch_assoc($clientes)) {
                                        echo "<option value='" . $cliente['idcliente'] . "'>" . $cliente['cliente'] . "</option>";
                                    }
                                } else {
                                    echo "<option value='nenhum'>Nenhum cliente encontrado</option>";
                                }
                            ?>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                        <button type="button" class="btn btn-primary" onclick="gerarRelatorio()">Gerar
                            Relatório</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <a href="adimim.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-gear-fill"
                viewBox="0 0 16 16">
                <path
                    d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872zM8 10.93a2.929 2.929 0 1 1 0-5.86 2.929 2.929 0 0 1 0 5.858z" />
            </svg>
        </a>
    </nav>
    <script>
    document.getElementById("openMenu").addEventListener("click", function() {
        document.getElementById("menu").style.left = "0";
    });

    document.getElementById("closeMenu").addEventListener("click", function() {
        document.getElementById("menu").style.left = "-250px";
    });
    </script>
</body>

</html>