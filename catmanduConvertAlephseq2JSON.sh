#!/bin/bash
catmandu convert MARC --type ALEPHSEQ to JSON --line_delimited 1 < input/2records.seq --fix fixesCatmandu.txt | jq .