<?php
include_once('config.php');
include('protect.php');
include('menu.php');
verificarAcesso();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio</title>
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.css" />
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.css" />
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.js">
    </script>

    <style>
    body,
    html {
        overflow-x: hidden;
        width: 100%;
        margin: 0;
    }

    .slider-container {
        width: 100vw;
        overflow: hidden;
        display: flex;
        justify-content: center;
        margin-top: 50px;
    }

    .slider {
        width: 95%;
        margin: 0 auto;
        height: 350px;
        /* Aumenta a altura do slider */
    }

    .slider img {
        max-width: 100%;
        max-height: 100%;
        /* Ajusta para ocupar toda a altura do slider */
        border-radius: 8px;
        transition: transform 0.3s, opacity 0.3s;
        opacity: 0.6;
    }

    .slick-center img {
        transform: scale(1.0);
        /* Ajusta o tamanho da imagem central */
        opacity: 1;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.5);
    }

    .slider-segundo .slick-slide {
        padding: 0 20px;
        /* Espaçamento entre as imagens */
        display: flex;
        justify-content: center;
    }

    .slider-segundo img {
        width: 100%;
        /* Define o tamanho igual para todas as imagens */
        height: 300px;
        border-radius: 15px;
        object-fit: cover;
    }

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.8);
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    .modal img {
        max-width: 90%;
        max-height: 90%;
        border-radius: 8px;
    }

    .modal-close {
        position: absolute;
        top: 10px;
        right: 10px;
        font-size: 30px;
        color: white;
        cursor: pointer;
        background: none;
        border: none;
    }
    </style>
</head>

<body>
    <main>
        <div class="slider-container">
            <div class="slider">
                <div><img src="img/tr-60.png" alt="Imagem 1"></div>
                <div><img src="img/tr-80.png" alt="Imagem 2"></div>
                <div><img src="img/tr-100.png" alt="Imagem 3"></div>
                <div><img src="img/tr-120.png" alt="Imagem 4"></div>
            </div>
        </div>
        <div class="slider-container">
            <div class="slider slider-segundo">
                <div><img src="img/sma-silo.jpg" alt="Imagem 1"></div>
                <div><img src="img/sma-silo2.jpg" alt="Imagem 2"></div>
                <div><img src="img/sma-secador.jpg" alt="Imagem 3"></div>
                <div><img src="img/sma-elevador.jpg" alt="Imagem 4"></div>
            </div>
        </div>
    </main>

    <div class="modal" id="imageModal">
        <button class="modal-close" id="closeModal">&times;</button>
        <img src="" alt="Imagem Expandida" id="modalImage">
    </div>

    <script type="text/javascript">
    $(document).ready(function() {
        $('.slider').slick({
            dots: true,
            infinite: true,
            speed: 500,
            slidesToShow: 3,
            centerMode: true,
            centerPadding: '0px',
            autoplay: true,
            autoplaySpeed: 3000
        });

        // Ao clicar em uma imagem, abra o modal e exiba a imagem
        $('.slider img').on('click', function() {
            const imageUrl = $(this).attr('src');
            $('#modalImage').attr('src', imageUrl);
            $('#imageModal').fadeIn();
        });

        // Ao clicar no botão de fechar, esconda o modal
        $('#closeModal').on('click', function() {
            $('#imageModal').fadeOut();
        });

        // Clique fora da imagem para fechar o modal
        $('#imageModal').on('click', function(e) {
            if (e.target.id === 'imageModal' || e.target.id === 'closeModal') {
                $('#imageModal').fadeOut();
            }
        });
    });
    </script>
</body>

</html>