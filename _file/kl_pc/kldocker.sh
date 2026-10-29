#!/usr/bin/env bash
set -uo pipefail

# ============ 配置区 ============
BASE_DIR="/code/show/_file/kl_pc"
COMPOSE_MAIN="$BASE_DIR/docker_yaml_20260713.yaml"   # nginx + php
COMPOSE_ES="$BASE_DIR/docker_es_20261028.yaml"       # es
COMPOSE_REDIS="$BASE_DIR/docker_redis_20261028.yaml" # redis
NETWORK="kl_desktop_network"

# 别名 -> 容器名
declare -A CONTAINERS=(
  [nginx]="kl_desktop_nginx"
  [php]="kl_desktop_php8"
  [mysql]="kl_mysql8"
  [es]="kl_desktop_es"
  [redis]="kl_desktop_redis"
)

# 别名 -> compose 服务名（mysql 不是 compose 管理的）
declare -A SERVICE_NAMES=(
  [nginx]="nginx"
  [php]="kl_desktop_php8"
  [es]="kl_desktop_es"
  [redis]="kl_desktop_redis"
)

# 别名 -> compose 文件
declare -A COMPOSE_FILES=(
  [nginx]="$COMPOSE_MAIN"
  [php]="$COMPOSE_MAIN"
  [es]="$COMPOSE_ES"
  [redis]="$COMPOSE_REDIS"
)

ALL_SERVICES=(nginx php mysql es redis)

# ============ 颜色/日志 ============
GREEN='\033[0;32m'; RED='\033[0;31m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'
info(){ echo -e "${GREEN}[INFO]${NC} $*"; }
warn(){ echo -e "${YELLOW}[WARN]${NC} $*"; }
err(){  echo -e "${RED}[ERR ]${NC} $*" >&2; }
step(){ echo -e "${CYAN}==>${NC} $*"; }

# 自动选择 docker compose / docker-compose
if docker compose version >/dev/null 2>&1; then
  DC=(docker compose)
elif docker-compose version >/dev/null 2>&1; then
  DC=(docker-compose)
else
  err "未检测到 docker compose / docker-compose"
  exit 1
fi

container_exists(){ docker inspect "$1" >/dev/null 2>&1; }
container_running(){ [ "$(docker inspect -f '{{.State.Running}}' "$1" 2>/dev/null)" = "true" ]; }

ensure_network(){
  if ! docker network inspect "$NETWORK" >/dev/null 2>&1; then
    info "外部网络 $NETWORK 不存在，自动创建"
    docker network create "$NETWORK" >/dev/null || { err "创建网络失败"; return 1; }
  fi
}

# ============ 具体操作 ============
do_start(){
  local svc=$1 cname=${CONTAINERS[$1]}

  if container_running "$cname"; then
    warn "$svc 已在运行，跳过"
    return 0
  fi

  if [ "$svc" = "mysql" ]; then
    if container_exists "$cname"; then
      step "启动 mysql ($cname)"
      docker start "$cname" >/dev/null
    else
      err "容器 $cname 不存在；mysql 非 compose 管理，请先手动创建"
      return 1
    fi
  else
    ensure_network || return 1
    local file=${COMPOSE_FILES[$svc]} svcname=${SERVICE_NAMES[$svc]}
    step "启动 $svc ($svcname) via $file"
    "${DC[@]}" -f "$file" up -d "$svcname"
    if [ "$svc" = "nginx" ]; then
      warn "nginx 配置了 depends_on，会顺带启动 php ($cname 之外: kl_desktop_php8)"
    fi
  fi
}

do_stop(){
  local svc=$1 cname=${CONTAINERS[$1]}
  if container_running "$cname"; then
    step "停止 $svc ($cname)"
    docker stop "$cname" >/dev/null
  else
    warn "$svc 未运行，跳过"
  fi
}

do_restart(){
  local svc=$1 cname=${CONTAINERS[$1]}
  if container_exists "$cname"; then
    step "重启 $svc ($cname)"
    docker restart "$cname" >/dev/null
  else
    warn "$svc 容器不存在，改为启动"
    do_start "$svc"
  fi
}

do_status(){
  printf "%-8s %-22s %-8s\n" "SERVICE" "CONTAINER" "STATUS"
  local svc cname state
  for svc in "${ALL_SERVICES[@]}"; do
    cname=${CONTAINERS[$svc]}
    if ! container_exists "$cname"; then
      state="不存在"
    elif container_running "$cname"; then
      state="running"
    else
      state="stopped"
    fi
    printf "%-8s %-22s %-8s\n" "$svc" "$cname" "$state"
  done
}

usage(){
  cat <<EOF
用法: $0 <start|stop|restart|status> [服务名...]

服务名: nginx | php | mysql | es | redis | all
  不写服务名 = all

示例:
  $0 start                 # 启动全部
  $0 stop                  # 停止全部
  $0 restart               # 重启全部
  $0 start nginx php       # 只启动 nginx + php
  $0 stop mysql es         # 只停止 mysql + es
  $0 restart redis         # 只重启 redis
  $0 status                # 查看状态
EOF
}

# ============ 主流程 ============
ACTION="${1:-}"
if [ -z "$ACTION" ]; then usage; exit 1; fi
case "$ACTION" in
  start|stop|restart|status) ;;
  -h|--help|help) usage; exit 0 ;;
  *) err "未知操作: $ACTION"; usage; exit 1 ;;
esac
shift

SERVICES=()
if [ $# -eq 0 ] || [ "$1" = "all" ]; then
  SERVICES=("${ALL_SERVICES[@]}")
else
  for s in "$@"; do
    if [ -z "${CONTAINERS[$s]:-}" ]; then
      err "未知服务: $s （可选: ${ALL_SERVICES[*]} all）"
      exit 1
    fi
    SERVICES+=("$s")
  done
fi

# 去重
declare -A seen=()
UNIQ=()
for s in "${SERVICES[@]}"; do
  if [ -z "${seen[$s]:-}" ]; then
    seen[$s]=1
    UNIQ+=("$s")
  fi
done
SERVICES=("${UNIQ[@]}")

if [ "$ACTION" = "status" ]; then
  do_status
  exit 0
fi

FAIL=0
info "操作: $ACTION | 服务: ${SERVICES[*]}"
for svc in "${SERVICES[@]}"; do
  case "$ACTION" in
    start)   do_start   "$svc" || FAIL=$((FAIL+1)) ;;
    stop)    do_stop    "$svc" || FAIL=$((FAIL+1)) ;;
    restart) do_restart "$svc" || FAIL=$((FAIL+1)) ;;
  esac
done

echo
if [ "$FAIL" -eq 0 ]; then
  info "全部完成 ✅"
else
  err "有 $FAIL 个服务操作失败 ❌"
  exit 1
fi

#docker-compose  -f  /code/show/_file/kl_pc/docker_yaml_20260713.yaml  down;docker stop kl_mysql8;docker-compose  -f  /code/show/_file/kl_pc/docker_yaml_20260713.yaml  up -d;docker start kl_mysql8;
# docker network rm kl_desktop_network;docker-compose  -f  /code/show/_file/kl_pc/docker_yaml_20260713.yaml  down;docker-compose  -f  /code/show/_file/kl_pc/docker_yaml_20260713.yaml  up -d;
#docker-compose  -f  /code/show/_file/kl_pc/docker_redis_20261028.yaml  down;docker-compose  -f  /code/show/_file/kl_pc/docker_redis_20261028.yaml  up -d;