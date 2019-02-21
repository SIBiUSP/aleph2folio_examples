<?php

/* Load libraries for PHP composer */
require __DIR__.'/vendor/autoload.php';

$oaiURL = "http://dedalus.usp.br/OAI";
$setOAI = "SET_IEB";

$oaiUrl = $oaiURL;
$client_harvester = new \Phpoaipmh\Client(''.$oaiUrl.'');
$myEndpoint = new \Phpoaipmh\Endpoint($client_harvester);


if ($setOAI != "") {
    $recs = $myEndpoint->listRecords('marc21', null, null, $setOAI);
} else {
    $recs = $myEndpoint->listRecords('marc21');
}

foreach ($recs as $rec) {

    $json = json_encode($rec, true);
    print_r($json);


    exit;
}

?>
