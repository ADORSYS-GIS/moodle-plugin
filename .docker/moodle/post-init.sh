#!/bin/bash

# Configure Moodle to use Redis-backed sessions and a container-local cache dir.
# This mirrors the approach used in the reference moodle-on-gcp repo, but keeps
# it local to Docker Compose by patching config.php during the first-run init.

CONFIG_FILE="/bitnami/moodle/config.php"
MARKER="BEGIN moodle-plugin redis config"

if [ ! -f "$CONFIG_FILE" ]; then
  info "Redis bootstrap skipped: $CONFIG_FILE does not exist yet."
  return 0
fi

if ! php -m | grep -qi '^redis$'; then
  warn "Redis bootstrap skipped: PHP redis extension is not available in the Moodle image."
  return 0
fi

mkdir -p "${MOODLE_REDIS_LOCALCACHEDIR:-/tmp/moodle/localcache}"

if grep -q "$MARKER" "$CONFIG_FILE"; then
  info "Redis bootstrap already present in Moodle config.php."
  return 0
fi

REDIS_AUTH_LINE="\$CFG->session_redis_auth = '${MOODLE_REDIS_SESSION_AUTH:-}';"

TMP_FILE="$(mktemp)"
awk -v marker="$MARKER" \
    -v localcachedir="${MOODLE_REDIS_LOCALCACHEDIR:-/tmp/moodle/localcache}" \
    -v sessionhost="${MOODLE_REDIS_SESSION_HOST:-redis}" \
    -v sessionport="${MOODLE_REDIS_SESSION_PORT:-6379}" \
    -v sessiondatabase="${MOODLE_REDIS_SESSION_DATABASE:-0}" \
    -v sessionprefix="${MOODLE_REDIS_SESSION_PREFIX:-mdl_sessid_1_}" \
    -v sessionauthline="$REDIS_AUTH_LINE" \
    -v locktimeout="${MOODLE_REDIS_LOCK_TIMEOUT:-120}" \
    -v lockwarn="${MOODLE_REDIS_LOCK_WARN:-0}" \
    -v lockexpire="${MOODLE_REDIS_LOCK_EXPIRE:-7200}" \
    -v lockretry="${MOODLE_REDIS_LOCK_RETRY:-100}" \
    -v compressor="${MOODLE_REDIS_COMPRESSOR:-gzip}" '
  /require_once/ && !done {
    print "// " marker
    print "$CFG->localcachedir = '\''" localcachedir "'\'';"
    print "$CFG->session_handler_class = '\''\\core\\session\\redis'\'';"
    print "$CFG->session_redis_host = '\''" sessionhost "'\'';"
    print "$CFG->session_redis_port = " sessionport ";"
    print "$CFG->session_redis_database = " sessiondatabase ";"
    print sessionauthline
    print "$CFG->session_redis_prefix = '\''" sessionprefix "'\'';"
    print "$CFG->session_redis_acquire_lock_timeout = " locktimeout ";"
    print "$CFG->session_redis_acquire_lock_warn = " lockwarn ";"
    print "$CFG->session_redis_lock_expire = " lockexpire ";"
    print "$CFG->session_redis_lock_retry = " lockretry ";"
    print "$CFG->session_redis_serializer_use_igbinary = false;"
    print "$CFG->session_redis_compressor = '\''" compressor "'\'';"
    print "// END moodle-plugin redis config"
    print ""
    done=1
  }
  { print }
' "$CONFIG_FILE" > "$TMP_FILE"

cat "$TMP_FILE" > "$CONFIG_FILE"
rm -f "$TMP_FILE"

info "Injected Redis session configuration into Moodle config.php."
