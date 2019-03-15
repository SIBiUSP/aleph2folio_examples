#!/usr/bin/perl

use strict;
use warnings;

system('perl load-data.pl --custom-method "instances/"=PUT sample-data');

unlink glob "'sample-data/instance-storage/instances/*.*'";
unlink glob "'sample-data/holdings-storage/holdings/*.*'";
unlink glob "'sample-data/item-storage/items/*.*'";
