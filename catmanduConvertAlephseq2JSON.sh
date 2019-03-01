#!/bin/bash
#Use Catmandu to convert ALEPHSEQ to Folio Codex and import in Folio using Inventory API


#Get Folio Token

FOLIO_TOKEN=$( curl --silent --output /dev/null -w '\n' -D - -X POST -H "Content-type: application/json" \
  -H "Accept: application/json" -H "X-Okapi-Tenant: diku" \
  -d '{"username":"diku_admin","password":"admin"}' \
  http://172.31.1.52:9130/authn/login | grep x-okapi-token | sed 's/x-okapi-token: //')

# Convert ALEPHSEQ to Folio Codex JSON

catmandu convert MARC --type ALEPHSEQ to JSON < input/bas01iri.seq --fix fixesCatmandu.txt  >> output/output.json

# Import Folio Codex on JSON
jq -c '.[]' output/output.json >| output/line.txt 

while read -r line; do 

  echo $line > input/temp.json

  UUID=$(uuidgen -r)
  jq -c '. += {"id": "'$UUID'"}' < input/temp.json > output/complete.json
  curl -H "Accept: application/json" -H "Content-type: application/json" -H "X-Okapi-Tenant: diku" -H "X-Okapi-Token: $FOLIO_TOKEN" -X POST -d @output/complete.json  http://172.31.1.52:9130/inventory/instances
  #unset UUID
  rm input/temp.json
  rm output/complete.json

done < output/line.txt

# Delete temp file
rm output/output.json
rm output/line.txt
