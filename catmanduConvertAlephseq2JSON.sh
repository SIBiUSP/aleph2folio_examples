#!/bin/bash
rm output/output.json

catmandu convert MARC --type ALEPHSEQ to JSON --line_delimited 1 < input/41records.seq --fix fixes/fixes.txt >> output/output.json