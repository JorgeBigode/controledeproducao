<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor de PDF</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf-lib/1.17.1/pdf-lib.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        #pdf-container {
            position: relative;
            border: 1px solid #ccc;
            overflow: auto;
            width: 100%;
            height: 80vh; /* Ajusta a altura do contêiner para ocupar parte da tela */
        }
        iframe {
            border: none;
            width: 100%;
            height: 100%;
        }
        .draggable {
            position: absolute;
            cursor: move;
            background: rgba(255, 255, 255, 0.7);
            padding: 5px;
            border: 1px solid #ccc;
            user-select: none; /* Evita a seleção do texto durante o arraste */
        }
        .editor-controls {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <h1>Editor de PDF</h1>
    <input type="file" id="file-input" accept="application/pdf" />
    <div class="editor-controls">
        <label for="text-input">Texto:</label>
        <input type="text" id="text-input" placeholder="Digite o texto aqui" />
        <button id="add-text-button">Adicionar Texto</button>
    </div>
    <div id="pdf-container">
        <iframe id="pdf-iframe"></iframe>
    </div>

    <script>
        const fileInput = document.getElementById('file-input');
        const addTextButton = document.getElementById('add-text-button');
        const textInput = document.getElementById('text-input');
        let pdfDoc = null;

        fileInput.addEventListener('change', async (event) => {
            const file = event.target.files[0];
            if (file && file.type === 'application/pdf') {
                const arrayBuffer = await file.arrayBuffer();
                pdfDoc = await PDFLib.PDFDocument.load(arrayBuffer);

                // Exibir PDF no iframe
                const pdfDataUri = await pdfDoc.saveAsBase64({ dataUri: true });
                const pdfIframe = document.getElementById('pdf-iframe');
                pdfIframe.src = pdfDataUri; // Exibe o PDF no iframe
                alert("PDF carregado com sucesso!");
            } else {
                alert('Por favor, selecione um arquivo PDF válido.');
            }
        });

        addTextButton.addEventListener('click', () => {
            const newText = textInput.value;
            if (!newText) {
                alert('Por favor, insira um texto para adicionar ao PDF.');
                return;
            }

            // Criar um novo elemento de texto arrastável
            const textElement = document.createElement('div');
            textElement.className = 'draggable';
            textElement.innerText = newText;

            // Posições iniciais
            textElement.style.left = '50px';
            textElement.style.top = '50px';

            // Adicionar eventos de arrastar
            textElement.onmousedown = (e) => dragMouseDown(e, textElement);

            document.getElementById('pdf-container').appendChild(textElement);
            textInput.value = ''; // Limpar o campo de texto
        });

        function dragMouseDown(e, element) {
            e.preventDefault();
            let posX = e.clientX;
            let posY = e.clientY;

            document.onmouseup = closeDragElement;
            document.onmousemove = (e) => elementDrag(e, element);

            function elementDrag(e, element) {
                e.preventDefault();
                const deltaX = posX - e.clientX;
                const deltaY = posY - e.clientY;
                posX = e.clientX;
                posY = e.clientY;

                // Atualizar a posição do elemento arrastável
                element.style.top = (element.offsetTop - deltaY) + "px";
                element.style.left = (element.offsetLeft - deltaX) + "px";
            }

            function closeDragElement() {
                document.onmouseup = null;
                document.onmousemove = null;
            }
        }
    </script>
</body>
</html>
