# Simple Voting — Drupal

Sistema de votação customizado desenvolvido em Drupal 10/11. Módulo backend independente, sem nodes, sem JSON:API, sem módulos contrib de UI.

---

## Sumário

1. [Tecnologias](#tecnologias)
2. [Configuração do ambiente](#configuração-do-ambiente)
3. [Credenciais](#credenciais)
4. [Interface administrativa](#interface-administrativa)
   - [Perguntas](#perguntas)
   - [Opções de resposta](#opções-de-resposta)
   - [Configurações gerais](#configurações-gerais)
5. [Interface do usuário (CMS)](#interface-do-usuário-cms)
6. [API REST](#api-rest)
7. [Collection do Postman](#collection-do-postman)
8. [Arquitetura](#arquitetura)
9. [Decisões técnicas](#decisões-técnicas)

---

## Tecnologias

| Ferramenta | Versão  |
| ---------- | ------- |
| PHP        | 8.2+    |
| Drupal     | 10 / 11 |
| MariaDB    | 10.6    |
| Lando      | latest  |

---

## Configuração do ambiente

### 1. Iniciar o Lando

```bash
lando start
```

Após o start, o Lando exibe a URL local (ex: `http://localhost:PORTA`).

### 2. Instalar dependências

```bash
lando composer install
```

### 3. Importar o banco de dados

```bash
lando db-import database/dump.sql.gz
```

### 4. Limpar o cache

```bash
lando drush cr
```

### 5. Acessar no navegador

Acesse a URL exibida pelo Lando. Credenciais padrão: **admin / admin**

---

## Credenciais

| Usuário | Senha |
| ------- | ----- |
| admin   | admin |

Usadas tanto para login no Drupal quanto para autenticação Basic Auth na API.

---

## Interface administrativa

### Perguntas

**Caminho:** `/admin/simple-voting/questions`

#### Cadastrar pergunta

1. Acesse `/admin/simple-voting/questions`
2. Clique em **+ Adicionar Pergunta**
3. Preencha:
   - **Título** — texto da pergunta
   - **Ativo** — se a pergunta aceita votos
   - **Exibir Resultados** — se os resultados aparecem para o usuário após votar
4. Clique em **Salvar**

#### Editar pergunta

1. Acesse `/admin/simple-voting/questions`
2. Clique em **Editar** na linha da pergunta desejada
3. Atualize os campos e clique em **Salvar**

#### Remover pergunta

1. Acesse `/admin/simple-voting/questions`
2. Clique em **Excluir** na linha da pergunta desejada
3. Confirme a exclusão

---

### Opções de resposta

**Caminho:** `/admin/simple-voting/questions/{id}/options`

Cada pergunta pode ter várias opções. Cada opção suporta: título, descrição curta e imagem.

#### Cadastrar opção

1. Acesse `/admin/simple-voting/questions` e clique em **Gerenciar Opções** na pergunta desejada
2. Clique em **+ Adicionar Opção**
3. Preencha:
   - **Título** _(obrigatório)_ — texto exibido na votação
   - **Descrição** _(opcional)_ — complemento exibido abaixo do título
   - **Imagem** _(opcional)_ — PNG, JPG, JPEG ou WEBP, até 100 MB
   - **Peso** — controla a ordem de exibição (menor = aparece primeiro)
4. Clique em **Salvar**

#### Editar opção

1. Acesse `/admin/simple-voting/questions/{id}/options`
2. Clique em **Editar** na linha da opção desejada
3. Atualize os campos e clique em **Salvar**

#### Remover opção

1. Acesse `/admin/simple-voting/questions/{id}/options`
2. Clique em **Excluir** na linha da opção desejada
3. Confirme a exclusão

---

### Configurações gerais

**Caminho:** `/admin/config/simple-voting/settings`

| Campo              | Descrição                                                          |
| ------------------ | ------------------------------------------------------------------ |
| Votação habilitada | Quando desmarcado, todo o fluxo de votação é bloqueado — CMS e API |

---

## Interface do usuário (CMS)

**Caminho:** `/voting`

- Lista todas as perguntas ativas
- Botão **Votar** para perguntas ainda não respondidas pelo usuário
- Botão **Ver resultado** para perguntas já respondidas
- Ao votar, o usuário seleciona uma opção e clica em **Votar**
- Após o voto, os resultados são exibidos conforme a configuração de cada pergunta

> Requer login. Qualquer usuário do Drupal com permissão `cast vote` pode votar.

---

## API REST

**Base:** `/api/v1/voting`

Todos os endpoints exigem **HTTP Basic Auth** (`admin:admin`).
Envie `Content-Type: application/json` e `Accept: application/json`.

---

### `GET /api/v1/voting/questions`

Retorna todas as perguntas ativas.

**Resposta 200:**

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

### `GET /api/v1/voting/questions/{uuid}`

Retorna uma pergunta com suas opções de resposta.

**Resposta 200:**

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

**Erros:** `404` pergunta não encontrada.

---

### `POST /api/v1/voting/questions/{uuid}/vote`

Registra o voto do usuário autenticado.

**Corpo:**

```json
{ "option_id": 4 }
```

**Respostas:**

| Código | Significado                                          |
| ------ | ---------------------------------------------------- |
| 201    | Voto registrado com sucesso                          |
| 400    | `option_id` ausente ou inválido                      |
| 403    | Votação desabilitada globalmente ou pergunta inativa |
| 404    | Pergunta não encontrada                              |
| 422    | Usuário já votou nesta pergunta                      |

---

### `GET /api/v1/voting/questions/{uuid}/results`

Retorna a contagem de votos por opção.

**Resposta 200:**

```json
{
  "uuid": "0a843847-1e1c-11f1-86b8-0242ac140002",
  "title": "Qual a raça de cachorro mais bonita?",
  "total": 3,
  "options": [
    { "id": "4", "title": "Chow Chow", "votes": 2, "percentage": 66.67 },
    { "id": "5", "title": "Husky", "votes": 1, "percentage": 33.33 },
    { "id": "6", "title": "São Bernardo", "votes": 0, "percentage": 0 },
    { "id": "7", "title": "Akita", "votes": 0, "percentage": 0 }
  ]
}
```

**Erros:** `403` se a pergunta tiver **Exibir Resultados** desativado.

---

### Exemplos com curl

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
  -d '{"option_id": 4}' \
  "$BASE/api/v1/voting/questions/$UUID/vote" | python3 -m json.tool

# Resultados
curl -s -u "$AUTH" "$BASE/api/v1/voting/questions/$UUID/results" | python3 -m json.tool
```

---

## Collection do Postman

Importe `simple_voting.postman_collection.json` (raiz do projeto) no Postman.

Variáveis de coleção:

| Variável   | Valor padrão                           |
| ---------- | -------------------------------------- |
| `base_url` | `http://localhost:61513`               |
| `uuid`     | `0a843847-1e1c-11f1-86b8-0242ac140002` |

Atualize `base_url` com a porta exibida pelo `lando start`.

---

## Arquitetura

```
web/modules/custom/simple_voting/
├── config/
│   ├── install/simple_voting.settings.yml
│   └── schema/simple_voting.schema.yml
├── src/
│   ├── Controller/
│   │   ├── Api/
│   │   │   ├── QuestionController.php   # GET /questions e /questions/{uuid}
│   │   │   ├── VoteController.php       # POST /questions/{uuid}/vote
│   │   │   └── ResultController.php     # GET /questions/{uuid}/results
│   │   ├── VotingListController.php     # Listagem pública /voting
│   │   └── VotingOptionAdminController.php  # Admin /questions/{id}/options
│   ├── Entity/
│   │   ├── VotingQuestion.php
│   │   ├── VotingOption.php
│   │   ├── VotingRecord.php
│   │   └── VotingQuestionListBuilder.php
│   ├── Form/
│   │   ├── VotingSettingsForm.php
│   │   ├── VotingQuestionForm.php
│   │   ├── VotingOptionForm.php
│   │   └── VotingVoteForm.php
│   ├── Plugin/Block/
│   │   └── VotingBlock.php
│   └── Service/
│       └── VotingManager.php            # Toda a lógica de negócio
├── simple_voting.info.yml
├── simple_voting.install               # UNIQUE KEY no banco + uninstall
├── simple_voting.links.menu.yml
├── simple_voting.permissions.yml
├── simple_voting.routing.yml
└── simple_voting.services.yml
```

---

## Decisões técnicas

| Decisão                                      | Justificativa                                                                                                        |
| -------------------------------------------- | -------------------------------------------------------------------------------------------------------------------- |
| `VotingManager` como serviço central         | Única fonte de verdade para as regras de negócio; reutilizado pelo CMS e pela API sem duplicação                     |
| `UNIQUE KEY (question_id, user_id)` no banco | Impede votos duplicados mesmo em requisições concorrentes — proteção a nível de banco além da validação de aplicação |
| Entidades customizadas (sem nodes)           | Separação de domínio, esquema controlado, sem campos desnecessários do node                                          |
| Campo `source` no `VotingRecord`             | Diferencia votos via CMS (`cms`) de votos via API (`api`) para observabilidade                                       |
| API manual sem JSON:API                      | Controle total sobre o contrato da API, sem expor endpoints não planejados                                           |
| Basic Auth via módulo `basic_auth`           | Autenticação simples e segura para o contexto do teste; fácil de usar no Postman/curl                                |
| `show_results` por pergunta                  | Permite controle granular de transparência sem afetar o fluxo de votação                                             |
