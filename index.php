<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KumaDownloader - Baixador de Vídeos</title>
    <link rel="stylesheet" href="style.css">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Estilos adicionais para Skeleton Loader e Modais */
        .skeleton {
            background: linear-gradient(90deg, rgba(255,255,255,0.05) 25%, rgba(255,255,255,0.1) 50%, rgba(255,255,255,0.05) 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 8px;
        }
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        .skeleton-thumb {
            width: 240px;
            height: 135px;
        }
        
        .skeleton-title {
            width: 70%;
            height: 24px;
            margin-bottom: 10px;
        }

        .skeleton-meta {
            width: 40%;
            height: 16px;
        }

        /* Video Modal Player */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(2, 6, 23, 0.85);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-content {
            background: #0f172a;
            border: 1px solid var(--card-border);
            border-radius: 16px;
            width: 90%;
            max-width: 800px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            transform: scale(0.9);
            transition: transform 0.3s ease;
            position: relative;
        }

        .modal-overlay.active .modal-content {
            transform: scale(1);
        }

        .modal-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-weight: 600;
            font-size: 1.1rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 80%;
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 1.25rem;
            cursor: pointer;
            transition: color 0.2s;
        }

        .modal-close:hover {
            color: var(--danger);
        }

        .modal-body {
            position: relative;
            padding-top: 56.25%; /* 16:9 Aspect Ratio */
            background: #000;
        }

        .modal-body video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            outline: none;
        }

        /* Botão de Configurações no Header */
        .header-wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 0.5rem;
            width: 100%;
        }
        .btn-config {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--card-border);
            color: var(--text-color);
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(4px);
        }
        .btn-config:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }
        .btn-config i {
            transition: transform 0.6s ease;
        }
        .btn-config:hover i {
            transform: rotate(90deg);
        }
        @media (max-width: 576px) {
            .header-wrap {
                flex-direction: column;
                align-items: flex-start;
            }
            .btn-config {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <!-- Header -->
        <header>
            <div class="header-wrap">
                <h1><i class="fa-solid fa-cloud-arrow-down"></i> KumaDownloader</h1>
                <button class="btn-config" onclick="openConfigModal()" title="Configurações da Pasta de Destino">
                    <i class="fa-solid fa-gear"></i> Configurações
                </button>
            </div>
            <p>Baixe vídeos do ok.ru e de outras plataformas com velocidade acelerada em Full HD, HD ou SD.</p>
        </header>

        <!-- Main Grid Layout -->
        <div class="grid">
            
            <!-- Left Side: URL Analyzer & Downloader -->
            <div class="card">
                <h2><i class="fa-solid fa-magnifying-glass"></i> Analisar Novo Vídeo</h2>
                
                <div class="info-alert">
                    <i class="fa-solid fa-circle-info"></i> Cole o link do vídeo desejado abaixo. O sistema irá consultar as resoluções e tamanhos disponíveis para download.
                </div>

                <form id="analyzeForm" onsubmit="event.preventDefault(); analyzeVideo();">
                    <div class="form-group">
                        <div class="input-wrapper">
                            <input type="text" id="videoUrl" placeholder="https://ok.ru/videoembed/..." required autocomplete="off">
                            <button type="submit" id="analyzeBtn" class="btn">
                                <i class="fa-solid fa-wand-magic-sparkles"></i> Analisar
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Skeleton Loader (Hidden by default) -->
                <div id="skeletonLoader" style="display: none;">
                    <div class="video-preview" style="margin-top: 2rem;">
                        <div class="thumbnail skeleton skeleton-thumb"></div>
                        <div class="video-details">
                            <div class="skeleton skeleton-title"></div>
                            <div class="skeleton skeleton-meta"></div>
                        </div>
                    </div>
                </div>

                <!-- Analysis Results Card (Hidden by default) -->
                <div id="analysisResult" style="display: none; margin-top: 2rem;">
                    <div class="video-preview">
                        <div class="thumbnail">
                            <img id="videoThumb" src="" alt="Thumbnail do vídeo">
                        </div>
                        <div class="video-details">
                            <div class="video-title" id="videoTitle">Título do Vídeo</div>
                            <div class="video-meta">
                                <span><i class="fa-regular fa-clock"></i> <strong id="videoDuration">00:00</strong></span>
                                <span><i class="fa-solid fa-circle-play"></i> OK.ru Video</span>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 1.5rem;">
                        <h3 style="font-size: 1rem; margin-bottom: 0.75rem;"><i class="fa-solid fa-list-ul"></i> Selecione a Resolução</h3>
                        <div class="formats-list" id="formatsContainer">
                            <!-- Os formatos serão injetados aqui -->
                        </div>
                    </div>

                    <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
                        <button class="btn" id="startDownloadBtn" onclick="startDownload()">
                            <i class="fa-solid fa-download"></i> Baixar Vídeo Selecionado
                        </button>
                    </div>
                </div>

                <!-- Active Download Progress Card (Hidden by default) -->
                <div class="card download-progress-card" id="downloadProgressCard" style="display: none;">
                    <div class="progress-header">
                        <span style="font-weight: 600;"><i class="fa-solid fa-spinner fa-spin"></i> Baixando Vídeo...</span>
                        <span id="progressPercent" style="font-weight: 700; color: var(--accent);">0%</span>
                    </div>
                    
                    <div class="progress-bar-container">
                        <div class="progress-bar-fill" id="progressBarFill" style="width: 0%;"></div>
                    </div>

                    <div class="progress-stats">
                        <div class="stat-box">
                            <span class="stat-label">Velocidade</span>
                            <span class="stat-value" id="statSpeed">0 MB/s</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-label">Tempo Restante</span>
                            <span class="stat-value" id="statEta">--:--</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-label">Tamanho Estimado</span>
                            <span class="stat-value" id="statSize">Calculando...</span>
                        </div>
                    </div>

                    <div style="margin-top: 1rem; display: flex; justify-content: space-between; align-items: center;">
                        <span id="downloadingFilename" style="font-size: 0.85rem; color: var(--text-muted); max-width: 70%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Nome do arquivo</span>
                        <button class="btn btn-danger" onclick="cancelDownload()" style="padding: 0.5rem 1rem; font-size: 0.85rem;">
                            <i class="fa-solid fa-ban"></i> Cancelar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Side: Library of Downloaded Videos -->
            <div class="card">
                <h2><i class="fa-solid fa-folder-open"></i> Biblioteca Local</h2>
                
                <div class="library-table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Vídeo</th>
                                <th>Tamanho</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="libraryTableBody">
                            <!-- Os arquivos serão injetados aqui -->
                        </tbody>
                    </table>
                </div>

                <div id="libraryEmptyState" class="empty-state" style="display: none;">
                    <i class="fa-solid fa-video-slash" style="font-size: 3rem;"></i>
                    <p>Nenhum vídeo baixado ainda nesta pasta.</p>
                </div>
            </div>

        </div>

        <!-- Footer -->
        <footer>
            Desenvolvido com <i class="fa-solid fa-heart" style="color: var(--secondary);"></i> por KumaCorp Desenvolvimento e soluções
        </footer>
    </div>

    <!-- Video Modal Player Overlay -->
    <div class="modal-overlay" id="videoModal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="modal-title" id="modalVideoTitle">Nome do Filme</span>
                <button class="modal-close" onclick="closeVideoModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <video id="modalVideoPlayer" controls>
                    Seu navegador não suporta reprodução de vídeo HTML5.
                </video>
            </div>
        </div>
    </div>

    <!-- Config Modal Overlay -->
    <div class="modal-overlay" id="configModal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <span class="modal-title"><i class="fa-solid fa-gear"></i> Configurações do Sistema</span>
                <button class="modal-close" onclick="closeConfigModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body" style="padding: 1.5rem; color: var(--text-color);">
                <form id="configForm" onsubmit="event.preventDefault(); saveConfig();">
                    <div style="margin-bottom: 1.25rem;">
                        <label style="display: block; font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--text-muted);">Pasta de Destino dos Downloads</label>
                        <input type="text" id="configDownloadsDir" style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid var(--card-border); background: rgba(0,0,0,0.2); color: #fff; font-family: inherit; font-size: 0.9rem;" required autocomplete="off">
                        <small style="display: block; margin-top: 0.4rem; font-size: 0.8rem; color: var(--text-muted);">
                            Espaço Livre Disponível: <strong id="configFreeSpace" style="color: var(--accent);">Calculando...</strong>
                        </small>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-top: 1.5rem;">
                        <button type="submit" class="btn" style="width: 100%;">
                            <i class="fa-solid fa-floppy-disk"></i> Salvar Pasta de Destino
                        </button>
                        <button type="button" class="btn btn-danger" onclick="cleanTempFiles()" style="width: 100%; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444;">
                            <i class="fa-solid fa-broom"></i> Limpar Arquivos Temporários (.part)
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let selectedFormat = null;
        let activeDownloadId = null;
        let progressInterval = null;

        // Ao carregar a página, lista a biblioteca
        document.addEventListener('DOMContentLoaded', () => {
            loadLibrary();
        });

        // Analisar a URL do vídeo
        async function analyzeVideo() {
            const urlInput = document.getElementById('videoUrl');
            const url = urlInput.value.trim();
            if (!url) return;

            const analyzeBtn = document.getElementById('analyzeBtn');
            const skeletonLoader = document.getElementById('skeletonLoader');
            const analysisResult = document.getElementById('analysisResult');

            // UI feedback
            analyzeBtn.disabled = true;
            analyzeBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Analisando...';
            skeletonLoader.style.display = 'block';
            analysisResult.style.display = 'none';
            selectedFormat = null;

            try {
                const formData = new FormData();
                formData.append('url', url);

                const response = await fetch('api.php?action=analyze', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.error) {
                    alert(data.error);
                } else {
                    // Preenche dados do vídeo
                    document.getElementById('videoThumb').src = data.thumbnail || 'https://placehold.co/240x135/0f172a/f8fafc?text=Sem+Thumb';
                    document.getElementById('videoTitle').innerText = data.title;
                    document.getElementById('videoDuration').innerText = data.duration;

                    // Injeta formatos
                    const container = document.getElementById('formatsContainer');
                    container.innerHTML = '';

                    // Pega somente os 6 primeiros formatos para não poluir visualmente (priorizando alta qualidade)
                    const formatList = data.formats.slice(0, 8);

                    formatList.forEach((fmt, index) => {
                        const div = document.createElement('div');
                        div.className = 'format-item' + (index === 0 ? ' selected' : '');
                        if (index === 0) selectedFormat = fmt.format_id;

                        div.onclick = () => {
                            document.querySelectorAll('.format-item').forEach(el => el.classList.remove('selected'));
                            div.classList.add('selected');
                            selectedFormat = fmt.format_id;
                        };

                        div.innerHTML = `
                            <div class="format-info">
                                <span class="format-resolution"><i class="fa-solid fa-film"></i> Resolução: ${fmt.resolution}</span>
                                <span class="format-ext">Extensão: ${fmt.ext.toUpperCase()} | Protocolo: ${fmt.protocol}</span>
                            </div>
                            <span class="format-size">${fmt.size || 'Tamanho desconhecido'}</span>
                        `;
                        container.appendChild(div);
                    });

                    analysisResult.style.display = 'block';
                }
            } catch (err) {
                console.error(err);
                alert('Ocorreu um erro de rede ao tentar analisar o vídeo.');
            } finally {
                analyzeBtn.disabled = false;
                analyzeBtn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Analisar';
                skeletonLoader.style.display = 'none';
            }
        }

        // Inicia o download
        async function startDownload() {
            const url = document.getElementById('videoUrl').value.trim();
            if (!url || !selectedFormat) {
                alert('Selecione uma resolução e certifique-se de que a URL é válida.');
                return;
            }

            const downloadBtn = document.getElementById('startDownloadBtn');
            downloadBtn.disabled = true;
            downloadBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Inicializando...';

            try {
                const formData = new FormData();
                formData.append('url', url);
                formData.append('format_id', selectedFormat);

                const response = await fetch('api.php?action=download', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.error) {
                    alert(data.error);
                    downloadBtn.disabled = false;
                    downloadBtn.innerHTML = '<i class="fa-solid fa-download"></i> Baixar Vídeo Selecionado';
                } else {
                    activeDownloadId = data.id;
                    // Mostra o card de progresso
                    document.getElementById('downloadProgressCard').style.display = 'block';
                    // Rola a tela até o progresso
                    document.getElementById('downloadProgressCard').scrollIntoView({ behavior: 'smooth' });

                    // Inicia o monitoramento
                    startPollingProgress();
                }
            } catch (err) {
                console.error(err);
                alert('Erro ao tentar iniciar o download.');
                downloadBtn.disabled = false;
                downloadBtn.innerHTML = '<i class="fa-solid fa-download"></i> Baixar Vídeo Selecionado';
            }
        }

        // Inicia o loop de consulta do progresso
        function startPollingProgress() {
            if (progressInterval) clearInterval(progressInterval);

            // Reseta a barra
            updateProgressBar(0, 'Calculando...', '--:--', 'Calculando...', 'Iniciando...');

            progressInterval = setInterval(async () => {
                if (!activeDownloadId) {
                    clearInterval(progressInterval);
                    return;
                }

                try {
                    const response = await fetch(`api.php?action=progress&id=${activeDownloadId}`);
                    const data = await response.json();

                    if (data.status === 'error') {
                        clearInterval(progressInterval);
                        alert(`Erro no download: ${data.message}`);
                        resetDownloadUI();
                    } else if (data.status === 'failed') {
                        clearInterval(progressInterval);
                        alert('O download falhou inesperadamente.');
                        resetDownloadUI();
                    } else if (data.status === 'completed') {
                        clearInterval(progressInterval);
                        updateProgressBar(100, 'N/A', '00:00', data.size, data.filename);
                        
                        setTimeout(() => {
                            alert('Download concluído com sucesso!');
                            resetDownloadUI();
                            loadLibrary();
                        }, 500);
                    } else {
                        // Status: downloading
                        updateProgressBar(
                            data.progress,
                            data.speed,
                            data.eta,
                            data.size,
                            data.filename || 'Processando fluxo de vídeo...'
                        );
                    }
                } catch (err) {
                    console.error('Erro ao buscar progresso:', err);
                }
            }, 1000);
        }

        // Atualiza elementos da barra de progresso
        function updateProgressBar(percent, speed, eta, size, filename) {
            document.getElementById('progressBarFill').style.width = `${percent}%`;
            document.getElementById('progressPercent').innerText = `${percent}%`;
            document.getElementById('statSpeed').innerText = speed;
            document.getElementById('statEta').innerText = eta;
            document.getElementById('statSize').innerText = size;
            document.getElementById('downloadingFilename').innerText = filename;
        }

        // Limpa a interface após o término/cancelamento
        function resetDownloadUI() {
            activeDownloadId = null;
            document.getElementById('downloadProgressCard').style.display = 'none';
            const downloadBtn = document.getElementById('startDownloadBtn');
            downloadBtn.disabled = false;
            downloadBtn.innerHTML = '<i class="fa-solid fa-download"></i> Baixar Vídeo Selecionado';
        }

        // Cancela o download ativo
        async function cancelDownload() {
            if (!activeDownloadId) return;

            if (confirm('Tem certeza que deseja cancelar o download ativo?')) {
                clearInterval(progressInterval);
                try {
                    await fetch(`api.php?action=cancel&id=${activeDownloadId}`);
                } catch (e) {
                    console.error(e);
                }
                alert('Download cancelado pelo usuário.');
                resetDownloadUI();
            }
        }

        // Carrega a biblioteca de vídeos
        async function loadLibrary() {
            try {
                const response = await fetch('api.php?action=list');
                const files = await response.json();

                const tbody = document.getElementById('libraryTableBody');
                const emptyState = document.getElementById('libraryEmptyState');

                tbody.innerHTML = '';

                if (files.length === 0) {
                    emptyState.style.display = 'flex';
                    tbody.parentElement.style.display = 'none';
                } else {
                    emptyState.style.display = 'none';
                    tbody.parentElement.style.display = 'table';

                    files.forEach(file => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>
                                <div class="file-name" title="${file.name}">
                                    <i class="fa-solid fa-file-video" style="color: var(--primary); margin-right: 0.5rem;"></i>
                                    ${file.name}
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">Baixado em: ${file.created}</div>
                            </td>
                            <td><span style="font-weight: 500;">${file.size}</span></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-icon" onclick="playVideo('${file.url}', '${file.name.replace(/'/g, "\\'")}')" title="Assistir no Navegador">
                                        <i class="fa-solid fa-play"></i>
                                    </button>
                                    <a href="${file.url}" download class="btn-icon" style="text-decoration: none;" title="Salvar no Computador">
                                        <i class="fa-solid fa-floppy-disk"></i>
                                    </a>
                                    <button class="btn-icon btn-icon-danger" onclick="deleteVideo('${file.name}')" title="Deletar Arquivo">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
            } catch (err) {
                console.error(err);
            }
        }

        // Deleta vídeo da biblioteca
        async function deleteVideo(filename) {
            if (confirm(`Deseja realmente deletar o arquivo "${filename}" permanentemente do servidor?`)) {
                try {
                    const formData = new FormData();
                    formData.append('filename', filename);

                    const response = await fetch('api.php?action=delete', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    if (data.error) {
                        alert(data.error);
                    } else {
                        loadLibrary();
                    }
                } catch (e) {
                    console.error(e);
                    alert('Erro ao tentar deletar arquivo.');
                }
            }
        }

        // Controladores do Video Player Modal
        function playVideo(url, title) {
            const modal = document.getElementById('videoModal');
            const video = document.getElementById('modalVideoPlayer');
            const modalTitle = document.getElementById('modalVideoTitle');

            modalTitle.innerText = title;
            video.src = url;
            modal.classList.add('active');
            video.play();
        }

        function closeVideoModal() {
            const modal = document.getElementById('videoModal');
            const video = document.getElementById('modalVideoPlayer');

            video.pause();
            video.src = '';
            modal.classList.remove('active');
        }

        // Controladores do Modal de Configurações
        async function openConfigModal() {
            const modal = document.getElementById('configModal');
            modal.classList.add('active');
            
            try {
                const response = await fetch('api.php?action=get_config');
                const data = await response.json();
                
                document.getElementById('configDownloadsDir').value = data.downloads_dir;
                document.getElementById('configFreeSpace').innerText = data.free_space;
            } catch (e) {
                console.error(e);
            }
        }

        function closeConfigModal() {
            const modal = document.getElementById('configModal');
            modal.classList.remove('active');
        }

        async function saveConfig() {
            const dirInput = document.getElementById('configDownloadsDir');
            const dir = dirInput.value.trim();
            if (!dir) return;

            const saveBtn = document.querySelector('#configForm button[type="submit"]');
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Salvando...';

            const formData = new FormData();
            formData.append('downloads_dir', dir);

            try {
                const response = await fetch('api.php?action=save_config', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.error) {
                    alert(data.error);
                } else {
                    alert('Diretório de downloads atualizado com sucesso!');
                    closeConfigModal();
                    loadLibrary(); // Recarrega a biblioteca com base na nova pasta
                }
            } catch (err) {
                console.error(err);
                alert('Erro ao tentar salvar configurações.');
            } finally {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Salvar Pasta de Destino';
            }
        }

        async function cleanTempFiles() {
            if (confirm('Tem certeza que deseja apagar todos os arquivos temporários (.part e .ytdl) da pasta de downloads? Isso interromperá downloads parciais ativos e liberará o espaço correspondente.')) {
                const cleanBtn = document.querySelector('#configForm button[onclick="cleanTempFiles()"]');
                cleanBtn.disabled = true;
                cleanBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Limpando...';

                try {
                    const response = await fetch('api.php?action=clean_temp');
                    const data = await response.json();
                    
                    if (data.success) {
                        alert(`Limpeza concluída! Foram deletados ${data.deleted_files} arquivos temporários.`);
                        openConfigModal(); // Atualiza espaço livre no modal
                    } else {
                        alert('Erro ao tentar limpar arquivos temporários.');
                    }
                } catch (e) {
                    console.error(e);
                    alert('Erro de conexão ao limpar temporários.');
                } finally {
                    cleanBtn.disabled = false;
                    cleanBtn.innerHTML = '<i class="fa-solid fa-broom"></i> Limpar Arquivos Temporários (.part)';
                }
            }
        }
    </script>
</body>
</html>
