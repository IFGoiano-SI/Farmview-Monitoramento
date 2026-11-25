# FarmView

Sistema web para monitoramento de cultivos em ambiente controlado (IF Goiano — Campus Ceres / CEBIO).
Integra uma aplicação PHP a um Raspberry Pi com sensores de umidade do solo (ADS1115 + 10HS),
quatro tomadas (relés), câmera ao vivo, gravação de vídeo e irrigação automática.

## Arquitetura

```
Navegador ──► Nginx ──► Apache/PHP (MVC próprio) ──► MySQL
                │              │
                │              └──► Flask no Raspberry Pi (X-Api-Key)
                └── /camera/stream ─► Flask /video_feed (auth_request + token)
```

| Pasta | Conteúdo |
|---|---|
| `index.php`, `core/`, `router/` | Front controller, roteador (autenticação, perfil e CSRF são verificados **antes** do controller) e rotas |
| `Controllers/` | Controllers finos; `BaseController` com helpers comuns |
| `Models/` | Acesso a dados com prepared statements (`UsuarioModel`, `ExperimentoModel`, `VideoModel`, ...) |
| `Services/` | `FlaskService` (único cliente do Raspberry), `Upload`, `Validacao`, `Email`, `Logger` |
| `views/` | Telas; trechos comuns em `views/partials/` |
| `Public/` | CSS, JS, imagens e uploads (execução de scripts bloqueada) |
| `storage/` | Logs, cache e vídeos — **não** acessíveis pela web |
| `db/` | `schema.sql`, `seed.sql` e `migrations/` |
| `raspberry/` | Serviço Flask (`app.py`), dependências e unidade `systemd` |
| `tests/` | PHPUnit (`tests/php`) e pytest com hardware simulado (`tests/raspberry`) |
| `docs/` | Documentação do laboratório de firewall |

## Executando com Docker

```bash
cp .env.example .env      # ajuste senhas, SMTP e RASPBERRY_*
docker compose up -d --build
```

- Acesse `http://localhost`. Na primeira subida o banco é criado a partir de `db/schema.sql`
  com um administrador inicial: **admin@farmview.local / TroqueEstaSenha!** — troque a senha
  em *Usuários* no primeiro acesso.
- phpMyAdmin (opcional, apenas em `127.0.0.1:8081`): `docker compose --profile ferramentas up -d`.

## Executando localmente (XAMPP/WAMP)

1. `composer install`
2. Copie `.env.example` para `.env` e ajuste `DB_*` (ex.: `DB_PORT=3309` no XAMPP), `RASPBERRY_*` e `SMTP_*`.
3. Crie o banco: `mysql -u root -p CEBIOHUB < db/schema.sql` e `db/seed.sql`.
4. Sirva a pasta pelo Apache com `AllowOverride All` (o `.htaccess` faz o roteamento e bloqueia arquivos internos).

Sem Nginx, a câmera é entregue pelo próprio PHP em `/camera/stream`.

## Raspberry Pi

```bash
cd raspberry
sudo apt install python3-opencv python3-rpi.gpio ffmpeg
pip install -r requirements.txt
cp config_farmview.example.json config_farmview.json
echo "FARMVIEW_TOKEN=$(openssl rand -hex 32)" | sudo tee /etc/farmview.env
sudo cp farmview.service /etc/systemd/system/ && sudo systemctl enable --now farmview
```

Use o mesmo valor do token em `RASPBERRY_TOKEN` no `.env` do servidor PHP.

- Todas as rotas (exceto `/health`) exigem o cabeçalho `X-Api-Key`. O painel local do Flask
  funciona sem token no próprio Raspberry; de outra máquina, abra uma vez `http://IP:5000/?token=SEU_TOKEN`.
- Logs: `raspberry/logs/farmview.log` e `journalctl -u farmview`.
- Irrigação automática vem **desligada** por padrão. Proteções: desliga a bomba se a leitura dos
  sensores ficar desatualizada (`leitura_maxima_segundos`) e limita o tempo ligada
  (`tempo_maximo_bomba_segundos` + `pausa_apos_limite_segundos`).
- O histórico do SQLite é limpo automaticamente após `retencao_dias`.

### Contrato PHP ↔ Flask

| Rota | Método | Uso |
|---|---|---|
| `/status` | GET | Câmera, gravação, estado real das 4 tomadas, sensores (com idade da leitura) e limites |
| `/api_sensores` | GET | Tensão e % de umidade calibrada por sensor |
| `/toggle_pin_{1..4}` | POST `acao=on\|off` | Liga/desliga tomada |
| `/toggle_camera` | POST `acao=on\|off` | Liga/desliga câmera (sem `acao`, alterna — painel local) |
| `/start_recording`, `/stop_recording` | POST | Gravação (para e converte para mp4) |
| `/gravacoes/<arquivo>` | GET | Download da gravação pelo PHP |
| `/desligar_tudo` | POST | Desliga tomadas, gravação e câmera (finalizar/excluir experimento) |
| `/video_feed` | GET | MJPEG da câmera |
| `/api_historico` | GET | Histórico de sensores e tomadas em JSON |

## Testes

```bash
composer install && vendor/bin/phpunit                    # regras, helpers, rotas e upload
DB_TEST=1 DB_NAME=farmview_test ... vendor/bin/phpunit     # inclui testes com banco
pip install -r raspberry/requirements-dev.txt && python -m pytest tests/raspberry
```

O workflow `.github/workflows/ci.yml` roda tudo isso a cada push.

## Atualizando uma instalação anterior

1. **Backup** do banco e das pastas `Public/uploadImages`, `Public/templates/Images` e `Public/UploadsVideos`
   (elas deixaram de ser versionadas; um `git pull` remove do disco os arquivos que estavam no repositório).
2. Rode `db/migrations/001_seguranca_e_integridade.sql` (veja as verificações no topo do arquivo).
3. Crie o `.env` a partir de `.env.example` e rode `composer install`.
4. Vídeos antigos continuam disponíveis: o download procura em `storage/videos/` e em `Public/UploadsVideos/`.
5. Atualize o Raspberry para `raspberry/app.py` e configure o `FARMVIEW_TOKEN`.

## Segurança

- Credenciais só por variável de ambiente (`.env` não é versionado).
- Senhas com `password_hash`; tokens de redefinição guardados como SHA-256 e válidos por 1 hora.
- CSRF em todos os formulários; ações que alteram dados aceitam apenas POST.
- Limite de 5 tentativas de login por e-mail/IP a cada 15 minutos.
- Uploads validados pelo conteúdo (finfo), renomeados aleatoriamente e sem execução em `Public/`.
- Vídeos servidos apenas ao dono, fora da pasta pública.
