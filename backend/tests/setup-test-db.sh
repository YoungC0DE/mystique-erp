#!/usr/bin/env bash
# Cria o banco isolado de testes (mystique_test) e concede acesso ao usuário da aplicação.
# Uso (no host, via WSL): docker compose exec -T mysql sh < backend/tests/setup-test-db.sh
set -e

until mysqladmin ping -uroot -proot --silent 2>/dev/null; do
  sleep 2
done

mysql -uroot -proot <<'SQL'
CREATE DATABASE IF NOT EXISTS mystique_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON mystique_test.* TO 'mystique'@'%';
FLUSH PRIVILEGES;
SQL

echo "mystique_test pronto."
