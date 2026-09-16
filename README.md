# CDR Teste

Sistema Laravel 13 para consulta autenticada de SUP, CADUP/STFC e CADUP/SMP, com PHP 8.5 e `laravel/boost` em `require-dev`.

## Instalação

Requer PHP 8.5 com `mbstring`, `fileinfo`, PDO, Composer e um banco compatível com transações. Execute `composer install`, copie `.env.example` para `.env`, crie `database/database.sqlite` (ou configure MySQL), `php artisan key:generate` e `php artisan migrate`. Crie o primeiro administrador com `php artisan cdr:create-admin email@dominio.com "Nome"`. Inicie o servidor e um worker com `php artisan queue:work --timeout=3600`. Em produção, configure HTTPS, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, SMTP para redefinição de senha e limites `upload_max_filesize`/`post_max_size` maiores que 100 MB. Laravel Boost pode ser configurado com `php artisan boost:install` após instalar dependências.

Não há cadastro público. O administrador cria contas, escolhe perfil e ativa/desativa usuários. Clientes autenticados acessam todos os relatórios; apenas administradores acessam `/admin`.

## Referência e decisões de adaptação

Foi examinado `sapn_teste` no commit `240b220f408461fe98e29f773be4174b9a47c37c`. `SupController` corta 25 campos de uma linha fixa de 589 bytes (posições 0–588); `AbrTelecomController` usa índices 0,1,3–9,11 de STFC e 0–6 de SMP em arquivo separado por `;`. As migrations originais guardam DDD, prefixo e CNL como inteiros, perdendo zeros à esquerda; aqui são texto. O importador antigo carrega tudo em memória, faz `truncate` antes de validar, usa `LOAD DATA LOCAL INFILE`, armazena o upload sob `public/storage` e descarta o original. Aqui a leitura é em fluxo, em lotes de 500, com armazenamento privado, histórico e publicação por ponteiro de importação ativa.

Nenhum arquivo real ou exemplo SUP, STFC ou SMP foi incluído no repositório de referência. Por isso não se confirmou cabeçalho, encoding, largura efetiva dos campos, semântica de status nem associação entre conjuntos. O importador CADUP exige cabeçalhos explícitos com os nomes das colunas das migrations, aceita `;`, `,` ou tabulação quando inequívocos e rejeita o layout indexado sem cabeçalho do controlador antigo. STFC exige `nome_prestadora,cnpj_prestadora,codigo_nacional,prefixo,mcdu_i,mcdu_f,cnl,nome_localidade,area_local,cod_area_local`; SMP exige `nome_prestadora,cnpj_prestadora,codigo_nacional,prefixo,faixa_inicial,faixa_final,status`. A ordem pode variar e colunas extras são ignoradas. SUP usa provisoriamente as posições do controlador de referência, exige comprimento mínimo de 589 bytes e códigos de localidade/município; um arquivo de produção deve ser confrontado campo a campo antes de confiar nessa carga. Planilhas XLS/XLSX não foram habilitadas porque não há evidência de que façam parte dos dados. Nenhuma coluna nova de origem foi inventada; `import_id` é metadado interno.

Cada carga escreve somente na tabela correspondente com um `import_id` novo. Até terminar, as consultas filtram o lote anterior. Ao concluir, uma transação troca `datasets.active_import_id` daquele tipo. Falhas removem o lote incompleto e deixam o ponteiro anterior intacto. Um lock de cache serializa uploads do mesmo tipo; um segundo upload concorrente falha com erro registrado e pode ser reenviado. Os lotes antigos permanecem fisicamente para auditoria, mas não são consultados. É necessária uma política de retenção/limpeza antes de acumular grandes volumes.

## Regras de consulta

O número pesquisado remove pontuação e espaços; remove `55` inicial somente quando o total tem 12 ou 13 dígitos. Aceita então 10 ou 11 dígitos, interpretados como DDD de 2 dígitos, prefixo de 4 ou 5 e faixa dos 4 dígitos finais. A comparação é textual e inclusiva (`inicial <= finais <= final`) somente para faixas de largura 4. Entradas fora dessa estrutura são rejeitadas. Uma linha STFC exibe seu CNL; SUP exibe seus códigos de localidade e município. SMP não possui CNL e nenhum CNL é inferido por DDD, prefixo ou operadora. Não há junção automática SUP↔STFC↔SMP porque a chave e a cardinalidade não foram confirmadas. Todos os resultados correspondentes são paginados em grupos de 25 por conjunto.

## Pendências de confirmação

Antes de usar dados de produção, forneça exemplos representativos e anonimizados dos três feeds, incluindo cabeçalho, linhas com acentos, zeros à esquerda, faixas de borda, status e localização. Eles permitirão confirmar posições SUP, layout CADUP original, encoding, obrigatoriedade real das colunas e qualquer associação segura entre CNL SUP e STFC. Até lá, a interface e o parser suportam somente as regras descritas acima.
