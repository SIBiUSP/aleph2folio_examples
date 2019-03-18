#!/bin/bash
# Import Record Source to FOLIO

catmandu convert MARC --type ALEPHSEQ to MARC --type MiJ --array 0 --line_delimited 1 < $1 > tmp/output.json

split -l 1 -d --additional-suffix=.json tmp/output.json outputJSON/source_file


rm tmp/output.json

for entry in "outputJSON"/*
do  
  SYSNO=$(cat $entry | jq '.fields[]."001"' | grep '"' | tr -d '"') 
  UUID=$(cat output/sysno_id.csv | grep $SYSNO | cut -d, -f2)
  mkdir -p sample-data/instance-storage/instances/$UUID/source-record/marc-json
  mv $entry sample-data/instance-storage/instances/$UUID/source-record/marc-json/$UUID.json
done

perl load-data.pl --custom-method "instances/"=PUT sample-data

rm -rf sample-data/instance-storage/instances/*

