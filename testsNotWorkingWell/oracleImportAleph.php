#!/usr/bin/php
<?php

require 'inc/functions.php';

$conn = oci_connect("user", "password", "//111.111.111.111:1111/table",'UTF8');

//SQL query to get last modified records - NEED CHANGE TABLE NAME
$consulta = "select DISTINCT Z00_DOC_NUMBER from USP01.Z00 where ORA_ROWSCN > TIMESTAMP_TO_SCN(CURRENT_TIMESTAMP - NUMTODSINTERVAL(1440, 'MINUTE'))";
$stid = oci_parse($conn, $consulta) or die ("erro");
 
//Run query
oci_execute($stid);

while (($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) != false) {
    foreach ($row as $sysno) {
        $result_oracle_sysno = oracle_sysno($sysno);
        foreach ($result_oracle_sysno as $record_line) {
            AlephseqToFolioCodex($record_line);
		}
        $body = fixes($marc);
        $jsonOutput = json_encode($body);
        print_r($jsonOutput);           
        echo "\n\n";
		$marc = [];        
    }
}

// Close the Oracle connection
oci_close($conn);