# Sistema de Transferências

## Índice
1. [Sobre o Projeto](#sobre-o-projeto)
2. [Tecnologias Utilizadas](#tecnologias-utilizadas)
3. [Configuração do Ambiente](#configuração-do-ambiente)
4. [Como Funciona](#como-funciona)
5. [Endpoints da API](#endpoints-da-api)
6. [Testes](#testes)

---

## Sobre o Projeto

Este é um sistema de transferências financeiras desenvolvido em **Laravel** que permite que usuários realizem transferências de dinheiro entre carteiras digitais. O sistema utiliza mensageria com **Kafka** para processamento assíncrono e notificações.

### Funcionalidades Principais:
- Transferência de valores entre usuários
- Validação de saldo
- Autorização externa de transferências
- Notificações via Kafka

---

### Componentes:

- **Controllers**: Recebem requisições e retornam respostas
- **Services**: Contêm a lógica de negócio
- **Repositories**: Gerenciam acesso ao banco de dados
- **Models**: Representam as tabelas do banco
- **DTOs**: Transferem dados entre camadas
- **Kafka**: Mensageria para processamento assíncrono

---

## Tecnologias Utilizadas

### Backend
- **PHP 8.2**
- **Laravel 12**
- **MySQL** (dados relacionais)
- **MongoDB** (transferências)

### Infraestrutura
- **Docker** e **Docker Compose**
- **Apache Kafka** (mensageria)
- **PostgreSQL** (Conduktor Console)

### Ferramentas
- **Conduktor Console** (interface para Kafka)
- **PHPUnit** (testes)

---

## Configuração do Ambiente

### Pré-requisitos
- Docker Desktop instalado
- Git

### Passo a Passo

#### 1. Clone o repositório
```bash
git clone https://github.com/LucasViniciusR/sistema-pagamento.git
cd pagamento-app
```

#### 2. Configure o arquivo .env
```bash
cp .env.example .env
```

#### 3. Suba os containers
```bash
docker-compose up -d
```

#### 4. Gere a chave da aplicação
```bash
docker exec -it pagamento-app php artisan key:generate
```

#### 5. Execute as migrations
```bash
docker exec -it pagamento-app php artisan migrate
```

#### 6. (Opcional) Popule o banco com dados de teste
```bash
docker exec -it pagamento-app php artisan db:seed
```

### 7. (Opcional) Consumir Mensagens do Kafka:

```bash
docker exec -it pagamento-app php artisan kafka:consumir-transferencias
```

---

## Como Funciona

### Conceitos Principais

#### 1. **Usuários**
Existem dois tipos de usuários:
- **Comum**: Pode enviar e receber transferências
- **Lojista**: Pode apenas receber transferências

#### 2. **Carteiras**
Cada usuário possui uma carteira digital com saldo.

#### 3. **Transferências**
São registros de movimentações financeiras entre usuários.

---

## Endpoints da API

### POST /api/transferencias

Realiza uma transferência entre usuários.

#### Request:
```json
{
  "valor": 100.50,
  "pagador_id": 1,
  "recebedor_id": 2
}
```

#### Response - Sucesso (200):
```json
{
  "mensagem": "Transferência realizada com sucesso",
  "transferencia": {
    "_id": "507f1f77bcf86cd799439011",
    "pagador_id": 1,
    "recebedor_id": 2,
    "valor": 100.50,
    "status": "sucesso",
    "created_at": "2025-11-27T10:30:00Z"
  }
}
```

#### Response - Erro (400):
```json
{
  "mensagem": "Saldo insuficiente",
  "erro": "SaldoInsuficienteException"
}
```

---

## Testes

### Executar todos os testes
```bash
docker exec -it pagamento-app php artisan test
```

### Executar testes específicos
```bash
# Testes de Feature
docker exec -it pagamento-app php artisan test --filter TransferenciaTest

# Testes de Unit
docker exec -it pagamento-app php artisan test --testsuite Unit
```

### Cobertura de Testes
```bash
docker exec -it pagamento-app php artisan test --coverage
```

### Principais Cenários Testados:

- Transferência bem-sucedida
- Saldo insuficiente
- Lojista tentando transferir
- Valor inválido
- Usuário não encontrado
- Transferência para mesmo usuário
- Processamento de mensagens Kafka

---

### Acessar Conduktor Console:

1. Abra http://localhost:8080
2. Visualize tópicos, mensagens e consumidores
3. Monitore o fluxo de mensagens

---

## Possíveis Próximos Passos

1. Adicionar autenticação e criação de usuários
2. Criar dashboard de monitoramento
3. Implementar retry de notificações
4. Melhorar cobertura de testes
5. Documentar API (Swagger/OpenAPI)
