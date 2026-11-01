# DiscoGest — Sistema de Gestão de Discos

Aplicação web em PHP + MySQL para gerir discos compactos, artistas, produção, instrumentos, géneros e utilizadores, com autenticação por perfis e auditoria de acções.

Projeto académico. Os dados incluídos no dump são **de desenvolvimento**.

---

## Requisitos

- **PHP 8.0+** com as extensões `mysqli` e `mbstring`
  > A pesquisa usa `mb_strtolower()`/`mb_stripos()`. Sem `mbstring` as páginas com `?q=` rebentam com *Call to undefined function*.
- **MySQL 8** ou **MariaDB 10.4+**
- Linux ou Windows

> Em Linux o MySQL distingue maiúsculas (`lower_case_table_names=0`). O dump cria as tabelas em **PascalCase** (`Utilizador`, `Disco_Compacto`, `NivelAcesso`, …) para coincidir com o SQL do código.

---

## Instalação

1. **Criar a base de dados** (o dump apaga e recria `discocompacto`):
   ```bash
   mysql -u root -p < Dump20261101.sql
   ```

2. **Configurar a ligação** em `src/repository/connection.php`:
   ```php
   private static $hostname = "127.0.0.1";
   private static $username = "root";
   private static $password = "...";
   private static $database = "discocompacto";
   ```
   Usa `127.0.0.1` (TCP) e não `localhost`, para o PHP não tentar o socket Unix.

3. **Arrancar a aplicação** a partir da raiz do projeto:
   ```bash
   php -S 127.0.0.1:8181
   ```
   ou servir a pasta com Apache/XAMPP.

4. Abrir `http://127.0.0.1:8181/` — o `index.php` encaminha para a administração (com sessão) ou para o login.

---

## Contas de desenvolvimento

| Utilizador | Senha | Perfil |
|---|---|---|
| `kenny` | `kenny1` | Administrador |
| `ju` | `julia1234` | Operador |
| `EV` | `Nenani123` | Auditor |
| `Fernando` | `langa123` | SuperOperador |
| `ewen` | `ewen123` | Estagiário |

---

## Estrutura

```
.
├── index.php                  # entrada: redireciona para login/administração
├── Dump20261101.sql           # dump completo (schema + dados de dev)
└── src/
    ├── controller/            # 14 controllers (orquestram os DAOs)
    ├── model/                 # 14 entidades + Sessao + Permissao
    ├── repository/            # 15 DAOs + connection.php
    └── view/
        ├── pages/             # 12 páginas .php (view + tratamento do POST)
        ├── css/               # 1 CSS por página
        └── resources/         # imagens estáticas (login.png, user.png)
```

**Fluxo:** `pages/*.php` → `controller/*.php` → `repository/*.php` (DAO) → `model/*.php` → MySQL.

Cada página começa por validar a sessão (`Sessao::getUtilizadorLogado()`) e as permissões (`Permissao::pode()`); sem sessão redireciona para `Login.php`.

---

## Páginas

| Página | Função |
|---|---|
| `Login.php` / `LogOut.php` | Autenticação e saída |
| `mudar-senha.php` | Alteração da própria senha |
| `registro.php` | Registo de discos, edição e faixas |
| `listagemdiscos.php` | Listagem, detalhes, edição de discos e faixas |
| `artistas.php` | Músicos, compositores e cantores |
| `producao.php` | Produtores, gravadoras e editoras (3 abas) |
| `instrumentos.php` | CRUD de instrumentos |
| `generos.php` | CRUD de géneros |
| `administracao.php` | Utilizadores, perfis e permissões |
| `logs.php` | Auditoria (perfil Auditor) |
| `foto.php` | Serve a foto de perfil a partir da base de dados |

As listagens aceitam `?q=` para pesquisa (filtragem feita em PHP, em memória; `logs.php` também).

---

## Perfis e permissões

As regras vivem na tabela `NivelAcesso` e são lidas por `Permissao::pode($utilizador, $accao)`; a interface esconde os botões e o servidor volta a validar no `POST`.

| Perfil | Ler | Adicionar | Editar | Remover | Utilizadores | Logs |
|---|:-:|:-:|:-:|:-:|:-:|:-:|
| Operador | ✅ | ✅ | — | — | — | — |
| SuperOperador | ✅ | ✅ | ✅ | ✅ | — | — |
| Administrador | ✅ | ✅ | ✅ | ✅ | ✅ | — |
| Auditor | ✅ | ✅ | ✅ | ✅ | — | ✅ |
| Estagiário | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

---

## Fotos de perfil

- Guardadas na base de dados: `Utilizador.foto` (`longblob`) + `Utilizador.foto_tipo` (MIME).
- Upload validado pelo conteúdo (`getimagesize()`), aceita JPEG/PNG/WEBP até **2 MB**.
- Servidas por `foto.php` (própria foto, ou qualquer uma para quem tem a permissão `utilizadores`), com `ETag`/304 e `nosniff`.

---

## Base de dados

`discocompacto` — 25 tabelas, 26 chaves estrangeiras, com `ON DELETE CASCADE` nas tabelas de ligação.

O dump inclui o schema e os dados de desenvolvimento (5 utilizadores, 4 discos, 176 logs) e pode ser reexecutado a qualquer momento: apaga e recria a base de dados.

---

## Limitações conhecidas

- **Senhas em texto simples** e comparação com `BINARY` — adequado apenas a contexto académico.
- **Sem proteção CSRF** nos formulários.
- **Credenciais da base de dados** no código (`connection.php`).
- O *reset* de senha em `administracao.php` usa um valor fixo.
- **Sem autoload**: cada ficheiro faz `require_once` manual.
- Pesquisa filtrada em memória, não em SQL.
- `InstrumentoDAO` não tem `atualizar()` (só inserir/listar/remover).
- Sem testes automatizados nem integração contínua.
