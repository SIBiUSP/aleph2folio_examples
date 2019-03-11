#!/bin/bash
#Use Catmandu to convert ALEPHSEQ to Folio Codex and import in Folio using Inventory API


#Get Folio Token

OKAPI=http://172.31.1.52:9130
FOLIO_TOKEN=$( curl -s -S -D - -H "X-Okapi-Tenant: diku" -H "Content-type: application/json" -H "Accept: application/json" \
    -d '{"tenant":"diku","username":"diku_admin","password":"admin"}' $OKAPI/authn/login \
    | grep -i "^x-okapi-token: " | sed 's/x-okapi-token: //' )

# Convert input file to JSON and import in FOLIO

catmandu convert MARC --type ALEPHSEQ to JSON --fix fixesCatmandu.txt --array 0 --line_delimited 1 < $1 > tmp/output.json

split -l 1 -d --additional-suffix=.json tmp/output.json sample-data/instance-storage/instances/import_file

#perl load-data.pl --custom-method "instances/"=PUT sample-data

rm tmp/output.json
#rm sample-data/instance-storage/instances/*.json
