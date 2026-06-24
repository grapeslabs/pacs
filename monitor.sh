#!/usr/bin/env bash
set -u

PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
LOG_FILE="${PROJECT_DIR}/logs.log"
LOG_MAX_BYTES="${LOG_MAX_BYTES:-52428800}"
LOG_KEEP="${LOG_KEEP:-5}"
SEP="================================================================"
PROJECT_NAME="$(basename "$PROJECT_DIR")"
DOCKER_VOLUMES="/var/lib/docker/volumes"
LOG_SOURCES="
${DOCKER_VOLUMES}/${PROJECT_NAME}_lpr_logs/_data/app.log
${DOCKER_VOLUMES}/${PROJECT_NAME}_storage/_data/logs/laravel.log
"

DC="docker compose"
docker compose version >/dev/null 2>&1 || DC="docker-compose"

ts() { date '+%Y-%m-%d %H:%M:%S'; }

rotate_log() {
    [ -f "$LOG_FILE" ] || return 0
    local size
    size="$(stat -c%s "$LOG_FILE" 2>/dev/null || echo 0)"
    [ "$size" -gt "$LOG_MAX_BYTES" ] || return 0

    local i
    for ((i = LOG_KEEP - 1; i >= 1; i--)); do
        [ -f "${LOG_FILE}.${i}" ] && mv -f "${LOG_FILE}.${i}" "${LOG_FILE}.$((i + 1))"
    done
    cp -f "$LOG_FILE" "${LOG_FILE}.1"
    : > "$LOG_FILE"
    rm -f "${LOG_FILE}.$((LOG_KEEP + 1))"

    echo "$(ts) [rotate] logs.log повёрнут (был ${size} Б, лимит ${LOG_MAX_BYTES} Б)" >> "$LOG_FILE"
}

start_stream() {
    local pidfile="$1"; shift
    if [ -f "$pidfile" ] && kill -0 "$(cat "$pidfile" 2>/dev/null)" 2>/dev/null; then
        return 0
    fi
    nohup "$@" >> "$LOG_FILE" 2>&1 &
    echo $! > "$pidfile"
}

mode_logs() {
    {
        echo "$SEP"
        echo "$(ts) [logs] Старт/проверка потоков логов"
        echo "$SEP"
    } >> "$LOG_FILE"

    start_stream "${PROJECT_DIR}/.monitor-tail.pid" \
        tail -n0 -F --retry $LOG_SOURCES

    start_stream "${PROJECT_DIR}/.monitor-docker.pid" \
        sh -c "while true; do cd '$PROJECT_DIR' && $DC logs -f --tail=0; sleep 5; done"
}

section() { echo "---- $1 ----"; }

collect_metrics() {
    echo "$SEP"
    echo "$(ts)  МЕТРИКИ СЕРВЕРА"
    echo "$SEP"

    section "Диски (использование)"
    df -h -x tmpfs -x devtmpfs 2>/dev/null | awk 'NR==1 || $1 ~ /^\/dev\//'

    section "Диски (нагрузка, задержки)"
    if command -v iostat >/dev/null 2>&1; then
        if iostat -dx 1 2 >/dev/null 2>&1; then
            iostat -dx 1 2 | awk '/Device/{p++} p==2'
        else
            echo "(busybox iostat — без задержек/util)"
            iostat -d 1 2 | awk '/Device|Disk/{p++} p==2'
        fi
    else
        echo "iostat недоступен (нет пакета sysstat)"
    fi

    section "SMART"
    if command -v smartctl >/dev/null 2>&1; then
        for dev in $(lsblk -dn -o NAME,TYPE 2>/dev/null | awk '$2=="disk"{print $1}'); do
            echo ">> /dev/$dev"
            smartctl -H -A "/dev/$dev" 2>/dev/null \
                | grep -Ei 'SMART overall|Reallocated|Pending|Temperature|Power_On|Wear|Percentage' \
                || echo "   нет данных SMART"
        done
    else
        echo "smartctl недоступен (нет пакета smartmontools)"
    fi

    section "CPU (нагрузка по ядрам, %)"
    if command -v mpstat >/dev/null 2>&1; then
        mpstat -P ALL 1 1 | awk '/CPU/{h=1} h'
    else
        cpu_per_core_fallback
    fi
    echo "LoadAvg: $(cut -d' ' -f1-3 /proc/loadavg)"

    section "Память"
    if command -v free >/dev/null 2>&1; then
        free -h
    else
        awk '/MemTotal|MemFree|MemAvailable/{print}' /proc/meminfo
    fi

    section "Доступ в интернет"
    if ping -c1 -W2 8.8.8.8 >/dev/null 2>&1; then
        echo "Интернет: ДА (ping 8.8.8.8 успешно)"
    else
        echo "Интернет: НЕТ (ping 8.8.8.8 не прошёл)"
    fi

    echo "$SEP"
    echo
}

cpu_per_core_fallback() {
    awk '/^cpu[0-9]/{c[$1]=$2+$3+$4+$5+$6+$7+$8; idle1[$1]=$5}' /proc/stat
    local tmp1 tmp2
    tmp1="$(grep '^cpu[0-9]' /proc/stat)"
    sleep 1
    tmp2="$(grep '^cpu[0-9]' /proc/stat)"
    paste <(echo "$tmp1") <(echo "$tmp2") | awk '{
        core=$1;
        t1=$2+$3+$4+$5+$6+$7+$8; i1=$5;
        t2=$11+$12+$13+$14+$15+$16+$17; i2=$14;
        dt=t2-t1; di=i2-i1;
        if (dt>0) printf "  %s: %.1f%%\n", core, (1-di/dt)*100;
    }'
}

mode_metrics() {
    collect_metrics >> "$LOG_FILE" 2>&1
}

case "${1:-}" in
    logs)    rotate_log; mode_logs ;;
    metrics) rotate_log; mode_metrics ;;
    *)
        echo "Использование: $0 {logs|metrics}" >&2
        exit 1
        ;;
esac
