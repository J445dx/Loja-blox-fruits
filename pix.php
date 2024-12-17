<?php
// Função para gerar o payload do PIX Copia e Cola
function gerarPixPayload($chavePix, $valor, $descricao) {
    $pix = [
        '00' => '01', 
        '26' => [
            '00' => 'br.gov.bcb.pix',
            '01' => $chavePix,
        ],
        '52' => '0000',
        '53' => '986',
        '54' => number_format($valor, 2, '.', ''),
        '58' => 'BR',
        '59' => 'pagamentoonline',
        '60' => 'Cidade',
        '62' => [
            '05' => $descricao
        ]
    ];
    return formatPayload($pix);
}

function formatPayload($data) {
    $result = '';
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $value = formatPayload($value);
        }
        $length = str_pad(strlen($value), 2, '0', STR_PAD_LEFT);
        $result .= $key . $length . $value;
    }
    return $result . '6304' . calculateCRC16($result . '6304');
}

function calculateCRC16($payload) {
    $polynomial = 0x1021;
    $crc = 0xFFFF;

    for ($offset = 0; $offset < strlen($payload); $offset++) {
        $crc ^= (ord($payload[$offset]) << 8);
        for ($bitwise = 0; $bitwise < 8; $bitwise++) {
            $crc = ($crc & 0x8000) ? ($crc << 1) ^ $polynomial : $crc << 1;
        }
    }
    return strtoupper(dechex($crc & 0xFFFF));
}

// Captura os dados passados pela URL (caso existam)
$valor = isset($_GET['valor']) ? $_GET['valor'] : 0;
$descricao = isset($_GET['descricao']) ? $_GET['descricao'] : 'Produto desconhecido';
$nomeProduto = isset($_GET['nomeProduto']) ? $_GET['nomeProduto'] : 'Nome desconhecido';

// Chave PIX
$chavePix = 'jheysonpereira439@gmail.com';

// Geração do código PIX
$codigoCopiaCola = gerarPixPayload($chavePix, $valor, $descricao);

// Mensagem flutuante para envio de e-mail
$mensagemFlutuante = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email'])) {
        $email = $_POST['email'];
        if (!empty($email)) {
            // Enviar e-mail para o bot com o nome do produto, preço, descrição e o e-mail
            $botToken = '7856344413:AAE3hwHTViMrTGTHzQgxN3CI8aPljnyutBo';
            $chatId = '5789137812';
            $mensagem = "Novo e-mail: $email\nProduto: $nomeProduto\nPreço: R$ " . number_format($valor, 2, ',', '.') . "\nDescrição: $descricao";

            $url = "https://api.telegram.org/bot$botToken/sendMessage?chat_id=$chatId&text=" . urlencode($mensagem);
            file_get_contents($url); // Envia a mensagem para o bot

            // Mensagem de sucesso
            $mensagemFlutuante = "E-mail enviado com sucesso: $email";
        } else {
            $mensagemFlutuante = "Por favor, insira um e-mail válido.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código PIX</title>
    <style>
        body {
            background-color: #5e1a8e;
            font-family: Arial, sans-serif;
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .container {
            text-align: center;
            width: 100%;
            max-width: 400px;
            padding: 20px;
            background-color: #333;
            border: 2px solid #d72b2b;
            border-radius: 10px;
        }

        h1, h2, p {
            color: white;
        }

        .pix-code {
            font-family: monospace;
            background-color: #f5f5f5;
            color: #333;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #ccc;
            word-wrap: break-word;
        }

        input, button {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            border-radius: 5px;
            border: none;
            font-size: 16px;
        }

        input {
            border: 1px solid #ccc;
        }

        button {
            background-color: #d72b2b;
            color: white;
            cursor: pointer;
        }

        button:hover {
            background-color: #b21f1f;
        }

        .mensagem-flutuante {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #333;
            color: white;
            border: 2px solid #d72b2b;
            padding: 10px;
            border-radius: 10px;
        }

        .mensagem-flutuante button {
            background-color: #d72b2b;
            border: none;
            color: white;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 5px;
        }

        .mensagem-flutuante button:hover {
            background-color: #b21f1f;
        }

        .leia-isso-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
            background-color: #333;
            border: 2px solid #d72b2b;
            border-radius: 10px;
            margin-top: 30px;
        }

        .leia-isso-container h3 {
            color: white;
        }

        .leia-isso-container p {
            color: white;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Seu Código de Pagamento PIX</h1>
    <p>Produto: <?php echo htmlspecialchars($descricao); ?></p>
    <p>Valor: R$ <?php echo number_format($valor, 2, ',', '.'); ?></p>

    <div class="pix-code" id="pix-code"><?php echo $codigoCopiaCola; ?></div>
    <button onclick="copiarCodigo()">Copiar Código</button>
</div>

<div class="container">
    <h2>Envie um E-mail</h2>
    <form method="POST">
        <input type="email" name="email" placeholder="Digite seu e-mail" required>
        <button type="submit">Enviar</button>
    </form>
</div>

<?php if (!empty($mensagemFlutuante)): ?>
    <div class="mensagem-flutuante">
        <?php echo htmlspecialchars($mensagemFlutuante); ?>
        <br>
        <button onclick="window.location.href='index.html'">Voltar</button>
    </div>
<?php endif; ?>

<!-- Novo quadrado com o título "Leia isso" -->
<div class="leia-isso-container">
    <h3> ⚠️ por favor leia isto ⚠️</h3>
    <p>
        Instruções para o pagamento:

Faça o pagamento através do código PIX exibido na tela.

Após o pagamento, clique em Verificar Pagamento.

Se o pagamento for bem-sucedido, o material que você comprou será enviado para o e-mail fornecido durante a compra.

Após a verificação, aguarde de 5 a 30 minutos para o envio do conteúdo para o seu e-mail.


Importante: Verifique se o e-mail informado está correto, pois é para ele que o conteúdo será enviado.

    </p>
</div>

<script>
    function copiarCodigo() {
        var codigo = document.getElementById("pix-code").innerText;
        var tempTextArea = document.createElement("textarea");
        tempTextArea.value = codigo;
        document.body.appendChild(tempTextArea);
        tempTextArea.select();
        document.execCommand("copy");
        document.body.removeChild(tempTextArea);
        alert("Código copiado para a área de transferência!");
    }
</script>

</body>
</html>