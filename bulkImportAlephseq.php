#!/usr/bin/php
<?php

require 'inc/functions.php';

$folioCookies = FolioREST::loginREST();

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

        foreach ($record as $record_line) {
            AlephseqToFolioCodex($record_line);  
        }

        $body = fixes($marc);
        $jsonOutput = json_encode($body); 

        FolioREST::addRecordREST($folioCookies, $jsonOutput);        
            
        $marc = [];
        $record = [];
    } 

    $sysno_old = $sysno;
    $i++;
}