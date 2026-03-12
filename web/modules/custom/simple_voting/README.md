# simple_voting — Módulo Drupal

Sistema de votação customizado para Drupal 10/11. Desenvolvido como um módulo backend independente — sem nodes, sem JSON:API, sem módulos contrib de UI.

---

## Funcionalidades

- 3 entidades de conteúdo customizadas: `VotingQuestion`, `VotingOption`, `VotingRecord`
- Interface administrativa completa para gerenciar perguntas em `/admin/simple-voting/questions`
- Chave global para habilitar/desabilitar votações em `/admin/config/simple-voting/settings`
- Formulário de votação via CMS em `/voting/{id}`
- API REST com autenticação Basic Auth
- Proteção contra votos duplicados em nível de banco de dados (`UNIQUE KEY`) e de aplicação
- Serviço `VotingManager` centraliza toda a lógica de negócio; reutilizado pelo CMS e pela API

---

## Requisitos

| Ferramenta | Versão   |
| ---------- | -------- |
| PHP        | 8.2+     |
| Drupal     | 10 ou 11 |
| MariaDB    | 10.6+    |
| Lando      | latest   |

---

## Configuração Local

### 1. Iniciar o ambiente

```bash
lando start
```

### 2. Instalar dependências

```bash
lando composer install
```

### 3. Importar o dump do banco de dados

```bash
lando db-import database/dump.sql.gz
```

### 4. Habilitar o módulo

```bash
lando drush en simple_voting basic_auth -y
lando drush cr
```

### 5. Acessar no navegador

O Lando exibe a URL local após o `lando start` (ex: `http://localhost:XXXXX`).

Credenciais padrão do admin: **admin / admin**

---

## Interface Administrativa

| Caminho                                           | Descrição                                        |
| ------------------------------------------------- | ------------------------------------------------ |
| `/admin/simple-voting/questions`                  | Listar, criar e editar perguntas                 |
| `/admin/simple-voting/questions/{id}/options/add` | Adicionar opções a uma pergunta                  |
| `/admin/config/simple-voting/settings`            | Habilitar / desabilitar votações globalmente     |
| `/voting/{id}`                                    | Formulário de votação CMS para a pergunta `{id}` |

---

## API REST

Caminho base: `/api/v1/voting`

Todos os endpoints exigem **HTTP Basic Auth**.
Envie `Content-Type: application/json` e `Accept: application/json` em todas as requisições.

### GET /api/v1/voting/questions

Retorna todas as perguntas ativas.

```json
[
  {
    "uuid": "0a843847-1e1c-11f1-86b8-0242ac140002",
    "title": "Qual a raça de cachorro mais bonita?",
    "show_results": true
  }
]
```

---

### GET /api/v1/voting/questions/{uuid}

Retorna uma pergunta com suas opções de resposta.

```json
{
  "uuid": "0a843847-1e1c-11f1-86b8-0242ac140002",
  "title": "Qual a raça de cachorro mais bonita?",
  "show_results": true,
  "options": [
    { "id": "4", "title": "Chow Chow" },
    { "id": "5", "title": "Husky" },
    { "id": "6", "title": "São Bernardo" },
    { "id": "7", "title": "Akita" }
  ]
}
```

---

### POST /api/v1/voting/questions/{uuid}/vote

Registra um voto para o usuário autenticado.

**Corpo da requisição:**

```json
{ "option_id": 1 }
```

**Respostas:**

| Código | Significado                                          |
| ------ | ---------------------------------------------------- |
| 201    | Voto registrado com sucesso                          |
| 400    | `option_id` ausente ou inválido                      |
| 403    | Votação globalmente desabilitada ou pergunta inativa |
| 404    | Pergunta não encontrada                              |
| 422    | Usuário já votou nesta pergunta                      |

---

### GET /api/v1/voting/questions/{uuid}/results

Retorna a contagem de votos.

```json
{
  "uuid": "0a843847-1e1c-11f1-86b8-0242ac140002",
  "title": "Qual a raça de cachorro mais bonita?",
  "total": 0,
  "options": [
    { "id": "4", "title": "Chow Chow", "votes": 0, "percentage": 0 },
    { "id": "5", "title": "Husky", "votes": 0, "percentage": 0 },
    { "id": "6", "title": "São Bernardo", "votes": 0, "percentage": 0 },
    { "id": "7", "title": "Akita", "votes": 0, "percentage": 0 }
  ]
}
```

Retorna `403` se a pergunta tiver a opção **Exibir Resultados** desativada.

---

## Exemplos rápidos com curl

```bash
BASE="http://localhost:61513"
AUTH="admin:admin"
UUID="0a843847-1e1c-11f1-86b8-0242ac140002"

# Listar perguntas
curl -s -u "$AUTH" "$BASE/api/v1/voting/questions" | python3 -m json.tool

# Detalhe da pergunta
curl -s -u "$AUTH" "$BASE/api/v1/voting/questions/$UUID" | python3 -m json.tool

# Registrar voto
curl -s -u "$AUTH" -X POST \
  -H "Content-Type: application/json" \
  -d '{"option_id": 1}' \
  "$BASE/api/v1/voting/questions/$UUID/vote" | python3 -m json.tool

# Resultados
curl -s -u "$AUTH" "$BASE/api/v1/voting/questions/$UUID/results" | python3 -m json.tool
```

---

## Collection do Postman

Importe o arquivo `simple_voting.postman_collection.json` (raiz do projeto) no Postman.

Atualize a variável de coleção `base_url` com a URL do seu Lando antes de executar as requisições.

---

## Arquitetura

```
simple_voting/
├── config/
│   ├── install/simple_voting.settings.yml   # config padrão
│   └── schema/simple_voting.schema.yml
├── src/
│   ├── Controller/Api/
│   │   ├── QuestionController.php
│   │   ├── VoteController.php
│   │   └── ResultController.php
│   ├── Entity/
│   │   ├── VotingQuestion.php
│   │   ├── VotingOption.php
│   │   └── VotingRecord.php
│   ├── Form/
│   │   ├── VotingSettingsForm.php
│   │   ├── VotingQuestionForm.php
│   │   ├── VotingOptionForm.php
│   │   └── VotingVoteForm.php
│   ├── ListBuilder/
│   │   └── VotingQuestionListBuilder.php
│   ├── Plugin/Block/
│   │   └── VotingBlock.php
│   └── Service/
│       └── VotingManager.php
├── simple_voting.info.yml
├── simple_voting.install
├── simple_voting.links.menu.yml
├── simple_voting.permissions.yml
├── simple_voting.routing.yml
└── simple_voting.services.yml
```

**Decisões de design:**

- `VotingManager` é a única fonte de verdade para as regras de negócio — tanto os formulários CMS quanto os controllers da API o utilizam.
- A `UNIQUE KEY (question_id, user_id)` no banco de dados impede votos duplicados mesmo em requisições concorrentes.
- O campo `source` do voto diferencia votos via CMS (`cms`) de votos via API (`api`).
- O endpoint de resultados respeita o flag `show_results` configurado por pergunta.
