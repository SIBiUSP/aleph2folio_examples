# aleph2folio_examples
Examples to Convert ALEPHSEQ to JSON

// Dependencies

catmandu
jq

UUID=$(cat /proc/sys/kernel/random/uuid)


// Run Bulk Import

php bulkImportAlephseq.php < input/41records.seq


// Convert ALEPHSEQ to JSON using Catmandu

./catmanduConvertAlephseq2JSON.sh

// Convert OAI to JSON using Catmandu

./catmanduOAItoJSON.sh

// FOLIO Codex Model 

https://s3.amazonaws.com/foliodocs/api/mod-inventory/inventory.html#inventory_instances_post



// Using load-perl.pl

perl load-data.pl --custom-method "instances/"=PUT sample-data