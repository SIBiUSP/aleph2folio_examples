#!/bin/bash


sysno=$(head -n 1 $1 | cut -c 1-9)
number_of_lines=$(head -n 200 $1 | grep ^$sysno | wc -l)

(head -$number_of_lines > tmp/f1.txt; cat > tmp/f2.txt) < $1