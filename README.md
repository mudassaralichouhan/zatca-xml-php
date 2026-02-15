# ZATCA Fatoora API

Unofficial REST API for ZATCA (Saudi e-invoicing): compliance/production CSID, simplified and standard invoice reporting and clearance.

---

## Requirements

- **PHP** 8.4+ (extensions: `curl`, `dom`, `json`, `mbstring`, `openssl`, `pdo`, `redis`, `xml`, `xmlwriter`, `zip`, `gmp`)
- **Composer**
- **Redis** (optional; can use file cache via `.env`)

---

## Setup

### Option 1: Docker (recommended)

1. **Clone and go to the project**
   ```bash
   cd zatca-lib
   ```

2. **Environment**
   ```bash
   cp .env.example .env
   ```
   Edit `.env` if you need to change ports or secrets. Defaults are fine for local use.

3. **Run**
   ```bash
   docker compose up -d
   ```

4. **Install dependencies (inside container)**  
   If you change code and need to run composer:
   ```bash
   docker compose exec web composer install
   ```

5. **Base URL**
   - API: `http://localhost:8080/api/v1`
   - Health: `http://localhost:8080/api/v1/health`
   - Mail UI (MailHog): `http://localhost:8025`

---

### Option 2: Hosting server (no Docker)

1. **Clone and enter project**
   ```bash
   git clone https://github.com/mudassaralichouhan/zatca-xml-php zatca-lib && cd zatca-lib
   ```

2. **Install PHP dependencies**
   ```bash
   composer install --no-dev
   ```

3. **Environment**
   ```bash
   cp .env.example .env
   ```
   Set at least:
   - `BASE_URL` – your public URL (e.g. `https://api.yourdomain.com`)
   - `APP_ENV=pro`
   - `JWT_SECRET` and `CRYPTO_SECRET_KEY` – strong random values
   - `REDIS_*` if using Redis, or keep `STORAGE_DRIVER=file` and `STORAGE_FILE_PATH=storage/cache`
   - Mail settings if you use email (e.g. confirmations)

4. **Permissions**
   ```bash
   chmod -R 775 storage
   chown -R www-data:www-data storage   # or your web server user
   ```

5. **Web server**
   - Document root: project root (where `index.php` is), or point your vhost to this directory.
   - Ensure `index.php` receives all requests (e.g. nginx `try_files $uri $uri/ /index.php?$query_string;`).

6. **Cron (optional)**  
   If you use the cleanup cron, add something like:
   ```bash
   * * * * * cd /path/to/zatca-lib && php -r "require 'vendor/autoload.php'; /* your cleanup script */"
   ```

---

## Testing the API (Postman)

1. **Import** the collection from: https://gist.github.com/mudassaralichouhan/1f075942c7d9dcf2a372cc1ffa10b5d9

2. **Variables**
   - `base_url` = `http://localhost:8080/api/v1` (Docker) or `https://your-api-domain.com/api/v1` (server).
   - `bearer_token` = set after login (e.g. from **auth → login** response).
   - `x_api_key` = your API key (from **auth → key** after login).

3. **Headers**
   - **zatca-mode**: `developer-portal` or `simulation` (as in the collection).
   - **Authorization**: Bearer token for auth endpoints; **X-API-KEY** for Fatoora endpoints (CSID, reporting, clearance).

4. **Flow**
   - **auth → register** → confirm email (or use **auth → mail/resend**).
   - **auth → login** → copy `access_token` into `bearer_token`.
   - **auth → key** (PATCH) to add allowed IPs/domains; use the returned API key as `x_api_key`.
   - Call **fatoora → csr** (compliance/production) and **reporting** / **clearance** with `request_id` and the invoice payload.

---

## Main endpoints (from collection)

| Area        | Method | Path                 | Description              |
|------------|--------|----------------------|--------------------------|
| Health     | GET    | `/api/v1/health`      | Health check             |
| Auth       | POST   | `/api/v1/auth/register` | Register                 |
| Auth       | POST   | `/api/v1/auth/login`  | Login (get JWT)           |
| Auth       | GET    | `/api/v1/auth/me`    | Current user (JWT)       |
| Auth       | GET/PATCH/PUT/DELETE | `/api/v1/auth/key` | API keys (IP/domain)     |
| CSID       | POST/GET | `/api/v1/csid/compliance` | Compliance CSID     |
| CSID       | GET    | `/api/v1/csid/production` | Production CSID     |
| Reporting  | POST   | `/api/v1/reporting?request_id=...` | Simplified invoices |
| Clearance  | POST   | `/api/v1/clearance?request_id=...` | Standard invoices  |

Use the Postman collection for exact request bodies and examples.

---

## Contributing

Contributions are welcome! Feel free to open issues or submit pull requests on the repository.
