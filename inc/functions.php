<?php

function gen_uuid() {
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

        // 16 bits for "time_mid"
        mt_rand( 0, 0xffff ),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand( 0, 0x0fff ) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand( 0, 0x3fff ) | 0x8000,

        // 48 bits for "node"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
}

/*
* Converte Alephseq em JSON *
*/
function AlephseqToFolioCodex($line)
{

    global $marc;
    global $i;
    global $id;

    $id = substr($line, 0, 9);
    $field = substr($line, 10, 3);
    //$ind_1 = substr($line, 13, 1);
    //$ind_2 = substr($line, 14, 1);


    $control_fields = array("LDR","DEL","FMT","001","008");
    $repetitive_fields = array("100","510","536","650","651","655","700","856","946","952","CAT","Z30");

    if (in_array($field, $control_fields)) {
        $marc["record"][$field]["content"] = trim(substr($line, 18));

    } elseif (in_array($field, $repetitive_fields)) {
        $content = explode("\$", substr($line, 18));
        foreach ($content as &$content_line) {
            if (!empty($content_line)) {
                $marc["record"][$field][$i][substr($content_line, 0, 1)] = trim(substr($content_line, 1));
            }


        }


    } else {
        $content = explode("\$", substr($line, 18));
        foreach ($content as &$content_line) {
            if (!empty($content_line)) {
                $marc["record"][$field][substr($content_line, 0, 1)][] = trim(substr($content_line, 1));
            }
        }
    }

    //$marc["record"][$field]["ind_1"] = $ind_1;
    //$marc["record"][$field]["ind_2"] = $ind_2;

    $i++;

}

/*
* Processa o fixes *
*/
function fixes($marc)
{

    global $i;
    $body = [];

    if (isset($marc["record"]["001"])) {

        $body["id"] = gen_uuid();
        $body["hrid"] = $marc["record"]["001"]["content"];
    }

    $body["source"] = "DEDALUS";

    $body["instanceTypeId"] = "6312d172-f0cf-40f6-b27d-9fa8feaf332f";

    if (isset($marc["record"]["008"])) {
        $body["languages"][] = substr($marc["record"]["008"]["content"], 35, 3);
    }

    if (isset($marc["record"]["020"]["a"])) {
        $body["identifiers"][0]["value"] = $marc["record"]["020"]["a"][0];
         $body["identifiers"][0]["identifierTypeId"] = "8261054f-be78-422d-bd51-4ed9f33c3422";
    }

    // if (isset($marc["record"]["024"]["a"])) {
    //     $body["identifiers"]["value"] = $marc["record"]["024"]["a"][0];
    //     $body["identifiers"]["identifierTypeId"] = "DOI";
    // }

    if (isset($marc["record"]["100"])) {

        foreach (($marc["record"]["100"]) as $person) {
            $author["name"] = $person["a"];
            //if (!empty($person["0"])) {
            //    $author["person"]["orcid"] = $person["0"];
            //}
            $author["contributorNameTypeId"] = "2b94c631-fca9-4892-a730-03ee529ffe2a";
            if (!empty($person["4"])) {
                $author["contributorTypeText"] = $person["4"];
            } else {
                $author["contributorTypeText"] = "Author";
            }
            //if (!empty($person["d"])) {
            //    $author["person"]["date"] = $person["d"];
            //}
        }

        $body["contributors"][] = $author;
        unset($person);
        unset($author);
    }

    // if (isset($marc["record"]["242"])) {
    //     if (isset($marc["record"]["242"]["b"][0])) {
    //         $body["alternativeTitles"][] = $marc["record"]["242"]["a"][0] . ": " . $marc["record"]["242"]["b"][0];
    //     } else {
    //         $body["alternativeTitles"][] = $marc["record"]["242"]["a"][0];
    //     }
    // }

    if (isset($marc["record"]["245"])) {
        if (isset($marc["record"]["245"]["b"][0])) {
            $body["title"] = $marc["record"]["245"]["a"][0] . ": " . $marc["record"]["245"]["b"][0];
        } else {
            $body["title"] = $marc["record"]["245"]["a"][0];
        }
    }

    // if (isset($marc["record"]["246"])) {
    //     if (isset($marc["record"]["246"]["b"][0])) {
    //         $body["indexTitle"] = $marc["record"]["246"]["a"][0] . ": " . $marc["record"]["246"]["b"][0];
    //     } else {
    //         $body["indexTitle"] = $marc["record"]["246"]["a"][0];
    //     }
    // }

    if (isset($marc["record"]["250"]["a"])) {
        $body["editions"] = $marc["record"]["250"]["a"];
    }

    if (isset($marc["record"]["260"])) {
        if (isset($marc["record"]["260"]["b"])) {
            $body["publication"][0]["publisher"] = $marc["record"]["260"]["b"][0];
        }
        if (isset($marc["record"]["260"]["a"])) {
            $body["publication"][0]["place"] = $marc["record"]["260"]["a"][0];
        }
        if (isset($marc["record"]["260"]["c"])) {
            $body["publication"][0]["dateOfPublication"] = $marc["record"]["260"]["c"][0];
        }
        //$body["publication"]["role"] = null;
    }

    if (isset($marc["record"]["300"]["a"])) {
        $body["physicalDescriptions"] = $marc["record"]["300"]["a"];
    }

    if (isset($marc["record"]["650"])) {
        foreach (($marc["record"]["650"]) as $subject) {
            if (isset($subject["a"])) {
                $body["subjects"][] = $subject["a"];
            }
        }
    }

    if (isset($marc["record"]["700"])) {

        foreach (($marc["record"]["700"]) as $person) {
            $author["name"] = $person["a"];
            //if (!empty($person["0"])) {
            //    $author["person"]["orcid"] = $person["0"];
            //}
            $author["contributorNameTypeId"] = "2b94c631-fca9-4892-a730-03ee529ffe2a";
            if (!empty($person["4"])) {
                $author["contributorTypeText"] = $person["4"];
            } else {
                $author["contributorTypeText"] = "Author";
            }
            //if (!empty($person["d"])) {
            //    $author["person"]["date"] = $person["d"];
            //}
        }

        $body["contributors"][] = $author;
        unset($person);
        unset($author);
    }

    return $body;
    unset($body);

}

/*
* Processa o fixes *
*/
function fixesMods($marc)
{

    global $i;

    //print_r($marc);
    $body = [];

    if (isset($marc["record"]["LDR"])) {
        $body["leader"] = $marc["record"]["LDR"]["content"];
    }

    foreach ($marc["record"] as $key => $value) {
        $controlFieldsAleph = array("LDR", "BAS", "CAT", "FMT");
        $controlFields = array("001", "003", "005", "007", "008");
        if (!in_array($key, $controlFieldsAleph)) {
            if  (in_array($key, $controlFields)) {
                $body["fields"][$key] = $value["content"];
            } else {
                foreach ($value as $keyField => $valueField) {
                    switch ($keyField) {
                        case "ind_1":
                            if ($valueField != " ") {
                                $body["fields"][$key]["ind1"] = $valueField[0];
                            } else {
                                $body["fields"][$key]["ind1"] = "\\\\";
                            }
                            break;
                        case "ind_2":
                            if ($valueField != " ") {
                                $body["fields"][$key]["ind2"] = $valueField[0];
                            } else {
                                $body["fields"][$key]["ind2"] = "\\\\";
                            }
                            break;
                        default:
                            if (count($valueField) < 1) {
                                $body["fields"][$key]["subfields"][][$keyField] = $valueField;
                            } else {
                                foreach ($valueField as $keySubfield => $valueSubfield) {
                                    if (is_string($keySubfield)) {
                                        $body["fields"][$key]["subfields"][][$keySubfield] = $valueSubfield;
                                    } else {
                                        $body["fields"][$key]["subfields"][][$keyField] = $valueSubfield;
                                    }

                                }
                            }
                            break;
                    }
                }
            }

        }
    }

    return json_encode($body);
    unset($body);

}

function oracle_sysno($sysno)
{
    global $conn;
    $consulta_alephseq = "select Z00R_DOC_NUMBER, Z00R_FIELD_CODE, Z00R_ALPHA, Z00R_TEXT from USP01.Z00R where Z00R_DOC_NUMBER = '$sysno'";
    $stid = oci_parse($conn, $consulta_alephseq) or die("erro");
    oci_execute($stid);
    while (($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) != false) {
            $record[] = implode(" ", $row);
    }
    return $record;
}

class FolioREST
{
    static function loginREST()
    {

        $ch = curl_init();

        $headers = array();
        $headers[] = "X-Okapi-Tenant: diku";
        $headers[] = "Content-type: application/json";
        $headers[] = "Accept: application/json";

        curl_setopt($ch, CURLOPT_URL, "http://172.31.1.52:9130/authn/login");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"username\":\"diku_admin\",\"password\":\"admin\"}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);
        $output_parsed = explode(" ", $server_output);
        return $output_parsed[10];
        curl_close($ch);

    }

    static function getContributorNameTypes($cookies)
    {

        $ch = curl_init();

        $headers = array($cookies);
        $headers[] = "X-Okapi-Tenant: diku";
        $headers[] = 'X-Okapi-Token: '.$cookies.'';
        //$headers[] = "Content-type: application/json";
        //$headers[] = "Accept: application/json";

        curl_setopt($ch, CURLOPT_URL, "http://172.31.1.52:9130/contributor-name-types");
        //curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, "active==\"true\"");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);
        print_r($server_output);
        curl_close($ch);

    }

    static function getInstancesContext($cookies)
    {

        $ch = curl_init();

        $headers = array($cookies);
        $headers[] = "X-Okapi-Tenant: diku";
        $headers[] = 'X-Okapi-Token: '.$cookies.'';
        //$headers[] = "Content-type: application/json";
        //$headers[] = "Accept: application/json";

        curl_setopt($ch, CURLOPT_URL, "http://172.31.1.52:9130/inventory/instances/context");
        //curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);
        print_r($server_output);
        curl_close($ch);

    }

    static function getIdentifierTypes($cookies, $query = null)
    {

        $ch = curl_init();

        $headers = array($cookies);
        $headers[] = "X-Okapi-Tenant: diku";
        $headers[] = 'X-Okapi-Token: '.$cookies.'';
        $headers[] = "Content-type: application/json";
        $headers[] = "Accept: application/json";

        curl_setopt($ch, CURLOPT_URL, "http://172.31.1.52:9130/identifier-types?limit=50$query");
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);
        print_r($server_output);
        curl_close($ch);

    }

    static function queryInstancesHRID($cookies, $query)
    {
        $ch = curl_init();

        $headers = array($cookies);
        $headers[] = "X-Okapi-Tenant: diku";
        $headers[] = 'X-Okapi-Token: '.$cookies.'';
        $headers[] = "Accept: application/json";

        curl_setopt($ch, CURLOPT_URL, "http://172.31.1.52:9130/inventory/instances?query=$query");
        //curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);
        $resultArray = json_decode($server_output, true);
        return $resultArray;
        curl_close($ch);

    }

    static function queryHoldingsHRID($cookies, $query)
    {
        $ch = curl_init();

        $headers = array($cookies);
        $headers[] = "X-Okapi-Tenant: diku";
        $headers[] = 'X-Okapi-Token: '.$cookies.'';
        $headers[] = "Accept: application/json";

        curl_setopt($ch, CURLOPT_URL, "http://172.31.1.52:9130/holdings-storage/holdings?query=$query");
        //curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);
        $resultArray = json_decode($server_output, true);
        return $resultArray;
        curl_close($ch);

    }

    static function addRecordREST($cookies,$json) {
        $ch = curl_init();

        $headers = array();
        $headers[] = "X-Okapi-Tenant: diku";
        $headers[] = 'X-Okapi-Token: '.$cookies.'';
        $headers[] = "Content-type: application/json";
        $headers[] = "Accept: application/json";


        curl_setopt($ch, CURLOPT_URL, 'http://172.31.1.52:9130/inventory/instances');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);
        print_r($server_output);
        curl_close($ch);

    }

    static function addHoldingsREST($cookies,$json) {
        $ch = curl_init();

        $headers = array();
        $headers[] = "X-Okapi-Tenant: diku";
        $headers[] = 'X-Okapi-Token: '.$cookies.'';
        $headers[] = "Content-type: application/json";
        $headers[] = "Accept: application/json";


        curl_setopt($ch, CURLOPT_URL, 'http://172.31.1.52:9130/holdings-storage/holdings');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);
        print_r($server_output);
        curl_close($ch);

    }
    
    static function addItemREST($cookies,$json) {
        $ch = curl_init();

        $headers = array();
        $headers[] = "X-Okapi-Tenant: diku";
        $headers[] = 'X-Okapi-Token: '.$cookies.'';
        $headers[] = "Content-type: application/json";
        $headers[] = "Accept: application/json";


        curl_setopt($ch, CURLOPT_URL, 'http://172.31.1.52:9130/item-storage/items');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);
        print_r($server_output);
        curl_close($ch);

    }    

    static function deleteRecordsREST($cookies,$id) {
        $ch = curl_init();

        $headers = array();
        $headers[] = "X-Okapi-Tenant: diku";
        $headers[] = 'X-Okapi-Token: '.$cookies.'';
        $headers[] = "Content-type: application/json";
        $headers[] = "Accept: application/json";


        curl_setopt($ch, CURLOPT_URL, 'http://172.31.1.52:9130/inventory/instances/'.$id.'');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);
        print_r($server_output);
        curl_close($ch);

    }

    static function deleteAllRecordsREST($cookies) {
        $ch = curl_init();

        $headers = array();
        $headers[] = "X-Okapi-Tenant: diku";
        $headers[] = 'X-Okapi-Token: '.$cookies.'';
        $headers[] = "Content-type: application/json";
        $headers[] = "Accept: application/json";


        curl_setopt($ch, CURLOPT_URL, 'http://172.31.1.52:9130/inventory/instances');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);
        print_r($server_output);
        curl_close($ch);

    }

    static function deleteItensREST($cookies) {
        $ch = curl_init();

        $headers = array();
        $headers[] = "X-Okapi-Tenant: diku";
        $headers[] = 'X-Okapi-Token: '.$cookies.'';
        $headers[] = "Content-type: application/json";
        $headers[] = "Accept: text/plain";


        curl_setopt($ch, CURLOPT_URL, 'http://172.31.1.52:9130/holdings-storage/holdings');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);
        print_r($server_output);
        curl_close($ch);

    }

    static function logoutREST($folioCookies)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Cookie: $folioCookies"));
        curl_setopt($ch, CURLOPT_URL, "$folioRest/rest/logout");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        curl_close($ch);
    }
}

?>
