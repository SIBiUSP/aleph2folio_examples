#!/bin/bash

#Get Folio Token

FOLIO_TOKEN=$( curl --silent --output /dev/null -w '\n' -D - -X POST -H "Content-type: application/json" \
  -H "Accept: application/json" -H "X-Okapi-Tenant: diku" \
  -d '{"username":"diku_admin","password":"admin"}' \
  http://172.31.1.52:9130/authn/login | grep x-okapi-token | sed 's/x-okapi-token: //')

DELETE_ID=$(curl --silent -H "Accept: application/json" -H "Content-type: application/json" -H "X-Okapi-Tenant: diku" -H "X-Okapi-Token: $FOLIO_TOKEN" -X GET http://172.31.1.52:9130/inventory/instances?query=hrid="$1" | jq --raw-output '.instances[0].id')

curl --silent -H "Accept: application/json" -H "Content-type: application/json" -H "X-Okapi-Tenant: diku" -H "X-Okapi-Token: $FOLIO_TOKEN" -X DELETE http://172.31.1.52:9130/inventory/instances/$DELETE_ID