#!/bin/bash
#Use Catmandu to convert ALEPHSEQ to Folio Codex and import in Folio using Inventory API


#Get Folio Token

OKAPI=http://172.31.1.52:9130
FOLIO_TOKEN=$( curl -s -S -D - -H "X-Okapi-Tenant: diku" -H "Content-type: application/json" -H "Accept: application/json" \
    -d '{"tenant":"diku","username":"diku_admin","password":"admin"}' $OKAPI/authn/login \
    | grep -i "^x-okapi-token: " | sed 's/x-okapi-token: //' )  

# Convert input file to JSON and import in FOLIO

function callcatmandu()
{
     echo "${1}" | catmandu convert MARC --type ALEPHSEQ to JSON --fix fixesCatmandu.txt | jq -c '.[]' | while read -r line
     do 
          echo $line | curl -s -S -H "Accept: application/json" -H "Content-type: application/json" -H "X-Okapi-Tenant: diku" -H "X-Okapi-Token: $FOLIO_TOKEN" -X POST -d @- $OKAPI/inventory/instances 
     done
}

lgp=''
ctmlins=''

cat $1 | while read -r seqlin
do
   sysno=$(echo ${seqlin} | cut -c 1-9)
   if [[ "${lgp}" != "${sysno}" ]]
   then
          lgp="${sysno}"
          if [[ "${ctmlins}" != "" ]]
          then callcatmandu "${ctmlins}"
          fi
          ctmlins=''
   fi
   printf -v ctmlins "${ctmlins}\n${seqlin}"
done
callcatmandu ${ctmlins}