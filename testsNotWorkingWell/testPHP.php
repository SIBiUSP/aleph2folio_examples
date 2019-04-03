#!/usr/bin/php
<?php

require 'inc/functions.php';

$folioCookies = FolioREST::loginREST();
$OKAPI = 'http://172.31.1.52:9130';

$record = array();
$sysno_old = '000000000';

$i = 0;
while ($line = fgets(STDIN)) {
    $sysno = substr($line, 0, 9);
    if ($sysno_old == '000000000') {
        $sysno_old = $sysno;
    } 
    if ($sysno_old == $sysno) {
        $record[] = $line;
    } else {

        $alephseqFile = 'tmp/alephseq.seq';
        foreach ($record as $record_line) {
            AlephseqToFolioCodex($record_line);
            file_put_contents($alephseqFile, $record_line, FILE_APPEND);  
        }
      
        $body = fixes($marc);
        $jsonOutput = json_encode($body); 

        $instanceFile = fopen("sample-data/instance-storage/instances/$i.json", "w") or die("Unable to open file!");
        fwrite($instanceFile, $jsonOutput);
        fclose($instanceFile);
        
        # Import Source Record

        $instanceID = $body["id"];
        mkdir("sample-data/instance-storage/instances/$instanceID/source-record/marc-json", 0755, true);        
        shell_exec("catmandu convert MARC --type ALEPHSEQ to MARC --type MiJ --array 0 --line_delimited 1 < tmp/alephseq.seq > sample-data/instance-storage/instances/$instanceID/source-record/marc-json/source.json");
        #shell_exec("curl -s -S -w -D -X PUT -H 'Content-type: application/json' -H 'X-Okapi-Tenant: diku' -H 'X-Okapi-Token: $folioCookies' -d @tmp/source.json $OKAPI/instance-storage/instances/$instanceID/source-record/marc-json > /dev/null");
        unlink("tmp/alephseq.seq");
        #unlink("tmp/source.json");               

        if (isset($marc["record"]["Z30"])) {
            # Create holdings 

            foreach ($marc["record"]["Z30"] as $libraryHolding) {
                $librariesArray[] = $libraryHolding["1"];
            }
            $librariesArrayUnique = array_unique($librariesArray);

            $i_holdings = 0;
            foreach ($librariesArrayUnique as $libraryCode) {
                $holdings["id"] = gen_uuid();
                $holdings["instanceId"] = $body["id"];
                $holdings["permanentLocationId"] = decode::get_library_location_id($libraryCode);
                $jsonHoldings = json_encode($holdings);
                $holdingsID["$libraryCode"] = $holdings["id"];
                
                $holdingsFile = fopen("sample-data/holdings-storage/holdings/$i_holdings.json", "w") or die("Unable to open file!");
                fwrite($holdingsFile, $jsonHoldings);
                fclose($holdingsFile);
                $i_holdings++;
            }
         

            # Create itens
            $i_itens = 0;    
            foreach ($marc["record"]["Z30"] as $item) {

                $libraryCodeitem = $item["1"];
                $itemArray["id"] = gen_uuid();
                $itemArray["holdingsRecordId"] = $holdingsID["$libraryCodeitem"];                
                $itemArray["itemLevelCallNumber"] = $item["3"];
                $itemArray["barcode"] = $item["5"];
                $itemArray["materialTypeId"] = "1a54b431-2e4f-452d-9cae-9cee66c9a892";
                $itemArray["permanentLoanTypeId"] = "2b94c631-fca9-4892-a730-03ee529ffe27";
                
                $jsonItem = json_encode($itemArray);

                $itemFile = fopen("sample-data/item-storage/items/$i_itens.json", "w") or die("Unable to open file!");
                fwrite($itemFile, $jsonItem);
                fclose($itemFile);

                $i_itens++;

            }
        }
            
        $marc = [];
        $record = [];
        $alephseq = [];
        $holdings = [];
        $librariesArray = [];
    } 

    $sysno_old = $sysno;
    $i++;
}

shell_exec('perl load-data.pl --custom-method "instances/"=PUT sample-data');

array_map('unlink', glob("sample-data/instance-storage/instances/*.json"));
array_map('unlink', glob("sample-data/holdings-storage/holdings/*.json"));
array_map('unlink', glob("sample-data/item-storage/items/*.json"));

shell_exec('rm -rf sample-data/instance-storage/instances/*');