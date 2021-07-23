#!/usr/bin/env bash
cd "$(dirname "$0")"

uid=$(id -u)
gid=$(id -g)

printf "UID=${uid}\nGID=${gid}\nCOMPOSE_PROJECT_NAME=readid" > .env

docker-compose up -d
