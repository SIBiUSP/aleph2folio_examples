#!/usr/bin/perl

# Convert ALEPHSEQ to JSON

use Catmandu;
use strict;
use warnings;

my $importer = Catmandu->importer('MARC',file => $ARGV[0] , type => 'ALEPHSEQ');
my $fixer    = Catmandu->fixer('fixesCatmandu.txt');
#my $exporter = Catmandu->exporter('JSON', array => 0, line_delimited =>1, file => "output/output1.json");

my $count = 0;

$fixer->fix($importer->benchmark)->each(sub {
  my $rec = $_[0];
  Catmandu->exporter('JSON', array => 0, file => "sample-data/instance-storage/instances/out_${count}.json")->add($rec);
  $count++;
  if(($count % 1000) == 0){
    system('perl load-data.pl --custom-method "instances/"=PUT sample-data');
    unlink glob "'sample-data/instance-storage/instances/*.*'";
  }
});

system('perl load-data.pl --custom-method "instances/"=PUT sample-data');

unlink glob "'sample-data/instance-storage/instances/*.*'";
