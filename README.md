## Sobre este projeto

A estrutura de pastas e o desacoplamento em
camadas (Actions, Services, Repositories, DTOs, Enums, Exceptions de domínio,
versionamento de rotas) são as práticas que já utilizo (ou já utilizei) ao longo da minha
carreira como desenvolvedor PHP/Laravel. Documento as decisões pontuais
abaixo para deixar claro o raciocínio por trás de cada uma.

## Arquitetura, em uma frase por camada

| Camada                        | Responsabilidade                                                            |
| ----------------------------- | --------------------------------------------------------------------------- |
| `Http/Controllers`            | Orquestra a requisição HTTP, sem lógica de negócio                          |
| `Http/Requests` (FormRequest) | Validação de forma/existência do input, antes de chegar no Controller       |
| `Http/Resources`              | Formata a saída JSON de forma consistente, sem expor colunas cruas do banco |
| `Http/Traits` (ApiResponse)   | Padroniza o envelope de resposta (`success`/`message`/`data` ou `errors`)   |
| `Actions`                     | Caso de uso — a regra de negócio em si, testável sem HTTP                   |
| `Repositories`                | Acesso a dado, isolado do domínio                                           |
| `Services`                    | Integrações externas (autorizador, notificador)                             |
| `Dto`                         | Objetos imutáveis transportando dado entre camadas                          |
| `Enums`                       | Estados fechados de domínio (tipo de cliente, status de transação)          |
| `Exceptions/Domain`           | Exceptions autorrenderizáveis (cada uma sabe seu HTTP status)               |
| `Docs/OpenApi`                | Documentação Swagger separada do Controller, nunca instanciada em runtime   |

## Requisitos

- Docker e Docker Compose — nenhuma outra dependência local é necessária,
  todo o ambiente (PHP 8.4, MySQL, Redis, worker de fila) roda em container.

## Como rodar

1. Copie o arquivo de variáveis de ambiente e preencha:
   ```bash
   cp .env.example .env
   ```
   Variáveis obrigatórias no `.env`: `DB_DATABASE`, `DB_PASSWORD`,
   `REDIS_PASSWORD` (defina qualquer valor local para os três).

2. Suba o ambiente:
   ```bash
   docker compose up -d --build
   ```
   O container `app` já tenta instalar dependências e rodar as migrations
   automaticamente na subida — mas, dependendo da ordem de inicialização
   dos serviços, o MySQL pode ainda não estar pronto nesse momento. Se as
   tabelas não existirem após o `up`, rode manualmente:
   ```bash
   docker compose exec app php artisan migrate
   ```

3. Acesse a API em:
   ```
   http://localhost:6789
   ```

## Banco de dados

### Rodando os seeders

As tabelas de apoio (`customer_types`, `transaction_statuses`) e uma massa
de clientes de teste com carteira precisam ser populadas manualmente:
```bash
docker compose exec app php artisan db:seed
```

### Credenciais (ambiente local/dev)

Como o `.env` não é versionado, seguem os valores usados neste ambiente
local, definidos por você mesmo no passo 1:

| Variável              | Valor                                           |
| --------------------- | ----------------------------------------------- |
| Host (fora do Docker) | `localhost`                                     |
| Porta                 | `33007`                                         |
| Usuário               | `root`                                          |
| Senha                 | o valor definido em `DB_PASSWORD` no seu `.env` |
| Database              | o valor definido em `DB_DATABASE` no seu `.env` |

## Documentação da API (Swagger / OpenAPI)

Gere a especificação a partir dos attributes definidos em `app/Docs/OpenApi`:
```bash
docker compose exec app php artisan l5-swagger:generate
```

Acesse a interface interativa em:
```
http://localhost:6789/api/documentation
```

## Testes

O projeto usa **Pest**, com um banco MySQL isolado (`mysql_testing`) só para
a suíte de testes — não compartilha dados com o banco de desenvolvimento.

Credenciais do banco de teste (já configuradas em `.env.testing`, versionado
por não conter segredo real):

| Variável                    | Valor                                       |
| --------------------------- | ------------------------------------------- |
| Host (interno, rede Docker) | `mysql_testing`                             |
| Porta                       | `3306` (interna) / `3324` (exposta ao host) |
| Usuário                     | `root`                                      |
| Senha                       | `root`                                      |
| Database                    | `testing`                                   |

Rodando a suíte completa:
```bash
docker compose exec app php artisan test
```

Rodando só um grupo específico:
```bash
docker compose exec app php artisan test --filter=TransactionControllerTest
docker compose exec app php artisan test --filter=TransferMoneyActionTest
```

## Endpoint principal

```
POST /api/v1/transfer
Content-Type: application/json

{
  "value": 100.0,
  "payer": 1,
  "payee": 2
}
```

## Filas e notificação assíncrona

O container `queue` processa jobs continuamente (fila `default`, Redis como
driver). A notificação ao recebedor de uma transferência é enviada de forma
assíncrona, com retry e backoff progressivo em caso de falha do serviço
externo. Acompanhar o processamento:
```bash
docker compose logs -f queue
```

## Idioma das mensagens de resposta
 
Todas as mensagens de erro (validação e regras de negócio) são resolvidas
via o sistema de tradução nativo do Laravel (`lang/pt_BR/validation.php` e
`lang/pt_BR/transaction.php`), não estão hardcoded nas classes. O locale
padrão é `pt_BR` (`APP_LOCALE` no `.env`). Isso significa que dar suporte a
outro idioma é uma questão de criar o diretório `lang/{locale}/` equivalente
e resolver o locale ativo (ex: middleware lendo `Accept-Language`) — nenhuma
exception ou `FormRequest` precisa ser alterado para isso.

## Decisões de arquitetura que valem destaque

- **Repositories sem interface**: optei por classes concretas em vez de
  contratos, dado o escopo fechado do desafio — o Laravel resolve por
  autowiring sem necessidade de `bind` manual em `ServiceProvider`.
- **Actions em vez de Services genéricos**: uma classe por caso de uso,
  evitando que um `Service` vire um repositório de métodos não relacionados
  ao longo do tempo.
- **Lock pessimista sem ordenação de IDs**: `lockForUpdate` protege contra
  race condition; o risco (raro) de deadlock em transferências opostas
  simultâneas é resolvido pelo retry nativo do `DB::transaction()`.
- **Falha de negócio não é descartada**: mesmo quando uma transferência é
  barrada (saldo insuficiente, lojista tentando enviar, autorizador
  negando), um registro com status `Failed` é persistido fora do escopo da
  transação principal, preservando rastreabilidade.