#!/bin/bash
ENV_FILE=".env"
LOG_PREFIX="[DEPLOY]"
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'
log() { echo -e "${GREEN}${LOG_PREFIX} $1${NC}"; }
warn() { echo -e "${YELLOW}${LOG_PREFIX} WARNING: $1${NC}" >&2; }
error() { echo -e "${RED}${LOG_PREFIX} ERROR: $1${NC}" >&2; exit 1; }
if docker compose version &> /dev/null; then
    DOCKER_COMPOSE="docker compose"
elif command -v docker-compose &> /dev/null; then
    DOCKER_COMPOSE="docker-compose"
else
    error "Docker Compose not installed. Please install docker-compose or docker compose plugin."
fi

if [ ! -f "$ENV_FILE" ]; then
    error "File $ENV_FILE not found."
fi

set -a
source "$ENV_FILE"
set +a

log "Загрузка конфигурации из $ENV_FILE..."

is_enabled() {
    local var_name=$1
    local value=${!var_name}
    if [[ "$value" == "true" || "$value" == "1" || "$value" == "yes" ]]; then
        return 0
    else
        return 1
    fi
}

MEDIA_CONFIG="media-config/config.toml"
MEDIA_RESTART_NEEDED="false"

valid_ipv4() {
    printf '%s' "$1" | grep -qE '^([0-9]{1,3}\.){3}[0-9]{1,3}$' || return 1
    local IFS='.' octet
    for octet in $1; do
        [ "$octet" -le 255 ] || return 1
    done
}

webrtc_public_ips() {
    local raw="${MEDIA_SERVER_WEBRTC_PUBLIC_IP:-}" ip out=""
    for ip in ${raw//,/ }; do
        if valid_ipv4 "$ip"; then
            out="${out:+$out, }\"$ip\""
        else
            warn "MEDIA_SERVER_WEBRTC_PUBLIC_IP: '$ip' не похож на IPv4-адрес, пропускаю."
        fi
    done
    printf '[%s]' "$out"
}

sync_media_config() {
    if [ ! -f "$MEDIA_CONFIG" ]; then
        warn "$MEDIA_CONFIG не найден - настройки WebRTC не применены."
        return 1
    fi

    local ips port tmp
    ips="$(webrtc_public_ips)"
    port="${MEDIA_SERVER_WEBRTC_UDP_PORT:-8189}"

    case "$port" in
        ''|*[!0-9]*)
            warn "MEDIA_SERVER_WEBRTC_UDP_PORT='$port' не число - использую 8189."
            port=8189 ;;
    esac

    tmp="$(mktemp)" || return 1

    awk -v ips="$ips" -v port="$port" '
    function flush() {
        if (!seen_ip)   print ind "public_ips = " ips
        if (!seen_port) print ind "udp_port = " port
    }
    /^[[:space:]]*\[/ {
        if (inb) { flush(); inb = 0 }
        if ($0 ~ /^[[:space:]]*\[webrtc\][[:space:]]*$/) {
            inb = 1; found = 1; seen_ip = 0; seen_port = 0; ind = "  "
            print; next
        }
    }
    inb && /^[[:space:]]*public_ips[[:space:]]*=/ {
        match($0, /^[[:space:]]*/); ind = substr($0, 1, RLENGTH)
        print ind "public_ips = " ips; seen_ip = 1; next
    }
    inb && /^[[:space:]]*udp_port[[:space:]]*=/ {
        match($0, /^[[:space:]]*/); ind = substr($0, 1, RLENGTH)
        print ind "udp_port = " port; seen_port = 1; next
    }
    { print }
    END {
        if (inb) flush()
        else if (!found) {
            print ""
            print "[webrtc]"
            print "  enabled = true"
            print "  public_ips = " ips
            print "  udp_port = " port
        }
    }
    ' "$MEDIA_CONFIG" > "$tmp"

    if [ $? -ne 0 ] || [ ! -s "$tmp" ]; then
        rm -f "$tmp"
        warn "Не удалось обновить $MEDIA_CONFIG - оставляю как есть."
        return 1
    fi

    if cmp -s "$tmp" "$MEDIA_CONFIG"; then
        rm -f "$tmp"
        log "$MEDIA_CONFIG уже соответствует .env (public_ips=$ips, udp_port=$port)."
        return 1
    fi

    cat "$tmp" > "$MEDIA_CONFIG"
    rm -f "$tmp"
    log "$MEDIA_CONFIG обновлён из .env: public_ips=$ips, udp_port=$port"
    return 0
}

prepare_media_config() {
    is_enabled "MEDIA_SERVER_ENABLED" || is_enabled "ANALYTIC_ENABLED" || is_enabled "LPR_ENABLED" || return 0

    if [ -z "${MEDIA_SERVER_WEBRTC_PUBLIC_IP:-}" ]; then
        warn "MEDIA_SERVER_WEBRTC_PUBLIC_IP не задан в .env."
        warn "Видеостены и режим LIVE не смогут работать по WebRTC с других машин и перейдут"
        warn "на резервный HLS (задержка ~30 секунд). Укажите адрес сервера и перезапустите start.sh."
    fi

    local running
    running="$($DOCKER_COMPOSE ps -q media-server 2>/dev/null)"

    if sync_media_config && [ -n "$running" ]; then
        MEDIA_RESTART_NEEDED="true"
    fi
}

prepare_media_config

log "Starting app..."
if [ -n "$DOCKER_DOMAIN" ]; then
    log "DOCKER_DOMAIN=$DOCKER_DOMAIN - starting Traefik"
    if $DOCKER_COMPOSE up -d reverse-proxy; then
        log "App started (Traefik)."
    else
        error "Starting failed"
    fi
else
    warn "DOCKER_DOMAIN not found."
    export APP_HTTP_BIND="0.0.0.0:8888"
    if $DOCKER_COMPOSE up -d app; then
        log "App started (HTTP :8888, no Traefik)."
    else
        error "Starting failed"
    fi
fi

log "Starting queue worker..."
if $DOCKER_COMPOSE up -d horizon; then
    log "Queue worker started."
else
    error "Starting failed"
fi

if is_enabled "MEDIA_SERVER_ENABLED"; then
    log "Starting media-server..."
    $DOCKER_COMPOSE up -d media-server || warn "FAILED"
fi

if is_enabled "ANALYTIC_ENABLED"; then
    log "Starting analytic services..."
    $DOCKER_COMPOSE up -d analytic-database analytic-api analytic-server events-processor media-server || warn "FAILED"
fi

if is_enabled "LPR_ENABLED"; then
    log "Starting LPR services..."
    $DOCKER_COMPOSE up -d analytic-database analytic-api analytic-server lpr-database lpr-api lpr-core media-server lpr-rules-processor media-server || warn "FAILED"
fi

if [ "$MEDIA_RESTART_NEEDED" = "true" ]; then
    log "Перезапуск media-server для применения настроек WebRTC..."
    $DOCKER_COMPOSE restart media-server || warn "FAILED"
fi
