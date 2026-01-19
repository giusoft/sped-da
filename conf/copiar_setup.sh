#!/usr/bin/env bash
# Uso:
#   ./copiar_setup.sh logic /var/www/html/wms/logic/setup.php

empresa="$1"
caminhoCompleto="$2"
container="emitenota_app"

# --- Validações ---

# precisa rodar no HOST, não no container!
if ! command -v docker >/dev/null 2>&1; then
  echo "❌ ERRO: Este script deve ser executado NO HOST. O comando 'docker' não foi encontrado."
  exit 1
fi

if [ -z "$empresa" ] || [ -z "$caminhoCompleto" ]; then
  echo "Uso: $0 <empresa> <caminho_do_setup_no_host>"
  exit 1
fi

if [ ! -f "$caminhoCompleto" ]; then
  echo "❌ ERRO: Arquivo não encontrado no host: $caminhoCompleto"
  exit 1
fi

if ! docker ps --format '{{.Names}}' | grep -qx "$container"; then
  echo "❌ ERRO: O container '$container' não está rodando."
  exit 1
fi

# --- Execução ---

echo "📁 Criando diretório dentro do container..."
docker exec "$container" bash -lc "mkdir -p /opt/runtime_includes/$empresa && chown -R www-data:www-data /opt/runtime_includes"

echo "📦 Copiando arquivo para dentro do container..."
docker cp "$caminhoCompleto" "$container:/opt/runtime_includes/$empresa/setup.php"

echo "✅ Copiado!"
echo "📌 Verifique dentro do container:"
echo "    docker exec -it $container ls -l /opt/runtime_includes/$empresa"
