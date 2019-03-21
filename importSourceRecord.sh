#!/bin/bash
# Import Record Source to FOLIO

#perl convertAlephseqToMARC-In-JSON.pl $1

OKAPI=http://172.31.1.52:9130
FOLIO_TOKEN=$( curl -s -S -D - -H "X-Okapi-Tenant: diku" -H "Content-type: application/json" -H "Accept: application/json" \
    -d '{"tenant":"diku","username":"diku_admin","password":"admin"}' $OKAPI/authn/login \
    | grep -i "^x-okapi-token: " | sed 's/x-okapi-token: //' )  

ls outputJSON/*.json | while read f; 
do
  echo "Processando o arquivo ${f}"
  SYSNO=$( cat $f | jq '.fields[]."001"' | grep '"' | tr -d '"' )
  if [[ -z "$SYSNO" ]]; then
    echo "Empty $SYSNO"
  else
    UUID=$(LC_ALL=C fgrep "$SYSNO" < output/sysno_id.csv | cut -d, -f2)
    curl -s -S -w '\n' -D - -X PUT -H "Content-type: application/json" -H "X-Okapi-Tenant: diku" -H "X-Okapi-Token: $FOLIO_TOKEN" -d @$f $OKAPI/instance-storage/instances/$UUID/source-record/marc-json > /dev/null
    rm $f
  fi   

done;

