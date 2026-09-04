#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")"

COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.local.yml}"
TEST_DB="${TEST_DB:-pacs_testing}"
TEST_USER="${TEST_USER:-pacs_testing}"
TEST_PASSWORD="${TEST_PASSWORD:-pacs_testing}"

for pair in "TEST_DB=${TEST_DB}" "TEST_USER=${TEST_USER}"; do
    name="${pair%%=*}"
    value="${pair#*=}"
    if ! [[ "$value" =~ ^[A-Za-z_][A-Za-z0-9_]*$ ]]; then
        echo "Недопустимое значение ${name}='${value}': разрешены латиница, цифры и подчёркивание." >&2
        exit 1
    fi
done

TEST_PASSWORD_SQL="${TEST_PASSWORD//\'/\'\'}"

if [ ! -f "$COMPOSE_FILE" ]; then
    echo "Не найден compose-файл '${COMPOSE_FILE}' в $(pwd)." >&2
    exit 1
fi

compose() {
    docker compose -f "$COMPOSE_FILE" "$@"
}

if ! compose ps --status running --services 2>/dev/null | grep -qx app-database; then
    echo "Контейнер app-database не запущен: docker compose -f ${COMPOSE_FILE} up -d app-database" >&2
    exit 1
fi

candidates=()
[ -n "${DB_SUPERUSER:-}" ] && candidates+=("$DB_SUPERUSER")
if container_user="$(compose exec -T app-database printenv POSTGRES_USER 2>/dev/null | tr -d '\r')"; then
    [ -n "$container_user" ] && candidates+=("$container_user")
fi
if [ -f .env ]; then
    env_user="$(grep -E '^DB_USERNAME=' .env | head -1 | cut -d= -f2- | tr -d '"'"'"'')"
    [ -n "$env_user" ] && candidates+=("$env_user")
fi
candidates+=("pacs" "postgres")

SUPERUSER=""
for candidate in "${candidates[@]}"; do
    [ -n "$candidate" ] || continue
    if [ "$(compose exec -T app-database psql -tAX -U "$candidate" -d postgres \
            -c "SELECT rolcreatedb OR rolsuper FROM pg_roles WHERE rolname = current_user" \
            2>/dev/null | tr -d '\r')" = "t" ]; then
        SUPERUSER="$candidate"
        break
    fi
done

if [ -z "$SUPERUSER" ]; then
    echo "Не удалось подобрать роль с правом создавать базы (пробовали: ${candidates[*]})." >&2
    echo "Задайте её явно: DB_SUPERUSER=имя ./setup-test-db.sh" >&2
    exit 1
fi

psql_exec() {
    compose exec -T app-database psql -q -v ON_ERROR_STOP=1 -U "$SUPERUSER" -d postgres "$@"
}

role_exists="$(psql_exec -tAc "SELECT 1 FROM pg_roles WHERE rolname = '${TEST_USER}'" | tr -d '\r')"

if [ "$role_exists" != "1" ]; then
    psql_exec -c "CREATE ROLE ${TEST_USER} LOGIN CREATEDB PASSWORD '${TEST_PASSWORD_SQL}'"
    echo "Создана роль ${TEST_USER}."
else
    foreign_dbs="$(psql_exec -tAc "SELECT string_agg(datname, ', ' ORDER BY datname) FROM pg_database
        WHERE pg_get_userbyid(datdba) = '${TEST_USER}'
          AND datname <> '${TEST_DB}'
          AND coalesce(shobj_description(oid, 'pg_database'), '') <> 'pinvideo-pacs:testing'" | tr -d '\r')"

    if [ -z "$foreign_dbs" ] || [ "${ALLOW_ROLE_PASSWORD_RESET:-}" = "1" ]; then
        psql_exec -c "ALTER ROLE ${TEST_USER} LOGIN CREATEDB PASSWORD '${TEST_PASSWORD_SQL}'"
        echo "Роль ${TEST_USER} уже существует, пароль приведён к заданному."
    else
        echo "Роль ${TEST_USER} уже существует и владеет базами вне тестового контура: ${foreign_dbs}." >&2
        echo "Пароль не меняется — похоже, это роль работающего приложения." >&2
        echo "Если роль всё-таки тестовая: ALLOW_ROLE_PASSWORD_RESET=1 ./setup-test-db.sh" >&2
        exit 1
    fi
fi

owner="$(psql_exec -tAc "SELECT pg_get_userbyid(datdba) FROM pg_database WHERE datname = '${TEST_DB}'" | tr -d '\r')"

if [ -z "$owner" ]; then
    psql_exec -c "CREATE DATABASE ${TEST_DB} OWNER ${TEST_USER}"
    echo "Создана база ${TEST_DB}, владелец ${TEST_USER} (суперпользователь: ${SUPERUSER})."
elif [ "$owner" != "$TEST_USER" ]; then
    echo "База ${TEST_DB} уже существует, но принадлежит роли '${owner}', а не '${TEST_USER}'." >&2
    echo "Тесты упрутся в права. Передайте владение или удалите базу вручную." >&2
    exit 1
else
    echo "База ${TEST_DB} уже существует (суперпользователь: ${SUPERUSER})."
fi

psql_exec -c "COMMENT ON DATABASE ${TEST_DB} IS 'pinvideo-pacs:testing'"

echo "Проставлен маркер тестовой базы."
echo "Готово. Схему создаст RefreshDatabase при первом запуске:"
echo "  docker compose -f ${COMPOSE_FILE} run --rm artisan test"
