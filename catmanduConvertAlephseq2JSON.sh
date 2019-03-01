#!/bin/bash
catmandu convert MARC --type ALEPHSEQ to JSON < input/2records.seq --fix fixesCatmandu.txt | jq .