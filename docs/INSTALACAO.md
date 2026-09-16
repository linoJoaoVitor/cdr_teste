# Instalação do CDR Teste com MariaDB

Há dois caminhos: Docker Compose (em Debian, Windows com Docker Desktop ou outro host Docker) e instalação nativa no Debian 13. A aplicação exige PHP 8.5; o Debian 13 oferece PHP 8.4 nos seus repositórios padrão, então a instalação nativa de 8.5 usa o repositório PHP de Ondřej Surý. [Pacotes Debian](https://packages.debian.org/source/stable/php8.4), [instruções oficiais do repositório Sury](https://packages.sury.org/php/README.txt).

## Docker Compose: PHP 8.5 + Nginx + MariaDB

Instale Docker Desktop no Windows, ou Docker Engine e o plugin Compose no Debian conforme a [documentação oficial do Docker](https://docs.docker.com/engine/install/debian/). O host não precisa instalar PHP, Composer, Nginx ou MariaDB: o `Dockerfile` instala PHP 8.5, extensões e Composer; `compose.yaml` inicia Nginx, MariaDB e o worker.

```bash
git clone -b codex/cdr-teste https://github.com/linoJoaoVitor/cdr_teste.git
cd cdr_teste
cp .env.docker.example .env
```

Edite `.env`: substitua `DB_PASSWORD`, e configure SMTP se quiser que usuários recebam links de redefinição de senha. A senha MariaDB será usada na primeira inicialização do volume. Gere a chave da aplicação e copie a linha `base64:...` exibida para `APP_KEY=` em `.env`:

```bash
docker compose build app
docker compose run --rm --no-deps app php artisan key:generate --show
docker compose up -d
docker compose exec -T app php artisan migrate --force
docker compose exec app php artisan cdr:create-admin admin@exemplo.com "Administrador"
```

O último comando pede uma senha inicial de pelo menos 12 caracteres sem mostrá-la no terminal. Abra `http://localhost:8080/entrar`. Os uploads originais e arquivos de sessão/cache ficam no volume `cdr_storage`; os dados MariaDB ficam no volume `mariadb_data`. O banco não publica a porta 3306 no host. `docker compose down` interrompe os serviços e preserva os volumes; `docker compose up -d --build` atualiza a imagem após mudanças no código. Depois de uma atualização, execute `docker compose exec -T app php artisan migrate --force`.

Para usar Docker em produção, coloque o acesso HTTP atrás de HTTPS, ajuste `APP_URL`, `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, configure SMTP e substitua senhas de exemplo. O Nginx permite corpo de até 105 MB; PHP aceita arquivo de até 100 MB, conforme a validação da aplicação. A imagem oficial do MariaDB usa `MARIADB_DATABASE`, `MARIADB_USER`, `MARIADB_PASSWORD` e `healthcheck.sh`, como descrito pela [MariaDB](https://mariadb.com/docs/server/server-management/automated-mariadb-deployment-and-administration/docker-and-mariadb/using-healthcheck-sh).

## Debian 13 nativo: PHP 8.5 + MariaDB + Nginx ou Apache

Execute como usuário com `sudo`. Comece pelo repositório PHP 8.5 e pelos pacotes necessários. A sequência de configuração do repositório abaixo segue o [README do mantenedor](https://packages.sury.org/php/README.txt):

```bash
sudo apt-get update
sudo apt-get install -y lsb-release ca-certificates curl git unzip
sudo curl -sSLo /tmp/debsuryorg-archive-keyring.deb https://packages.sury.org/debsuryorg-archive-keyring.deb
sudo dpkg -i /tmp/debsuryorg-archive-keyring.deb
echo "deb [signed-by=/usr/share/keyrings/debsuryorg-archive-keyring.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/php.list
sudo apt-get update
sudo apt-get install -y php8.5-cli php8.5-fpm php8.5-mysql php8.5-mbstring php8.5-xml php8.5-curl php8.5-zip php8.5-bcmath php8.5-intl
php8.5 -v
php8.5 -m | grep -E 'pdo_mysql|mbstring|fileinfo'
```

Instale o [Composer pelo instalador oficial](https://getcomposer.org/download/), conferindo a assinatura atual publicada pelo projeto antes de executá-lo:

```bash
curl -sS https://getcomposer.org/installer -o composer-setup.php
EXPECTED_SIGNATURE=$(curl -sS https://composer.github.io/installer.sig)
ACTUAL_SIGNATURE=$(php8.5 -r "echo hash_file('sha384', 'composer-setup.php');")
test "$EXPECTED_SIGNATURE" = "$ACTUAL_SIGNATURE" || { echo 'Assinatura inválida'; exit 1; }
sudo php8.5 composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php
composer --version
```

Instale MariaDB, crie o banco com `utf8mb4` e um usuário exclusivo para a aplicação. Troque a senha SQL abaixo e repita a mesma senha em `DB_PASSWORD` no `.env`. MariaDB recomenda instalação via APT e dispõe de [guia oficial para Debian](https://mariadb.com/docs/server/mariadb-quickstart-guides/installing-mariadb-server-guide).

```bash
sudo apt-get install -y mariadb-server mariadb-client
sudo systemctl enable --now mariadb
sudo mariadb
```

```sql
CREATE DATABASE cdr_teste CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cdr_teste'@'127.0.0.1' IDENTIFIED BY 'troque-por-uma-senha-forte';
GRANT ALL PRIVILEGES ON cdr_teste.* TO 'cdr_teste'@'127.0.0.1';
EXIT;
```

Instale o código e configure ambiente, chave e migrations. O `DB_HOST=127.0.0.1` usa conexão TCP e combina com o usuário MariaDB criado acima. Configure também `APP_URL`, SMTP e senha do banco no `.env` antes das migrations.

```bash
sudo mkdir -p /var/www/cdr_teste
sudo chown "$USER":"$USER" /var/www/cdr_teste
git clone -b codex/cdr-teste https://github.com/linoJoaoVitor/cdr_teste.git /var/www/cdr_teste
cd /var/www/cdr_teste
composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader
cp .env.example .env
# Edite .env: DB_CONNECTION=mariadb, DB_HOST=127.0.0.1 e a senha do banco
php8.5 artisan key:generate
php8.5 artisan migrate --force
sudo chown -R www-data:www-data storage bootstrap/cache
php8.5 artisan cdr:create-admin admin@exemplo.com "Administrador"
```

Se precisar de Boost no servidor de desenvolvimento, execute `composer install` sem `--no-dev` e depois `php8.5 artisan boost:install`. Em produção, `--no-dev` mantém essa dependência fora da imagem/runtime.

### Opção Nginx

```bash
sudo apt-get install -y nginx
sudo systemctl enable --now php8.5-fpm nginx
```

Crie `/etc/nginx/sites-available/cdr_teste` com o conteúdo abaixo, trocando `ServerName` por seu domínio em `server_name`. O diretório público é sempre `/var/www/cdr_teste/public`, conforme as [instruções de deploy do Laravel](https://laravel.com/docs/13.x/deployment).

```nginx
server {
    listen 80;
    server_name cdr.exemplo.com;
    root /var/www/cdr_teste/public;
    index index.php;
    client_max_body_size 105m;
    charset utf-8;
    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }
    location ~ \.php$ { return 404; }
    location ~ /\. { deny all; }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/cdr_teste /etc/nginx/sites-enabled/cdr_teste
sudo nginx -t
sudo systemctl reload nginx
```

### Opção Apache

```bash
sudo apt-get install -y apache2
sudo a2enmod proxy_fcgi setenvif rewrite headers
sudo a2enconf php8.5-fpm
sudo systemctl enable --now php8.5-fpm apache2
```

Crie `/etc/apache2/sites-available/cdr_teste.conf` e troque o domínio. O `.htaccess` de Laravel em `public/` depende de `AllowOverride All` e `mod_rewrite`; a [documentação do Apache](https://httpd.apache.org/docs/2.4/mod/core.html#allowoverride) descreve essa diretiva.

```apache
<VirtualHost *:80>
    ServerName cdr.exemplo.com
    DocumentRoot /var/www/cdr_teste/public
    <Directory /var/www/cdr_teste/public>
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog ${APACHE_LOG_DIR}/cdr_teste_error.log
    CustomLog ${APACHE_LOG_DIR}/cdr_teste_access.log combined
</VirtualHost>
```

```bash
sudo a2ensite cdr_teste.conf
sudo apache2ctl configtest
sudo systemctl reload apache2
```

### PHP-FPM, uploads e worker

Em `/etc/php/8.5/fpm/php.ini`, defina `upload_max_filesize=100M`, `post_max_size=105M` e `memory_limit=512M`; reinicie `php8.5-fpm`. Para importações grandes, mantenha `DB_QUEUE_RETRY_AFTER=3700`, maior que o timeout de 3600 segundos do job.

Crie `/etc/systemd/system/cdr-teste-worker.service`:

```ini
[Unit]
Description=CDR Teste - worker de importações
After=network.target mariadb.service

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/cdr_teste
ExecStart=/usr/bin/php8.5 /var/www/cdr_teste/artisan queue:work --sleep=3 --tries=1 --timeout=3600
Restart=always
RestartSec=5
TimeoutStopSec=3650

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now cdr-teste-worker
sudo systemctl status cdr-teste-worker
```

Antes de abrir o serviço na Internet, instale HTTPS, ajuste `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`, `SESSION_SECURE_COOKIE=true`, configure SMTP e rode `php8.5 artisan optimize`. Depois de atualizar o código, rode migrations e `sudo systemctl restart cdr-teste-worker`. O Laravel exige escrita em `storage/` e `bootstrap/cache/`; o procedimento acima concede essa escrita ao processo web. [Documentação oficial de deploy](https://laravel.com/docs/13.x/deployment).
