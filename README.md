# DiscoGest

Catalogue management for compact discs, tracks, artists, production entities, instruments and genres, with session-based authentication, role-based permissions and audit logging.

Plain PHP 8 + MySQL. No framework, no Composer, no build step.

---

## 1. Requirements

| Dependency | Version | Notes |
|---|---|---|
| PHP | 8.0+ | Uses named arguments and `str_contains()` |
| `ext-mysqli` | — | All database access |
| `ext-mbstring` | — | Search normalisation (`mb_strtolower`, `mb_stripos`). Without it, every `?q=` page fails with a fatal error |
| MySQL / MariaDB | 8.0 / 10.4+ | InnoDB, `utf8mb4` |

---

## 2. Installation

### 2.1 Database

The dump drops and recreates the `discocompacto` schema, so it is safe to re-run:

```bash
mysql -u root -p < Dump20261101.sql
```

It contains the schema, reference data (`NivelAcesso`), and the demo catalogue. It contains **no user accounts and no audit rows** — those come from the seed step.

### 2.2 Connection

Edit `src/repository/connection.php`:

```php
private static $hostname = "127.0.0.1";
private static $username = "root";
private static $password = "...";
private static $database = "discocompacto";
```

Use `127.0.0.1` (TCP) rather than `localhost`: with `localhost`, `mysqli` tries the Unix socket, which fails when PHP and MySQL come from different installations.

### 2.3 Seed

```bash
php seeds/seed.php [username] [password]
```

Defaults to `admin` / `admin123`. The script is idempotent (it checks the username first) and creates a user with the `Administrador` profile and `Primeiro_Acesso = 1`, so the application forces a password change on the first login.

Password length is validated at 8 characters. The password is stored in clear text, matching the current authentication implementation (see §10).

### 2.4 Run

```bash
php -S 127.0.0.1:8181
```

Then open `http://127.0.0.1:8181/`. The root `index.php` redirects to `Login.php` or to `administracao.php`, depending on the session. Serving the repository root with Apache works the same way.

---

## 3. Architecture

### 3.1 Layers

| Layer | Directory | Responsibility | Current state |
|---|---|---|---|
| View | `src/view/pages` | Session guard, permission flags for the UI, HTML helpers and rendering | No request handling, validation or data access |
| Controller | `src/controller` | Entity controllers are thin façades over DAOs; **page controllers** handle POST, validation, permission re-checks, audit logging, filtering and view-model preparation | Page controllers may use DAOs directly where the fine-grained controllers lack the needed orchestration |
| Repository | `src/repository` | SQL via prepared statements, row → entity mapping | Mixed return types (`bool`/`int`/`array`/entity); some methods swallow `mysqli_sql_exception` |
| Model | `src/model` | Entities (POJO), `Sessao`, `Permissao` | No behaviour beyond getters/setters |
| Database | MySQL | Schema, FK constraints, permission flags | `connection.php` is a static singleton with hardcoded credentials |

### 3.2 Request lifecycle

1. The page file bootstraps: `require_once` of `Sessao`, `Permissao` and the page controller.
2. `Sessao::getUtilizadorLogado()` restores the `Utilizador` object from `$_SESSION['utilizadorLogado']` (the object is serialised in the session; the photo blob is not).
3. If there is no session, the page redirects to `Login.php`; if the profile lacks the required permission, it redirects or blocks.
4. On `POST`, the page delegates to the page controller (`processarPost`): permission check (`Permissao::bloquear`), validation, DAO calls and `LogsController::registar(...)`.
5. The page asks the controller for the ready-to-render state (`prepararEcra`) and only outputs HTML.

Page controllers: `ProducaoController`, `ArtistasController`, `RegistoDiscoController`, `ListagemDiscosController` and `AdministracaoController`. Simple pages reuse the entity controllers directly (`GeneroController`, `InstrumentoController`, `LogsController`, `NivelController`, `LoginController`, `MusicoController`). There is no service layer: the flow is view → controller → repository → model.

There is no router: every page is directly reachable and repeats the bootstrap block.

### 3.3 Permission model

Permissions are stored as flags on `NivelAcesso` and read once per request by `Permissao::regras($nivel)` (cached in a static array). `Permissao::pode($utilizador, $accao)` accepts `ler`, `adicionar`, `editar`, `remover`, `utilizadores`, `logs`.

The UI hides unavailable actions **and** the server re-validates every POST — hiding elements is presentation only.

| Profile | read | add | edit | remove | users | logs |
|---|:-:|:-:|:-:|:-:|:-:|:-:|
| Operador | ✅ | ✅ | — | — | — | — |
| SuperOperador | ✅ | ✅ | ✅ | ✅ | — | — |
| Administrador | ✅ | ✅ | ✅ | ✅ | ✅ | — |
| Auditor | ✅ | ✅ | ✅ | ✅ | — | ✅ |


---

## 4. Directory layout

```
.
├── index.php                   # entry point: session check + redirect
├── Dump20261101.sql            # schema + reference/demo data (no users)
├── seeds/
│   └── seed.php                # CLI: creates the initial administrator
└── src/
    ├── controller/             # 19 controllers (5 page controllers + 14 entity controllers)
    ├── model/                  # 14 entities + Sessao + Permissao
    ├── repository/             # 15 DAOs + connection.php
    └── view/
        ├── pages/              # 12 PHP pages (rendering only)
        ├── css/                # one stylesheet per page
        ├── js/                 # progressive enhancement: photo preview and form validation
        └── resources/          # static images (login.png, default avatar)
```

---

## 5. Database

### 5.1 Schema

25 tables, 26 foreign keys, InnoDB / `utf8mb4_general_ci`.

Core tables:

| Table | Purpose |
|---|---|
| `Utilizador` | Accounts, profile FK, `foto` (`longblob`) + `foto_tipo` |
| `NivelAcesso` | Profile plus one boolean per permission |
| `Logs` | Audit trail; denormalised actor data (`Nome`, `Apelido`, `Perfil`, `Email`), no FK to `Utilizador` |
| `Disco_Compacto`, `Faixa`, `Edicao` | Discs, tracks and their publishers |
| `Cantor`, `Compositor`, `Musico`, `Produtor`, `Gravadora`, `Editora`, `Instrumento`, `Genero` | Catalogue entities |
| `*_DC`, `Faixa_*`, `Musico_Instrumento`, `Disco_Genero` | Many-to-many link tables, `ON DELETE CASCADE` |

### 5.2 Identifier casing

Table names are **PascalCase** (`Utilizador`, `Disco_Compacto`, `NivelAcesso`). On Linux, MySQL uses `lower_case_table_names = 0`, so table identifiers are case-sensitive and must match the SQL in the code exactly. On Windows the comparison is case-insensitive.

### 5.3 Re-seeding

`Dump20261101.sql` starts with `DROP DATABASE IF EXISTS discocompacto`, so importing it always yields a clean state. User accounts and audit rows are intentionally absent; run `seeds/seed.php` afterwards.

---

## 6. Authentication

- `Login.php` posts `username` + `password`; `LoginDAO::login()` looks the account up with `WHERE BINARY username = ? AND BINARY senha = ?`.
- On success, the `Utilizador` object (id, name, profile) is stored in `$_SESSION` and a login row is written to `Logs`.
- `Primeiro_Acesso = 1` redirects the user to `mudar-senha.php` before any other page.
- `LogOut.php` destroys the session.
- There is no session id regeneration, and no `HttpOnly`/`SameSite` cookie configuration.

---

## 7. Pages

| Page | Function |
|---|---|
| `Login.php`, `LogOut.php` | Authentication and sign-out |
| `mudar-senha.php` | Password change (also used for first access) |
| `registro.php` | Register a disc, its edition and tracks |
| `listagemdiscos.php` | List, detail, edit and delete discs; edit tracks |
| `artistas.php` | Musicians, composers and singers |
| `producao.php` | Producers, labels and publishers (three tabs) |
| `instrumentos.php`, `generos.php` | CRUD of instruments and genres |
| `administracao.php` | Users, profiles and permissions |
| `logs.php` | Audit trail (requires the `logs` permission) |
| `foto.php` | Serves profile photos from the database |

---

## 8. Profile photos

- Stored in `Utilizador.foto` (`longblob`) with the MIME type in `Utilizador.foto_tipo`. No filesystem path is involved.
- Upload is validated by content (`getimagesize()`), restricted to JPEG/PNG/WEBP and capped at 2 MB (`upload_max_filesize`). Invalid or oversized files produce a red error instead of being silently ignored.
- Written with `mysqli_stmt::send_long_data()`; when no new file is submitted the existing photo is preserved (`UPDATE ... SET` without the `foto` column).
- Served by `foto.php`, which enforces the same permission rule as the UI: own photo, or any photo for profiles with `utilizadores`. Responses carry `ETag`, `304` revalidation, `Cache-Control: private, no-cache` and `X-Content-Type-Options: nosniff`.
- Templates fall back to `src/view/resources/user.png` when no photo is stored; `foto.php` serves that fallback itself.
- The create and edit forms use a small script (`src/view/js/foto-preview.js`) to show the selected image before upload. This is the only JavaScript on the page.

---

## 9. Search

List pages accept `?q=`, applied in PHP over the already-loaded collection (`array_filter`) after normalising case with `mb_strtolower`/`mb_stripos`. Complexity is O(n) in rows loaded and it does not use SQL indexes. This is the reason `ext-mbstring` is a hard requirement.

---

## 10. Form validation

Forms use standard HTML constraints (`required`, `type="email"`, `min`, `max`, `maxlength`, `minlength`) plus a single shared script, `src/view/js/form-validation.js`, loaded by the common header and by the login/password pages. On submit the script:

- calls the browser's Constraint Validation API (`checkValidity()` / `reportValidity()`) for the normal fields;
- blocks list actions when no selection radio (`sel`, `sel_perfil`, ...) is chosen;
- requires at least one genre when registering a disc.

Search forms have no required fields and remain freely submittable.

---

## 11. Security posture

Known and accepted for an academic context, but not production-ready:

- Passwords are stored in clear text and compared with `BINARY`.
- No CSRF tokens on any form.
- No `session_regenerate_id()` on login; no cookie hardening.
- Database credentials are committed in `src/repository/connection.php`.
- `administracao.php` resets passwords to a hardcoded value.
- `Connection::connectionDB()` calls `die()` with the driver error message.
- No rate limiting, no HTTPS enforcement, no security headers other than `nosniff` on photos.

---

## 12. Technical debt / next steps

| Area | Issue |
|---|---|
| Bootstrap | No autoloader (PSR-4) — every file repeats `require_once`; no single entry point |
| Naming | Inconsistent file casing (`Login.php`, `LogOut.php`, `nivelAcesso.php`, `generorepository.php`) — breaks on case-sensitive filesystems |
| Layers | Page controllers still reach some DAOs directly where entity controllers lack the needed orchestration; no service layer |
| Persistence | No transactions around multi-table writes (disc + edition + tracks + relations) |
| Contracts | DAOs return `bool`/`int`/`array`/entity indifferently; some catch exceptions and return `false` |
| Logging | `Logs` stores copied actor data instead of `Codigo_User` + FK |
| Search | In-memory filtering instead of SQL `LIKE`/indices |
| Quality | No automated tests, no CI, no static analysis, no migrations |
| Instrumentos | `InstrumentoDAO` has no `update()` |

---

## 13. Development

```bash
# syntax check
find . -name '*.php' -not -path './.git/*' -exec php -l {} \;

# reset the database and recreate the administrator
mysql -u root -p < Dump20261101.sql
php seeds/seed.php admin admin123
```
