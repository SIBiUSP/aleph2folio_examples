#!/bin/bash
#Use Catmandu to convert ALEPHSEQ to Folio Codex and import in Folio using Inventory API


#Get Folio Token

FOLIO_TOKEN=$( curl --silent --output /dev/null -w '\n' -D - -X POST -H "Content-type: application/json" \
  -H "Accept: application/json" -H "X-Okapi-Tenant: diku" \
  -d '{"username":"diku_admin","password":"admin"}' \
  http://172.31.1.52:9130/authn/login | grep x-okapi-token | sed 's/x-okapi-token: //')

# Convert ALEPHSEQ to Folio Codex JSON

catmandu convert MARC --type ALEPHSEQ to JSON < input/2records.seq --fix fixesCatmandu.txt  > output/output.json

# Import Folio Codex on JSON

jq -c '.[]' output/output.json | while read i; do
    UUID=$(cat /proc/sys/kernel/random/uuid)
    COMPLETE_JSON=$(jq '. += {"id": "'$UUID'"}')
    curl -H "Accept: application/json" -H "Content-type: application/json" -H "X-Okapi-Tenant: diku" -H "X-Okapi-Token: $FOLIO_TOKEN" -X POST -d "$COMPLETE_JSON"  http://172.31.1.52:9130/inventory/instances
done

# Delete temp file
rm output/output.json
