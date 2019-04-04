# aleph2folio_examples
Examples to Convert ALEPHSEQ to JSON


## Dependencies

Catmandu: http://librecat.org/

# Install Catmandu in Debian

sudo apt-get update

sudo apt-get install libcatmandu*-perl

## If need install an extra Catmandu module

sudo cpanm  Catmandu::XLS

# To install Catmandu in another OS

http://librecat.org/Catmandu/#installation

# Sample usage

perl bulkImportAlephseq.pl fixesBias.txt < input/2records.seq