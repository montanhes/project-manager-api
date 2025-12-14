# Project Manager API

Um simples gerenciador de projetos.

## Requisitos

- Docker
- Composer

## Instalação

1. Clone o repositório:
   ```bash
   git clone git@github.com:montanhes/project-manager-api.git
   ```
2. Navegue até o diretório do projeto:
   ```bash
   cd project-manager-api
   ```
3. Copie o arquivo de ambiente:
   ```bash
   cp .env.example .env
   ```
4. Instale as dependências do PHP:
   ```bash
   composer install
   ```
5. Gere a chave da aplicação:
   ```bash
   php artisan key:generate
   ```
6. Inicie os containers do Docker com o Sail:
    ```bash
    ./vendor/bin/sail up -d
    ```
7. Rode as migrações do banco de dados:
   ```bash
   ./vendor/bin/sail artisan migrate
   ```
8. Execute as seeds padrões do banco de dados:
   ```bash
   ./vendor/bin/sail artisan db:seed
   ```

## Executando a Aplicação

1. Certifique-se de que os containers do Docker estão em execução:
   ```bash
   ./vendor/bin/sail up -d
   ```
## Executando os Testes

- Para rodar a suíte de testes completa:
  ```bash
  ./vendor/bin/sail test
  ```
