#!/usr/bin/perl

# Convert ALEPHSEQ to JSON

use Catmandu;
use strict;
use warnings;
use Data::Dumper;

my $importer = Catmandu->importer('MARC',file => $ARGV[0], type => 'ALEPHSEQ');
#my $fixer    = Catmandu->fixer('marcInJSON.txt');

my $count = 0;

$importer->benchmark->each(sub {
   my $rec = $_[0];
   Catmandu->exporter('JSON', array => 0, fix=> 'fixesCatmandu.txt', file => "sample-data/instance-storage/instances/out_${count}.json")->add($rec);   
   #Catmandu->exporter('MARC', type => 'MiJ', array => 0, fix=> 'marcInJSON.txt', file => "outputJSON/out_${count}.json")->add($rec);
   #Catmandu->exporter('JSON', array => 0, fix=> 'fixesCatmandu.txt', file => "sample-data/instance-storage/instances/out_${count}.json")->add($rec);
   $count++;
   if(($count % 10) == 0){
      #system('/bin/bash importSourceRecord.sh');
   }
});

#system('/bin/bash importSourceRecord.sh');