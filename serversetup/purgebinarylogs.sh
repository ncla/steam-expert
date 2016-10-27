#!/bin/bash
SLAVE_PASS=############
MASTER_USER=forge
MASTER_PASS=###########

# TODO : centralize server data
SLAVES=('##.###.###.###')
MASTER_LOGS=()
LOWEST_LOG=1000000
for SLAVE in "${SLAVES[@]}"
do
    LOG=$(mysql --host=$SLAVE --user=master --password=$SLAVE_PASS -e 'show slave status\G' | grep ' Relay_Master_Log_File' | cut -d'.' -f 2)
    if [ -z "$LOG" ]
    then
        echo "FAILED TO CONNECT TO $SLAVE";
        exit 1;
    fi
    MASTER_LOGS+=$((10#$LOG))
    if [[ "10#$LOG" -lt "$LOWEST_LOG" ]]; then
        LOWEST_LOG="$LOG"
    fi
done

if [ "$LOWEST_LOG" -ne 1000000 ]; then
    echo PURGING LOGS TO $LOWEST_LOG
    $(mysql -u $MASTER_USER -p$MASTER_PASS -e 'PURGE BINARY LOGS TO "mariadb-bin.'$LOWEST_LOG'"')
fi

