#!/bin/bash
i=0
function callcatmandu()
{

     i=$(($i+1))

     echo "${1}" | catmandu convert MARC --type ALEPHSEQ to JSON --fix fixesCatmandu.txt | jq -c '.[]' | while read -r line
     do 
          UUID=$(uuidgen -r)  
          if ! test -d "sample-data/instance-storage/instances/$(( $i / 1000 ))"
          then  mkdir -p "sample-data/instance-storage/instances/$(( $i / 1000 ))"
          fi
          echo $line | jq -c '. += {"id": "'$UUID'"}' > "sample-data/instance-storage/instances/$(( $i / 1000 ))/$i.json"
          # | curl -s -S -H "Accept: application/json" -H "Content-type: application/json" -H "X-Okapi-Tenant: diku" -H "X-Okapi-Token: $FOLIO_TOKEN" -X POST -d @- $OKAPI/inventory/instances 
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


