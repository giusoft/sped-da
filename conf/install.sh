#!/bin/bash

# 1. Inicializa e atualiza todos os submódulos definidos no .gitmodules
echo "Iniciando a atualização dos submódulos..."
git submodule update --init --recursive

# 2. Configura o GFW (Giusoft Framework)
echo "Configurando gfw..."
# Caminho: raiz -> giusoft/gfw
cd giusoft/gfw
git remote set-url origin git@github.com:giusoft/gfw.git
# Volta 2 níveis para a raiz do projeto (emitenota)
cd ../../

# 3. Configura os submódulos dentro de giusoft/src/Lib
libs=("sped-nfe" "sped-common" "sped-da")

for lib in "${libs[@]}"; do
    echo "Configurando $lib..."
    if [ -d "giusoft/src/Lib/$lib" ]; then
        cd "giusoft/src/Lib/$lib"
        git checkout develop 2>/dev/null || git checkout -b develop
        git remote set-url origin git@github.com:giusoft/$lib.git
        # Sobe 4 níveis para voltar à raiz (lib -> Lib -> src -> giusoft -> raiz)
        cd ../../../../
    else
        echo "Erro: Pasta giusoft/src/Lib/$lib não encontrada."
    fi
done

echo "Instalação concluída com sucesso!"