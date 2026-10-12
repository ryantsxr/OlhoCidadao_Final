# Diagramas do Olho Cidadão (v1.0.3)

Os diagramas estão em **Mermaid**: o GitHub desenha sozinho, e dá para colar em https://mermaid.live para exportar como imagem para os slides.

## 1. Casos de uso

```mermaid
flowchart LR
    C((Cidadão))
    A((Administrador))
    V((Visitante))

    subgraph Sistema Olho Cidadão
        UC1[Cadastrar-se / entrar]
        UC2[Registrar denúncia<br/>categoria, foto, mapa]
        UC3[Acompanhar status<br/>e notificações]
        UC4[Complementar / recorrer<br/>/ confirmar resolução]
        UC5[Apoiar denúncia de outro morador]
        UC6[Baixar ou excluir meus dados]
        UC7[Consultar protocolo]
        UC8[Ver mapa, transparência<br/>e Mural de vitórias]
        UC9[Analisar, encaminhar a setor,<br/>priorizar e mudar status]
        UC10[Estatísticas, relatórios,<br/>Kanban, telão]
        UC11[Gerir usuários e auditoria]
        UC12[Ativar 2FA, conferir<br/>integridade e segurança]
    end

    C --> UC1 & UC2 & UC3 & UC4 & UC5 & UC6
    V --> UC7 & UC8
    C --> UC7 & UC8
    A --> UC1 & UC9 & UC10 & UC11 & UC12
```

## 2. Arquitetura MVC

```mermaid
flowchart TB
    N[Navegador] -->|"?rota=..."| I[public/index.php<br/>front controller]
    I --> SEG[Cabeçalhos de segurança, CSP com nonce,<br/>HTTPS, sessão, backup e faxina diários]
    I --> R[Router]
    R --> CT[Controllers<br/>Auth · Denuncia · Admin · Perfil<br/>Configuracao · Dashboard · Pagina<br/>Assistente · Anexo]
    CT --> M[Models<br/>User · Denuncia · Notificacao<br/>LogAuditoria · LoginAttempt · RateLimiter]
    CT --> V[Views<br/>app/Views/*.php]
    CT --> CORE[Core<br/>Uploader · Mailer · Totp · SegurancaConta<br/>PrivacidadeCpf · Integridade · PainelSeguranca<br/>BackupDiario · Manutencao · Ambiente]
    M --> ST{DB_DRIVER}
    ST -->|mysql| DB[(MySQL / MariaDB)]
    ST -->|json| JS[(storage/*.json)]
    CORE --> UP[(storage/uploads<br/>fotos privadas)]
    CORE --> K[(storage/*.key<br/>chaves)]
```

## 3. Banco de dados

```mermaid
erDiagram
    users ||--o{ denuncias : "registra"
    users ||--o{ notificacoes : "recebe"
    users ||--o{ auditoria : "admin executa"
    denuncias ||--o{ denuncia_historico : "tem"
    denuncias ||--o{ denuncia_apoios : "recebe"
    users ||--o{ denuncia_apoios : "apoia"
    denuncias ||--o{ notificacoes : "gera"

    users {
        bigint id PK
        varchar email UK
        char cpf_hash UK "HMAC do CPF"
        varchar role "cidadao | admin"
        tinyint ativo
        longtext payload "nome, senha Argon2id, CPF cifrado, 2FA..."
    }
    denuncias {
        bigint id PK
        bigint usuario_id "nulo se a conta foi excluída"
        varchar categoria
        varchar status
        varchar prioridade
        varchar orgao
        datetime criada_em
        decimal latitude
        decimal longitude
        longtext payload "descrição, fotos, selos..."
    }
    denuncia_historico {
        bigint denuncia_id PK,FK
        int sequencia PK
        varchar status
        datetime data
        longtext payload "mensagem + selo HMAC"
    }
    denuncia_apoios {
        bigint denuncia_id PK,FK
        bigint usuario_id PK
    }
    notificacoes {
        bigint id PK
        bigint usuario_id
        bigint denuncia_id
        tinyint lida
        datetime criada_em
    }
    auditoria {
        bigint seq_id PK
        bigint admin_id
        bigint denuncia_id
        datetime criada_em
    }
    login_attempts {
        varchar chave PK "e-mail|IP (apagado após 24 h)"
    }
    rate_limits {
        varchar chave PK "ação:IP ou ação:conta"
    }
    app_settings {
        varchar chave PK "âncoras de integridade"
    }
```

## 4. Ciclo de vida da denúncia

```mermaid
stateDiagram-v2
    [*] --> Pendente: cidadão registra (recebe protocolo)
    Pendente --> Cancelada: cidadão cancela
    Pendente --> EmAnalise: admin analisa / encaminha a um setor
    EmAnalise --> AguardandoConfirmacao: admin marca como resolvida (com foto)
    Pendente --> Rejeitada: admin rejeita (motivo obrigatório)
    EmAnalise --> Rejeitada: admin rejeita (motivo obrigatório)
    Rejeitada --> EmAnalise: cidadão recorre (justificativa)
    AguardandoConfirmacao --> Resolvido: cidadão confirma (+ avaliação)
    AguardandoConfirmacao --> EmAnalise: cidadão reabre ("não foi resolvido")
    Resolvido --> [*]
    Cancelada --> [*]

    EmAnalise: Em análise
    AguardandoConfirmacao: Aguardando confirmação
```

Cada transição vira uma linha no histórico com um **selo HMAC encadeado** — se alguém alterar o banco por fora, a tela "Histórico íntegro" acusa.

## 5. Login com segurança

```mermaid
sequenceDiagram
    actor U as Usuário
    participant S as Sistema
    U->>S: e-mail + senha (+ token CSRF)
    S->>S: limite por IP, por e-mail+IP e por conta
    S->>S: confere senha (tempo igual exista ou não a conta)
    alt senha errada
        S-->>U: "E-mail ou senha incorretos" (conta mais uma tentativa)
    else admin com 2FA
        S-->>U: pede o código de 6 dígitos
        U->>S: código do app (ou de recuperação)
        S->>S: valida, impede reutilizar o mesmo código
        S-->>U: painel + e-mail "novo acesso"
    else admin sem 2FA num servidor
        S-->>U: só libera a tela para ativar a 2FA (QR code)
    else cidadão
        S-->>U: nova sessão (ID regenerado) → painel
    end
```
