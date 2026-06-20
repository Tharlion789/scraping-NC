# Scraping NC (KumaDownloader)

Uma aplicação web local desenvolvida em PHP, HTML e JavaScript para baixar vídeos de forma acelerada de plataformas como OK.ru e players integrados (como NetCinema/eee1.lat). A aplicação conta com um painel de gerenciamento moderno com design premium em Glassmorphism, biblioteca local para assistir aos vídeos diretamente pelo navegador e suporte a pastas de destino personalizáveis (incluindo discos externos).

## 🚀 Funcionalidades

- **Análise Inteligente de Links:** Extração automática de resoluções, durações e tamanhos estimados a partir de links do OK.ru ou páginas do NetCine.
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

1. **Analisar Link:** Cole a URL do filme/episódio da plataforma suportada na caixa de entrada e clique em **Analisar**. A aplicação buscará as qualidades de vídeo disponíveis.
2. **Baixar Vídeo:** Escolha a resolução desejada na lista apresentada e clique em **Baixar Vídeo Selecionado**.
3. **Acompanhar Progresso:** O download iniciará no background e exibirá a porcentagem, velocidade média e tempo restante de forma dinâmica. Você pode fechar ou atualizar a página, pois o download continuará rodando em segundo plano.
4. **Assistir Online:** Assim que concluído, o filme aparecerá na **Biblioteca Local** na lateral direita. Clique no ícone de Play para assistir diretamente no player do navegador com avanço rápido suave.
5. **Liberar Espaço:** Caso um download falhe por falta de espaço, acesse o painel de **Configurações** e clique em **Limpar Arquivos Temporários (.part)** para apagar os arquivos parciais residuais.

## 📂 Estrutura de Arquivos

- `index.php` - Interface do usuário (UI frontend) responsiva e interativa.
- `style.css` - Folha de estilos premium baseada em Glassmorphism.
- `api.php` - API backend que gerencia análise, downloads via PowerShell e monitoramento de processos.
- `stream.php` - Transmissor binário de streaming HTTP 206 para players de mídia.
- `config.json` - Armazena as configurações locais de caminhos salvos pelo usuário.

---
Desenvolvido por **KumaCorp Desenvolvimento e soluções**.
