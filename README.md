# aleph2folio_examples
Examples to Convert ALEPHSEQ to JSON


// Run Bulk Import

php bulkImportAlephseq.php < input/41records.seq


// Run OAI Client

curl -s http://getcomposer.org/installer | php

php composer.phar install --no-dev

sudo pear install File_MARC

// FOLIO Codex Model 

https://github.com/folio-org/raml/blob/raml1.0/schemas/codex/instance.json