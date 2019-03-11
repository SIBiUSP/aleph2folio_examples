#!/usr/bin/php
<?php

#require 'inc/config.php';
require 'inc/functions.php';

$folioCookies = FolioREST::loginREST();

$handle = fopen("input/2itens.csv", "r");
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        // process the line read.
        $item = explode(",", $line);
        $sysno = substr("$item[0]", 1,9);
        $instanceUUID = FolioREST::queryInstancesHRID($folioCookies, $sysno);

        // Create holdings


#       $body_id["query"]["terms"]["_id"][] = $item[0];
#        $exists_test = elasticsearch::elastic_search($type, "item", null, $body_id);
#        if ($exists_test["hits"]["total"] == 1) {
#            if (!empty($exists_test["hits"]["hits"][0]["_source"]["item"])) {
#                foreach ($exists_test["hits"]["hits"][0]["_source"]["item"] as $existingItens) {
#                    if ($existingItens["Z30_BARCODE"] == trim($item[2])) {
#                        unset($existingItens);
#                        $existingItens["Z30_BARCODE"] = trim($item[2]);
#                        $existingItens["Z30_SUB_LIBRARY"] = trim($item[3]);
#                        $existingItens["Z30_OPEN_DATE"] = trim($item[4]);
#                        $existingItens["Z30_UPDATE_DATE"] = trim($item[5]);
#                        $existingItens["Z30_NO_LOANS"] = trim($item[6]);
#                        $existingItens["Z30_CALL_NO"] = trim($item[7]);
#                        $existingItens["Z30_INVENTORY_NUMBER"] = trim($item[8]);
#                        $body["doc"]["item"][] =  $existingItens;
#                    } else {
#                        $body["doc"]["item"][] =  $existingItens;
#                    }
#                }
#            } else {
#                $body["doc"]["item"][0]["Z30_BARCODE"] = trim($item[2]);
#                $body["doc"]["item"][0]["Z30_SUB_LIBRARY"] = trim($item[3]);
#                $body["doc"]["item"][0]["Z30_OPEN_DATE"] = trim($item[4]);
#                $body["doc"]["item"][0]["Z30_UPDATE_DATE"] = trim($item[5]);
#                $body["doc"]["item"][0]["Z30_NO_LOANS"] = trim($item[6]);
#                $body["doc"]["item"][0]["Z30_CALL_NO"] = trim($item[7]);
#                $body["doc"]["item"][0]["Z30_INVENTORY_NUMBER"] = trim($item[8]);
#            }

#            $body["doc"]["itemCollect"] = true;
#            $body["doc"]["itemCollectDate"] = date("Ymd");
#            $body["doc_as_upsert"] = true;
#            $response = elasticsearch::elastic_update($item[0], $type, $body);
#            print_r($response);
#        }
#        unset($body_id);
#        unset($body);

  }

    fclose($handle);
} else {
    // error opening the file.
    echo "Erro ao abrir o arquivo";
}
