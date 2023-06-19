#!/usr/bin/env bash
set -e

cd "$(dirname $0)"

export USER_UID="$(id -u):$(id -g)"
export PROJECT=sprout

if [[ $1 ]]; then
    CMD="$1"
    shift 1
else
    CMD="help"
fi

COMPOSE="docker-compose --project-name $PROJECT"

case "$CMD" in
    "up")
        $COMPOSE up \
            --build \
            --remove-orphans \
            --detach \
            $@
        exit $?
    ;;

    "sprout")
        $COMPOSE exec app ./web/index.php $@
        exit $?
    ;;

    "shell"|"sh")
        $COMPOSE exec $1 sh
        exit $?
    ;;

    "sql")
        $COMPOSE exec mysql mysql -u sprout3
        exit $?
    ;;

    "help")
        echo "One of:"
        echo " - up [...images]"
        echo " - sprout [...args]"
        echo " - shell <image>"
        echo " - sql"
        exit 1
    ;;

    *)
        $COMPOSE $CMD $@
        exit $?
    ;;
esac
