#!/bin/bash

#Get Folio Token

FOLIO_TOKEN=$( curl --silent --output /dev/null -w '\n' -D - -X POST -H "Content-type: application/json" \
  -H "Accept: application/json" -H "X-Okapi-Tenant: diku" \
  -d '{"username":"diku_admin","password":"admin"}' \
  http://172.31.1.52:9130/authn/login | grep x-okapi-token | sed 's/x-okapi-token: //')

curl --silent -H "X-Okapi-Tenant: diku" -H "X-Okapi-Token: $FOLIO_TOKEN" -X DELETE http://172.31.1.52:9130/inventory/items

curl --silent -H "X-Okapi-Tenant: diku" -H "X-Okapi-Token: $FOLIO_TOKEN" -X DELETE http://172.31.1.52:9130/holdings-storage/holdings

curl --silent -H "X-Okapi-Tenant: diku" -H "X-Okapi-Token: $FOLIO_TOKEN" -X DELETE http://172.31.1.52:9130/inventory/instances