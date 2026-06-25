#!/bin/sh
set -e

# Ajusta permissões das chaves OAuth quando o filesystem permitir (Linux).
if [ -f storage/oauth-private.key ]; then
  chmod 600 storage/oauth-private.key 2>/dev/null || true
fi
if [ -f storage/oauth-public.key ]; then
  chmod 644 storage/oauth-public.key 2>/dev/null || true
fi

exec "$@"
