#!/usr/bin/perl

# Convert ALEPHSEQ to JSON

use Catmandu;
use JSON;
use Data::UUID;
use strict;
use warnings;

my $importer = Catmandu->importer('MARC',file => 'input/2records.seq' , type => 'ALEPHSEQ');
my $fixer    = Catmandu->fixer('fixesCatmandu.txt');
my $exporter = Catmandu->exporter('JSON', file => "output/output.json");

$exporter->add_many(
     $fixer->fix($importer)
);
