#!/bin/bash

# add urls separated by white space TODO : centralize
HOSTS="https://steam.expert/api/items/name/AK-47%20%7C%20Redline%20%28Field-Tested%29?history=1&appid=730"

COUNT=1

for myHost in $HOSTS
do
    CODE=$(curl --write-out "%{http_code}\n" --silent --output /dev/null "$myHost")
    if [ "$CODE" -ne 200 ]; then
        curl -H "Content-Type: application/json" -X POST -d '{"text":"'$myHost' is down?"}' https://hooks.slack.com/$
    fi
    echo "$CODE $myHost"
done

