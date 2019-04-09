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

# Sample usage with ALEPHSEQ

perl bulkImportAlephseq.pl fixes.txt ALEPHSEQ < input/2records.seq

# Sample usage with .mrc

perl bulkImportAlephseq.pl fixes.txt ISO < input/examples.mrc

# Formats accepted

ISO: L<Catmandu::Importer::MARC::ISO> (default) - a strict ISO 2709 parser
RAW: L<Catmandu::Importer::MARC::RAW> - a loose ISO 2709 parser that skips faulty records
ALEPHSEQ: L<Catmandu::Importer::MARC::ALEPHSEQ> - a parser for Ex Libris Aleph sequential files
Lint: L<Catmandu::Importer::MARC::Lint> - a MARC syntax checker
MicroLIF: L<Catmandu::Importer::MARC::MicroLIF> - a parser for the MicroLIF format
MARCMaker: L<Catmandu::Importer::MARC::MARCMaker> - a parser for MARCMaker/MARCBreaker records
MiJ: L<Catmandu::Importer::MARC::MiJ> (MARC in JSON) - a parser for the MARC-in-JSON format
XML: L<Catmandu::Importer::MARC::XML> - a parser for the MARC XML format


# Catmandu Fixes 

https://github.com/LibreCat/Catmandu/wiki/Fixes-Cheat-Sheet