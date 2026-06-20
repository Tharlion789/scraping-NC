<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KumaDownloader - Baixador de Vídeos Premium</title>
    <link rel="stylesheet" href="style.css">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <div class="container">
        <!-- Header -->
        <header>
            <div class="header-wrap" style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; margin-bottom: 0.5rem; width: 100%;">
                <h1><i class="fa-solid fa-cloud-arrow-down" style="color: var(--primary);"></i> KumaDownloader</h1>
                <button class="btn-config" onclick="openConfigModal()" title="Configurações da Pasta de Destino" style="background: rgba(255, 255, 255, 0.03); border: 1px solid var(--card-border); color: var(--text-main); padding: 0.6rem 1.2rem; border-radius: 10px; cursor: pointer; font-size: 0.9rem; font-weight: 500; display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease; backdrop-filter: blur(4px);">
                    <i class="fa-solid fa-gear"></i> Configurações
                </button>
            </div>
            <p>Baixe vídeos individuais e séries completas do NetCinema ou OK.ru com visualização em galeria interativa.</p>
        </header>

        <!-- Stats Widgets Dashboard -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-clapperboard"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Biblioteca Local</span>
                    <span class="stat-value" id="widgetTotalFiles">0 Vídeos</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-hard-drive"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Espaço Disponível</span>
                    <span class="stat-value" id="widgetFreeSpace">Calculando...</span>
                    <div class="storage-progress">
                        <div class="storage-progress-bar" id="widgetStorageBar" style="width: 0%;"></div>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-server"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Status do Servidor</span>
                    <span class="stat-value" id="widgetServerStatus" style="color: var(--success);"><i class="fa-solid fa-circle fa-xs" style="font-size: 0.6rem; vertical-align: middle; margin-right: 0.4rem;"></i>Online</span>
                </div>
            </div>
        </div>

        <!-- Main Grid Layout -->
        <div class="grid">
            
            <!-- Left Side: URL Analyzer & Downloader -->
            <div class="card">
                <h2><i class="fa-solid fa-magnifying-glass"></i> Analisar Novo Link</h2>
                
                <div class="info-alert">
                    <i class="fa-solid fa-circle-info"></i> Cole o link do filme, vídeo do ok.ru ou página da série. O sistema identificará o tipo de conteúdo e as resoluções de forma automática.
                </div>

                <form id="analyzeForm" onsubmit="event.preventDefault(); analyzeVideo();">
                    <div class="form-group">
                        <div class="input-wrapper">
                            <input type="text" id="videoUrl" placeholder="Cole o link do filme, série ou ok.ru aqui..." required autocomplete="off">
                            <button type="submit" id="analyzeBtn" class="btn">
                                <i class="fa-solid fa-wand-magic-sparkles"></i> Analisar
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Skeleton Loader (Hidden by default) -->
                <div id="skeletonLoader" style="display: none;">
                    <div class="video-preview" style="margin-top: 1.5rem;">
                        <div class="thumbnail skeleton skeleton-thumb"></div>
                        <div class="video-details">
                            <div class="skeleton skeleton-title"></div>
                            <div class="skeleton skeleton-meta"></div>
                        </div>
                    </div>
                </div>

                <!-- Analysis Results Card (Hidden by default) -->
                <div id="analysisResult" style="display: none; margin-top: 1.5rem;">
                    <div class="video-preview">
                        <div class="thumbnail">
                            <img id="videoThumb" src="" alt="Thumbnail do vídeo">
                        </div>
                        <div class="video-details">
                            <div class="video-title" id="videoTitle">Título do Vídeo</div>
                            <div class="video-meta" id="videoMetaContainer">
                                <!-- Meta infos preenchidas por JS -->
                            </div>
                        </div>
                    </div>

                    <!-- Seção de Formatos para FILMES -->
                    <div id="movieFormatsSection" style="margin-top: 1.5rem;">
                        <h3 style="font-size: 1rem; margin-bottom: 0.75rem;"><i class="fa-solid fa-list-ul"></i> Selecione a Resolução</h3>
                        <div class="formats-list" id="formatsContainer">
                            <!-- Formatos injetados aqui -->
                        </div>
                    </div>

                    <!-- Seção de Formatos e Episódios para SÉRIES -->
                    <div id="seriesSection" style="margin-top: 1.5rem; display: none;">
                        <h3 style="font-size: 1rem; margin-bottom: 0.75rem;"><i class="fa-solid fa-list-ul"></i> Qualidade do Download</h3>
                        <div class="formats-list" id="seriesQualityContainer">
                            <!-- Qualidades genéricas injetadas aqui -->
                        </div>

                        <div class="series-episodes-card">
                            <div class="series-episodes-header">
                                <span style="font-weight: 700; color: #fff;"><i class="fa-solid fa-list-ol"></i> Episódios</span>
                                <label class="series-select-all">
                                    <input type="checkbox" id="selectAllEpisodes" checked onchange="toggleSelectAllEpisodes(this)">
                                    Selecionar Todos
                                </label>
                            </div>
                            <div class="episodes-scroll-container" id="episodesListContainer">
                                <!-- Episódios injetados aqui -->
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
                        <button class="btn" id="startDownloadBtn" onclick="startDownload()">
                            <i class="fa-solid fa-download"></i> Baixar Conteúdo Selecionado
                        </button>
                    </div>
                </div>

                <!-- Active Download Progress Card (Hidden by default) -->
                <div class="card download-progress-card" id="downloadProgressCard" style="display: none;">
                    <div class="progress-header">
                        <span style="font-weight: 600;"><i class="fa-solid fa-spinner fa-spin"></i> Efetuando Download...</span>
                        <span id="progressPercent" style="font-weight: 700; color: var(--accent);">0%</span>
                    </div>
                    
                    <div class="progress-bar-container">
                        <div class="progress-bar-fill" id="progressBarFill" style="width: 0%;"></div>
                    </div>

                    <!-- Informações adicionais do lote (Série) -->
                    <div class="batch-status-info" id="batchStatusInfo" style="display: none; margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                            <span>Episódio Ativo:</span>
                            <span class="batch-episode-active" id="batchActiveEpisode">Episódio 01</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Progresso do Episódio:</span>
                            <span id="batchEpisodeProgress" style="font-weight: 600; color: var(--secondary);">0%</span>
                        </div>
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
                            <span class="stat-label">Tamanho</span>
                            <span class="stat-value" id="statSize">Calculando...</span>
                        </div>
                    </div>

                    <div style="margin-top: 1rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
                        <span id="downloadingFilename" style="font-size: 0.85rem; color: var(--text-muted); max-width: 65%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Nome do arquivo</span>
                        <button class="btn btn-danger" onclick="cancelDownload()" style="padding: 0.5rem 1rem; font-size: 0.85rem; flex-shrink: 0;">
                            <i class="fa-solid fa-ban"></i> Cancelar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Side: Library of Downloaded Videos -->
            <div class="card">
                <h2><i class="fa-solid fa-folder-open"></i> Biblioteca Local</h2>
                
                <div class="library-grid" id="libraryGrid">
                    <!-- Os cards serão injetados dinamicamente por JavaScript -->
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

    <!-- Video Player Modal Overlay -->
    <div class="modal-overlay" id="videoModal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="modal-title" id="modalVideoTitle">Nome do Filme</span>
                <button class="modal-close" onclick="closeVideoModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <video id="modalVideoPlayer" controls>
                    Seu navegador não suporta a reprodução de vídeo HTML5 de forma nativa.
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
                            Espaço Livre Disponível na Pasta: <strong id="configFreeSpace" style="color: var(--accent);">Calculando...</strong>
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
        let isSeriesMode = false;
        let seriesEpisodes = [];

        // Ao carregar a página, lista a biblioteca e atualiza widgets
        document.addEventListener('DOMContentLoaded', () => {
            loadLibrary();
            updateStorageWidget();
            checkActiveDownload();
        });

        // Verifica se há algum download ativo rodando no background e restaura o monitoramento
        async function checkActiveDownload() {
            try {
                const response = await fetch('api.php?action=get_active');
                const data = await response.json();
                
                if (data.active_id) {
                    activeDownloadId = data.active_id;
                    document.getElementById('downloadProgressCard').style.display = 'block';
                    startPollingProgress();
                }
            } catch (e) {
                console.error("Erro ao verificar download ativo:", e);
            }
        }

        // Helper para gerar gradientes elegantes e únicos com base no nome do filme
        function getGradientForTitle(title) {
            let hash = 0;
            for (let i = 0; i < title.length; i++) {
                hash = title.charCodeAt(i) + ((hash << 5) - hash);
            }
            const h1 = Math.abs(hash % 360);
            const h2 = (h1 + 60) % 360;
            return `linear-gradient(135deg, hsl(${h1}, 65%, 20%), hsl(${h2}, 75%, 10%))`;
        }

        // Limpa o nome do arquivo removendo a extensão e IDs do yt-dlp como [id_do_video]
        function cleanFileName(filename) {
            let name = filename.replace(/\.[^/.]+$/, "");
            name = name.replace(/\s*\[[a-zA-Z0-9_\-]+\]$/, "");
            return name;
        }

        // Atualiza os widgets superiores de estatísticas
        async function updateStorageWidget() {
            try {
                const response = await fetch('api.php?action=get_config');
                const data = await response.json();
                
                // Atualiza o widget do topo
                document.getElementById('widgetFreeSpace').innerText = `${data.free_space} Livres`;
                
                if (data.total_space_raw && data.free_space_raw) {
                    const total = parseFloat(data.total_space_raw);
                    const free = parseFloat(data.free_space_raw);
                    if (total > 0) {
                        const usedPercent = ((total - free) / total) * 100;
                        document.getElementById('widgetStorageBar').style.width = `${usedPercent}%`;
                        
                        // Se o espaço estiver acabando (menos de 15% livre), muda a cor para alerta
                        const freePercent = (free / total) * 100;
                        if (freePercent < 15) {
                            document.getElementById('widgetStorageBar').style.background = 'linear-gradient(to right, var(--warning), var(--danger))';
                        } else {
                            document.getElementById('widgetStorageBar').style.background = 'linear-gradient(to right, var(--accent), var(--primary))';
                        }
                    }
                }
            } catch (e) {
                console.error("Erro ao atualizar widget de armazenamento:", e);
            }
        }

        // Analisar a URL do vídeo ou série
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
                    if (data.is_series) {
                        renderSeriesView(data);
                    } else {
                        renderMovieView(data);
                    }
                }
            } catch (err) {
                console.error(err);
                alert('Ocorreu um erro de rede ao tentar analisar o link.');
            } finally {
                analyzeBtn.disabled = false;
                analyzeBtn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Analisar';
                skeletonLoader.style.display = 'none';
            }
        }

        // Renderiza visualização de filmes individuais
        function renderMovieView(data) {
            isSeriesMode = false;
            seriesEpisodes = [];
            
            document.getElementById('movieFormatsSection').style.display = 'block';
            document.getElementById('seriesSection').style.display = 'none';

            // Preenche dados
            document.getElementById('videoThumb').src = data.thumbnail || 'https://placehold.co/240x135/0f172a/f8fafc?text=Sem+Thumb';
            document.getElementById('videoTitle').innerText = data.title;
            
            // Meta info
            const metaContainer = document.getElementById('videoMetaContainer');
            metaContainer.innerHTML = `
                <span><i class="fa-regular fa-clock"></i> <strong>${data.duration}</strong></span>
                <span><i class="fa-solid fa-circle-play"></i> Vídeo Individual</span>
            `;

            // Injeta formatos
            const container = document.getElementById('formatsContainer');
            container.innerHTML = '';
            selectedFormat = null;

            const formatList = data.formats.slice(0, 8);
            formatList.forEach((fmt, index) => {
                const div = document.createElement('div');
                div.className = 'format-item' + (index === 0 ? ' selected' : '');
                if (index === 0) selectedFormat = fmt.format_id;

                div.onclick = () => {
                    document.querySelectorAll('#formatsContainer .format-item').forEach(el => el.classList.remove('selected'));
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

            document.getElementById('analysisResult').style.display = 'block';
        }

        // Renderiza visualização de séries
        function renderSeriesView(data) {
            isSeriesMode = true;
            seriesEpisodes = data.episodes;

            document.getElementById('movieFormatsSection').style.display = 'none';
            document.getElementById('seriesSection').style.display = 'block';

            // Preenche dados
            document.getElementById('videoThumb').src = data.thumbnail || 'https://placehold.co/240x135/0f172a/f8fafc?text=Sem+Thumb';
            document.getElementById('videoTitle').innerText = data.title;

            // Meta info
            const metaContainer = document.getElementById('videoMetaContainer');
            metaContainer.innerHTML = `
                <span><i class="fa-solid fa-list-ol"></i> <strong>${data.episodes.length} Episódios</strong></span>
                <span><i class="fa-solid fa-tv"></i> Série</span>
            `;

            // Injeta qualidades padrão
            const qualityContainer = document.getElementById('seriesQualityContainer');
            qualityContainer.innerHTML = '';
            selectedFormat = 'best'; // valor padrão

            const qualities = [
                { id: 'best', resolution: 'Melhor Qualidade (Recomendado)', size: 'Automático' },
                { id: 'best[height<=1080]/best', resolution: 'Full HD (1080p)', size: 'Limite 1080p' },
                { id: 'best[height<=720]/best', resolution: 'HD (720p)', size: 'Limite 720p' },
                { id: 'best[height<=480]/best', resolution: 'SD (480p)', size: 'Limite 480p' }
            ];

            qualities.forEach((q, index) => {
                const div = document.createElement('div');
                div.className = 'format-item' + (index === 0 ? ' selected' : '');

                div.onclick = () => {
                    document.querySelectorAll('#seriesQualityContainer .format-item').forEach(el => el.classList.remove('selected'));
                    div.classList.add('selected');
                    selectedFormat = q.id;
                };

                div.innerHTML = `
                    <div class="format-info">
                        <span class="format-resolution"><i class="fa-solid fa-sliders"></i> ${q.resolution}</span>
                        <span class="format-ext">Filtro inteligente yt-dlp</span>
                    </div>
                    <span class="format-size">${q.size}</span>
                `;
                qualityContainer.appendChild(div);
            });

            // Injeta episódios
            const episodesContainer = document.getElementById('episodesListContainer');
            episodesContainer.innerHTML = '';
            
            document.getElementById('selectAllEpisodes').checked = true;

            data.episodes.forEach((ep, idx) => {
                const label = document.createElement('label');
                label.className = 'episode-item';
                label.innerHTML = `
                    <input type="checkbox" class="episode-checkbox" value="${idx}" checked>
                    <span class="episode-label" title="${ep.label}">${ep.label}</span>
                `;
                episodesContainer.appendChild(label);
            });

            document.getElementById('analysisResult').style.display = 'block';
        }

        // Alterna a seleção de todos os episódios
        function toggleSelectAllEpisodes(masterCheckbox) {
            const checkboxes = document.querySelectorAll('.episode-checkbox');
            checkboxes.forEach(cb => cb.checked = masterCheckbox.checked);
        }

        // Inicia o download
        async function startDownload() {
            const url = document.getElementById('videoUrl').value.trim();
            if (!url || !selectedFormat) {
                alert('Selecione uma resolução/qualidade e certifique-se de que a URL é válida.');
                return;
            }

            const downloadBtn = document.getElementById('startDownloadBtn');
            downloadBtn.disabled = true;
            downloadBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Inicializando...';

            try {
                const formData = new FormData();
                formData.append('url', url);
                formData.append('format_id', selectedFormat);
                
                const titleEl = document.getElementById('videoTitle');
                if (titleEl && titleEl.innerText) {
                    formData.append('title', titleEl.innerText.trim());
                }

                if (isSeriesMode) {
                    const checkboxes = document.querySelectorAll('.episode-checkbox:checked');
                    if (checkboxes.length === 0) {
                        alert('Selecione pelo menos um episódio para baixar.');
                        downloadBtn.disabled = false;
                        downloadBtn.innerHTML = '<i class="fa-solid fa-download"></i> Baixar Conteúdo Selecionado';
                        return;
                    }

                    const selectedEpisodes = [];
                    checkboxes.forEach(cb => {
                        const idx = parseInt(cb.value);
                        selectedEpisodes.push(seriesEpisodes[idx]);
                    });

                    formData.append('is_batch', 'true');
                    formData.append('batch_episodes', JSON.stringify(selectedEpisodes));
                }

                const response = await fetch('api.php?action=download', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.error) {
                    alert(data.error);
                    downloadBtn.disabled = false;
                    downloadBtn.innerHTML = '<i class="fa-solid fa-download"></i> Baixar Conteúdo Selecionado';
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
                downloadBtn.innerHTML = '<i class="fa-solid fa-download"></i> Baixar Conteúdo Selecionado';
            }
        }

        // Inicia o loop de consulta do progresso
        function startPollingProgress() {
            if (progressInterval) clearInterval(progressInterval);

            // Reseta a barra e esconde info de lote inicialmente
            updateProgressBar(0, 'Calculando...', '--:--', 'Calculando...', 'Iniciando...');
            document.getElementById('batchStatusInfo').style.display = 'none';

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
                        alert('O download falhou ou foi abortado.');
                        resetDownloadUI();
                    } else if (data.status === 'completed') {
                        clearInterval(progressInterval);
                        updateProgressBar(100, 'N/A', '00:00', data.size || 'N/A', data.filename || 'Completo');
                        
                        setTimeout(() => {
                            alert('Download concluído com sucesso!');
                            resetDownloadUI();
                            loadLibrary();
                            updateStorageWidget();
                        }, 500);
                    } else {
                        // Status: downloading
                        if (data.is_batch) {
                            document.getElementById('batchStatusInfo').style.display = 'block';
                            document.getElementById('batchActiveEpisode').innerText = `${data.current_episode.label} (${data.current_episode.index} de ${data.current_episode.total})`;
                            document.getElementById('batchEpisodeProgress').innerText = `${data.current_episode.progress}%`;
                            
                            updateProgressBar(
                                data.progress,
                                data.current_episode.speed,
                                data.current_episode.eta,
                                data.current_episode.size,
                                `Fila: ${data.current_episode.label}`
                            );
                        } else {
                            document.getElementById('batchStatusInfo').style.display = 'none';
                            updateProgressBar(
                                data.progress,
                                data.speed,
                                data.eta,
                                data.size,
                                data.filename || 'Processando fluxo de vídeo...'
                            );
                        }
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
            document.getElementById('batchStatusInfo').style.display = 'none';
            const downloadBtn = document.getElementById('startDownloadBtn');
            if (downloadBtn) {
                downloadBtn.disabled = false;
                downloadBtn.innerHTML = '<i class="fa-solid fa-download"></i> Baixar Conteúdo Selecionado';
            }
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

                const grid = document.getElementById('libraryGrid');
                const emptyState = document.getElementById('libraryEmptyState');

                grid.innerHTML = '';

                // Atualiza o widget de total de vídeos
                document.getElementById('widgetTotalFiles').innerText = `${files.length} Vídeo${files.length !== 1 ? 's' : ''}`;

                if (files.length === 0) {
                    emptyState.style.display = 'flex';
                    grid.style.display = 'none';
                } else {
                    emptyState.style.display = 'none';
                    grid.style.display = 'grid';

                    files.forEach(file => {
                        const card = document.createElement('div');
                        card.className = 'video-card';
                        
                        // Gera o gradiente dinâmico baseado no nome do arquivo
                        const gradient = getGradientForTitle(file.name);
                        
                        // Renderiza o card
                        card.innerHTML = `
                            <div class="video-poster-placeholder" style="background: ${gradient};">
                                <div style="font-size: 2.5rem; opacity: 0.25; margin-bottom: auto; align-self: center; margin-top: 2rem;">
                                    <i class="fa-solid fa-film"></i>
                                </div>
                            </div>
                            <div class="poster-info">
                                <div class="poster-title" title="${file.name}">${cleanFileName(file.name)}</div>
                                <div class="poster-meta">
                                    <span><i class="fa-solid fa-database"></i> ${file.size}</span>
                                    <span>${file.created.split(' ')[0]}</span>
                                </div>
                            </div>
                            <div class="video-card-overlay">
                                <button class="play-hover-btn" onclick="playVideo('${file.url}', '${file.name.replace(/'/g, "\\'")}')" title="Assistir Agora">
                                    <i class="fa-solid fa-play"></i>
                                </button>
                                <div class="video-card-actions">
                                    <a href="${file.url}" download class="btn-action-round" title="Salvar no Computador">
                                        <i class="fa-solid fa-download"></i>
                                    </a>
                                    <button class="btn-action-round danger-btn" onclick="deleteVideo('${file.name}')" title="Deletar Arquivo">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                        grid.appendChild(card);
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
                        updateStorageWidget();
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
                    updateStorageWidget(); // Recarrega espaço em disco
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
                        updateStorageWidget(); // Atualiza widget de armazenamento
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
