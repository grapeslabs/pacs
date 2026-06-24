#!/usr/bin/env bash
set -u

PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
LOG_FILE="${PROJECT_DIR}/logs.log"
METRICS_FILE="${PROJECT_DIR}/metrics.log"
LOG_KEEP_DAYS="${LOG_KEEP_DAYS:-7}"
HTTP_CHECK_URL="${HTTP_CHECK_URL:-http://127.0.0.1:8888}"
HTTP_CHECK_TIMEOUT="${HTTP_CHECK_TIMEOUT:-10}"
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

rotate_file() {
    local file="$1" stamp base
    stamp="$(date -d 'yesterday' +%F 2>/dev/null || date +%F)"
    base="$(basename "$file")"

    if [ -s "$file" ]; then
        cp -f "$file" "${file}.${stamp}"
        : > "$file"
        echo "$(ts) [rotate] ${base} повёрнут в ${base}.${stamp}" >> "$file"
    fi

    find "$PROJECT_DIR" -maxdepth 1 -name "${base}.*-*-*" \
        -mtime "+$((LOG_KEEP_DAYS - 1))" -type f -delete 2>/dev/null
}

mode_rotate() {
    rotate_file "$LOG_FILE"
    rotate_file "$METRICS_FILE"
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

    section "HTTP проверка приложения"
    http_check

    section "PostgreSQL (pg_isready)"
    pg_check

    section "Ядро: OOM / ошибки (journalctl -k, за 5 мин)"
    if command -v journalctl >/dev/null 2>&1; then
        journalctl -k --since '-5min' --no-pager 2>/dev/null \
            | grep -iE 'out of memory|oom-kill|killed process|i/o error|ext4-fs error|hung_task|segfault' \
            | tail -n 20 \
            || true
        echo "(если выше пусто — критичных событий ядра за 5 мин нет)"
    else
        echo "journalctl недоступен"
    fi

    echo "$SEP"
    echo
}

http_check() {
    local tmp code rc cpid wpid
    tmp="$(mktemp)"

    curl -s -o /dev/null -w '%{http_code}' "$HTTP_CHECK_URL" > "$tmp" 2>/dev/null &
    cpid=$!
    ( sleep "$HTTP_CHECK_TIMEOUT"; kill -9 "$cpid" 2>/dev/null ) &
    wpid=$!

    wait "$cpid" 2>/dev/null; rc=$?
    kill "$wpid" 2>/dev/null; wait "$wpid" 2>/dev/null

    code="$(cat "$tmp" 2>/dev/null)"; rm -f "$tmp"

    if [ "$rc" -ge 128 ]; then
        echo "Приложение (${HTTP_CHECK_URL}): НЕТ ОТВЕТА (таймаут ${HTTP_CHECK_TIMEOUT}с, curl убит)"
        return
    fi
    case "$code" in
        2??|3??) echo "Приложение (${HTTP_CHECK_URL}): ОК (HTTP ${code})" ;;
        000|"")  echo "Приложение (${HTTP_CHECK_URL}): НЕТ СОЕДИНЕНИЯ" ;;
        *)       echo "Приложение (${HTTP_CHECK_URL}): ПРОБЛЕМА (HTTP ${code})" ;;
    esac
}

pg_check() {
    local svc found="false"
    for svc in app-database analytic-database lpr-database; do
        if cd "$PROJECT_DIR" && $DC ps --services --filter status=running 2>/dev/null | grep -qx "$svc"; then
            found="true"
            printf '  %-18s ' "$svc:"
            $DC exec -T "$svc" pg_isready 2>/dev/null | sed 's/^.*- //' \
                || echo "не отвечает"
        fi
    done
    [ "$found" = "true" ] || echo "  запущенных БД-контейнеров не найдено"
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
    collect_metrics >> "$METRICS_FILE" 2>&1
}

case "${1:-}" in
    logs)    mode_logs ;;
    metrics) mode_metrics ;;
    rotate)  mode_rotate ;;
    *)
        echo "Использование: $0 {logs|metrics|rotate}" >&2
        exit 1
        ;;
esac
