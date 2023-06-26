#!/bin/sh

if ! command -v docker >/dev/null 2>&1; then
    echo
    echo "Please check that Docker is correctly installed https://www.docker.com"
    echo
    exit 1
fi

if [[ "$1" == "delete" ]]; then
    docker rmi -f ghcr.io/osteel/dime
    rm -- "$0"
    exit 0
fi

if ! [ -f "~/.dime/database.sqlite" ]; then
    mkdir -p ~/.dime && touch ~/.dime/database.sqlite
fi

command="docker run -it --rm -v ~/.dime/database.sqlite:/root/.dime/database.sqlite"

if [[ "$1" == "process" && -n "$2" && -f "$2" ]]; then
    filename=$(basename "$2")
    path=$(cd $(dirname $2); pwd)/$filename
    command+=" -v $path:/tmp/$filename ghcr.io/osteel/dime:latest process /tmp/$filename"
else
    command+=" ghcr.io/osteel/dime:latest ${@:1}"
fi

eval "$command"