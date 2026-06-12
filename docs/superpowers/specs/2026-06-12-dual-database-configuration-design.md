# Design Spec: Dual Database Configuration (Local PostgreSQL & Supabase)

This design specification outlines the setup to support switching database connections between local PostgreSQL (installed via Homebrew) and remote Supabase (hosted PostgreSQL).

## Objective

To allow the developer to seamlessly switch the application's active database between local PostgreSQL (for quick offline development) and remote Supabase (for production parity/remote data testing) with a single environment variable change.

## Proposed Changes

### 1. Environment Configuration (`.env`)

We will configure two distinct blocks of environment variables in the local `.env` file:
*   A default block prefixed with standard `DB_*` referencing the local PostgreSQL connection.
*   A secondary block prefixed with `SUPABASE_DB_*` referencing the Supabase transaction pooler.

```env
# Switch database: 'pgsql' (local PostgreSQL) or 'supabase' (remote Supabase)
DB_CONNECTION=pgsql

# --- LOCAL POSTGRESQL CONFIGURATION ---
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=be_fieldbooking
DB_USERNAME=postgres
DB_PASSWORD=

# --- SUPABASE (REMOTE) CONFIGURATION ---
SUPABASE_DB_HOST=aws-1-ap-northeast-1.pooler.supabase.com
SUPABASE_DB_PORT=6543
SUPABASE_DB_DATABASE=postgres
SUPABASE_DB_USERNAME=postgres.qcizbglhafqgrphobbly
SUPABASE_DB_PASSWORD=q8sdwFzovhv3lDUA
```

### 2. Database Connections Configuration (`config/database.php`)

Add a new database connection named `supabase` inside the `'connections'` array in `config/database.php`. This connection maps the `SUPABASE_DB_*` environment variables.

```php
        'supabase' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('SUPABASE_DB_HOST'),
            'port' => env('SUPABASE_DB_PORT', '6543'),
            'database' => env('SUPABASE_DB_DATABASE', 'postgres'),
            'username' => env('SUPABASE_DB_USERNAME'),
            'password' => env('SUPABASE_DB_PASSWORD'),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => env('DB_SSLMODE', 'prefer'),
        ],
```

---

## Verification Plan

1.  **Local Connection Check**:
    Ensure `DB_CONNECTION` is set to `pgsql` and run:
    ```bash
    php artisan db:show
    ```
    This should report connection details for `127.0.0.1:5432`.

2.  **Supabase Connection Check**:
    Switch `DB_CONNECTION` to `supabase` and run:
    ```bash
    php artisan db:show
    ```
    This should report connection details for the Supabase host on port `6543`.
