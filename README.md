# Project Manager API

Um simples gerenciador de projetos.

As seeds fornecidas (`./vendor/bin/sail artisan db:seed`) não são mandatórias para o funcionamento da aplicação, mas são extremamente úteis para popular o ambiente de desenvolvimento com dados de teste, facilitando a exploração e o teste das funcionalidades.

Para login, as credenciais de teste criadas pela seed são:
*   **Email:** `user@example.com`
*   **Senha:** `password`

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

### Arquitetura e Decisões Técnicas

A estrutura desta API foi planejada para promover uma clara separação de responsabilidades, o que facilita a manutenção e a futura expansão do projeto. A seguir, estão os principais padrões e decisões arquiteturais adotados.

**Autenticação com Laravel Sanctum**

A autenticação nesta API é gerenciada pelo **Laravel Sanctum**. Ele é utilizado para emitir e gerenciar *API tokens*, permitindo que clientes (como Single Page Applications ou aplicações móveis) se autentiquem e interajam de forma segura com os endpoints protegidos da aplicação. O `AuthController.php` (`app/Http/Controllers/AuthController.php`) é o responsável por lidar com o fluxo de login e logout, tanto para a API quanto para possíveis SPAs.

**Controllers**

Ao explorar a pasta `app/Http/Controllers`, você notará que os controllers, como o `ProjectController.php`, são intencionalmente concisos. A função deles é atuar como uma porta de entrada para as requisições HTTP, orquestrando o fluxo: eles recebem a requisição, delegam a validação para classes específicas (Form Requests, como o `StoreProjectRequest.php`) e invocam outras camadas da aplicação para executar a lógica de negócio, antes de finalmente formatar e retornar uma resposta.

**Separando Responsabilidades com Services e Repositories**

Para evitar que os controllers fiquem sobrecarregados com regras de negócio, adotamos duas camadas principais:

1.  **Service Layer**: Lógicas de negócio mais complexas ou reutilizáveis são encapsuladas em classes de serviço. O `app/Services/ProjectProgressService.php` é um bom exemplo. Sua única responsabilidade é calcular o progresso de um projeto com base em suas tarefas. Isso mantém a lógica centralizada, testável e desacoplada do controller.

2.  **Repository Pattern**: Para a camada de acesso a dados, utilizamos o Repository Pattern. Em `app/Repositories/Interfaces`, definimos "contratos", como a `ProjectRepositoryInterface.php`, que estabelecem os métodos que a aplicação pode usar para interagir com os dados, sem saber os detalhes da implementação. A implementação concreta, que utiliza o Eloquent, reside em `app/Repositories/Eloquent/EloquentProjectRepository.php`. Essa abstração é valiosa, pois nos permite, por exemplo, trocar a fonte de dados ou adicionar uma camada de cache no futuro, alterando apenas a implementação do repositório, sem impactar os controllers que o consomem.

**Uma Nota Sobre o Cálculo de Progresso**

Um aspecto central da regra de negócio é como o progresso de um projeto é calculado. Em vez de uma simples contagem de tarefas concluídas, implementamos um sistema de cálculo ponderado, onde cada tarefa contribui com uma quantidade de "pontos" baseada em sua dificuldade.

*   **Enum para Dificuldade**: Para gerenciar essa complexidade de forma segura e legível, utilizamos um Enum "backed" do PHP em `app/Enums/TaskDifficulty.php`. Por ser do tipo inteiro (`Int`), ele armazena no banco de dados os valores `1`, `2` ou `3`, em vez de strings como "baixa" ou "alta". Essa abordagem é mais eficiente, otimizando tanto o espaço de armazenamento quanto a performance das consultas e indexações na coluna de dificuldade. Além disso, o próprio Enum encapsula os pontos correspondentes a cada nível no método `points()`, mantendo a regra de negócio centralizada na própria estrutura de dados.

*   **Cálculo Otimizado**: A execução desse cálculo é otimizada para diferentes cenários:
    *   Ao buscar um único projeto, o `ProjectProgressService` é acionado para calcular o progresso detalhado.
    *   Na listagem de projetos, para evitar o conhecido problema de N+1 queries, o progresso é calculado diretamente no banco de dados através de uma subquery SQL, definida no método `paginate` do `EloquentProjectRepository`. Isso garante uma performance muito melhor ao carregar múltiplos projetos.

Em resumo, a arquitetura busca um código limpo e desacoplado, onde cada componente tem uma responsabilidade bem definida.

**Documentação da API com Scramble**

Para facilitar a documentação interativa e o teste dos endpoints da API, integramos o [Scramble](https://scramble.dedoc.co/installation). Por conta disso, alguns endpoints foram replicados no `routes/api.php` para que o Scramble possa processá-los corretamente e gerar uma interface intuitiva para explorar a API.

Adicionalmente, no `app/Http/Controllers/AuthController.php`, você encontrará pares de métodos para login e logout:
*   `login()` e `logout()`: Utilizados para autenticação e desautenticação via *API tokens* (gerado pelo Sanctum), ideal para clientes que consomem a API diretamente.
*   `spaLogin()` e `spaLogout()`: Projetados para autenticação e desautenticação de aplicações Single Page Application (SPA), que geralmente utilizam autenticação baseada em sessão.

Essa separação e a replicação de rotas garantem que a documentação gerada pelo Scramble seja completa e que seja possível testar facilmente ambos os fluxos de autenticação diretamente pela interface da ferramenta.

