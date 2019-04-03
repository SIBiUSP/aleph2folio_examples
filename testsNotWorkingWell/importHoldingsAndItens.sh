#!/bin/bash
# Process CSV file

sed 1d $1 | while IFS="," read -r a b c d e f;
do
    BARCODE=$(echo $b | sed -e 's/^"//' -e 's/"$//' | awk '{$1=$1};1')
    SYSNO=$(sed -e 's/^"//' -e 's/"$//' <<< $a | cut -c -9)
    INSTANCEID=$(cat output/sysno_id.csv | grep $SYSNO | cut -d, -f2)

    #echo $INSTANCEID

    #create Holdings
    echo "" >> output/sysno_holdings_id.csv

    PERMANENT_LOCATION_ID="bd1ec401-d62e-4c43-9a59-04419a270618"
    cat output/sysno_holdings_id.csv | grep $SYSNO | cut -d, -f2
    EXISTING_HOLDINGS=$(cat output/sysno_holdings_id.csv | grep $SYSNO | cut -d, -f2)

    echo $EXISTING_HOLDINGS

    if [ ! "$EXISTING_HOLDINGS" ]
    then
        UUID_Holdings=$(uuidgen -r)

        JSON_STRING_HOLDINGS=$( jq -n \
                    --arg a "$UUID_Holdings" \
                    --arg b "$INSTANCEID" \
                    --arg c "$PERMANENT_LOCATION_ID" \
                    '{id: $a, instanceId: $b, permanentLocationId: $c}' )

        echo $JSON_STRING_HOLDINGS > sample-data/holdings-storage/holdings/$UUID_Holdings.json
        echo $SYSNO,$UUID_Holdings >> output/sysno_holdings_id.csv

    else
        UUID_Holdings="$EXISTING_HOLDINGS"
    fi  

    #create Items

    UUID_Item=$(uuidgen -r)
    MATERIAL_TYPE_ID="1a54b431-2e4f-452d-9cae-9cee66c9a892"
    PERMANENT_LOAN_TYPE_ID="2b94c631-fca9-4892-a730-03ee529ffe27"
    STATUS_NAME="Available"

    JSON_STRING_ITEMS=$( jq -n \
                  --arg a "$UUID_Item" \
                  --arg b "$UUID_Holdings" \
                  --arg c "$BARCODE" \
                  --arg d "$MATERIAL_TYPE_ID" \
                  --arg e "$PERMANENT_LOAN_TYPE_ID" \
                  --arg f "$STATUS_NAME" \
                  '{id: $a, holdingsRecordId: $b, barcode: $c, materialTypeId: $d, permanentLoanTypeId: $e, status: {name: $f}}' )

    echo $JSON_STRING_ITEMS > sample-data/item-storage/items/$UUID_Item.json

done

perl load-data.pl --custom-method "instances/"=PUT sample-data

rm sample-data/holdings-storage/holdings/*   
rm sample-data/item-storage/items/*
rm output/*