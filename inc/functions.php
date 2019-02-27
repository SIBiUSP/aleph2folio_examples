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
    $repetitive_fields = array("100","510","536","650","651","655","700","856","946","952","CAT");

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


function AlephseqToMods($line)
{

    global $marc;
    global $i;
    global $id;

    $id = substr($line, 0, 9);
    $field = substr($line, 10, 3);
    $ind_1 = substr($line, 13, 1);
    $ind_2 = substr($line, 14, 1);


    $control_fields = array("LDR","DEL","FMT","001","008");
    $repetitive_fields = array("100","510","536","650","651","655","700","856","946","952","CAT");

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

    $marc["record"][$field]["ind_1"] = $ind_1;
    $marc["record"][$field]["ind_2"] = $ind_2;

    $i++;

}

/*
* Processa o fixes *
*/
function fixes($marc)
{

    global $i;

    //print_r($marc);
    $body = [];

    

    if (isset($marc["record"]["001"])) {

        $body["id"] = gen_uuid();
        $body["hrid"] = $marc["record"]["001"]["content"];
    }

    $body["source"] = "DEDALUS";

    $body["instanceTypeId"] = "6312d172-f0cf-40f6-b27d-9fa8feaf332f";

    // if (isset($marc["record"]["008"])) {
    //     $body["languages"][] = substr($marc["record"]["008"]["content"], 35, 3);
    // }    

    if (isset($marc["record"]["020"]["a"])) {
        $body["identifiers"][0]["value"] = $marc["record"]["020"]["a"][0];
         $body["identifiers"][0]["identifierTypeId"] = "8261054f-be78-422d-bd51-4ed9f33c3422";
    }

    // if (isset($marc["record"]["024"]["a"])) {
    //     $body["identifiers"]["value"] = $marc["record"]["024"]["a"][0];
    //     $body["identifiers"]["identifierTypeId"] = "DOI";
    // }

    //if (isset($marc["record"]["044"])) {
    //    $country_correct = decode::country($marc["record"]["044"]["a"][0]);
    //    $body["doc"]["country"][] = $country_correct;
    //}

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

    // if (isset($marc["record"]["260"])) {
    //     if (isset($marc["record"]["260"]["b"])) {
    //         $body["publication"]["publisher"] = $marc["record"]["260"]["b"][0];
    //     }
    //     if (isset($marc["record"]["260"]["a"])) {
    //         $body["publication"]["place"] = $marc["record"]["260"]["a"][0];
    //     }
    //     if (isset($marc["record"]["260"]["c"])) {
    //         $body["publication"]["dateOfPublication"] = $marc["record"]["260"]["c"][0];
    //     }        
    // }

    // if (isset($marc["record"]["650"])) {
    //     foreach (($marc["record"]["650"]) as $subject) {
    //         if (isset($subject["a"])) {
    //             $body["subjects"]["name"] = $subject["a"];
    //         }
    //     }
    // }

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



/*
* Decodifica dados *
*/
class decode
{

    /* Pegar o tipo de material */
    static function get_type($material_type)
    {
        switch ($material_type) {
        case "ARTIGO DE JORNAL":
            return "article-newspaper";
            break;
        case "ARTIGO DE PERIODICO":
            return "article-journal";
            break;
        case "PARTE DE MONOGRAFIA/LIVRO":
            return "chapter";
            break;
        case "APRESENTACAO SONORA/CENICA/ENTREVISTA":
            return "interview";
            break;
        case "TRABALHO DE EVENTO-RESUMO":
            return "paper-conference";
            break;
        case "TRABALHO DE EVENTO":
            return "paper-conference";
            break;
        case "TESE":
            return "thesis";
            break;
        case "TEXTO NA WEB":
            return "post-weblog";
        break;
        }
    }

    /* Decodificar idioma */
    static function language($language)
    {
        switch ($language) {
        case "por":
            return "Português";
            break;
        case "eng":
            return "Inglês";
            break;
        case "spa":
            return "Espanhol";
            break;
        case "fre":
            return "Francês";
            break;
        case "mul":
            return "Multiplos idiomas";
            break;
        case "ger":
            return "Alemão";
            break;
        case "ita":
            return "Italiano";
            break;
        case "jpn":
            return "Japonês";
            break;
        case "rus":
            return "Russo";
            break;
        case "chi":
            return "Chinês";
            break;
        case "pol":
            return "Polonês";
            break;
        case "dut":
            return "Holandês";
            break;
        case "tur":
            return "Turco";
            break;
        case "hun":
            return "Húngaro";
            break;
        case "dan":
            return "Dinamarquês";
            break;
        case "cze":
            return "Checo";
            break;
        case "scc":
            return "Sérvio";
            break;
        case "swe":
            return "Sueco";
            break;
        case "ara":
            return "Árabe";
            break;
        case "cat":
            return "Catalão";
            break;
        case "kor":
            return "Coreano";
            break;
        case "heb":
            return "Hebreu";
            break;
        case "lat":
            return "Latin";
            break;
        case "grc":
            return "Grego";
            break;
        case "slo":
            return "Eslovaco";
            break;
        default:
            return $language;
        }
    }

    /* Decodificar pais */
    static function country($country)
    {
        switch ($country) {
        case "ag":
            return "Argentina";
            break;
        case "aru":
            return "Estados Unidos";
            break;
        case "alu":
            return "Estados Unidos";
            break;
        case "at":
            return "Austrália";
            break;
        case "au":
            return "Áustria";
            break;
        case "be":
            return "Bélgica";
            break;
        case "bl":
            return "Brasil";
            break;
        case "bo":
            return "Bolívia";
            break;
        case "bu":
            return "Bulgária";
            break;
        case "cau":
            return "Estados Unidos";
            break;
        case "cb":
            return "Camboja";
            break;
        case "cc":
            return "China";
            break;
        case "ch":
            return "China";
            break;
        case "ci":
            return "Croácia";
            break;
        case "ck":
            return "Colômbia";
            break;
        case "cl":
            return "Chile";
            break;
        case "cou":
            return "Estados Unidos";
            break;
        case "cr":
            return "Costa Rica";
            break;
        case "cu":
            return "Cuba";
            break;
        case "dcu":
            return "Estados Unidos";
            break;
        case "dk":
            return "Dinamarca";
            break;
        case "dr":
            return "República Dominicana";
            break;
        case "ec":
            return "Equador";
            break;
        case "enk":
            return "Inglaterra";
            break;
        case "es":
            return "El Salvador";
            break;
        case "et":
            return "Etiópia";
            break;
        case "fi":
            return "Finlândia";
            break;
        case "flu":
            return "Estados Unidos";
            break;
        case "fr":
            return "França";
            break;
        case "gb":
            return "República de Kiribati";
            break;
        case "gr":
            return "Grécia";
            break;
        case "gw":
            return "Alemanha";
            break;
        case "gt":
            return "Guatemala";
            break;
        case "hiu":
            return "Estados Unidos";
            break;
        case "hk":
            return "Hong-Kong";
            break;
        case "ho":
            return "Honduras";
            break;
        case "hu":
            return "Hungria";
            break;
        case "iau":
            return "Estados Unidos";
            break;
        case "ic":
            return "Islândia";
            break;
        case "ie":
            return "Irlanda";
            break;
        case "ii":
            return "Índia";
            break;
        case "ilu":
            return "Estados Unidos";
            break;
        case "inu":
            return "Estados Unidos";
            break;
        case "io":
            return "Indonésia";
            break;
        case "ir":
            return "Irã";
            break;
        case "is":
            return "Israel";
            break;
        case "it":
            return "Itália";
            break;
        case "ja":
            return "Japão";
            break;
        case "ke":
            return "Quênia";
            break;
        case "ko":
            return "Coreia do Sul";
            break;
        case "li":
            return "Lituânia";
            break;
        case "mau":
            return "Estados Unidos";
            break;
        case "mdu":
            return "Estados Unidos";
            break;
        case "miu":
            return "Estados Unidos";
            break;
        case "mou":
            return "Estados Unidos";
            break;
        case "mr":
            return "Marrocos";
            break;
        case "mx":
            return "México";
            break;
        case "my":
            return "Malásia";
            break;
        case "mz":
            return "Moçambique";
            break;
        case "ne":
            return "Holanda";
            break;
        case "ng":
            return "Nigéria";
            break;
        case "nl":
            return "Nova Caledonia";
            break;
        case "nmu":
            return "Estados Unidos";
            break;
        case "no":
            return "Noruega";
            break;
        case "nr":
            return "Nigéria";
            break;
        case "nju":
            return "Estados Unidos";
            break;
        case "nyu":
            return "Estados Unidos";
            break;
        case "nvu":
            return "Estados Unidos";
            break;
        case "nz":
            return "Nova Zelândia";
            break;
        case "ohu":
            return "Estados Unidos";
            break;
        case "pau":
            return "Estados Unidos";
            break;
        case "pe":
            return "Peru";
            break;
        case "ph":
            return "Filipinas";
            break;
        case "pk":
            return "Paquistão";
            break;
        case "pl":
            return "Polônia";
            break;
        case "pn":
            return "Panamá";
            break;
        case "pr":
            return "Porto Rico";
            break;
        case "po":
            return "Portugal";
            break;
        case "py":
            return "Paraguai";
            break;
        case "riu":
            return "Estados Unidos";
            break;
        case "rm":
            return "Romênia";
            break;
        case "ru":
            return "Rússia";
            break;
        case "sa":
            return "África do Sul";
            break;
        case "si":
            return "Singapura";
            break;
        case "sp":
            return "Espanha";
            break;
        case "stk":
            return "Escócia";
            break;
        case "su":
            return "Arábia Saudita";
            break;
        case "sw":
            return "Suécia";
            break;
        case "sz":
            return "Suiça";
            break;
        case "ti":
            return "Tunísia";
            break;
        case "th":
            return "Tailândia";
            break;
        case "ts":
            return "Emirados Árabes Unidos";
            break;
        case "tu":
            return "Turquia";
            break;
        case "txu":
            return "Estados Unidos";
            break;
        case "xo":
            return "Eslováquia";
            break;
        case "xr":
            return "República Checa";
            break;
        case "xx":
            return "Desconhecido";
            break;
        case "xxk":
            return "Reino Unido";
            break;
        case "xxu":
            return "Estados Unidos";
            break;
        case "xxc":
            return "Canadá";
            break;
        case "xv":
            return "Eslovênia";
            break;
        case "ua":
            return "Egito";
            break;
        case "utu":
            return "Estados Unidos";
            break;
        case "un":
            return "Ucrânia";
            break;
        case "uy":
            return "Uruguai";
            break;
        case "uk":
            return "Reino Unido";
            break;
        case "yu":
            return "Iugoslávia";
            break;
        case "vau":
            return "Estados Unidos";
            break;
        case "ve":
            return "Venezuela";
            break;
        case "xr":
            return "República Tcheca";
            break;
        case "wau":
            return "Estados Unidos";
            break;
        case "wiu":
            return "Estados Unidos";
            break;
        default:
            return $country;
        }
    }

    /* Decodificar função */
    static function potentialAction($potentialAction) 
    {
        switch ($potentialAction) {
        case "adapt":
            return "Adaptação";
            break;
        case "arranjo mus":
            return "Arranjo musical / Arranjador musical";
            break;
        case "comp":
            return "Compilador";
            break;
        case "compos":
            return "Compositor musical";
            break;
        case "coord pesq musico":
            return "Coordenador de pesquisa musicológica";
            break;
        case "co-orient":
            return "Co-orientador";
            break;
        case "ed":
            return "Editor";
            break;
        case "elab":
            return "Elaborador";
            break;
        case "entrev":
            return "Entrevistador";
            break;
        case "org":
            return "Organizador";
            break;
        case "pref":
            return "Prefácio";
            break;
        case "rev":
            return "Revisor";
            break;
        case "text":
            return "Autor texto";
            break;
        case "trad":
            return "Tradução";
            break;
        case "transc":
            return "Transcrição";
            break;
        case "orient":
            return "Orientador";
            break;
        default:
            return $potentialAction;
        }
    }

    /* Vincular Unidades antigas */
    static function unidadeAntiga($unidade) 
    {
        switch ($unidade) {
        case "IFQSC-Q":
            return "IQSC";
            break;
        case "IFQSC-F":
            return "IFSC";
            break;
        case "ICMSC":
            return "ICMC";
            break;
        case "CBM":
            return "CEBIMAR";
            break;
        case "HPRLLP":
            return "HRAC";
            break;
        default:
            return $unidade;
        }
    }

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