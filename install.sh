#!/bin/sh
set -eu

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; NC='\033[0m'
log()  { printf '%b[INSTALL]%b %s\n' "$GREEN" "$NC" "$1"; }
info() { printf '%b[INSTALL]%b %s\n' "$BLUE" "$NC" "$1"; }
warn() { printf '%b[INSTALL] ВНИМАНИЕ:%b %s\n' "$YELLOW" "$NC" "$1"; }
err()  { printf '%b[INSTALL] ОШИБКА:%b %s\n' "$RED" "$NC" "$1" >&2; exit 1; }

VERSION="v1.2.2"
INSTALL_DIR="/opt/pacs"
DOMAIN=""
DB_USER="pacs"
DB_NAME="pacs"
DB_PASS=""
ADB_NAME="pacs-frg"
ADB_PASS=""
LPR_DB_NAME="pacs-lpr"
LPR_DB_PASS=""
MEDIA_ENABLED=""
ANALYTIC_ENABLED=""
LPR_ENABLED=""
ADMIN_EMAIL=""
ADMIN_PASSWORD=""
NONINTERACTIVE="false"
DOMAIN_SET="false"
VERSION_SET="false"
MODE=""
TARBALL_URL_TEMPLATE="https://github.com/grapeslabs/pacs/releases/download/%s/pacs.tar.gz"
gen_pass() {
    od -An -tx1 -N16 /dev/urandom | tr -d ' \n'
}

detect_ip() {
    ip route get 1.1.1.1 2>/dev/null \
        | awk '{for(i=1;i<=NF;i++) if($i=="src"){print $(i+1); exit}}'
}
to_bool() {
    case "$(echo "${1:-}" | tr '[:upper:]' '[:lower:]')" in
        y|yes|да|true|1) echo "true" ;;
        *)               echo "false" ;;
    esac
}

onoff() { [ "$1" = "true" ] && echo "включён" || echo "выключен"; }
yn()    { [ "$1" = "true" ] && echo "yes"     || echo "no"; }

TTY="/dev/tty"
have_tty() { [ "$NONINTERACTIVE" = "false" ] && [ -e "$TTY" ] && [ -r "$TTY" ]; }

ask() {
    local prompt="$1" default="$2" answer=""
    if have_tty; then
        printf "%b" "${BLUE}? ${prompt}${NC}" > "$TTY"
        [ -n "$default" ] && printf " [%s]" "$default" > "$TTY"
        printf ": " > "$TTY"
        IFS= read -r answer < "$TTY" || answer=""
    fi
    echo "${answer:-$default}"
}

ask_yesno() {
    local prompt="$1" default="$2" answer
    answer="$(ask "$prompt (да/нет)" "$default")"
    to_bool "$answer"
}

ask_bool_or() {
    local var="$1" prompt="$2" default="$3" fallback="$4" answer
    eval "[ -n \"\$$var\" ]" && return 0
    if have_tty; then
        answer="$(ask "$prompt (да/нет)" "$default")"
        eval "$var=\"\$(to_bool \"\$answer\")\""
    else
        eval "$var=\"\$fallback\""
    fi
}

parse_args() {
    while [ $# -gt 0 ]; do
        local key="$1" val=""
        case "$key" in
            -y|--non-interactive|--non_interactive)
                NONINTERACTIVE="true"; shift; continue ;;
            --reconfigure|--reconfig|--update)
                MODE="reconfigure"; shift; continue ;;
            --fresh|--reinstall)
                MODE="fresh"; shift; continue ;;
            --*=*)
                val="${key#*=}"; key="${key%%=*}" ;;
            --*)
                val="${2:-}"; shift ;;
            *)
                warn "Неизвестный аргумент: $key"; shift; continue ;;
        esac
        key="$(echo "$key" | tr '_' '-')"
        case "$key" in
            --version)         VERSION="$val"; VERSION_SET="true" ;;
            --dir)             INSTALL_DIR="$val" ;;
            --domain)          DOMAIN="$val"; DOMAIN_SET="true" ;;
            --db-password)     DB_PASS="$val" ;;
            --adb-password)    ADB_PASS="$val" ;;
            --lpr-db-password) LPR_DB_PASS="$val" ;;
            --media-server)    MEDIA_ENABLED="$(to_bool "$val")" ;;
            --analytic)        ANALYTIC_ENABLED="$(to_bool "$val")" ;;
            --lpr)             LPR_ENABLED="$(to_bool "$val")" ;;
            --admin-email)     ADMIN_EMAIL="$val" ;;
            --admin-password)  ADMIN_PASSWORD="$val" ;;
            *)                 warn "Неизвестный флаг: $key" ;;
        esac
        shift
    done
}

OS_ID=""; PKG=""
detect_os() {
    [ -r /etc/os-release ] || err "Не удалось определить ОС (нет /etc/os-release)."
    OS_ID="$(. /etc/os-release; echo "${ID:-unknown}")"
    local pretty; pretty="$(. /etc/os-release; echo "${PRETTY_NAME:-$OS_ID}")"
    if command -v apt-get >/dev/null 2>&1;  then PKG="apt"
    elif command -v dnf   >/dev/null 2>&1;  then PKG="dnf"
    elif command -v yum   >/dev/null 2>&1;  then PKG="yum"
    elif command -v apk   >/dev/null 2>&1;  then PKG="apk"
    else err "Поддерживаются только системы с apt/dnf/yum/apk. Обнаружено: ${OS_ID}"
    fi
    log "ОС: ${pretty}, пакетный менеджер: $PKG"
}

pkg_update_upgrade() {
    log "Обновление системы и установка зависимостей..."
    case "$PKG" in
        apt)
            export DEBIAN_FRONTEND=noninteractive
            apt-get update -y
            apt-get upgrade -y
            apt-get install -y tar cron curl ca-certificates sysstat smartmontools iproute2
            ;;
        dnf|yum)
            $PKG -y update
            $PKG -y install tar cronie curl ca-certificates sysstat smartmontools iproute
            ;;
        apk)
            apk update
            apk upgrade
            apk add --no-cache bash tar curl ca-certificates sysstat smartmontools iproute2 util-linux
            ;;
    esac
}

ensure_root() {
    if [ "$(id -u)" -ne 0 ]; then
        err "Скрипт нужно запускать от root (или через sudo)."
    fi
}

ensure_docker() {
    if command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
        log "Docker и docker compose уже установлены."
        return
    fi
    log "Установка Docker (get.docker.com)..."
    curl -fsSL --http1.1 https://get.docker.com | bash
    if [ "$PKG" = "apk" ]; then
        rc-update add docker boot 2>/dev/null || true
        rc-service docker start 2>/dev/null || service docker start 2>/dev/null || true
    else
        systemctl enable --now docker 2>/dev/null || true
    fi
    command -v docker >/dev/null 2>&1 || err "Не удалось установить Docker."
}

ensure_cron_running() {
    case "$PKG" in
        apt)     systemctl enable --now cron   2>/dev/null || service cron start  2>/dev/null || true ;;
        dnf|yum) systemctl enable --now crond  2>/dev/null || service crond start 2>/dev/null || true ;;
        apk)     rc-update add crond default 2>/dev/null || true
                 rc-service crond start 2>/dev/null || service crond start 2>/dev/null || true ;;
    esac
}

download_project() {
    local url
    url="$(printf "$TARBALL_URL_TEMPLATE" "$VERSION")"
    log "Скачивание проекта $VERSION из $url"
    mkdir -p "$INSTALL_DIR"
    curl -fL --http1.1 "$url" | tar -xz --strip-components=1 -C "$INSTALL_DIR"
    [ -f "$INSTALL_DIR/start.sh" ] || err "В архиве не найден start.sh - неверная структура релиза."
    log "Проект распакован в $INSTALL_DIR"
}

collect_params() {
    [ -n "$DB_PASS" ]     || DB_PASS="$(gen_pass)"
    [ -n "$ADB_PASS" ]    || ADB_PASS="$(gen_pass)"
    [ -n "$LPR_DB_PASS" ] || LPR_DB_PASS="$(gen_pass)"

    if [ -z "$DOMAIN" ] && have_tty; then
        DOMAIN="$(ask "Домен проекта (Enter - пропустить, тогда http://<IP>:8888)" "")"
    fi

    ask_bool_or MEDIA_ENABLED    "Включить модуль работы с камерами (медиа-сервер)?" "no" "false"
    ask_bool_or ANALYTIC_ENABLED "Включить модуль видеоаналитики лиц?"              "no" "false"
    ask_bool_or LPR_ENABLED      "Включить модуль видеоаналитики ГРЗ?"              "no" "false"

    warn_media_requirement
}

warn_media_requirement() {
    if [ "$MEDIA_ENABLED" != "true" ] && { [ "$ANALYTIC_ENABLED" = "true" ] || [ "$LPR_ENABLED" = "true" ]; }; then
        warn "Для работы модулей видеоаналитики требуется медиа-сервер, но он выключен."
        if have_tty && [ "$(ask_yesno "Включить медиа-сервер автоматически?" "yes")" = "true" ]; then
            MEDIA_ENABLED="true"
            log "Медиа-сервер включён."
        fi
    fi
}

get_env() {
    grep -E "^$1=" "$INSTALL_DIR/.env" 2>/dev/null | head -n1 | cut -d= -f2-
}

set_env() {
    local k="$1" v="$2" f="$INSTALL_DIR/.env"
    if grep -qE "^${k}=" "$f"; then
        local esc; esc="$(printf '%s' "$v" | sed -e 's/[\/&|]/\\&/g')"
        sed -i "s|^${k}=.*|${k}=${esc}|" "$f"
    else
        printf '%s=%s\n' "$k" "$v" >> "$f"
    fi
}

apply_domain_env() {
    if [ -n "$DOMAIN" ]; then
        set_env DOCKER_DOMAIN "$DOMAIN"
        local cert="${ADMIN_EMAIL:-$1}"
        [ -n "$cert" ] && set_env CERT_ADMIN_EMAIL "$cert"
        URL="https://$DOMAIN"
    else
        set_env DOCKER_DOMAIN ""
        URL="http://$(detect_ip || echo '<IP-сервера>'):8888"
    fi
}

configure_env() {
    log "Настройка .env..."
    [ -f "$INSTALL_DIR/.env.production.example" ] || err "Не найден .env.production.example в релизе."
    cp "$INSTALL_DIR/.env.production.example" "$INSTALL_DIR/.env"

    set_env DB_DATABASE       "$DB_NAME"
    set_env DB_USERNAME       "$DB_USER"
    set_env DB_PASSWORD       "$DB_PASS"

    set_env MEDIA_SERVER_ENABLED "$MEDIA_ENABLED"
    set_env ANALYTIC_ENABLED     "$ANALYTIC_ENABLED"
    set_env LPR_ENABLED          "$LPR_ENABLED"

    set_env ADB_USERNAME      "postgres"
    set_env ADB_DATABASE      "$ADB_NAME"
    set_env ADB_PASSWORD      "$ADB_PASS"

    set_env LPR_DB_USERNAME   "postgres"
    set_env LPR_DB_DATABASE   "$LPR_DB_NAME"
    set_env LPR_DB_PASSWORD   "$LPR_DB_PASS"

    set_env APP_VERSION       "${VERSION#v}"
    set_env COMPOSE_FILE      "docker-compose.production.yml"

    apply_domain_env "admin@$DOMAIN"
}

start_stack() {
    log "Запуск контейнеров (start.sh)..."
    cd "$INSTALL_DIR"
    chmod +x start.sh
    ./start.sh
}

compose_down() {
    log "Остановка текущих контейнеров..."
    cd "$INSTALL_DIR"
    local dc="docker compose"
    docker compose version >/dev/null 2>&1 || dc="docker-compose"
    $dc down --remove-orphans 2>/dev/null || true
}

create_admin() {
    if [ -z "$ADMIN_EMAIL" ]; then
        local default_email="admin@${DOMAIN:-pacs.local}"
        ADMIN_EMAIL="$(have_tty && ask "E-mail администратора" "$default_email" || echo "$default_email")"
    fi
    [ -n "$ADMIN_PASSWORD" ] || ADMIN_PASSWORD="$(gen_pass)"

    log "Ожидание готовности приложения и применения миграций..."
    cd "$INSTALL_DIR"
    local ok="false" i
    for i in $(seq 1 60); do
        if docker compose exec -T php php artisan migrate:status >/dev/null 2>&1; then
            ok="true"; break
        fi
        sleep 5
    done
    [ "$ok" = "true" ] || warn "Приложение не подтвердило готовность за отведённое время - пробую создать пользователя всё равно."

    log "Создание администратора $ADMIN_EMAIL..."
    docker compose exec -T php php artisan moonshine:user \
        --username="$ADMIN_EMAIL" \
        --name="Администратор" \
        --password="$ADMIN_PASSWORD" \
        || warn "Не удалось автоматически создать пользователя. Создайте вручную: docker compose exec php php artisan moonshine:user"
}

setup_cron() {
    log "Настройка мониторинга (crontab)..."
    ensure_cron_running
    chmod +x "$INSTALL_DIR/monitor.sh" 2>/dev/null || true
    if [ ! -f "$INSTALL_DIR/monitor.sh" ]; then
        warn "monitor.sh не найден в релизе - мониторинг не настроен."
        return
    fi
    local marker="# grapespacs-monitor"
    ( crontab -l 2>/dev/null | grep -v "$marker" || true
      echo "@reboot ${INSTALL_DIR}/monitor.sh logs ${marker}"
      echo "*/5 * * * * ${INSTALL_DIR}/monitor.sh metrics ${marker}"
    ) | crontab -
    "$INSTALL_DIR/monitor.sh" logs >/dev/null 2>&1 || true
    log "Мониторинг пишет в ${INSTALL_DIR}/logs.log (метрики каждые 5 минут)."
}

print_summary() {
    echo
    echo "========================================================"
    echo " Установка успешно завершена!"
    echo " Адрес проекта:        ${URL}"
    echo "--------------------------------------------------------"
    echo " Учётные данные администратора:"
    echo " E-mail:               ${ADMIN_EMAIL}"
    echo " Пароль:               ${ADMIN_PASSWORD}"
    echo "--------------------------------------------------------"
    echo " Учётные данные БД (внутренние):"
    echo " Пользователь БД:      ${DB_USER}"
    echo " Пароль БД:            ${DB_PASS}"
    echo " БД видеоаналитики:    postgres / ${ADB_PASS}"
    echo " БД ГРЗ:               postgres / ${LPR_DB_PASS}"
    echo "--------------------------------------------------------"
    echo " Модули:"
    echo " Камеры (медиа-сервер):  $(onoff "$MEDIA_ENABLED")"
    echo " Видеоаналитика лиц:     $(onoff "$ANALYTIC_ENABLED")"
    echo " Видеоаналитика ГРЗ:     $(onoff "$LPR_ENABLED")"
    echo "========================================================"
    warn "Сохраните пароли в надёжном месте - повторно они не показываются."
}

collect_reconfig_params() {
    local cur_domain cur_media cur_analytic cur_lpr
    cur_domain="$(get_env DOCKER_DOMAIN)"
    cur_media="$(get_env MEDIA_SERVER_ENABLED)"
    cur_analytic="$(get_env ANALYTIC_ENABLED)"
    cur_lpr="$(get_env LPR_ENABLED)"

    if [ "$DOMAIN_SET" != "true" ]; then
        if have_tty; then
            DOMAIN="$(ask "Домен проекта (Enter - оставить текущий, '-' - убрать домен)" "$cur_domain")"
            [ "$DOMAIN" = "-" ] && DOMAIN=""
        else
            DOMAIN="$cur_domain"
        fi
    fi

    ask_bool_or MEDIA_ENABLED    "Модуль работы с камерами (медиа-сервер)?" "$(yn "$cur_media")"    "${cur_media:-false}"
    ask_bool_or ANALYTIC_ENABLED "Модуль видеоаналитики лиц?"               "$(yn "$cur_analytic")" "${cur_analytic:-false}"
    ask_bool_or LPR_ENABLED      "Модуль видеоаналитики ГРЗ?"               "$(yn "$cur_lpr")"      "${cur_lpr:-false}"

    warn_media_requirement
}

apply_reconfig_env() {
    log "Обновление .env..."
    set_env MEDIA_SERVER_ENABLED "$MEDIA_ENABLED"
    set_env ANALYTIC_ENABLED     "$ANALYTIC_ENABLED"
    set_env LPR_ENABLED          "$LPR_ENABLED"

    apply_domain_env ""

    if [ "$VERSION_SET" = "true" ]; then
        set_env APP_VERSION "${VERSION#v}"
    fi
}

print_reconfig_summary() {
    echo
    echo "========================================================"
    echo " Конфигурация обновлена!"
    echo " Адрес проекта:        ${URL}"
    echo "--------------------------------------------------------"
    echo " Модули:"
    echo " Камеры (медиа-сервер):  $(onoff "$MEDIA_ENABLED")"
    echo " Видеоаналитика лиц:     $(onoff "$ANALYTIC_ENABLED")"
    echo " Видеоаналитика ГРЗ:     $(onoff "$LPR_ENABLED")"
    echo "========================================================"
}

reconfigure_instance() {
    log "Обнаружена установка в $INSTALL_DIR - режим изменения конфигурации."
    log "(пароли БД и администратор не затрагиваются)"
    if [ "$VERSION_SET" = "true" ]; then
        ensure_docker
        download_project
    fi
    collect_reconfig_params
    apply_reconfig_env
    compose_down
    start_stack
    setup_cron
    print_reconfig_summary
}

fresh_install() {
    pkg_update_upgrade
    ensure_docker
    download_project
    collect_params
    configure_env
    start_stack
    create_admin
    setup_cron
    print_summary
}

main() {
    parse_args "$@"
    ensure_root
    detect_os

    if [ -z "$MODE" ]; then
        if [ -f "$INSTALL_DIR/.env" ]; then MODE="reconfigure"; else MODE="fresh"; fi
    fi
    if [ "$MODE" = "reconfigure" ] && [ ! -f "$INSTALL_DIR/.env" ]; then
        err "В $INSTALL_DIR нет установки (.env не найден) - нечего реконфигурировать."
    fi

    case "$MODE" in
        reconfigure) reconfigure_instance ;;
        fresh)       fresh_install ;;
    esac
}

main "$@"
