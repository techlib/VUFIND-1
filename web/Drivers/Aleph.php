<?php
/**
 * Aleph ILS driver
 *
 * PHP version 5
 *
 * Copyright (C) UB/FU Berlin
 *
 * last update: 7.11.2007
 * tested with X-Server Aleph 18.1.
 *
 * TODO: login, course information, getNewItems, duedate in holdings, https connection to x-server, ...
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
 * @category VuFind
 * @package  ILS_Drivers
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_an_ils_driver Wiki
 */
require_once 'Interface.php';
require_once 'AlephTables.php';
require_once 'sys/Proxy_Request.php';

/**
 * Aleph ILS driver
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_an_ils_driver Wiki
 */
class Aleph implements DriverInterface
{

    /**
     * Constructor
     *
     * @access public
     */
    function __construct()
    {
        // Load Configuration for this Module
        $configArray = parse_ini_file('conf/Aleph.ini', true);
        $this->host = $configArray['Catalog']['host'];
        $this->bib = split(',', $configArray['Catalog']['bib']);
        $this->useradm = $configArray['Catalog']['useradm'];
        $this->admlib = $configArray['Catalog']['admlib'];
#<MJ.>        $this->wwwuser = $configArray['Catalog']['wwwuser'];
#<MJ.>        $this->wwwpasswd = $configArray['Catalog']['wwwpasswd'];
        $this->dlfport = $configArray['Catalog']['dlfport'];
        $this->sublibadm = $configArray['sublibadm'];
        $this->available_statuses = split(',', $configArray['Catalog']['available_statuses']);
    }

    protected function doXRequest($op, $params, $auth=false)
    {
        $url = "http://$this->host/X?op=$op";
        $url = $this->appendQueryString($url, $params);
        if ($auth) {
# <MJ.>           $url = $this->appendQueryString($url, array('user_name' => $this->wwwuser, 'user_password' => $this->wwwpasswd));
        }
        $result = $this->doHTTPRequest($url);
        if ($result->error) {
           throw new Exception("XServer error: $result->error.");
        }
        return $result;
    }

    protected function doRestDLFRequest($path_elements, $params = null, $method='GET', $body = null) {
        $path = '';
        foreach ($path_elements as $path_element) {
           $path .= $path_element . "/";
        }
        $url = "http://$this->host:$this->dlfport/rest-dlf/" . $path;
        $url = $this->appendQueryString($url, $params);
       # <MJ.>
       error_log("MJ. doRestDLF log: ".$url);
       

        return $this->doHTTPRequest($url, $method, $body);
    }

    protected function appendQueryString($url, $params) {
        $sep = (strpos($url, "?") === false)?'?':'&';
        if ($params != null) {
           foreach ($params as $key => $value) {
              $url.= $sep . $key . "=" . $value;
              $sep = "&";
           }
        }
        return $url;
    }

    protected function doHTTPRequest($url, $method='GET', $body = null) {
        //$url = str_replace('items/','items',$url); #<MJ.>

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($body != null) {
           curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $answer = curl_exec($ch);
        curl_close($ch);
        $answer = str_replace('xmlns=', 'ns=', $answer);
        $result = simplexml_load_string($answer);
        if (!$result) { 
           throw new Exception("XML is not valid, URL is '$url'.");
        }
        return $result;
    }


    protected function parseId($id) 
    {
        if (count($this->bib)==1) {
            return array($this->bib[0], $id);
        } else {
            return split('-', $id);
        }
    }

    /**
     * Get Status
     *
     * This is responsible for retrieving the status information of a certain
     * record.
     *
     * @param string $id The record id to retrieve the holdings for
     *
     * @return mixed     On success, an associative array with the following keys:
     * id, availability (boolean), status, location, reserve, callnumber; on
     * failure, a PEAR_Error.
     * @access public
     */
    public function getStatus($id) 
    {
        list($bib, $sys_no) = $this->parseId($id);
        $xml = $this->doXRequest("publish_avail", array('library' => $bib, 'doc_num' => $sys_no), false);
        $records = $xml->xpath('/publish-avail/OAI-PMH/ListRecords/record/metadata/record') or print "xpath eval failed";
        $holding = array();
        foreach ($records as $record) {
           foreach ($record->xpath("//datafield[@tag='AVA']") as $datafield) {
               $status = $datafield->xpath('subfield[@code="e"]/text()');

               $location = $datafield->xpath('subfield[@code="j"]/text()');
               $location = $location[0];
	       if (preg_match("/(\d)([A-Z])(\d+)/", $location, $matches)) {
	           $location = translate("Shelf")." ".$location;
	       }
	       else {
		   $location = translate("code_".$location);
	       }
               /*
               TODO: Implementovat parsovani umisteni.
               $matches = array();
               preg_match("/(\d)([A-Z])(\d+)/", $location, $matches);
               if ($matches) {
                 $location = printf(
                  "[%d]. [%s], [%s] [%s], [%s] [%d]",
                  $matches[1],
                  translate("floor"),
                  translate("sector"),
                  $matches[2],
                  translate("shelf"),
                  $matches[3]
                );
               }
               */
               $signature = $datafield->xpath('subfield[@code="d"]/text()');
               $availability = ($status[0] == 'available' || $status[0] == 'check_holdings');
               $reserve = true;
               $callnumber = $signature;
               $holding[] = array('id' => $id,
                               'availability' => $availability,
                               'status' => (string) $status[0],
                               'location' => (string) $location,
                               'signature' => (string) $signature[0],
                               'reserve' => $reserve,
                               'callnumber' => (string) $signature[0]
                            );
           }
        }
        return $holding;
    }

    /**
     * Get Statuses
     *
     * This is responsible for retrieving the status information for a
     * collection of records.
     *
     * @param array $idList The array of record ids to retrieve the status for
     *
     * @return mixed        An array of getStatus() return values on success,
     * a PEAR_Error object otherwise.
     * @access public
     */
    public function getStatuses($idList)
    {
        foreach ($idList as $id) {
            $holdings[] = $this->getStatus($id);
        }
        return $holdings;
    }

    /**
     * Get Holding
     *
     * This is responsible for retrieving the holding information of a certain
     * record.
     *
     * @param string $id     The record id to retrieve the holdings for
     * @param array  $patron Patron data
     *
     * @return mixed     On success, an associative array with the following keys:
     * id, availability (boolean), status, location, reserve, callnumber, duedate,
     * number, barcode; on failure, a PEAR_Error.
     * @access public
     */
    public function getHolding($id, $patron = false)
    {
        $holding = array();
        list($bib, $sys_no) = $this->parseId($id);
        $resource = $bib . $sys_no;
        $xml = $this->doRestDLFRequest(array('record', $resource, 'items'), array('view' => 'full'));
// <MJ.> print  htmlentities($xml->asXML()); // <MJ.>
        foreach ($xml->xpath('//items/item') as $item) {
           $item_id = $item->xpath('@href');
           $item_id = substr($item_id[0], strrpos($item_id[0], '/') + 1);
// <MJ.> print "<br>item_id: $item_id<br>";
           $item_status = $item->xpath('z30-item-status-code/text()'); // $isc
           $item_process_status = $item->xpath('z30-item-process-status-code/text()'); // $ipsc
           $sub_library = $item->xpath('z30-sub-library-code/text()'); // $slc
// <MJ.>       print "tutem: $sub_library[0] <br>"; //<MJ.>
// <MJ.>           $item_status = tab15_translate((string) $sub_library[0], (string) $item_status[0], (string) $item_process_status[0]);
           $sub_library = $this->firstString($sub_library);
           $item_status = $this->firstString($item_status);
           $item_process_status = $this->firstString($item_process_status);
           $item_status = tab15_translate($sub_library, $item_status, $item_process_status);
// <MJ.> print "item status: " . http_build_query($item_status) . "<br>";
// <MJ.> print "item status opac: ". $item_status['opac'] . "<br>";
           if ($item_status['opac'] != 'Y') {
              continue;
           }
           $group = $item->xpath('@href');
           $group = substr(strrchr($group[0], "/"), 1);
           $status = $item->xpath('status/text()');
           $status = $this->firstString($status);
           $availability = false;

           $location = $item->xpath('z30/z30-sub-library-code/text()');
// <MJ.> nemame v z30, ale o uroven vys, v tab kolekce nejsou
// <MJ.>	   $location = array("");

           $reserve = ($item_status['request'] == 'C');
           $callnumber = $item->xpath('z30/z30-call-no/text()');
           $barcode = $item->xpath('z30/z30-barcode/text()');
           $number = $item->xpath('z30/z30-inventory-number/text()');
           $collection = $item->xpath('z30/z30-collection/text()');
           $collection_code = $item->xpath('z30-collection-code/text()');
// <MJ.> print "collection code: ". implode("::",$collection_code) . "<br>";
// <MJ.> print "collection code -arg: ". $collection_code["0"] . "<br>";

// <MJ.> print "location: ". implode("::",$location) . "<br>";
           $collection_code = $this->firstString($collection_code);
           $location = $this->firstString($location);
           $collection_desc = tab40_translate($collection_code, $location);
// <MJ.>           $collection_desc = tab40_translate((string) $collection_code[0],"");

           $sig1 = $item->xpath('z30/z30-call-no/text()');
           $sig2 = $item->xpath('z30/z30-call-no-2/text()');
           $desc = $item->xpath('z30/z30-description/text()');
           $note = $item->xpath('z30/z30-note-opac/text()'); 
           $no_of_loans = $item->xpath('z30/z30-no-loans/text()');
           $requested = false;
           $duedate = '';
           if (in_array($status, $this->available_statuses)) {
               $availability = true;
           }
           $reserve = 'N';
           if ($item_status['request'] == 'Y' && $availability == false) {
              $reserve = 'Y';
           }
           $matches = array();
           if (preg_match("/([0-9]*\\/[a-zA-Z]*\\/[0-9]*);([a-zA-Z ]*)/", $status, $matches)) {
               $duedate = $this->parseDate($matches[1]);
               $requested = (trim($matches[2]) == "Requested");
           } else if (preg_match("/([0-9]*\\/[a-zA-Z]*\\/[0-9]*)/", $status, $matches)) {
               $duedate = $this->parseDate($matches[1]);
           } else if (preg_match("/^(\d+\/?){3}$/", $status, $matches)) {
              $duedate = $this->parseDate($status);
           } else {
               $duedate = null;
           }
           $holding[] = array('id' => $id,
                              'item_id' => $item_id,
                              'availability' => $availability, // was true
                              'status' => (string) $item_status['desc'],
                              'location' => $this->firstString($location),
                              'reserve' => $reserve, // was 'reserve' => 'N'
                              'callnumber' => $this->firstString($callnumber),
                              'duedate' => (string) $duedate,
                              'number' => $this->firstString($number),
                              'collection' => $this->firstString($collection),
                              'collection_desc' => (string) $collection_desc['desc'],
                              'barcode' => $this->firstString($barcode),


// <MJ.>                              'description' => "",
                              'description' => $this->firstString($desc),

                              'note' => $this->firstString($note),
// <MJ.>                              'note' => "",

                              'is_holdable' => true,
                              'holdtype' => 'hold',
                              /* below are optional attributes*/
                              'sig1' => $this->firstString($sig1),
                             'sig2' => $this->firstString($sig2),
// <MJ.>                             'sig2' => "",
                              'sub_lib_desc' => (string) $item_status['sub_lib_desc'],
                              'no_of_loans' => (integer) $no_of_loans[0],
                              'requested' => (string) $requested);
        }
//<MJ.> print "HOLDING : ". http_build_query($holding) ." <br>";
        return $holding;
    }

    /**
     * Get Patron Transactions
     *
     * This is responsible for retrieving all transactions (i.e. checked out items)
     * by a specific patron.
     *
     * @param array $user The patron array from patronLogin
     *
     * @return mixed      Array of the patron's transactions on success,
     * PEAR_Error otherwise.
     * @access public
     */
    public function getMyTransactions($user)
    {
        $userId = $user['id'];
        $transList = array();
        $xml = $this->doRestDLFRequest(array('patron', $userId, 'circulationActions', 'loans'), array("view" => "full"));
        foreach ($xml->xpath('//loan') as $item) {
           $z36 = $item->z36;
           $z13 = $item->z13;
           $z30 = $item->z30;
           $group = $item->xpath('@href');
           $group = substr(strrchr($group[0], "/"), 1);
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
			       'id' => $this->barcodeToID($barcode),
                               'item_id' => $group,
                               'location' => $location,
                               'title' => $title,
                               'author' => $author,
                               'isbn' => array($isbn),
                               'reqnum' => $reqnum,
                               'barcode' => $barcode,
                               'duedate' => $this->parseDate($due),
                               'holddate' => $holddate,
                               'delete' => $delete,
                               'renewable' => true,
                               'create' => $this->parseDate($create));
        }
        return $transList;
    }

    public function getRenewDetails($details) {
        return $details['item_id'];
    }

    public function renewMyItems($details) {
        $patron = $details['patron'];
        foreach ($details['details'] as $id) {
           $xml = $this->doRestDLFRequest(array('patron', $patron['id'], 'circulationActions', 'loans', $id), null, 'POST', null);
        }
        return array('blocks' => false, 'details' => array());
    }

    /**
     * Get Patron Holds
     *
     * This is responsible for retrieving all holds by a specific patron.
     *
     * @param array $user The patron array from patronLogin
     *
     * @return mixed      Array of the patron's holds on success, PEAR_Error
     * otherwise.
     * @access public
     */
    public function getMyHolds($user)
    {
        $userId = $user['id'];
        $holdList = array();
        $xml = $this->doRestDLFRequest(array('patron', $userId, 'circulationActions', 'requests', 'holds'), array('view' => 'full'));
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
                                    'item_id' => $user['college'] . $docno . $itemseq . $seq,
                                    'location' => $location,
                                    'title' => $title,
                                    'author' => $author,
                                    'isbn' => array($isbn),
                                    'reqnum' => $reqnum,
                                    'barcode' => $barcode,
                                    'id' => $this->barcodeToID($barcode), 
                                    'expire' => $this->parseDate($expire),
                                    'holddate' => $holddate,
                                    'delete' => $delete,
                                    'create' => $this->parseDate($create));
           }
        }
        return $holdList;
    }

    public function getCancelHoldDetails($holdDetails)
    {
        return $holdDetails['item_id'];
    }

    public function cancelHolds($details)
    {
        $patron = $details['patron'];
        $patronId = $patron['id'];
        $count = 0;
        $statuses = array();
        foreach ($details['details'] as $id) {
           $result = $this->doRestDLFRequest(array('patron', $patronId, 'circulationActions', 'requests', 'holds', $id), null, HTTP_REQUEST_METHOD_DELETE);
           $reply_code = $result->{'reply-code'};
           if ($reply_code != "0000") {
              $message = $result->{'del-pat-hold'}->{'note'};
              if ($message == null) {
                 $message = $result->{'reply-text'};
              }
              $statuses[$id] = array('success' => false, 'status' => 'cancel_hold_failed', 'sysMessage' => (string) $message);
           } else {
              $count++;
              $statuses[$id] = array('success' => true, 'status' => 'cancel_hold_ok');
           }
        }
        $statuses['count'] = $count;
        return $statuses;
    }
     

    /**
     * Get Patron Fines
     *
     * This is responsible for retrieving all fines by a specific patron.
     *
     * @param array $user The patron array from patronLogin
     *
     * @return mixed      Array of the patron's fines on success, PEAR_Error
     * otherwise.
     * @access public
     */
    public function getMyFines($user)
    {
        $mult = 1;
        $finesList = array();
        $finesListSort = array();

        $xml = $this->doRestDLFRequest(array('patron', $user['id'], 'circulationActions', 'cash'));

        foreach ($xml->xpath('//cash') as $item) {

//MJ.
error_log("ITEM = ".json_encode($item));
error_log("link = ". $item['href']);
//MJ. zavola znovu REST API - pro konkretni cash 
        $item = $this->doHTTPRequest($item['href']);  
error_log("ITEM2 = ".json_encode($item));
//MJ. musime jeste otevrit element cash ve stavajicim (nove ziskanem) XMLku
          $item = $item->cash;
 
            $z31 = $item->z31;
            $z13 = $item->z13;
            $z30 = $item->z30;
            $delete = $item->xpath('@delete');
            $title = (string) $z13->{'z13-title'}; 
            $transactiondate = date('d-m-Y', strtotime((string) $z31->{'z31-date'}));
            $transactiontype = (string) $z31->{'z31-credit-debit'};
            $id = (string) $z13->{'z13-doc-number'};
            $barcode = (string) $z30->{'z30-barcode'};
            if($transactiontype=="Debit")
                $mult=-100;
            elseif($transactiontype=="Credit")
                $mult=100;
            $amount = (float)(preg_replace("/[\(\)]/", "", (string) $z31->{'z31-sum'}))*$mult;
            $cashref = (string) $z31->{'z31-sequence'};
error_log("Z31 ref=". json_encode($z31));
            $cashdate = date('d-m-Y', strtotime((string) $z31->{'z31-date'}));
            $balance = 0;
            $finesListSort["$cashref"]  = array(
                    "title"   => $title,
                    "barcode" => $barcode,
                    "amount" => $amount,
                    "transactiondate" => $transactiondate,
                    "transactiontype" => $transactiontype,
                    "balance"  => $balance,
                    "id"  => $id
            ); 
        }
        ksort($finesListSort);
        foreach ($finesListSort as $key => $value){
            $title = $finesListSort[$key]["title"]; 
            $barcode = $finesListSort[$key]["barcode"]; 
            $amount = $finesListSort[$key]["amount"]; 
            $transactiondate = $finesListSort[$key]["transactiondate"]; 
            $transactiontype = $finesListSort[$key]["transactiontype"]; 
            $balance += $finesListSort[$key]["amount"];
            $id = $finesListSort[$key]["id"];
            $finesList[] = array(
                "title"   => $title,
                "barcode"  => $barcode,
                "amount"   => $amount,
                "transactiondate" => $transactiondate,
                "transactiontype" => $transactiontype,
                "balance"  => $balance,
                "id"  => $id
            ); 
        }
//<MJ.>
        $log= json_encode($finesList);
        error_log("FINES: $log");
        return $finesList;
    }

    /**
     * Get Patron Profile
     *
     * This is responsible for retrieving the profile for a specific patron.
     *
     * @param array $user The patron array
     *
     * @return mixed      Array of the patron's profile data on success, PEAR_Error otherwise.
     * @access public
     */
    function getMyProfile($user)
    {   
        $xml = $this->doRestDLFRequest(array('patron', $user['id'], 'patronInformation', 'address'));

        $address = $xml->xpath('//address-information');
        $address = $address[0];
        $address1 = (string)$address->{'z304-address-1'};
        $address2 = (string)$address->{'z304-address-2'};
        $address3 = (string)$address->{'z304-address-3'};
        $address4 = (string)$address->{'z304-address-4'};
        $address5 = (string)$address->{'z304-address-5'};
        $zip = (string)$address->{'z304-zip'};
        $phone = (string)$address->{'z304-telephone-1'};
        $email = (string)$address->{'z304-email-address'}; //<MJ. bylo 404.. procpak? :-)
        $dateFrom = (string)$address->{'z304-date-from'};
        $dateTo = (string)$address->{'z304-date-to'};

        $recordList['firstname'] = $user['firstname'];
        $recordList['lastname'] = $user['lastname'];
        $recordList['address1'] = $address1;
        $recordList['address2'] = $address2;
        $recordList['address3'] = $address3;
        $recordList['address4'] = $address4;
        $recordList['address5'] = $address5;
        $recordList['zip'] = $zip;
        $recordList['phone'] = $phone;
        $recordList['email'] = $email;
       
        $datePattern="/(....)(..)(..)/"; 
        $datePatternCz='${3}.${2}. ${1}';
        $dateFrom=preg_replace($datePattern,$datePatternCz,$dateFrom);
        $dateTo=preg_replace($datePattern,$datePatternCz,$dateTo);

        $recordList['dateFrom'] = $dateFrom;
        $recordList['dateTo'] = $dateTo;
        return $recordList;
    }

    /**
     * Patron Login
     *
     * This is responsible for authenticating a patron against the catalog.
     *
     * @param string $barcode The patron barcode
     * @param string $lname   The patron last name
     *
     * @return mixed          Associative array of patron info on successful login,
     * null on unsuccessful login, PEAR_Error on error.
     * @access public
     */
    public function patronLogin($barcode, $lname)
    {
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

    public function getHoldingInfoForItem($patronId, $id, $group) {
        list($bib, $sys_no) = $this->parseId($id);
        error_log("aleph - getHoldingInfoForItem jsem tu - pID: $patronId id: $id gr: $group");
        $resource = $bib . $sys_no;
        $xml = $this->doRestDLFRequest(array('patron', $patronId, 'record', $resource, 'items', $group));
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


#    function placeHold($details) {
#        error_log("placehold details: $details");
#        list($bib, $sys_no) = $this->parseId($details['id']);
#        $recordId = $bib . $sys_no;
#        $itemId = $details['item_id'];
#        $patron = $details['patron'];
#        $requiredBy = $details['requiredBy'];
#        list($month, $day, $year) = split("-", $requiredBy);
#        $requiredBy = $year . $month . $day;
#        $patronId = $patron['id'];
#        error_log ("placeHold patronId:$patronId recordId:$recordId itemId:$itemId" );
#        $info = $this->getHoldingInfoForItem($patronId, $recordId, $itemId);
#        // FIXME: choose preffered location
#        $pickup_location = '';
#        foreach($info['pickup-locations'] as $key => $value) {
#           $pickup_location = $key;
#        }
#        $data = "post_xml=<?xml version='1.0' encoding='UTF-8'? >" . <MJ. '? >' ma byt u sebe;-)
#           "<hold-request-parameters>\n" .
#           "   <pickup-location>$pickup_location</pickup-location>\n" .
#           "   <last-interest-date>$requiredBy</last-interest-date>\n" .
#           "   <note-1>$comment</note-1>\n".
#           "</hold-request-parameters>\n";
#        $result = $this->doRestDLFRequest(array('patron', $patronId, 'record', $recordId, 'items', $itemId, 'hold'), null, HTTP_REQUEST_METHOD_PUT, $data);
#        $reply_code = $result->{'reply-code'};
#        if ($reply_code != "0000") {
#           $message = $result->{'create-hold'}->{'note'};
#           if ($message == null) {
#              $message = $result->{'reply-text'};
#           }
#           return array('success' => false, 'sysMessage' => $message); // new PEAR_Error($message);
#        } else {
#           return array('success' => true);
#        }
#    }


// prepsal MJ. protoze vyse uvedena verze ponekud nekoresponduje s tim co je v Record/ExtendedHold.php
    function placeHold($patronId, $recordId, $itemId, $location, $requiredBy, $comment) {
        list($bib, $sys_no) = $this->parseId($recordId);
        $recordId = $bib . $sys_no;
//        $itemId = $details['item_id'];
//        $patron = $details['patron'];
//        $requiredBy = $details['requiredBy'];
//        list($month, $day, $year) = split("-", $requiredBy);
//        $requiredBy = $year . $month . $day;
//        $patronId = $patron['id'];
        error_log ("placeHold patronId:$patronId recordId:$recordId itemId:$itemId" );
//        $info = $this->getHoldingInfoForItem($patronId, $recordId, $itemId);
        // FIXME: choose preffered location
//        $pickup_location = '';
//        foreach($info['pickup-locations'] as $key => $value) {
//          $pickup_location = $key;
//        }
        $data = "post_xml=<?xml version='1.0' encoding='UTF-8'?>\n" .
           "<hold-request-parameters>\n" .
           "   <pickup-location>$location</pickup-location>\n" .
           "   <last-interest-date>$requiredBy</last-interest-date>\n" .
           "   <note-1>$comment</note-1>\n".
           "</hold-request-parameters>\n";
        $result = $this->doRestDLFRequest(array('patron', $patronId, 'record', $recordId, 'items', $itemId, 'hold'), null, HTTP_REQUEST_METHOD_PUT, $data);
        $reply_code = $result->{'reply-code'};
        if ($reply_code != "0000") {
           $message = $result->{'create-hold'}->{'note'};
           if ($message == null) {
              $message = $result->{'reply-text'};
           }
           return array('success' => false, 'sysMessage' => $message); // new PEAR_Error($message);
        } else {
           return array('success' => true);
        }
    }




    public function barcodeToID($bar) {
        foreach ($this->bib as $base) {
           try {
              $xml = $this->doXRequest("find", array("base" => $base, "request" => "BAR=$bar"), false);
              $docs = (int) $xml->{"no_records"};
              if ($docs == 1) {
                 $set = (string) $xml->{"set_number"};
                 $result = $this->doXRequest("present", array("set_number" => $set, "set_entry" => "1"), false);
                 $id = $result->xpath('//doc_number/text()');
                 if (count($this->bib)==1) {
                    return $id[0];
                 } else {
                    return $base . "-" . $id[0];
                 }
              }
           } catch (Exception $ex) {
           }
        }
        return new PEAR_Error('barcode not found');
    }

    function parseDate($date) {
       if (preg_match("/^[0-9]{8}$/", $date) === 1) {
           return substr($date, 6, 2) . "." .substr($date, 4, 2) . "." . substr($date, 0, 4);
        } else {
           list($day, $month, $year) = split("/", $date, 3);
           if (!is_numeric($month)) {
             $translate_month = array ( 'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6,
                'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12);
             $month = $translate_month[strtolower($month)];
           }
           $day = ltrim($day, "0");
           $month = ltrim($month, "0");
           $year = "20" . $year;
           return $day . "." . $month . ". " . $year;
        }
    }

    public function getConfig($func) {
        if ($func == "Holds") {
           return array("HMACKeys" => "id:item_id", "extraHoldFields" => "comments:requiredByDate",
              "defaultRequiredDate" => "0:1:0");
        } else {
           return array();
        }
    }
    
    function firstString($array) {
      return !empty($array) ? (string) $array[0] : "";
    }

     /**
     * Get Purchase History
     *
     * This is responsible for retrieving the acquisitions history data for the
     * specific record (usually recently received issues of a serial).
     *
     * @param string $id The record id to retrieve the info for
     *
     * @return mixed     An array with the acquisitions data on success, PEAR_Error
     * on failure
     * @access public
     */
    public function getPurchaseHistory($id)
    {
        return array();
    }

    
    /**
     * Get New Items
     *
     * Retrieve the IDs of items recently added to the catalog.
     *
     * @param int $page    Page number of results to retrieve (counting starts at 1)
     * @param int $limit   The size of each page of results to retrieve
     * @param int $daysOld The maximum age of records to retrieve in days (max. 30)
     * @param int $fundId  optional fund ID to use for limiting results (use a value
     * returned by getFunds, or exclude for no limit); note that "fund" may be a
     * misnomer - if funds are not an appropriate way to limit your new item
     * results, you can return a different set of values from getFunds. The
     * important thing is that this parameter supports an ID returned by getFunds,
     * whatever that may mean.
     *
     * @return array       Associative array with 'count' and 'results' keys
     * @access public
     */
    public function getNewItems($page, $limit, $daysOld, $fundId = null)
    {
        $url = "http://aleph.techlib.cz/feed/novinky.xml";
        $result = $this->doHTTPRequest($url);
        $links = $result->xpath("/rss/channel/item/link");
        $items = array();
        foreach ($links as $link) {
          $matches = array();
          preg_match("/\d{9}/", $link[0], $matches);
          if ($matches) {
            $items[] = array(
              "id" => $matches[0]
            );
          }
        }
        if ($limit) {
          $items = array_slice($items, 0, $limit);
        }
        $response = array(
          "count" => count($items),
          "results" => $items
        );
        return $response;
    }

    /**
     * Get Departments
     *
     * Obtain a list of departments for use in limiting the reserves list.
     *
     * @return array An associative array with key = dept. ID, value = dept. name.
     * @access public
     */
    public function getDepartments()
    {
        $deptList = array();
        return $deptList;
    }

    /**
     * Get Instructors
     *
     * Obtain a list of instructors for use in limiting the reserves list.
     *
     * @return array An associative array with key = ID, value = name.
     * @access public
     */
    public function getInstructors()
    {
        $deptList = array();
        return $deptList;
    }

    /**
     * Get Courses
     *
     * Obtain a list of courses for use in limiting the reserves list.
     *
     * @return array An associative array with key = ID, value = name.
     * @access public
     */
    public function getCourses()
    {
        $deptList = array();
        return $deptList;
    }

    /**
     * Find Reserves
     *
     * Obtain information on course reserves.
     *
     * @param string $course ID from getCourses (empty string to match all)
     * @param string $inst   ID from getInstructors (empty string to match all)
     * @param string $dept   ID from getDepartments (empty string to match all)
     *
     * @return mixed An array of associative arrays representing reserve items (or a
     * PEAR_Error object if there is a problem)
     * @access public
     */
    public function findReserves($course, $inst, $dept)
    {
        $recordList = array();
        return $recordList;
    }

}

?>
