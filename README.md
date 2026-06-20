# Scraping NC (KumaDownloader)

Uma aplicação web local desenvolvida em PHP, HTML e JavaScript para baixar vídeos de forma acelerada de plataformas como OK.ru e players integrados (como NetCinema/eee1.lat). A aplicação conta com um painel de gerenciamento moderno com design premium em Glassmorphism, biblioteca local para assistir aos vídeos diretamente pelo navegador e suporte a pastas de destino personalizáveis (incluindo discos externos).

## 🚀 Funcionalidades

- **Análise Inteligente de Links:** Extração automática de resoluções, durações e tamanhos estimados a partir de links de vídeos individuais do OK.ru ou páginas completas de séries (/tvshows/) no NetCinema.
- **Download de Séries em Lote Sequencial:** Permite analisar uma série inteira, selecionar os episódios desejados por checklist e baixá-los sequencialmente no background através de um script PowerShell dinâmico, com monitoramento do progresso geral e do episódio ativo.
- **Visual Premium Atualizado (Netflix/Plex Style):** Interface repaginada em Glassmorphism escuro com neon, contendo um dashboard com widgets de estatísticas (total de vídeos na biblioteca, espaço em disco com barra de progresso colorida de alerta e status de conexão do servidor).
- **Biblioteca Local em Grade (Poster Grid):** Os vídeos baixados são organizados em uma grade de cartazes interativos com gradientes gerados dinamicamente com base nos títulos. Ao passar o mouse, é exibido um overlay com botão Play centralizado e ações rápidas (download local e exclusão).
- **Resiliência contra Anúncios e Bloqueios:** O extrator de player contorna estruturas complexas de iframes de propaganda e tokens de streaming dinâmicos.
- **Downloads em Segundo Plano:** Execução autônoma de downloads através do utilitário `yt-dlp` rodando sob PowerShell em segundo plano, monitorado de forma segura via identificador de processo (PID).
- **Tolerância a Instabilidades de Rede:** O monitoramento de progresso ignora avisos de timeout temporários de conexão, garantindo que o download continue tentando recuperar o sinal no background sem travar a interface do usuário.
- **Escolha de Pasta de Destino:** Painel de configurações dedicado para definir qualquer diretório do computador ou HD externo para salvar os vídeos, exibindo dinamicamente o espaço livre em disco.
- **Streaming com Alta Performance (`stream.php`):** Transmissão binária dos vídeos usando **HTTP Range Requests (HTTP 206)**, permitindo avançar e retroceder os filmes instantaneamente no player HTML5 sem precisar baixar o arquivo inteiro primeiro, mesmo que os vídeos estejam salvos em outra partição do computador.
- **Biblioteca Local Integrada:** Listagem higienizada (exibindo apenas vídeos reais `.mp4`, `.mkv`, etc., ocultando resíduos temporários `.part`) com player embutido e exclusão automática de arquivos e fragmentos residuais.

## 🛠️ Requisitos de Instalação

1. **Servidor Local PHP (XAMPP):**
   - Apache com suporte a PHP 7.4 ou superior.
   - PHP configurado com a extensão `curl` ativada (padrão no XAMPP).
2. **Python:**
   - Python instalado e configurado no sistema. A aplicação usa por padrão o executável no caminho `C:\Users\Tharlion\anaconda3\python.exe` (pode ser ajustado na linha 9 do `api.php`).
3. **yt-dlp:**
   - Instale o `yt-dlp` através do Python:
     ```bash
     pip install yt-dlp
     ```
4. **FFmpeg:**
   - FFmpeg instalado na máquina no caminho `C:\ffmpeg\bin\ffmpeg.exe` (utilizado para remuxagem automática de fragmentos de vídeo em arquivos finais de mídia).

## 💻 Como Instalar e Rodar

1. Clone ou baixe este repositório para a pasta `htdocs` do seu XAMPP (ex: `C:\xampp\htdocs\kumadownloader`).
2. Inicie o servidor Apache através do painel de controle do XAMPP.
3. Abra o seu navegador e acesse:
   ```
   http://localhost/kumadownloader/
   ```
4. No canto superior direito, clique em **Configurações** para verificar e definir a pasta onde deseja salvar os downloads (o padrão é a pasta interna `downloads/`).

## 📖 Como Usar

1. **Analisar Link:** Cole a URL de um filme ou de uma série na barra de pesquisa superior e clique em **Analisar**. O sistema identificará automaticamente o tipo de conteúdo.
2. **Configurar o Download:**
   - **Para Filmes:** Selecione a resolução desejada na lista de formatos extraídos da API.
   - **Para Séries:** Selecione a qualidade limite desejada (ex: Melhor Qualidade, Full HD, etc.) e marque os episódios que deseja baixar no checklist (por padrão todos vêm marcados).
3. **Baixar Conteúdo:** Clique no botão **Baixar Conteúdo Selecionado**.
4. **Acompanhar Progresso:** O download iniciará no background via PowerShell. O card de progresso mostrará o percentual geral do download (no caso de séries, exibe também qual episódio está sendo processado ativamente, o progresso dele, velocidade e ETA). Você pode fechar o navegador que os downloads continuarão rodando de forma resiliente.
5. **Assistir Online:** Concluído o download, o vídeo é listado instantaneamente na **Biblioteca Local** como um card de poster cinemático. Passe o mouse sobre ele para ver o botão Play e clique para assistir diretamente no player do navegador com avanço rápido suave (HTTP Range 206).
6. **Gerenciamento e Limpeza:** Use a engrenagem de **Configurações** para redefinir a pasta de destino (o espaço livre será recalculado em tempo real no topo) ou para apagar fragmentos temporários parciais `.part`.

## 📂 Estrutura de Arquivos

- `index.php` - Interface do usuário (UI frontend) responsiva e interativa.
- `style.css` - Folha de estilos premium baseada em Glassmorphism.
- `api.php` - API backend que gerencia análise, downloads via PowerShell e monitoramento de processos.
- `stream.php` - Transmissor binário de streaming HTTP 206 para players de mídia.
- `config.json` - Armazena as configurações locais de caminhos salvos pelo usuário.

---
Desenvolvido por **KumaCorp Desenvolvimento e soluções**.
