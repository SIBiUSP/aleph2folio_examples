#!/bin/bash
# Import Record Source to FOLIO

#perl convertAlephseqToMARC-In-JSON.pl $1

counter=0

for entry in "outputJSON"/*
do  
  SYSNO=$(cat $entry | jq '.fields[]."001"' | grep '"' | tr -d '"') 
  UUID=$(LC_ALL=C fgrep "$SYSNO" < output/sysno_id.csv | cut -d, -f2)
  mkdir -p sample-data/instance-storage/instances/$UUID/source-record/marc-json
  mv $entry sample-data/instance-storage/instances/$UUID/source-record/marc-json/$UUID.json
  counter=$((counter+1))
  if [[ $(( counter % 10 )) = 0 ]]
  then
    perl load-data.pl --custom-method "instances/"=PUT sample-data
    rm -rf sample-data/instance-storage/instances/*
  fi
done

perl load-data.pl --custom-method "instances/"=PUT sample-data

rm -rf sample-data/instance-storage/instances/*

