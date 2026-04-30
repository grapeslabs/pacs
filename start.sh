#!/bin/bash

ENV_FILE=".env"
LOG_PREFIX="[DEPLOY]"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() { echo -e "${GREEN}${LOG_PREFIX} $1${NC}"; }
warn() { echo -e "${YELLOW}${LOG_PREFIX} WARNING: $1${NC}"; }
error() { echo -e "${RED}${LOG_PREFIX} ERROR: $1${NC}"; exit 1; }

if ! command -v docker compose &> /dev/null; then
    error "Docker Compose not installed"
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
log "Starting app..."
if docker compose up -d app; then
    log "App started."
else
    error "Starting failed"
fi

if is_enabled "MEDIA_SERVER_ENABLED"; then
    log "Starting media-server..."
    docker compose up -d media-server || warn "FAILED"
fi

if is_enabled "ANALYTIC_ENABLED"; then
    log "Starting analytic services..."
    docker compose up -d analytic-database analytic-api analytic-server media-server || warn "FAILED"
fi
