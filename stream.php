<?php
// stream.php - Transmite arquivos de vídeo locais suportando Range Requests (HTML5 player)
session_write_close(); // Fecha a sessão para evitar travar outras requisições concorrentes

$configPath = __DIR__ . DIRECTORY_SEPARATOR . 'config.json';
$downloadsDir = __DIR__ . DIRECTORY_SEPARATOR . 'downloads';

// Carrega diretório configurado
if (file_exists($configPath)) {
    $config = json_decode(file_get_contents($configPath), true);
    if (!empty($config['downloads_dir'])) {
        $downloadsDir = $config['downloads_dir'];
    }
}

$file = $_GET['file'] ?? '';
if (empty($file)) {
    header("HTTP/1.1 400 Bad Request");
    exit("Arquivo não especificado.");
}

// Higieniza para evitar directory traversal
$file = basename($file);
$filePath = $downloadsDir . DIRECTORY_SEPARATOR . $file;

if (!file_exists($filePath) || !is_file($filePath)) {
    header("HTTP/1.1 404 Not Found");
    exit("Arquivo não encontrado.");
}

// Abre o arquivo para leitura binária
$fp = @fopen($filePath, 'rb');
if (!$fp) {
    header("HTTP/1.1 500 Internal Server Error");
    exit("Não foi possível ler o arquivo.");
}

$size = filesize($filePath);
$length = $size;
$start = 0;
$end = $size - 1;

// Define o Content-Type baseado na extensão do arquivo
$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$contentType = 'video/mp4';
if ($ext === 'mkv') {
    $contentType = 'video/x-matroska';
} elseif ($ext === 'webm') {
    $contentType = 'video/webm';
} elseif ($ext === 'avi') {
    $contentType = 'video/x-msvideo';
} elseif ($ext === 'mov') {
    $contentType = 'video/quicktime';
}

header("Content-Type: $contentType");
header("Accept-Ranges: bytes");

// Trata HTTP Range Requests (indispensável para player HTML5)
if (isset($_SERVER['HTTP_RANGE'])) {
    $c_start = $start;
    $c_end = $end;

    list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
    if (strpos($range, ',') !== false) {
        header('HTTP/1.1 416 Requested Range Not Satisfiable');
        header("Content-Range: bytes $start-$end/$size");
        exit;
    }
    if ($range == '-') {
        $c_start = $size - substr($range, 1);
    } else {
        $range = explode('-', $range);
        $c_start = $range[0];
        $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size - 1;
    }
    $c_end = ($c_end > $end) ? $end : $c_end;
    if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
        header('HTTP/1.1 416 Requested Range Not Satisfiable');
        header("Content-Range: bytes $start-$end/$size");
        exit;
    }
    $start = $c_start;
    $end = $c_end;
    $length = $end - $start + 1;
    fseek($fp, $start);
    header('HTTP/1.1 206 Partial Content');
}

header("Content-Range: bytes $start-$end/$size");
header("Content-Length: " . $length);

// Transmite o arquivo em buffers pequenos para gerenciar o consumo de memória do PHP
$buffer = 1024 * 64; // 64KB por iteração
while (!feof($fp) && ($p = ftell($fp)) <= $end) {
    if ($p + $buffer > $end) {
        $buffer = $end - $p + 1;
    }
    set_time_limit(0);
    echo fread($fp, $buffer);
    flush();
}
fclose($fp);
