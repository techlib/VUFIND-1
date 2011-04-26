<?php
/**
 *
 * Copyright (C) Moravian Library, Brno, Czech Republic
 * Copyright (C) UB/FU Berlin
 *
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
require_once 'Interface.php';

class Aleph implements DriverInterface
{
    private $db;
    private $dbName;

    function __construct() {
        // Load Configuration for this Module
        $configArray = parse_ini_file('conf/Aleph.ini', true);

        $this->host = $configArray['Catalog']['host'];
        $this->dlfport = $configArray['Catalog']['dlfport'];
        $this->dlfurl = $this->host . ":" . $this->dlfport;
        $this->bib = $configArray['Catalog']['bib'];
        $this->useradm = $configArray['Catalog']['useradm'];
        $this->admlib = $configArray['Catalog']['admlib'];
        $this->loanlib = $configArray['Catalog']['loanlib'];
        $this->wwwuser = $configArray['Catalog']['wwwuser'];
        $this->wwwpasswd = $configArray['Catalog']['wwwpasswd'];
        $this->sublibadm = $configArray['sublibadm'];
    }

    public function doXRequest($op, $params, $auth) {
        $url = "http://$this->host/X?op=$op";
        foreach ($params as $key => $value) {
           $url.="&$key=$value";
        }
        if ($auth) {
           $url.="&user_name=$this->wwwuser&user_password=$this->wwwpasswd";
        }
        $answer = file($url);
        $xmlfile = '';
        foreach ($answer as $line) {
           $xmlfile = $xmlfile . $line;
        }
        $xmlfile = str_replace('xmlns=', 'ns=', $xmlfile);
        $result = simplexml_load_string($xmlfile);
        if (!$result) { 
           throw new Exception("XML is not valid, URL is '$url'.");
        }
        $xml['xmlns'] = '';
        if ($result->error) {
           throw new Exception("XServer error: $result->error.");
        }
        return $result;
    }

    public function doRequest($request) {
        $answer = file($request);
        $xmlfile = '';
        foreach($answer as $line) {
           $xmlfile = $xmlfile . $line;
        }
        $xmlfile = str_replace('xmlns=', 'ns=', $xmlfile);
        $result = simplexml_load_string($xmlfile) or print "error creating xml";
        return $result;
    }

   /*
    * Fast check of status of an item
    */
   public function getStatus($id) {
        $xml = $this->doXRequest(
          "publish_avail",
          array(
            'library' => $this->bib,
            'doc_num' => $id
          ),
          false
        );
        $records = $xml->xpath('/publish-avail/OAI-PMH/ListRecords/record/metadata/record') or print "xpath eval failed";
        $holding = array();
        foreach ($records as $record) {
           foreach ($record->xpath("//datafield[@tag='AVA']") as $datafield) {
               $status = $datafield->xpath('subfield[@code="e"]/text()');
               $location = $datafield->xpath('subfield[@code="a"]/text()');
               $signature = $datafield->xpath('subfield[@code="d"]/text()');
               $availability = ($status[0] == 'available' || $status[0] == 'check_holdings');
               $reserve = true;
               $callnumber = $signature;
               $duedate = '';
               $collection = '';
               $number = '';
               $barcode = '';
               $holding[] = array('id' => $id,
                               'availability' => $availability,
                               'status' => (string) $status[0],
                               'location' => (string) $location[0],
                               'signature' => (string) $signature[0],
                               'reserve' => $reserve,
                               'callnumber' => (string) $callnumber[0],
                               'duedate' => (string) $duedate,
                               'number' => (string) $number,
                               'collection' => (string) $collection,
                               'barcode' => (string) $barcode);
           }
        }
        return $holding;
    }

    public function getStatuses($idList) {
        foreach ($idList as $id) {
            $holdings[] = $this->getStatus($id);
        }
        return $holdings;
    }
    public function getHolding($id)
    {
        return $this->getStatus($id);
    }
    /*
    public function getHolding($id) {
        $holding = array();
        list($bib, $sys_no) = split("-", $id, 2);
        $resource = $bib . $sys_no;
        $url = "http://$this->dlfurl/rest-dlf/record/" . $resource . "/items?view=full";
        $xml = $this->doRequest($url);
        foreach ($xml->xpath('//items/item') as $item) {
           $item_status = $item->xpath('z30-item-status-code/text()'); // $isc
           $item_process_status = $item->xpath('z30-item-process-status-code/text()'); // $ipsc
           $sub_library = $item->xpath('z30-sub-library-code/text()'); // $slc
           $item_status = tab15_translate((string) $sub_library[0], (string) $item_status[0], (string) $item_process_status[0]);
           if ($item_status['opac'] != 'Y') {
              continue;
           }
           $group = $item->xpath('@href');
           $group = substr(strrchr($group[0], "/"), 1);
           $status = $item->xpath('status/text()');
           $availability = false;
           $location = $item->xpath('z30/z30-sub-library-code/text()');
           $reserve = ($item_status['request'] == 'C');
           $callnumber = $item->xpath('z30/z30-call-no/text()');
           $barcode = $item->xpath('z30/z30-barcode/text()');
           $number = $item->xpath('z30/z30-inventory-number/text()');
           $collection = $item->xpath('z30/z30-collection/text()');
           $collection_code = $item->xpath('z30-collection-code/text()');
           $collection_desc = tab40_translate((string) $collection_code[0], (string) $location[0]);
           $sig1 = $item->xpath('z30/z30-call-no/text()');
           $sig2 = $item->xpath('z30/z30-call-no-2/text()');
           $desc = $item->xpath('z30/z30-description/text()');
           $note = $item->xpath('z30/z30-note-opac/text()'); 
           $no_of_loans = $item->xpath('z30/z30-no-loans/text()');
           $requested = false;
           $duedate = '';
           $status = $status[0];
           // FIXME: you may need to costumize it
           if ($status == "On Shelf" || $status == "Open St.-Month" || $status == "Vol.výb.-měs.") {
               $availability = true;
           }
           if ($item_status['request'] == 'Y' && $availability == false) {
              $reserve = true;
           }
           $matches = array();
           if (preg_match("/([0-9]*\\/[a-zA-Z]*\\/[0-9]*);([a-zA-Z ]*)/", $status, &$matches)) {
               $duedate = $this->parseDate($matches[1]);
               $requested = (trim($matches[2]) == "Requested");
           } else if (preg_match("/([0-9]*\\/[a-zA-Z]*\\/[0-9]*)/", $status, &$matches)) {
               $duedate = $this->parseDate($matches[1]);
           }
           $temp = mb_substr((string) $item_status['desc_cz'], 0, 6, "UTF-8");
           if ($availability) {
              if (strcmp($temp, "Jen do")==0 || strcmp($temp, "Studov")==0) {
                 $duedate = "only for present studium";
              } else if (strcmp($temp, "Příruč")==0) {
                 $duedate = "reference library";
              } else if (strcmp($temp, "Ve zpr")==0) {
                 $duedate = "in processing";
              } else {
                 $duedate = "absent loan";
              }
           } else {
              if ($status == "On Hold" || $status == "Requested") {
                 $duedate = "requested";
              }
           }
           // 
           $holding[] = array('id' => $id,
                               'availability' => $availability,
                               'status' => (string) $item_status['desc_cz'],
                               'location' => (string) $location[0],
                               'reserve' => $reserve,
                               'callnumber' => (string) $callnumber[0],
                               'duedate' => (string) $duedate,
                               'requested' => (string) $requested,
                               'number' => (string) $number[0],
                               'collection' => (string) $collection[0],
                               'collection_desc' => (string) $collection_desc['desc_cz'],
                               'barcode' => (string) $barcode[0],
                               'description' => (string) $desc[0],
                               'note' => (string) $note[0],
                               'sig1' => (string) $sig1[0],
                               'sig2' => (string) $sig2[0],
                               'sub_lib_desc' => (string) $item_status['sub_lib_desc'],
                               'no_of_loans' => (integer) $no_of_loans[0],
                               'group' => (string) $group);
        }
        return $holding;
    }
    */
    public function getHoldingInfoForItem($patronId, $id, $group) {
        list($bib, $sys_no) = split("-", $id, 2);
        $resource = $bib . $sys_no;
        $url = "http://$this->dlfurl/rest-dlf/patron/$patronId/record/$resource/items/$group";
        $xml = $this->doRequest($url);
        $locations = array();
        $part = $xml->xpath('//pickup-locations');
        foreach ($part[0]->children() as $node) {
           $arr = $node->attributes();
           $code = (string) $arr['code'];
           $loc_name = (string) $node;
           $locations[$code] = $loc_name;
        }
        $str = $xml->xpath('//item/queue/text()');
        list($requests, $other) = split(' ', trim($str[0]));
        if ($requests == null) {
           $requests = 0;
        }
        $date = $xml->xpath('//last-interest-date/text()');
        $date = $date[0];
        $date = "" . substr($date, 6, 2) . "." . substr($date, 4, 2) . "." . substr($date, 0, 4); 
        return array('pickup-locations' => $locations, 'last-interest-date' => $date, 'order' => $requests + 1);
    }

    public function getHoldings($id)
    {
        return $this->getStatus($id);
    }

    public function getPurchaseHistory($id)
    {
        return array();
    }
    
    public function patronLogin($barcode, $lname) {
        try {
            $xml = $this->doXRequest('bor-auth', array('library' => $this->useradm, 'bor_id' => $barcode, 'verification' => $lname), true);
        } catch (Exception $ex) {
            $patron = new PEAR_Error($ex->getMessage());
            return $patron;
        }
        $patron=array();
        $firstName = "";
        $lastName = "";
        $name = $xml->z303->{'z303-name'};
        list($lastName,$firstName) = split(",", $name); 
        $email_addr = $xml->z304->{'z304-email-address'};
        $id = $xml->z303->{'z303-id'};
        $home_lib = $xml->z303->z303_home_library;
        // Default the college to the useradm library and overwrite it if the
        // home_lib exists
        $patron['college'] = $this->useradm;
        if (($home_lib != '') && (array_key_exists("$home_lib",$this->sublibadm))) {
           if ($this->sublibadm["$home_lib"] != '') {
               $patron['college'] = $this->sublibadm["$home_lib"];
           }
        }
        $patron['id'] = (string) $id;
        $patron['barcode'] = (string) $barcode;
        $patron['firstname'] = (string) $firstName;
        $patron['lastname'] = (string) $lastName;
        $patron['cat_username'] = (string) $barcode;
        $patron['cat_password'] = (string) $lname;
        $patron['email'] = (string) $email_addr;
        $patron['major'] = NULL;
        return $patron;
    }

    public function getMyTransactions($user) {
        $userId = $user['id'];
        $transList = array();
        $url = "http://$this->dlfurl/rest-dlf/patron/$userId/circulationActions/loans/?view=full";
        $transList = array();
        $xml = $this->doRequest($url);
        foreach ($xml->xpath('//loan') as $item) {
           $z36 = $item->z36;
           $z13 = $item->z13;
           $z30 = $item->z30;
           $renew = $item->xpath('@renew');
           $docno = (string) $z36->{'z36-doc-number'};
           $itemseq = (string) $z36->{'z36-item-sequence'};
           $seq = (string) $z36->{'z36-sequence'};
           $location = (string) $z36->{'z36_pickup_location'};
           $reqnum = (string) $z36->{'z36-doc-number'} .
              (string) $z36->{'z36-item-sequence'} . (string) $z36->{'z36-sequence'};
           $due = (string) $z36->{'z36-due-date'};
           $loaned = (string) $z36->{'z36-loan-date'};
           $title = (string) $z13->{'z13-title'};
           $author = (string) $z13->{'z13-author'};
           $isbn = (string) $z13->{'z13-isbn-issn'};
           $barcode = (string) $z30->{'z30-barcode'};
           $transList[] = array('type' => $type,
                               'holdid' => $user['college'] . $docno . $itemseq . $seq,
                               'location' => $location,
                               'title' => $title,
                               'author' => $author,
                               'isbn' => array($isbn),
                               'reqnum' => $reqnum,
                               'barcode' => $barcode,
                               'duedate' => $this->parseDate($due),
                               'holddate' => $holddate,
                               'delete' => $delete,
                               'create' => $this->parseDate($create));
        }
        return $transList;
    }

    public function getMyHolds($user) {
        // var_dump($user);
        $userId = $user['id'];
        $holdList = array();
        $url = "http://$this->dlfurl/rest-dlf/patron/$userId/circulationActions/requests/holds?view=full";
        $xml = $this->doRequest($url);
        // foreach ($xml->xpath('//z37') as $item) {
        foreach ($xml->xpath('//hold-request') as $item) {
           $z37 = $item->z37;
           $z13 = $item->z13;
           $z30 = $item->z30;
           $delete = $item->xpath('@delete');
           if ((string) $z37->{'z37-request-type'} == "Hold Request" || true) {
                $type = "hold";
                $docno = (string) $z37->{'z37-doc-number'};
                $itemseq = (string) $z37->{'z37-item-sequence'};
                $seq = (string) $z37->{'z37-sequence'};
                $location = (string) $z37->{'z37_pickup_location'};
                $reqnum = (string) $z37->{'z37-doc-number'} .
                    (string) $z37->{'z37-item-sequence'} . (string) $z37->{'z37-sequence'};
                $expire = (string) $z37->{'z37-end-request-date'};
                $create = (string) $z37->{'z37-open-date'};
                $holddate = (string) $z37->{'z37-hold-date'};
                $title = (string) $z13->{'z13-title'};
                $author = (string) $z13->{'z13-author'};
                $isbn = (string) $z13->{'z13-isbn-issn'};
                $barcode = (string) $z30->{'z30-barcode'};
                if ($holddate == "00000000") {
                    $holddate = null;
                } else {
                    $holddate = $this->parseDate($holddate);
                }
                $delete = ($delete[0] == "Y");
                $holdList[] = array('type' => $type,
                                    'holdid' => $user['college'] . $docno . $itemseq . $seq,
                                    'location' => $location,
                                    'title' => $title,
                                    'author' => $author,
                                    'isbn' => array($isbn),
                                    'reqnum' => $reqnum,
                                    'barcode' => $barcode,
                                    'expire' => $this->parseDate($expire),
                                    'holddate' => $holddate,
                                    'delete' => $delete,
                                    'create' => $this->parseDate($create));
           }
        }
        return $holdList;
    }

    public function getMyFines($user) {
        $xml = $this->doXRequest("bor-info", array('loans' => 'N', 'hold' => 'N', 'cash' => 'Y', 'library' => $user['college'],
            'bor_id' => $user['id'], 'verification' => ''), true);
        $max = substr_count($xmlfile, "<fine>");

        for($i=0;$i < $max ; $i++){
            if (preg_match("/not paid/i",(string)$xml->fine[$i]->z31->z31_status)) {
                $description = preg_replace("/paid.*/i" ,"", (string) $xml->fine[$i]->z31->z31_description);
                $balance = (int)((float) preg_replace("/[\(\)]/", "", (string) $xml->fine[$i]->z31->z31_sum) * 100);
                if (preg_match_all("/(\d+\.\d{2})/", (string) $xml->fine[$i]->z31->z31_description, $matches)) {
                    $fine = (int)((float)$matches[0][1]*100);
                } else {
                    $fine = $balance;
                }
                $id = (string) $xml->fine[$i]->z30->z30_doc_number;
                // Note Aleph's X-Server doesn't tell us when the book was checked out or due back, just when the fine was issued.
                $finesList[] = array(
                    "amount"   => $fine,
                    "checkout" => "",
                    "fine"     => $description,
                    "balance"  => $balance,
                    "duedate"  => "",
                    "id"       => sprintf("%09d",$id) );
            }
        }
        return $finesList;
    }

    public function cancelHold($patronId, $id) {
        $url = "http://$this->dlfurl/rest-dlf/patron/$patronId/circulationActions/requests/holds/$id";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        $result = simplexml_load_string($output);
        $reply_code = $result->{'reply-code'};
        if ($reply_code != "0000") {
           $message = $result->{'del-pat-hold'}->{'note'};
           if ($message == null) {
              $message = $result->{'reply-text'};
           }
           return new PEAR_Error($message);
        } else {
           return true;
        }
    }

    public function placeHold($patronId, $recordId, $itemId , $pickup_location, $last_interest_date, $comment) {
        list($bib, $sys_no) = split("-", $recordId, 2);
        $recordId = $bib . $sys_no;
        $patron = array();
        $patron['cat_username'] = $patronId;
        $patron['college'] = $this->admlib;
        $patron = $this->getMyProfile($patron);
        $patronId = $patron['id'];
        $url = "http://$this->dlfurl/rest-dlf/patron/$patronId/record/$recordId/items/$itemId/hold";
        // print "$url<br>";
        $ch = curl_init($url);
        // print "$url<br>";
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        $data = "<?xml version='1.0' encoding='UTF-8'?>\n" .
           "<hold-request-parameters>\n" .
           "   <pickup-location>$pickup_location</pickup-location>\n" .
           "   <last-interest-date>$last_interest_date</last-interest-date>\n" .
           "   <note-1>$comment</note-1>\n".
           "</hold-request-parameters>\n";
        curl_setopt($ch, CURLOPT_POSTFIELDS, "post_xml=$data");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        // print "$output<br>";
        $result = simplexml_load_string($output);
        $reply_code = $result->{'reply-code'};
        // print "$reply_code<br>";
        if ($reply_code != "0000") {
           $message = $result->{'create-hold'}->{'note'};
           if ($message == null) {
              $message = $result->{'reply-text'};
           }
           return new PEAR_Error($message);
        } else {
           return true;
        }
        curl_close($ch);
    }

    public function barcodeToID($bar) {
        // foreach (array('MZK01', 'MZK03') as $base) {
        foreach ($this->bases as $base) {
           try {
              $xml = $this->doXRequest("find", array("base" => $base, "request" => "BAR=$bar"), false);
              $docs = (int) $xml->{"no_records"};
              if ($docs == 1) {
                 $set = (string) $xml->{"set_number"};
                 $result = $this->doXRequest("present", array("set_number" => $set, "set_entry" => "1"), false);
                 $id = $result->xpath('//doc_number/text()');
                 return $base . "-" . $id[0];
              }
           } catch (Exception $ex) {
           }
        }
        return new PEAR_Error('barcode not found');
    }

    public function getNewItems($page, $limit, $startdate, $enddate, $fundId = null) {
        $items = array();
        return $items;
    }
    
    function getDepartments() {
        $deptList = array();
        return $deptList;
    }
    
    function getInstructors() {
        $deptList = array();
        return $deptList;
    }
    
    function getCourses() {
        $deptList = array();
        return $deptList;
    }

    function findReserves($course, $inst, $dept) {
        $recordList = array();
        return $recordList;
    }

    function parseDate($date) {
       if (preg_match("/^[0-9]{8}$/", $date) === 1) {
           return substr($date, 6, 2) . "." .substr($date, 4, 2) . "." . substr($date, 0, 4);
        } else {
           list($day, $month, $year) = split("/", $date, 3);
           $translate_month = array ( 'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6,
              'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12);
           return $day . "." . $translate_month[strtolower($month)] . "." . $year;
        }
    }


    function getMyProfile($user) {
        $recordList=array();
        $xml = $this->doXRequest("bor-info", array('loans' => 'N', 'cash' => 'N', 'hold' => 'N', 'library' => $user['college'],
            'bor_id' => $user['cat_username']), true);
        $id = (string) $xml->z303->{'z303-id'};
        $address1 = (string) $xml->z304->{'z304-address-2'};
        $address2 = (string) $xml->z304->{'z304-address-3'};
        $zip = (string) $xml->z304->{'z304-zip'};
        $phone = (string) $xml->z304->{'z304-telephone'};
        $barcode = (string) $xml->z304->{'z304-address-0'};
        $group = (string) $xml->z305->{'z305-bor-status'};
        $expiry = (string) $xml->z305->{'z305-expiry-date'};
        $credit_sum = (string) $xml->z305->{'z305-sum'};
        $credit_sign = (string) $xml->z305->{'z305-credit-debit'};
        if ($credit_sing == null) {
           $credit_sign = "C";
        }
        $recordList['firstname'] = $user['firstname'];
        $recordList['lastname'] = $user['lastname'];
        $recordList['email'] = $user['email'];
        $recordList['address1'] = $address1;
        $recordList['address2'] = $address2;
        $recordList['zip'] = $zip;
        $recordList['phone'] = $phone;
        $recordList['group'] = $group;
        $recordList['barcode'] = $barcode;
        $recordList['expiry'] = $expiry;
        $recordList['credit'] = $expiry;
        $recordList['credit_sum'] = $credit_sum;
        $recordList['credit_sign'] = $credit_sign;
        $recordList['id'] = $id;
        return $recordList;
    }
}

?>
