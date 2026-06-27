<?php
// Configurações básicas
set_time_limit(180); // limite de tempo alto para a análise inicial
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

$pythonPath = "C:\\Users\\Tharlion\\anaconda3\\python.exe";
$progressDir = __DIR__ . DIRECTORY_SEPARATOR . 'progress';
$configPath = __DIR__ . DIRECTORY_SEPARATOR . 'config.json';
$downloadsDir = __DIR__ . DIRECTORY_SEPARATOR . 'downloads';

// Carrega diretório configurado
if (file_exists($configPath)) {
    $config = json_decode(file_get_contents($configPath), true);
    if (!empty($config['downloads_dir'])) {
        $downloadsDir = $config['downloads_dir'];
    }
}

// Garante que os diretórios existam
if (!file_exists($downloadsDir)) {
    @mkdir($downloadsDir, 0777, true);
}
if (!file_exists($progressDir)) {
    @mkdir($progressDir, 0777, true);
}

// Função para verificar se a URL pertence a um domínio do NetCinema ou espelho (.lat)
function isNetcinemaMirror($url) {
    $parsedHost = parse_url(trim($url), PHP_URL_HOST);
    if (!$parsedHost) return false;
    
    // Normaliza host (remove www.)
    $host = preg_replace('/^www\./i', '', $parsedHost);
    
    // Confirma se pertence a domínios do NetCinema ou espelhos como eee1.lat, qqq1.lat, netcinebw.lat, etc.
    if (stripos($host, 'netcinema') !== false || 
        stripos($host, 'netcine') !== false || 
        preg_match('/^[a-z0-9]+1?\.lat$/i', $host) ||
        preg_match('/^[a-z0-9]+1?\.xyz$/i', $host)
    ) {
        return true;
    }
    
    return false;
}

// Função para extrair a URL de streaming de players integrados (como eee1.lat e netcinema.lat)
function extractStream($url) {
    $url = trim($url);
    if (!isNetcinemaMirror($url) && strpos($url, 'hlsarchive.php') === false && strpos($url, 'hls.php') === false) {
        return ['success' => false, 'error' => 'URL is not a recognized NetCinema mirror or HLS page'];
    }

    $urlOriginal = $url;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36");
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    // Se for o link da página principal do filme, extraímos o iframe
    $isPlayerPage = (strpos($url, 'media-player/') !== false || strpos($url, 'hls.php') !== false || strpos($url, 'hlsarchive.php') !== false);
    if (!$isPlayerPage) {
        curl_setopt($ch, CURLOPT_URL, $url);
        $html = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (empty($html)) {
            $err = curl_error($ch);
            curl_close($ch);
            return ['success' => false, 'error' => "Failed to fetch episode page (HTTP $http_code): $err"];
        }

        preg_match_all('/<iframe[^>]+src=["\']?([^"\'\s>]+)["\']?/i', $html, $matches);
        if (empty($matches[1])) {
            curl_close($ch);
            return ['success' => false, 'error' => 'No iframes found on the episode page (Cloudflare block or page structure changed)'];
        }

        $iframeUrl = null;
        foreach ($matches[1] as $src) {
            $srcDecoded = htmlspecialchars_decode($src);
            if (strpos($srcDecoded, 'media-player/') !== false || strpos($srcDecoded, 'hlsarchive.php') !== false || strpos($srcDecoded, 'hls.php') !== false) {
                $iframeUrl = $srcDecoded;
                break;
            }
        }

        if (!$iframeUrl) {
            $iframeUrl = htmlspecialchars_decode($matches[1][0]);
        }

        $url = $iframeUrl;
        if (strpos($url, 'http') !== 0) {
            $parsed = parse_url($urlOriginal);
            $scheme = isset($parsed['scheme']) ? $parsed['scheme'] : 'https';
            if (strpos($url, '//') === 0) {
                $url = $scheme . ':' . $url;
            } else {
                $baseDomain = $scheme . '://' . (isset($parsed['host']) ? $parsed['host'] : 'eee1.lat');
                $url = $baseDomain . '/' . ltrim($url, '/');
            }
        }
    }

    // Agora acessamos a página do iframe para achar o botão "Assistir Online"
    curl_setopt($ch, CURLOPT_URL, $url);
    $html = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (empty($html)) {
        $err = curl_error($ch);
        curl_close($ch);
        return ['success' => false, 'error' => "Failed to fetch iframe page (HTTP $http_code): $err"];
    }

    // Se a própria página do iframe já tiver o source de vídeo direto, usamos ele!
    if (preg_match('/<source[^>]+src=["\']?([^"\'\s>]+)["\']?/i', $html, $m)) {
        $streamUrl = htmlspecialchars_decode($m[1]);
        $effectiveHlsUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        return [
            'success' => true,
            'stream_url' => $streamUrl,
            'referer' => $effectiveHlsUrl
        ];
    }

    preg_match('/href=["\']?([^"\']*(?:hls\.php|media-player\/hls\/hls\.php)[^"\']*)["\']?/i', $html, $m);

    if (empty($m)) {
        curl_close($ch);
        return ['success' => false, 'error' => 'HLS player link not found on the iframe page'];
    }

    $hlsUrl = htmlspecialchars_decode($m[1]);
    if (strpos($hlsUrl, 'http') !== 0) {
        $parsed = parse_url($urlOriginal);
        $scheme = isset($parsed['scheme']) ? $parsed['scheme'] : 'https';
        if (strpos($hlsUrl, '//') === 0) {
            $hlsUrl = $scheme . ':' . $hlsUrl;
        } else {
            $baseDomain = $scheme . '://' . (isset($parsed['host']) ? $parsed['host'] : 'eee1.lat');
            $hlsUrl = $baseDomain . '/' . ltrim($hlsUrl, '/');
        }
    }

    // Acessa o player de HLS propriamente dito
    curl_setopt($ch, CURLOPT_URL, $hlsUrl);
    curl_setopt($ch, CURLOPT_REFERER, $url);
    $html = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (empty($html)) {
        $err = curl_error($ch);
        curl_close($ch);
        return ['success' => false, 'error' => "Failed to fetch HLS player page (HTTP $http_code): $err"];
    }

    // Extrai o link de streaming da tag <source>
    preg_match('/<source[^>]+src=["\']?([^"\'\s>]+)["\']?/i', $html, $m);
    if (empty($m)) {
        curl_close($ch);
        return ['success' => false, 'error' => 'Video source link not found on the HLS player page'];
    }

    $streamUrl = htmlspecialchars_decode($m[1]);
    $effectiveHlsUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    return [
        'success' => true,
        'stream_url' => $streamUrl,
        'referer' => $effectiveHlsUrl
    ];
}

// Endpoint para execução via CLI (chamado pelo script PowerShell em segundo plano)
if (php_sapi_name() === 'cli' || (isset($argv) && count($argv) > 0)) {
    $cliAction = $argv[1] ?? '';
    if ($cliAction === 'scrape_cli') {
        $epUrl = $argv[2] ?? '';
        $extracted = extractStream($epUrl);
        echo json_encode($extracted);
        exit;
    }
}

// Helper para formatar bytes
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// Helper para limpar o título do filme (removendo propagandas e corrigindo codificação corrompida)
function cleanMovieTitle($title) {
    // Dicionário para corrigir caracteres corrompidos comuns em UTF-8 (double encoding)
    $utf8_chars = [
        'Ã¡' => 'á', 'Ã¢' => 'â', 'Ã£' => 'ã', 'Ã©' => 'é', 'Ãª' => 'ê', 
        'Ã' . "\xad" => 'í', 'Ãad' => 'í', 'Ã³' => 'ó', 'Ã´' => 'ô', 'Ãµ' => 'õ', 
        'Ãº' => 'ú', 'Ã§' => 'ç', 'Ã ' => 'à',
        'Ã' => 'Á', 'Ã' => 'Â', 'Ã' => 'Ã', 'Ã' => 'É', 'Ã' => 'Ê', 
        'Ã' => 'Í', 'Ã' => 'Ó', 'Ã' => 'Ô', 'ÃÕ' => 'Õ', 'Ã' => 'Ú', 
        'Ã' => 'Ç', 'Ã' => 'À'
    ];
    $title = strtr($title, $utf8_chars);

    // Decodifica entidades HTML
    $title = htmlspecialchars_decode($title, ENT_QUOTES | ENT_HTML5);
    
    // Remove "Assistir " do início
    $title = preg_replace('/^Assistir\s+/ui', '', $title);
    
    // Lista de padrões de sufixo a remover
    $suffixes = [
        '/\s*-\s*NetCine.*/ui',
        '/\s*-\s*Net\s*Cinema.*/ui',
        '/\s*Online\s+em\s+HD\s+no\s+NetCine.*/ui',
        '/\s*Online\s+em\s+HD\s+no\s+NetCinema.*/ui',
        '/\s*Online\s+Grátis\s+no\s+NetCine.*/ui',
        '/\s*Online\s+Grátis\s+no\s+NetCinema.*/ui',
        '/\s*Online\s+Grátis.*/ui',
        '/\s*Online\s+em\s+HD.*/ui',
        '/\s*Dublado\s+Online.*/ui',
        '/\s*Legendado\s+Online.*/ui'
    ];
    
    foreach ($suffixes as $pattern) {
        $title = preg_replace($pattern, '', $title);
    }
    
    // Limpa múltiplos espaços
    $title = preg_replace('/\s+/', ' ', trim($title));
    
    return $title;
}

// Helper para localizar o executável php.exe de forma resiliente (mesmo rodando sob Apache module)
function getPhpExecutable() {
    $phpPath = PHP_BINARY;
    
    // Se o caminho terminar com php.exe ou php-cgi.exe, está correto (CGI ou CLI direto)
    $basename = strtolower(basename($phpPath));
    if ($basename === 'php.exe' || $basename === 'php-cgi.exe') {
        return $phpPath;
    }
    
    // Se rodando sob módulo do Apache (httpd.exe), deduzimos no XAMPP:
    // C:\xampp\apache\bin\httpd.exe -> C:\xampp\php\php.exe
    $apacheDir = dirname(dirname($phpPath));
    $xamppDir = dirname($apacheDir);
    $xamppPhp = $xamppDir . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'php.exe';
    
    if (file_exists($xamppPhp)) {
        return $xamppPhp;
    }
    
    // Caminhos padrões adicionais no Windows
    $commonPaths = [
        'C:\\xampp\\php\\php.exe',
        'C:\\Program Files\\PHP\\php.exe',
        'C:\\Program Files (x86)\\PHP\\php.exe'
    ];
    
    foreach ($commonPaths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    
    return 'php';
}

// Ações da API
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'analyze':
        $url = $_POST['url'] ?? '';
        if (empty($url)) {
            echo json_encode(['error' => 'A URL do vídeo não pode estar vazia.']);
            exit;
        }

        // Verifica se é uma URL de série no NetCinema
        $isSeriesPage = (strpos($url, '/tvshows/') !== false);
        if ($isSeriesPage) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36");
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $html = curl_exec($ch);
            curl_close($ch);

            if (empty($html)) {
                echo json_encode(['error' => 'Não foi possível acessar a página da série.']);
                exit;
            }

            // Extrai Título
            preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $html, $h1Title);
            $title = isset($h1Title[1]) ? cleanMovieTitle(strip_tags(trim($h1Title[1]))) : 'Série sem título';

            // Extrai Thumbnail
            preg_match('/<meta[^>]+property="og:image"[^>]+content="([^"]+)"/i', $html, $ogImage);
            $thumbnail = $ogImage[1] ?? '';

            // Extrai Episódios usando regex em todo o HTML
            preg_match_all('/<a[^>]+href=["\']?([^"\']*episode\/[^"\']*)["\']?[^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER);
            
            $episodes = [];
            $seenUrls = [];
            foreach ($matches as $match) {
                $epUrl = trim(htmlspecialchars_decode($match[1]));
                if (in_array($epUrl, $seenUrls)) continue;
                $seenUrls[] = $epUrl;
                
                $label = strip_tags(trim($match[2]));
                $label = preg_replace('/\s+/', ' ', $label);
                
                $episodes[] = [
                    'url' => $epUrl,
                    'label' => $label
                ];
            }
            
            if (empty($episodes)) {
                echo json_encode(['error' => 'Nenhum episódio encontrado nesta série.']);
                exit;
            }

            echo json_encode([
                'is_series' => true,
                'title' => $title,
                'thumbnail' => $thumbnail,
                'episodes' => $episodes
            ]);
            exit;
        }

        $pageTitle = null;
        if (isNetcinemaMirror($url)) {
            $isPlayerPage = (strpos($url, 'media-player/') !== false || strpos($url, 'hls.php') !== false || strpos($url, 'hlsarchive.php') !== false);
            if (!$isPlayerPage) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36");
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 8);
                $html = curl_exec($ch);
                curl_close($ch);
                
                if (!empty($html)) {
                    if (preg_match('/<meta[^>]+property="og:title"[^>]+content="([^"]+)"/i', $html, $ogTitleMatches)) {
                        $pageTitle = cleanMovieTitle($ogTitleMatches[1]);
                    }
                    if (empty($pageTitle) && preg_match('/<title>(.*?)<\/title>/is', $html, $titleMatches)) {
                        $pageTitle = cleanMovieTitle($titleMatches[1]);
                    }
                    if (empty($pageTitle) && preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $html, $h1Matches)) {
                        $pageTitle = cleanMovieTitle(strip_tags($h1Matches[1]));
                    }
                }
            }
        }

        // Verifica se é uma URL do player netcinema e raspa o stream final
        $extracted = extractStream($url);
        $refererArg = "";
        if ($extracted && isset($extracted['success']) && $extracted['success']) {
            $url = $extracted['stream_url'];
            $refererArg = " --add-header " . escapeshellarg("Referer: " . $extracted['referer']);
        }

        // Executa o yt-dlp para obter as informações em JSON
        $cmd = escapeshellarg($pythonPath) . " -m yt_dlp " . $refererArg . " --dump-json " . escapeshellarg($url);
        $output = shell_exec($cmd);

        if (empty($output)) {
            echo json_encode(['error' => 'Não foi possível analisar este vídeo. Verifique se o link está correto e se o vídeo é público.']);
            exit;
        }

        $data = json_decode($output, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['error' => 'Erro ao decodificar a resposta do servidor de análise.']);
            exit;
        }

        // Monta os metadados do vídeo
        $info = [
            'title' => !empty($pageTitle) ? $pageTitle : cleanMovieTitle($data['title'] ?? ($data['fulltitle'] ?? 'Vídeo sem título')),
            'duration' => isset($data['duration']) ? gmdate($data['duration'] >= 3600 ? "H:i:s" : "i:s", $data['duration']) : 'Desconhecida',
            'thumbnail' => $data['thumbnail'] ?? ($data['thumbnails'][0]['url'] ?? ''),
            'formats' => []
        ];

        // Filtra os formatos úteis
        if (!empty($data['formats'])) {
            foreach ($data['formats'] as $f) {
                if (!isset($f['format_id']) || $f['format_id'] === '') continue;
                
                $res = $f['resolution'] ?? 'unknown';
                if ($res === 'unknown' && isset($f['width']) && isset($f['height'])) {
                    $res = $f['width'] . 'x' . $f['height'];
                }

                // Não inclui apenas áudio
                if (isset($f['vcodec']) && $f['vcodec'] === 'none') continue;

                $size = '';
                if (!empty($f['filesize'])) {
                    $size = formatBytes($f['filesize']);
                } elseif (!empty($f['filesize_approx'])) {
                    $size = '~' . formatBytes($f['filesize_approx']);
                } elseif (!empty($f['tbr']) && isset($data['duration'])) {
                    $estBytes = ($f['tbr'] * 1000 / 8) * $data['duration'];
                    $size = '~' . formatBytes($estBytes);
                }

                // Renomeia resoluções comuns para facilitar a leitura do usuário
                $formatNote = $f['format_note'] ?? '';
                $label = ($res !== 'unknown') ? $res : 'Resolução Padrão';
                if (!empty($formatNote)) {
                    $label .= " ({$formatNote})";
                }

                $info['formats'][] = [
                    'format_id' => $f['format_id'],
                    'resolution' => $label,
                    'ext' => $f['ext'] ?? 'mp4',
                    'size' => $size,
                    'protocol' => $f['protocol'] ?? 'unknown'
                ];
            }
        }

        // Remove formatos duplicados e limpa a lista
        $info['formats'] = array_values(array_filter($info['formats']));
        // Inverte a ordem para que os formatos de maior qualidade (normalmente no final do JSON do yt-dlp) fiquem no início
        $info['formats'] = array_reverse($info['formats']);

        echo json_encode($info);
        break;

    case 'download':
        $url = $_POST['url'] ?? '';
        $formatId = $_POST['format_id'] ?? '';
        $isBatch = (($_POST['is_batch'] ?? '') === 'true');

        if (empty($url) || $formatId === '') {
            echo json_encode(['error' => 'URL e ID do formato são obrigatórios.']);
            exit;
        }

        $formatIdClean = str_replace("'", "", $formatId);
        $pythonPathClean = str_replace("'", "", $pythonPath);
        $ffmpegBin = "C:/ffmpeg/bin";
        $phpBin = getPhpExecutable();
        $apiScript = __FILE__;

        // Download em Lote (Séries)
        if ($isBatch) {
            $episodesData = json_decode($_POST['batch_episodes'] ?? '[]', true);
            if (empty($episodesData)) {
                echo json_encode(['error' => 'Nenhum episódio fornecido para o download em lote.']);
                exit;
            }

            $downloadId = md5(serialize($episodesData) . time());
            $metaPath = $progressDir . '/' . $downloadId . '.meta';
            $pidPath = $progressDir . '/' . $downloadId . '.pid';
            $ps1Path = $progressDir . '/' . $downloadId . '.ps1';

            // Monta o script PowerShell temporário de lote sequencial com extração on-demand
            $ps1Content = "\$PID | Out-File -FilePath '" . str_replace("'", "''", $pidPath) . "' -Encoding ascii\n";
            $ps1Content .= "try {\n";

            $batchMeta = [
                'id' => $downloadId,
                'status' => 'downloading',
                'is_batch' => true,
                'start_time' => time(),
                'current_index' => 0,
                'total_episodes' => count($episodesData),
                'episodes' => []
            ];

            $index = 0;
            foreach ($episodesData as $ep) {
                $epUrl = $ep['url'];
                $epLabel = $ep['label'];
                
                $logPath = $progressDir . '/' . $downloadId . '_' . $index . '.log';
                $cleanLabel = preg_replace('/[\\\\\/:\*\?"<>\|]/', '', $epLabel);
                $cleanLabel = str_replace(['’', '‘', '`'], "'", $cleanLabel);
                $cleanLabel = preg_replace('/\s+/', ' ', trim($cleanLabel));

                // Escapa caminhos para uso dentro das strings com aspas simples do PowerShell
                $escapedEpUrl = str_replace("'", "''", $epUrl);
                $escapedLogPath = str_replace("'", "''", $logPath);
                $escapedCleanLabel = str_replace("'", "''", $cleanLabel);

                $ps1Content .= "    # Episodio $index: $epLabel\n";
                $ps1Content .= "    \$scrapeJson = & '" . str_replace("'", "''", $phpBin) . "' '" . str_replace("'", "''", $apiScript) . "' scrape_cli '" . $escapedEpUrl . "'\n";
                $ps1Content .= "    \$scrape = \$null\n";
                $ps1Content .= "    if (\$scrapeJson) {\n";
                $ps1Content .= "        \$jsonStr = (\$scrapeJson -join \"`n\").Trim()\n";
                $ps1Content .= "        \$startIdx = \$jsonStr.IndexOf('{')\n";
                $ps1Content .= "        \$endIdx = \$jsonStr.LastIndexOf('}')\n";
                $ps1Content .= "        if (\$startIdx -ge 0 -and \$endIdx -gt \$startIdx) {\n";
                $ps1Content .= "            \$jsonStrClean = \$jsonStr.Substring(\$startIdx, \$endIdx - \$startIdx + 1)\n";
                $ps1Content .= "            \$scrape = \$jsonStrClean | ConvertFrom-Json -ErrorAction SilentlyContinue\n";
                $ps1Content .= "        }\n";
                $ps1Content .= "    }\n";
                $ps1Content .= "    if (\$scrape -and \$scrape.success -and \$scrape.stream_url) {\n";
                $ps1Content .= "        \$urlClean = \$scrape.stream_url\n";
                $ps1Content .= "        \$refererArg = @()\n";
                $ps1Content .= "        if (\$scrape.referer) {\n";
                $ps1Content .= "            \$refererArg = @('--add-header', ('Referer: ' + \$scrape.referer))\n";
                $ps1Content .= "        }\n";
                $ps1Content .= "        & '" . $pythonPathClean . "' -m yt_dlp --ffmpeg-location '" . $ffmpegBin . "' --restrict-filenames --concurrent-fragments 16 --newline -f '" . $formatIdClean . "' -P 'home:" . str_replace("'", "''", $downloadsDir) . "' -P 'temp:" . str_replace("'", "''", $progressDir) . "' -o '" . $escapedCleanLabel . ".%(ext)s' @refererArg \$urlClean 2>&1 | Out-File -FilePath '" . $escapedLogPath . "' -Encoding utf8\n";
                $ps1Content .= "    } else {\n";
                $ps1Content .= "        \$errMsg = if (\$scrape -and \$scrape.error) { \$scrape.error } else { 'Failed to extract stream URL (video offline or unsupported)' }\n";
                $ps1Content .= "        \"ERROR: \$errMsg\" | Out-File -FilePath '" . $escapedLogPath . "' -Encoding utf8\n";
                $ps1Content .= "    }\n\n";

                $batchMeta['episodes'][] = [
                    'label' => $epLabel,
                    'url' => $epUrl,
                    'log_path' => $logPath,
                    'status' => 'pending',
                    'progress' => 0
                ];
                $index++;
            }

            $ps1Content .= "} finally {\n";
            $ps1Content .= "    if (Test-Path '" . str_replace("'", "''", $pidPath) . "') { Remove-Item '" . str_replace("'", "''", $pidPath) . "' }\n";
            $ps1Content .= "}\n";

            file_put_contents($ps1Path, "\xEF\xBB\xBF" . $ps1Content);
            file_put_contents($metaPath, json_encode($batchMeta, JSON_PRETTY_PRINT));

            // Executa o script .ps1 em segundo plano de forma síncrona/sequencial interna
            $winCmd = 'start "downloader_' . $downloadId . '" /B powershell -NoProfile -ExecutionPolicy Bypass -File "' . $ps1Path . '"';
            pclose(popen($winCmd, "r"));

            echo json_encode(['success' => true, 'id' => $downloadId]);
            break;
        }

        // Download Individual (Filmes / Vídeos OK.ru)
        $downloadId = md5($url . $formatId . time());

        $logPath = $progressDir . '/' . $downloadId . '.log';
        $metaPath = $progressDir . '/' . $downloadId . '.meta';
        $customTitle = $_POST['title'] ?? '';
        if (!empty($customTitle)) {
            $cleanTitle = preg_replace('/[\\\\\/:\*\?"<>\|]/', '', $customTitle);
            $cleanTitle = str_replace(['’', '‘', '`'], "'", $cleanTitle);
            $cleanTitle = preg_replace('/\s+/', ' ', trim($cleanTitle));
            $outputFile = $cleanTitle . '.%(ext)s';
        } else {
            $outputFile = '%(title)s.%(ext)s';
        }

        // Verifica se é uma URL do player netcinema e extrai o streaming/referer
        $extracted = null;
        $isNetcinemaUrl = isNetcinemaMirror($url);
        if ($isNetcinemaUrl) {
            $extracted = extractStream($url);
            if (!$extracted || !$extracted['success']) {
                $err = $extracted['error'] ?? 'Failed to extract stream URL (video offline or unsupported)';
                file_put_contents($logPath, "ERROR: " . $err);
                file_put_contents($metaPath, json_encode([
                    'id' => $downloadId,
                    'url' => $url,
                    'format_id' => $formatId,
                    'start_time' => time(),
                    'status' => 'error',
                    'error_message' => $err
                ]));
                echo json_encode(['success' => true, 'id' => $downloadId]);
                break;
            }
        }

        $refererArg = "";
        if ($extracted && isset($extracted['success']) && $extracted['success']) {
            $url = $extracted['stream_url'];
            $refererArg = " --add-header 'Referer: " . str_replace("'", "", $extracted['referer']) . "'";
        }

        $urlClean = str_replace("'", "", $url);

        // Monta o script PowerShell temporário individual
        $ps1Path = $progressDir . '/' . $downloadId . '.ps1';
        $pidPath = $progressDir . '/' . $downloadId . '.pid';

        $ps1Content = "\$PID | Out-File -FilePath '" . str_replace("'", "''", $pidPath) . "' -Encoding ascii\n";
        $ps1Content .= "try {\n";
        $ps1Content .= "    & '" . $pythonPathClean . "' -m yt_dlp --ffmpeg-location '" . $ffmpegBin . "' --restrict-filenames --concurrent-fragments 16 --newline -f '" . $formatIdClean . "' -P 'home:" . str_replace("'", "''", $downloadsDir) . "' -P 'temp:" . str_replace("'", "''", $progressDir) . "' -o '" . str_replace("'", "''", $outputFile) . "'" . $refererArg . " '" . $urlClean . "' 2>&1 | Out-File -FilePath '" . str_replace("'", "''", $logPath) . "' -Encoding utf8\n";
        $ps1Content .= "} finally {\n";
        $ps1Content .= "    if (Test-Path '" . str_replace("'", "''", $pidPath) . "') { Remove-Item '" . str_replace("'", "''", $pidPath) . "' }\n";
        $ps1Content .= "}\n";
        file_put_contents($ps1Path, "\xEF\xBB\xBF" . $ps1Content);

        // Executa o script .ps1 em segundo plano
        $winCmd = 'start "downloader_' . $downloadId . '" /B powershell -NoProfile -ExecutionPolicy Bypass -File "' . $ps1Path . '"';
        pclose(popen($winCmd, "r"));

        // Grava arquivo de metadados
        file_put_contents($metaPath, json_encode([
            'id' => $downloadId,
            'url' => $url,
            'format_id' => $formatId,
            'start_time' => time(),
            'status' => 'downloading'
        ]));

        echo json_encode(['success' => true, 'id' => $downloadId]);
        break;

    case 'progress':
        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            echo json_encode(['error' => 'ID do download é obrigatório.']);
            exit;
        }

        $metaPath = $progressDir . DIRECTORY_SEPARATOR . $id . '.meta';
        $pidPath = $progressDir . DIRECTORY_SEPARATOR . $id . '.pid';

        $meta = null;
        if (file_exists($metaPath)) {
            $meta = json_decode(file_get_contents($metaPath), true);
        }

        // Se já foi cancelado, retorna status cancelado
        if ($meta && isset($meta['status']) && $meta['status'] === 'cancelled') {
            echo json_encode(['status' => 'cancelled']);
            break;
        }

        // Verifica se o processo principal está ativo via PID
        $isRunning = false;
        if (file_exists($pidPath)) {
            $pid = trim(file_get_contents($pidPath));
            if (!empty($pid) && is_numeric($pid)) {
                $taskCheck = shell_exec('tasklist /FI "PID eq ' . $pid . '" /NH');
                if ($taskCheck && strpos($taskCheck, $pid) !== false) {
                    $isRunning = true;
                }
            }
        }

        // Tolerância de inicialização de 120 segundos para evitar condições de corrida em lotes gigantescos
        if (!$isRunning && $meta) {
            $startTime = $meta['start_time'] ?? 0;
            if (time() - $startTime < 120) {
                $isRunning = true;
            }
        }

        // Se o metadados existir, verificamos se é download em lote
        if ($meta) {
            if (!empty($meta['is_batch'])) {
                $totalEps = $meta['total_episodes'];
                
                // Determina qual é o episódio ativo varrendo a existência de arquivos de log
                $activeIdx = 0;
                for ($i = 0; $i < $totalEps; $i++) {
                    $logFile = $progressDir . DIRECTORY_SEPARATOR . $id . '_' . $i . '.log';
                    if (file_exists($logFile)) {
                        $activeIdx = $i;
                    }
                }
                
                $meta['current_index'] = $activeIdx;
                $currentIdx = $activeIdx;
                
                $logFileActive = $progressDir . DIRECTORY_SEPARATOR . $id . '_' . $currentIdx . '.log';
                
                $percent = 0;
                $speed = '';
                $eta = '';
                $totalSize = 'Calculando...';
                $destinationFile = '';
                $log = '';
                
                if (file_exists($logFileActive)) {
                    $log = file_get_contents($logFileActive);
                    if (substr($log, 0, 2) === "\xFF\xFE" || strpos($log, "\x00") !== false) {
                        $log = mb_convert_encoding($log, 'UTF-8', 'UTF-16LE');
                    }
                    
                    // Faz o parsing do log do episódio ativo
                    $lines = explode("\n", $log);
                    foreach ($lines as $line) {
                        if (preg_match('/\[download\] Destination: (.*)/', $line, $destMatches)) {
                            $destinationFile = basename(trim($destMatches[1]));
                        } elseif (preg_match('/\[download\] (.*) has already been downloaded/', $line, $destMatches)) {
                            $destinationFile = basename(trim($destMatches[1]));
                            $percent = 100;
                        }
                    }
                    
                    for ($i = count($lines) - 1; $i >= 0; $i--) {
                        $line = trim($lines[$i]);
                        if (empty($line)) continue;
                        if (preg_match('/\[download\]\s+([0-9.]+)%\s+of\s+(~?\s*[0-9a-zA-Z.]+)\s+at\s+([0-9a-zA-Z.\/s]+)\s+ETA\s+([0-9a-zA-Z.:]+)/', $line, $m)) {
                            $percent = floatval($m[1]);
                            $totalSize = trim($m[2]);
                            $speed = trim($m[3]);
                            $eta = trim($m[4]);
                            break;
                        } elseif (preg_match('/\[download\]\s+([0-9.]+)%\s+of\s+(~?\s*[0-9a-zA-Z.]+)/', $line, $m)) {
                            $percent = floatval($m[1]);
                            $totalSize = trim($m[2]);
                            break;
                        }
                    }
                }
                
                // Atualiza status e progresso dos episódios
                $meta['episodes'][$currentIdx]['progress'] = $percent;
                
                for ($i = 0; $i < $totalEps; $i++) {
                    $logFileI = $progressDir . DIRECTORY_SEPARATOR . $id . '_' . $i . '.log';
                    $hasError = false;
                    if (file_exists($logFileI)) {
                        $logContent = file_get_contents($logFileI);
                        if (substr($logContent, 0, 2) === "\xFF\xFE" || strpos($logContent, "\x00") !== false) {
                            $logContent = mb_convert_encoding($logContent, 'UTF-8', 'UTF-16LE');
                        }
                        if (stripos($logContent, 'ERROR:') !== false) {
                            $hasError = true;
                        }
                    }
                    
                    if ($i < $currentIdx) {
                        if ($hasError) {
                            $meta['episodes'][$i]['status'] = 'failed';
                            $meta['episodes'][$i]['progress'] = 0;
                        } else {
                            $meta['episodes'][$i]['status'] = 'completed';
                            $meta['episodes'][$i]['progress'] = 100;
                        }
                    } elseif ($i == $currentIdx) {
                        if ($percent >= 100 || (!empty($log) && (stripos($log, '100% of') !== false || stripos($log, 'has already been downloaded') !== false))) {
                            if ($hasError) {
                                $meta['episodes'][$i]['status'] = 'failed';
                                $meta['episodes'][$i]['progress'] = 0;
                            } else {
                                $meta['episodes'][$i]['status'] = 'completed';
                                $meta['episodes'][$i]['progress'] = 100;
                            }
                        } else {
                            if ($hasError) {
                                $meta['episodes'][$i]['status'] = 'failed';
                                $meta['episodes'][$i]['progress'] = 0;
                            } else {
                                $meta['episodes'][$i]['status'] = 'downloading';
                            }
                        }
                    } else {
                        $meta['episodes'][$i]['status'] = 'pending';
                        $meta['episodes'][$i]['progress'] = 0;
                    }
                }
                
                // Determina status geral do lote
                $status = 'downloading';
                
                $allCompleted = true;
                foreach ($meta['episodes'] as $ep) {
                    if ($ep['status'] !== 'completed') {
                        $allCompleted = false;
                    }
                }
                
                if ($allCompleted) {
                    $status = 'completed';
                } elseif (!$isRunning) {
                    // O processo PowerShell terminou.
                    // Se o episódio ativo atual ainda está marcando como 'downloading' ou 'pending', mudamos para falho
                    if ($meta['episodes'][$currentIdx]['status'] === 'downloading' || $meta['episodes'][$currentIdx]['status'] === 'pending') {
                        $meta['episodes'][$currentIdx]['status'] = 'failed';
                    }
                    $status = 'completed';
                }
                
                $meta['status'] = $status;
                file_put_contents($metaPath, json_encode($meta, JSON_PRETTY_PRINT));
                
                $totalProgressSum = 0;
                $succeededCount = 0;
                $failedCount = 0;
                foreach ($meta['episodes'] as $ep) {
                    $totalProgressSum += $ep['progress'];
                    if ($ep['status'] === 'completed') {
                        $succeededCount++;
                    } elseif ($ep['status'] === 'failed') {
                        $failedCount++;
                    }
                }
                $overallPercent = round($totalProgressSum / $totalEps, 1);
                
                $response = [
                    'is_batch' => true,
                    'status' => $status,
                    'progress' => $overallPercent,
                    'current_episode' => [
                        'index' => $currentIdx + 1,
                        'total' => $totalEps,
                        'label' => $meta['episodes'][$currentIdx]['label'],
                        'progress' => $percent,
                        'speed' => $speed ? $speed : 'N/A',
                        'eta' => $eta ? $eta : 'N/A',
                        'size' => $totalSize
                    ],
                    'summary' => [
                        'succeeded' => $succeededCount,
                        'failed' => $failedCount,
                        'total' => $totalEps
                    ]
                ];
                
                echo json_encode($response);
                break;
            }
        }

        // Lógica de download individual (Retrocompatibilidade intacta)
        $logPath = $progressDir . DIRECTORY_SEPARATOR . $id . '.log';
        if (!file_exists($logPath)) {
            echo json_encode(['status' => 'waiting', 'progress' => 0]);
            break;
        }

        $log = file_get_contents($logPath);
        if (substr($log, 0, 2) === "\xFF\xFE" || strpos($log, "\x00") !== false) {
            $log = mb_convert_encoding($log, 'UTF-8', 'UTF-16LE');
        }

        $lines = explode("\n", $log);
        $percent = 0;
        $speed = '';
        $metaId = '';
        $eta = '';
        $totalSize = 'Calculando...';
        $destinationFile = '';

        foreach ($lines as $line) {
            if (preg_match('/\[download\] Destination: (.*)/', $line, $destMatches)) {
                $destinationFile = basename(trim($destMatches[1]));
            } elseif (preg_match('/\[download\] (.*) has already been downloaded/', $line, $destMatches)) {
                $destinationFile = basename(trim($destMatches[1]));
                $percent = 100;
            }
        }

        for ($i = count($lines) - 1; $i >= 0; $i--) {
            $line = trim($lines[$i]);
            if (empty($line)) continue;
            if (preg_match('/\[download\]\s+([0-9.]+)%\s+of\s+(~?\s*[0-9a-zA-Z.]+)\s+at\s+([0-9a-zA-Z.\/s]+)\s+ETA\s+([0-9a-zA-Z.:]+)/', $line, $m)) {
                $percent = floatval($m[1]);
                $totalSize = trim($m[2]);
                $speed = trim($m[3]);
                $eta = trim($m[4]);
                break;
            } elseif (preg_match('/\[download\]\s+([0-9.]+)%\s+of\s+(~?\s*[0-9a-zA-Z.]+)/', $line, $m)) {
                $percent = floatval($m[1]);
                $totalSize = trim($m[2]);
                break;
            }
        }

        $status = 'downloading';
        $errorMessage = null;
        if ($percent >= 100 || stripos($log, '100% of') !== false || stripos($log, 'has already been downloaded') !== false) {
            $status = 'completed';
            $percent = 100;
        } elseif (!$isRunning) {
            if (stripos($log, 'ERROR:') !== false) {
                preg_match('/ERROR:\s*(.*)/i', $log, $errMatches);
                $errMsg = $errMatches[1] ?? 'Erro desconhecido no yt-dlp.';
                $status = 'error';
                $errorMessage = trim($errMsg);
            } elseif ($percent > 0 && !empty($destinationFile) && file_exists($downloadsDir . DIRECTORY_SEPARATOR . $destinationFile)) {
                $status = 'completed';
                $percent = 100;
            } else {
                $status = 'failed';
            }
        }

        if (file_exists($metaPath)) {
            $meta = json_decode(file_get_contents($metaPath), true);
            $meta['status'] = $status;
            if ($status === 'error' && $errorMessage !== null) {
                $meta['error_message'] = $errorMessage;
            }
            if (!empty($destinationFile)) {
                $meta['filename'] = $destinationFile;
            }
            file_put_contents($metaPath, json_encode($meta));
        }

        $response = [
            'status' => $status,
            'progress' => $percent,
            'speed' => $speed ? $speed : 'N/A',
            'eta' => $eta ? $eta : 'N/A',
            'size' => $totalSize,
            'filename' => $destinationFile
        ];
        if ($status === 'error' && $errorMessage !== null) {
            $response['message'] = $errorMessage;
        }

        echo json_encode($response);
        break;

    case 'cancel':
        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            echo json_encode(['error' => 'ID do download é obrigatório.']);
            exit;
        }

        // Encerra o processo PowerShell usando o PID se disponível
        $pidPath = $progressDir . DIRECTORY_SEPARATOR . $id . '.pid';
        if (file_exists($pidPath)) {
            $pid = trim(file_get_contents($pidPath));
            if (!empty($pid) && is_numeric($pid)) {
                shell_exec('taskkill /F /PID ' . $pid . ' /T');
                if (file_exists($pidPath)) {
                    unlink($pidPath);
                }
            }
        }

        // Fallback: Encerra por título de janela caso tenha sobrado algum resíduo
        $killCmd = 'taskkill /F /FI "WINDOWTITLE eq downloader_' . $id . '" /T';
        shell_exec($killCmd);

        $metaPath = $progressDir . DIRECTORY_SEPARATOR . $id . '.meta';
        if (file_exists($metaPath)) {
            $meta = json_decode(file_get_contents($metaPath), true);
            $meta['status'] = 'cancelled';
            file_put_contents($metaPath, json_encode($meta));
        }

        echo json_encode(['success' => true]);
        break;
    case 'get_active':
        $activeId = null;
        if (file_exists($progressDir)) {
            $dir = new DirectoryIterator($progressDir);
            foreach ($dir as $fileinfo) {
                if (!$fileinfo->isDot() && $fileinfo->isFile() && $fileinfo->getExtension() === 'pid') {
                    $pidFile = $fileinfo->getFilename();
                    $id = pathinfo($pidFile, PATHINFO_FILENAME);
                    
                    $pid = trim(file_get_contents($fileinfo->getPathname()));
                    if (!empty($pid) && is_numeric($pid)) {
                        $taskCheck = shell_exec('tasklist /FI "PID eq ' . $pid . '" /NH');
                        if ($taskCheck && strpos($taskCheck, $pid) !== false) {
                            $activeId = $id;
                            break;
                        }
                    }
                }
            }
        }
        echo json_encode(['active_id' => $activeId]);
        break;

    case 'get_config':
        $freeSpace = @disk_free_space($downloadsDir);
        $totalSpace = @disk_total_space($downloadsDir);
        $freeSpaceText = ($freeSpace !== false) ? formatBytes($freeSpace) : 'Desconhecido';
        $totalSpaceText = ($totalSpace !== false) ? formatBytes($totalSpace) : 'Desconhecido';
        echo json_encode([
            'downloads_dir' => $downloadsDir,
            'default_dir' => __DIR__ . DIRECTORY_SEPARATOR . 'downloads',
            'free_space' => $freeSpaceText,
            'total_space' => $totalSpaceText,
            'free_space_raw' => $freeSpace,
            'total_space_raw' => $totalSpace
        ]);
        break;

    case 'save_config':
        $newDir = $_POST['downloads_dir'] ?? '';
        if (empty($newDir)) {
            echo json_encode(['error' => 'O caminho do diretório não pode ser vazio.']);
            exit;
        }

        // Valida se a pasta existe. Se não existir, tenta criar
        if (!file_exists($newDir)) {
            if (!@mkdir($newDir, 0777, true)) {
                echo json_encode(['error' => 'O diretório não existe e não pôde ser criado. Verifique as permissões de gravação.']);
                exit;
            }
        }

        if (!is_dir($newDir) || !is_writable($newDir)) {
            echo json_encode(['error' => 'O caminho fornecido não é um diretório válido ou não tem permissão de gravação.']);
            exit;
        }

        $config = [];
        if (file_exists($configPath)) {
            $config = json_decode(file_get_contents($configPath), true);
        }
        $config['downloads_dir'] = realpath($newDir);

        if (file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT))) {
            echo json_encode(['success' => true, 'downloads_dir' => $config['downloads_dir']]);
        } else {
            echo json_encode(['error' => 'Falha ao salvar o arquivo de configuração config.json.']);
        }
        break;

    case 'clean_temp':
        $deletedCount = 0;
        if (file_exists($downloadsDir)) {
            $dir = new DirectoryIterator($downloadsDir);
            foreach ($dir as $fileinfo) {
                if (!$fileinfo->isDot() && $fileinfo->isFile()) {
                    $ext = strtolower($fileinfo->getExtension());
                    $name = $fileinfo->getFilename();
                    if (strpos($ext, 'part') !== false || $ext === 'ytdl' || strpos($name, '.part-Frag') !== false) {
                        if (@unlink($fileinfo->getPathname())) {
                            $deletedCount++;
                        }
                    }
                }
            }
        }
        echo json_encode(['success' => true, 'deleted_files' => $deletedCount]);
        break;

    case 'list':
        $files = [];
        if (file_exists($downloadsDir)) {
            $dir = new DirectoryIterator($downloadsDir);
            $allowedExtensions = ['mp4', 'mkv', 'avi', 'webm', 'mov', 'flv'];
            foreach ($dir as $fileinfo) {
                if (!$fileinfo->isDot() && $fileinfo->isFile()) {
                    $ext = strtolower($fileinfo->getExtension());
                    if (in_array($ext, $allowedExtensions)) {
                        $files[] = [
                            'name' => $fileinfo->getFilename(),
                            'size' => formatBytes($fileinfo->getSize()),
                            'created' => date('d/m/Y H:i', $fileinfo->getMTime()),
                            'url' => 'stream.php?file=' . rawurlencode($fileinfo->getFilename())
                        ];
                    }
                }
            }
        }
        
        // Ordena por data de criação decrescente (mais recentes primeiro)
        usort($files, function($a, $b) {
            return strtotime(str_replace('/', '-', $b['created'])) - strtotime(str_replace('/', '-', $a['created']));
        });

        echo json_encode($files);
        break;

    case 'delete':
        $filename = $_POST['filename'] ?? '';
        if (empty($filename)) {
            echo json_encode(['error' => 'Nome do arquivo é obrigatório.']);
            exit;
        }

        // Higieniza para evitar directory traversal
        $filename = basename($filename);
        $filePath = $downloadsDir . DIRECTORY_SEPARATOR . $filename;

        $deleted = false;
        if (file_exists($filePath)) {
            unlink($filePath);
            $deleted = true;
        }

        // Deleta também arquivos parciais e de controle correspondentes
        $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
        if (file_exists($downloadsDir)) {
            $dir = new DirectoryIterator($downloadsDir);
            foreach ($dir as $fileinfo) {
                if (!$fileinfo->isDot() && $fileinfo->isFile()) {
                    $name = $fileinfo->getFilename();
                    if (strpos($name, $filename) === 0 || strpos($name, $nameWithoutExt) === 0) {
                        $ext = strtolower($fileinfo->getExtension());
                        if (strpos($ext, 'part') !== false || $ext === 'ytdl' || strpos($name, '.part-Frag') !== false) {
                            @unlink($fileinfo->getPathname());
                        }
                    }
                }
            }
        }

        if ($deleted) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Arquivo não encontrado.']);
        }
        break;

    default:
        echo json_encode(['error' => 'Ação inválida.']);
        break;
}
