<?php
include_once('config.php');
include('menu.php');

// Consulta SQL otimizada
$sql_lista_pedidos = "
    SELECT c.idcliente, 
           c.pedido, 
           c.cliente AS nome_cliente, 
           c.endereco, 
           cp.id_vinculo, 
           cp.status_producao,
           e.equipamento_pai, 
           p.conjunto
    FROM cliente AS c
    LEFT JOIN cliente_produto AS cp ON c.idcliente = cp.id_cliente
    LEFT JOIN equipamento_produto AS ep ON cp.id_equipamento_produto = ep.id_equipamento_produto
    LEFT JOIN equipamento AS e ON ep.idequipamento = e.idequipamento
    LEFT JOIN produto AS p ON ep.idproduto = p.idproduto
    WHERE cp.id_vinculo IS NOT NULL
    ORDER BY c.idcliente
";

$result = $conexao->query($sql_lista_pedidos);
$pedidos = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Agrupar por id_vinculo
        $pedidos[$row['id_vinculo']][] = $row;
    }
} else {
    echo "Nenhum pedido encontrado.";
    exit;
}

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slide de Pedidos</title>
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css">
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.min.css">
    <style>
    body,
    html {
        overflow-x: hidden;
        width: 100%;
        margin: 0;
    }

    main {
        background-color: rgba(0, 0, 0, 0.6);
        flex: 20 0 500px;
        flex-wrap: wrap;
        overflow: auto;
        height: calc(100vh - 115px);
        margin: 3px;
        padding: 10px;
        border-radius: 8px 8px 8px;
    }

    .slide {
        width: 80%;
        margin: 0 auto;
        position: relative;
        background: #fff;
        border-radius: 20px;
        box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        text-align: center;
        padding: 20px;
        display: block;
    }

    .slick-slide {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .slick-slide .slide-content {
        width: 25%;
        padding: 10px;
        box-sizing: border-box;
    }

    .slide-content {
        display: block;
        padding: 20px;
    }

    .navigation {
        position: absolute;
        top: 50%;
        width: 100%;
        display: flex;
        justify-content: space-between;
        transform: translateY(-50%);
    }

    .navigation button {
        background: rgba(0, 0, 0, 0.5);
        color: white;
        border: none;
        padding: 10px;
        cursor: pointer;
        border-radius: 50%;
    }

    .slide-item {
        width: 250px;
        height: 320px;
        max-height: 320px;
        padding: 15px;
        box-sizing: border-box;
        border-radius: 10px;
        overflow: hidden;
        text-align: center;
        display: flex;
        flex-direction: column;
        justify-content: center;
        margin: 10px;
        box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
    }

    .slide-item h2,
    .slide-item h1,
    .slide-item ul {
        margin: 10px 0;
        overflow: hidden;
        text-overflow: ellipsis;
        /* Usado apenas se for necessário cortar */
        display: block;
        white-space: normal;
        /* Permite quebra de linha */
        max-height: 11.6em;
        /* Controla a altura de cada bloco de texto */
    }

    /* Cores de fundo alternadas */
    .slide-item:nth-child(odd) {
        background-color: #e1f5fe;
        /* Cor de fundo para itens ímpares */
    }

    .slide-item:nth-child(even) {
        background-color: #ffe0b2;
        /* Cor de fundo para itens pares */
    }

    .slick-prev,
    .slick-next {
        display: none !important;
    }

    @media screen and (max-width: 900px) {
        .slide-container {
            width: 100%;
        }

        .slick-slide .slide-content {
            width: 100%;
            /* Em telas menores, exibe um item por vez */
        }
    }

    @media screen and (max-width: 600px) {
        .slick-slide .slide-content {
            width: 100%;
            /* Em telas pequenas, exibe um item por vez */
        }
    }
    </style>
</head>

<body>
    <main>
        <!-- Slider com Slick Slider -->
        <div class="slider">
            <?php foreach ($pedidos as $id_vinculo => $pedido): ?>
            <?php foreach ($pedido as $item): 
        // Defina a cor de acordo com o status
        $bgColor = '';
        switch ($item['status_producao']) {
            case 'Aguardando Programação':
                $bgColor = '#F44343';
                break;
            case 'Aguardando PCP':
                $bgColor = '#007BFF'; // verde claro para 'Concluído'
                break;
            case 'Em Produção':
                $bgColor = '#FFFF00'; // vermelho claro para 'Atrasado'
                break;
            case 'Produção Finalizada':
                $bgColor = '#F0912B'; // vermelho claro para 'Atrasado'
                break;
            case 'Liberado para Expedição':
                $bgColor = '#008000'; // vermelho claro para 'Atrasado'
                 break;
            default:
                $bgColor = '#f4f4f4'; // cor padrão
                break;
        }
    ?>
            <div class="slide-item" style="background-color: <?php echo $bgColor; ?>;">
                <h2>Pedido: <?php echo htmlspecialchars($item['pedido']); ?></h2>
                <ul>
                    <li>
                        <strong>Cliente:</strong> <?php echo htmlspecialchars($item['nome_cliente']); ?> -
                        <?php echo htmlspecialchars($item['endereco']); ?><br>
                        <strong>Equipamento Pai:</strong> <?php echo htmlspecialchars($item['equipamento_pai']); ?><br>
                        <strong>Conjunto:</strong> <?php echo htmlspecialchars($item['conjunto']); ?><br>
                        <h1><strong>Status:</strong> <?php echo htmlspecialchars($item['status_producao']); ?></h1>
                    </li>
                </ul>
            </div>
            <?php endforeach; ?>
            <?php endforeach; ?>

        </div>
    </main>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.js"></script>

    <script>
    $(document).ready(function() {
        $('.slider').slick({
            infinite: true,
            slidesToShow: 3,
            slidesToScroll: 1,
            arrows: true,
            autoplay: true,
            autoplaySpeed: 4000,
            responsive: [{
                    breakpoint: 900,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 600,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1
                    }
                }
            ]
        });
    });
    </script>
</body>

</html>