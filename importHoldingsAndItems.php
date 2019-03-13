#!/usr/bin/php
<?php

#require 'inc/config.php';
require 'inc/functions.php';

$folioCookies = FolioREST::loginREST();

$handle = fopen("input/itensIRI2019.csv", "r");
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        // process the line read.
        $item = explode(",", $line);
        $sysno = substr("$item[0]", 1,9);
        $instance = FolioREST::queryInstancesHRID($folioCookies, "hrid=$sysno");

        $instanceUUID = $instance["instances"][0]["id"]; // Colocar erro caso dê 0 a busca

        // Create holdings

        //$holdings = FolioREST::queryInstancesHRID($folioCookies, "instanceId=$instanceUUID");

        //if ($holdings['totalRecords'] == 0) {
          $holdingsInstance["id"] = gen_uuid();
          $holdingsInstance["instanceId"] = $instanceUUID;
          $holdingsInstance["permanentLocationId"] = "bd1ec401-d62e-4c43-9a59-04419a270618";
          $fp = fopen('sample-data/holdings-storage/holdings/'.$holdingsInstance["id"].'.json', 'w');
          fwrite($fp, json_encode($holdingsInstance));
          fclose($fp);
        //} else {
        //  echo "Não";
        //}

        // Create items

        $barcode = trim(str_replace('"','',$item[1]));

        $itemToJSON["id"] = gen_uuid();
        $itemToJSON["holdingsRecordId"] = $holdingsInstance["id"];
        $itemToJSON["barcode"] = $barcode;
        $itemToJSON["materialTypeId"] = "1a54b431-2e4f-452d-9cae-9cee66c9a892";
        $itemToJSON["permanentLoanTypeId"] = "2b94c631-fca9-4892-a730-03ee529ffe27";
        $itemToJSON["status"]["name"] = "Available";

        $fp = fopen('sample-data/item-storage/items/'.$itemToJSON["id"].'.json', 'w');
        fwrite($fp, json_encode($itemToJSON));
        fclose($fp);

  }

    fclose($handle);
} else {
    // error opening the file.
    echo "Erro ao abrir o arquivo";
}
