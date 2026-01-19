#!/bin/bash
git submodule update --init --recursive
cd ../giusoft/gfw
git remote set-url origin git@github.com:giusoft/gfw.git
cd ../src/Lib/sped-nfe
git checkout -b develop
git remote set-url origin git@github.com:giusoft/sped-nfe.git
cd src/Lib/sped-common
git checkout -b develop
git remote set-url origin git@github.com:giusoft/sped-common.git
cd ../../../
cd src/Lib/sped-da
git checkout -b develop
git remote set-url origin git@github.com:giusoft/sped-da.git
cd ../../../
cd src/Lib/sped-nfe
git checkout -b develop
git remote set-url origin git@github.com:giusoft/sped-nfe.git