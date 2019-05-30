#!/bin/bash

sed 1d $1 | while IFS=" " read -r a b c d;
do
  echo "$a"
  echo "$b"
  echo "$c"
  echo "$d"
done;