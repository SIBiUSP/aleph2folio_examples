# aleph2folio_examples
Examples to Convert ALEPHSEQ to JSON


## Dependencies

Catmandu: http://librecat.org/


# IMPORTANT

Needs to create Locations on FOLIO with the same code used in Aleph

# Install Catmandu in Debian

sudo apt-get update

sudo apt-get install libcatmandu*-perl

## If need install an extra Catmandu module

sudo cpanm  Catmandu::XLS

# To install Catmandu in another OS

http://librecat.org/Catmandu/#installation

# Sample usage

perl bulkImportAlephseq.pl fixes.txt ALEPHSEQ < input/2records.seq

# Catmandu Fixes 

https://github.com/LibreCat/Catmandu/wiki/Fixes-Cheat-Sheet