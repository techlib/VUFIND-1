<?php
/**
 * SirsiDynix Unicorn ILS Driver (VuFind side)
 *
 * PHP version 5
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
 * @author   Tuan Nguyen <tuan@yorku.ca>
 * @author   Drew Farrugia <vufind-unicorn-l@lists.lehigh.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://code.google.com/p/vufind-unicorn/ vufind-unicorn project
 */

require_once 'Interface.php';
require_once 'sys/Proxy_Request.php';

/**
 * SirsiDynix Unicorn ILS Driver (VuFind side)
 *
 * IMPORTANT: To use this driver you need to download the SirsiDynix API driver.pl
 * from http://code.google.com/p/vufind-unicorn/ and install it on your Sirsi
 * Unicorn/Symphony server. Please note: currently you will need to download 
 * the driver.pl in the yorku branch on google code to use this driver.
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   Tuan Nguyen <tuan@yorku.ca>
 * @author   Drew Farrugia <vufind-unicorn-l@lists.lehigh.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://code.google.com/p/vufind-unicorn/ vufind-unicorn project
 **/
class Unicorn implements DriverInterface
{
    private $_host;
    private $_port;
    private $_search_prog;
    private $_url;

    protected $db;
    protected $ilsConfigArray;

    /**
     * Constructor.
     */
    function __construct()
    {
        // Load Configuration for this Module
        $this->ilsConfigArray = parse_ini_file('conf/Unicorn.ini', true);
        global $configArray;

        // allow user to specify the full url to the Sirsi side perl script
        $this->_url = $this->ilsConfigArray['Catalog']['url'];

        // host/port/search_prog kept for backward compatibility
        if (isset($this->ilsConfigArray['Catalog']['host'])
            && isset($this->ilsConfigArray['Catalog']['port'])
            && isset($this->ilsConfigArray['Catalog']['search_prog'])
        ) {
            $this->_host = $this->ilsConfigArray['Catalog']['host'];
            $this->_port = $this->ilsConfigArray['Catalog']['port'];
            $this->_search_prog = $this->ilsConfigArray['Catalog']['search_prog'];
        }

        /* unused code:
        $this->show_library = $this->ilsConfigArray['Catalog']['show_library'];
        $this->show_library_format
            = $this->ilsConfigArray['Catalog']['show_library_format'];
         */
        $this->db = ConnectionManager::connectToIndex();
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
        $params = array('query' => 'single', 'id' => $id);
        $response = $this->querySirsi($params);
        if (empty($response)) {
            return array();
        }

        // separate the item lines and the MARC holdings records
        $marc_marker = '-----BEGIN MARC-----';
        $marc_marker_pos = strpos($response, $marc_marker);
        $lines = ($marc_marker_pos !== false)
            ? substr($response, 0, $marc_marker_pos) : '';
        $marc = ($marc_marker_pos !== false)
            ? substr($response, $marc_marker_pos + strlen($marc_marker)) : '';

        $items = array();
        $lines = explode("\n", rtrim($lines));
        foreach ($lines as $line) {
            $item = $this->parseStatusLine($line);
            $items[] = $item;
        }

        if (!empty($items)) {
            // sort the items by shelving key in descending order, then ascending by
            // copy number; use create_function to create anonymous comparison
            // function for php 5.2 compatibility
            $cmp = create_function(
                '$a,$b',
                'if ($a["shelving_key"] == $b["shelving_key"]) '
                .     'return $a["number"] - $b["number"];'
                . 'return $a["shelving_key"] < $b["shelving_key"] ? 1 : -1;'
            );
            usort($items, $cmp);

            // put MARC holdings records into 'marc_holdings' field of the first item
            $items[0]['marc_holdings'] = $this->_getMarcHoldings($marc);
        }
        return $items;
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
        $statuses = array();
        $params = array(
            'query' => 'multiple', 'ids' => implode("|", array_unique($idList))
        );
        $response = $this->querySirsi($params);
        if (empty($response)) {
            return array();
        }
        $lines = explode("\n", $response);

        $currentId = null;
        $group = -1;
        foreach ($lines as $line) {
            $item = $this->parseStatusLine($line);
            if ($item['id'] != $currentId) {
                $currentId = $item['id'];
                $statuses[] = array();
                $group++;
            }
            $statuses[$group][] = $item;
        }
        return $statuses;
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
        return $this->getStatus($id);
    }

    /**
    * Get Holdings (alias for getStatuses).
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
    public function getHoldings($idList)
    {
        return $this->getStatuses($idList);
    }

    /**
     * Place a hold.
     *
     * @param string $itemId    record id
     * @param string $patronId  patron id
     * @param string $pickupLib library to pickup item
     *
     * @return array
     */
    public function placeHold($itemId, $patronId, $pickupLib)
    {
        $params = array(
            'query' => 'hold', 'itemId' => $itemId, 'patronId' => $patronId,
            'lib' => $pickupLib
        );
        $response = $this->querySirsi($params);

        list($code, $reqnum) = explode('|', $response);
        if ($code == "209") {
            $result = array(
                'hold' => $code,
                'reason' =>
                    'Your hold has been placed; Please choose a library for pickup.',
                'reqnum' => $reqnum,
                'lib' => $pickupLib
            );
        } elseif ($code == "722") {
            $result = array(
                'hold' => false,
                'reason' =>
                    'We could not place the hold. ' .
                    'You already have a hold on this item.',
                'reqnum' => $reqnum
            );
        } elseif ($code == "753") {
            $result = array(
                'hold' => false,
                'reason' =>
                    'We could not place the hold. ' .
                    'You already have this item checked out.',
                'reqnum' => $reqnum
            );
        } elseif ($code == "447") {
            $result = array(
                'hold' => false,
                'reason' =>
                    'We could not place the hold. ' .
                    'This item may not be available for circulation. ' .
                    'Please contact your library for assistance.',
                'reqnum' => $reqnum
            );
        } elseif ($code == "444") {
            $result = array(
                'hold' => false,
                'reason' =>
                    'We could not place the hold. ' .
                    'You have exceeded the limit for number of holds per user.',
                'reqnum' => $reqnum
            );
        } elseif ($code == "218") {
            $result = array(
                'hold' => false,
                'reason' =>
                    'We could not place the hold. ' .
                    'Your library card may be blocked. ' .
                    'Please contact your library for assistance',
                'reqnum' => $reqnum
            );
        } else {
            $result = array(
                'hold' => false,
                'reason' =>
                    'Hold failed. Please contact your library for assistance.'
            );
        }

        return $result;
    }

    /**
     * Renew a checked out item.
     *
     * @param string $itemId   item id
     * @param string $patronId patron id
     *
     * @return array return status of the renew request in an associative array.
     */
    public function renewItem($itemId, $patronId)
    {
        $params = array(
            'query' => 'renew', 'itemId' => $itemId, 'patronId' => $patronId
        );
        $response = $this->querySirsi($params);

        // process the API response
        if ($response == 'not_charged') {
            return array('error' => 'This item is not checked out');
        }
        if ($response == 'not_charged_by_user') {
            return array('error' => 'This item is not checked out by you');
        }

        $matches = array();
        preg_match('/\^MN([0-9][0-9][0-9])/', $response, $matches);
        if (isset($matches[1])) {
            $status = $matches[1];
            if ($status != '214') {
                return array(
                    'error' => $this->ilsConfigArray['ApiMessages'][$status]
                );
            }
        }

        $data = array();
        preg_match('/\^IB([^\^]+)\^/', $response, $matches);
        if (isset($matches[1])) {
            $data['title'] = $this->_toUTF8($matches[1]);
        }
        preg_match('/\^CI([^\^]+)\^/', $response, $matches);
        if (isset($matches[1])) {
            $data['duedate']
                = $this->_formatDateTime($this->_parseApiDateTime($matches[1]));
        }
        preg_match('/\^Up([^\^]+)\^/', $response, $matches);
        if (isset($matches[1])) {
            $data['fines'] = $matches[1];
        }
        preg_match('/\^Uq([^\^]+)\^/', $response, $matches);
        if (isset($matches[1])) {
            $data['overdues'] = $matches[1];
        }

        return $data;
    }

    /**
     * Find a patron in the Unicorn/Symphony user database matching given
     * username/password.
     *
     * @param string $username user id
     * @param string $password user password/pin
     *
     * @return array associative array of patron data or NULL if no matching user
     * found
     */
    public function patronLogin($username, $password)
    {
        //query sirsi
        $params = array(
            'query' => 'login', 'patronId' => $username, 'pin' => $password
        );
        $response = $this->querySirsi($params);

        if (empty($response)) {
            return null;
        }

        list($user_key, $alt_id, $barcode, $name, $library, $profile,
        $cat1, $cat2, $cat3, $cat4, $cat5) = explode('|', $response);

        list($last, $first) = explode(',', $name);
        $first = rtrim($first, " ");

        return array(
            'id' => $username,
            'firstname' => $first,
            'lastname' =>  $last,
            'cat_username' => $username,
            'cat_password' => $password,
            'email' => null,
            'major' => null,
            'college' => null
        );
    }

    /**
     * Fetch user's profile information.
     *
     * @param array $patron associative array containing patron information
     *
     * @return array associative array containing patron profile information
     */
    public function getMyProfile($patron)
    {
        $username = $patron['cat_username'];
        $password = $patron['cat_password'];

        //query sirsi
        $params = array(
            'query' => 'profile', 'patronId' => $username, 'pin' => $password
        );
        $response = $this->querySirsi($params);

        list($user_key, $alt_id, $barcode, $name, $library, $profile,
        $cat1, $cat2, $cat3, $cat4, $cat5,
        $email, $address1, $zip, $phone, $address2) = explode('|', $response);

        return array(
            'firstname' => $patron['firstname'],
            'lastname' => $patron['lastname'],
            'address1' => $address1,
            'address2' => $address2,
            'zip' => $zip,
            'phone' => $phone,
            'email' => $email,
            'group' => $profile,
            'library' => $library
        );
    }

    /**
     * Fetch fines.
     *
     * @param array $patron associative array containing patron information
     *
     * @return array array of associative arrays containing fines data.
     */
    public function getMyFines($patron)
    {
        $username = $patron['cat_username'];
        $password = $patron['cat_password'];

        $params = array(
            'query' => 'fines', 'patronId' => $username, 'pin' => $password
        );
        $response = $this->querySirsi($params);
        if (empty($response)) {
            return array();
        }
        $lines = explode("\n", $response);
        $items = array();
        foreach ($lines as $item) {
            list($catkey, $amount, $balance, $date_billed, $number_of_payments,
            $with_items, $reason, $date_charged, $duedate, $date_recalled)
                = explode('|', $item);

            // the amount and balance are in cents, so we need to turn them into
            // dollars if configured
            if (!$this->ilsConfigArray['Catalog']['leaveFinesAmountsInCents']) {
                $amount = (floatval($amount) / 100.00);
                $balance = (floatval($balance) / 100.00);
            }

            $date_billed = $this->_parseDateTime($date_billed);
            $date_charged = $this->_parseDateTime($date_charged);
            $duedate = $this->_parseDateTime($duedate);
            $date_recalled = $this->_parseDateTime($date_recalled);
            $items[] = array(
                'id' => $catkey,
                'amount' => $amount,
                'balance' => $balance,
                'date_billed' => $date_billed,
                'number_of_payments' => $number_of_payments,
                'with_items' => $with_items,
                'fine' => $reason,
                'checkout' => $this->_formatDateTime($date_charged),
                'duedate' => $this->_formatDateTime($duedate),
                'date_recalled' => $this->_formatDateTime($date_recalled)
            );
        }

        return $items;
    }

    /**
     * Get holds.
     *
     * @param array $patron associative array containing patron information
     *
     * @return array array of associative arrays containing hold records.
     */
    public function getMyHolds($patron)
    {
        $username = $patron['cat_username'];
        $password = $patron['cat_password'];

        $params = array(
            'query' => 'getholds', 'patronId' => $username, 'pin' => $password
        );
        $response = $this->querySirsi($params);
        if (empty($response)) {
            return array();
        }
        $lines = explode("\n", $response);
        $items = array();
        foreach ($lines as $item) {
            list($catkey, $holdkey, $available, $recall_status, $date_expires,
            $reserve, $date_created, $priority, $type, $pickup_library,
            $suspend_begin, $suspend_end, $date_recalled, $special_request,
            $date_available, $date_available_expires)
                = explode('|', $item);

            $date_created = $this->_parseDateTime($date_created);
            $date_expires = $this->_parseDateTime($date_expires);
            $items[] = array(
                'id' => $catkey,
                'reqnum' => $holdkey,
                'available' => $available,
                'expire' => $this->_formatDateTime($date_expires),
                'create' => $this->_formatDateTime($date_created),
                'type' => $type,
                'location' => $pickup_library
            );
        }

        return $items;
    }

    /**
     * Edit a hold record.
     *
     * @param string $reqnum    API param (TODO: document better)
     * @param string $cancel    API param (TODO: document better)
     * @param string $expire    API param (TODO: document better)
     * @param string $pickupLib API param (TODO: document better)
     * @param string $suspend   API param (TODO: document better)
     * @param string $unsuspend API param (TODO: document better)
     *
     * @return string API status
     */
    public function editHold($reqnum, $cancel, $expire, $pickupLib, $suspend,
        $unsuspend
    ) {
        // query sirsi
        $params = array('query' => 'edithold', 'holdId' => $reqnum,
        'cancel' => $cancel, 'lib' => $pickupLib, 'expire' => $expire,
        'suspend' => $suspend, 'unsuspend' => $unsuspend);

        $response = $this->querySirsi($params);

        return $response;
    }

    /**
     * Get checked out items.
     *
     * @param array $patron associative array containing patron information
     *
     * @return array array of associative arrays containing checkedout items
     */
    public function getMyTransactions($patron)
    {
        $username = $patron['cat_username'];
        $password = $patron['cat_password'];

        $params = array(
            'query' => 'transactions', 'patronId' => $username, 'pin' => $password
        );
        $response = $this->querySirsi($params);
        if (empty($response)) {
            return array();
        }
        $item_lines = explode("\n", $response);
        $items = array();
        foreach ($item_lines as $item) {
            list($catkey, $date_charged, $duedate, $date_renewed, $accrued_fine,
            $overdue, $number_of_renewals, $date_recalled) = explode('|', $item);

            $duedate = $this->_parseDateTime($duedate);
            $date_recalled = $this->_parseDateTime($date_recalled);
            if ($date_recalled !== false) {
                $duedate = $this->_calculateRecallDueDate($date_recalled);
            }
            $items[] = array(
                'id' => $catkey,
                'date_charged' =>
                    $this->_formatDateTime($this->_parseDateTime($date_charged)),
                'duedate' => $this->_formatDateTime($duedate),
                'date_renewed' =>
                    $this->_formatDateTime($this->_parseDateTime($date_renewed)),
                'accrued_fine' => $accrued_fine,
                'overdue' => $overdue,
                'number_of_renewals' => $number_of_renewals,
                'date_recalled' => $this->_formatDateTime($date_recalled),
                'recall_duedate' =>
                    ($date_recalled ? $this->_formatDateTime($duedate) : '')
            );
        }

        return $items;
    }

    /**
     * Get courses from course reserve database.
     *
     * @return array array of courses
     */
    public function getCourses()
    {
        //query sirsi
        $params = array('query' => 'courses');
        $response = $this->querySirsi($params);

        $response = rtrim($response);
        $course_lines = split("\n", $response);
        $courses = array();

        foreach ($course_lines as $course) {
            list($id, $name) = explode('|', $course);
            $courses[$id] = $name;
        }
        return $courses;
    }

    /**
     * Get instructors from course reserve database.
     *
     * @return array array of instructor information
     */
    public function getInstructors()
    {
        //query sirsi
        $params = array('query' => 'instructors');
        $response = $this->querySirsi($params);

        $response = rtrim($response);
        $user_lines = split("\n", $response);
        $users = array();

        foreach ($user_lines as $user) {
            list($id, $name) = explode('|', $user);
            $users[$id] = $name;
        }
        return $users;
    }

    /**
     * Get departments/reserve desks from course reserve database.
     *
     * @return array array of departments/reserve desks
     */
    public function getDepartments()
    {
        //query sirsi
        $params = array('query' => 'desks');
        $response = $this->querySirsi($params);

        $response = rtrim($response);
        $dept_lines = split("\n", $response);
        $depts = array();

        foreach ($dept_lines as $dept) {
            $dept = rtrim($dept, '\|');
            $depts[$dept] = $dept;
        }
        return $depts;
    }

    /**
     * Find course reserves.
     *
     * @param string $courseId     ID from getCourses (empty string to match
     * all)
     * @param string $instructorId ID from getInstructors (empty string to
     * match all)
     * @param string $departmentId ID from getDepartments (empty string to
     * match all)
     *
     * @return array  array of course reserve items
     */
    public function findReserves($courseId, $instructorId, $departmentId)
    {
        //query sirsi
        if ($courseId) {
            $params = array(
                'query' => 'reserves', 'course' => $courseId, 'instructor' => '',
                'desk' => ''
            );
        } elseif ($instructorId) {
            $params = array(
                'query' => 'reserves', 'course' => '', 'instructor' => $instructorId,
                'desk' => ''
            );
        } elseif ($departmentId) {
            $params = array(
                'query' => 'reserves', 'course' => '', 'instructor' => '',
                'desk' => $departmentId
            );
        }

        $response = $this->querySirsi($params);

        $item_lines = split("\n", $response);
        $items = array();
        $count = 0;
        foreach ($item_lines as $item) {
            $id = rtrim($item, "|");
            $record = $this->db->getRecordByCtrl($id);
            $items[$count] = array (
                'BIB_ID' => $record['id']
                //         'BIB_ID' => $id
            );
            $count++;
        }
        return $items;
    }

    /**
     * Get newly catalogued items.
     *
     * @param int    $page    Page number of results to retrieve (counting starts at
     * 1)
     * @param int    $limit   The size of each page of results to retrieve
     * @param int    $daysOld The maximum age of records to retrieve in days (max.
     * 30)
     * @param int    $fundId  optional fund ID to use for limiting results (use a
     * value returned by getFunds, or exclude for no limit); note that "fund" may be
     * a misnomer - if funds are not an appropriate way to limit your new item
     * results, you can return a different set of values from getFunds. The
     * important thing is that this parameter supports an ID returned by getFunds,
     * whatever that may mean.
     * @param string $lib     Unused, non-standard parameter
     *
     * @return array
     */
    public function getNewItems($page, $limit, $daysOld, $fundId = null, $lib = null)
    {
        //query sirsi
        //  isset($lib)
        // ? $params = array('query' => 'newItems',
        // 'lib' => array_search($lib, $ilsConfigArray['Libraries']))
        // : $params = array('query' => 'newItems');
        $params = array('query' => 'newitems', 'lib' => 'PPL');
        $response = $this->querySirsi($params);

        $item_lines = split("\n", rtrim($response));

        $rescount = 0;
        foreach ($item_lines as $item) {
            $item = rtrim($item, '|');
            $items[$item] = array (
                'id' => $item
            );
            $rescount++;
        }

        $results = array_slice($items, ($page - 1) * $limit, ($page * $limit)-1);
        return array('count' => $rescount, 'results' => $results);
    }

    /**
     * Get suppressed/shadowed records.
     *
     * @return array
     */
    public function getSuppressed()
    {
        $params = array('query' => 'shadowed');
        $response = $this->querySirsi($params);

        $record_lines = split("\n", rtrim($response));
        $records = array();
        foreach ($record_lines as $record) {
            $record = rtrim($record, '|');
            $records[] = $record;
        }

        return $records;
    }

    /**
     * Parse a pipe-delimited status line received from the script on the
     * Unicorn/Symphony server.
     *
     * @param string $line The pipe-delimited status line to parse.
     *
     * @return array       Associative array of holding information
     */
    protected function parseStatusLine($line)
    {
        list($catkey, $shelving_key, $callnum, $location_code, $reserve,
        $library_code, $barcode, $number_of_charges, $currLocCode,
        $item_type, $recirculate_flag, $holdcount, $itemkey1, $itemkey2, $itemkey3,
        $holdable, $circulation_rule, $duedate, $date_recalled, $format)
            = explode("|", $line);

        // availability
        $availability = ($number_of_charges == 0) ? 1 : 0;

        // due date (if checked out)
        $duedate = $this->_parseDateTime(trim($duedate));

        // date recalled
        $date_recalled = $this->_parseDateTime(trim($date_recalled));

        // a recalled item has a new due date, we have to calculate that new due date
        if ($date_recalled !== false) {
            $duedate = $this->_calculateRecallDueDate($date_recalled);
        }

        // item status
        $status = ($availability) ? 'Available' : 'Checked Out';

        // even though item is NOT checked out, it still may not be "Available"
        // the following are the special cases
        if (isset($this->ilsConfigArray['UnavailableItemTypes'])
            && isset($this->ilsConfigArray['UnavailableItemTypes'][$item_type])
        ) {
            $availability = 0;
            $status = $this->ilsConfigArray['UnavailableItemTypes'][$item_type];
        } else if (isset($this->ilsConfigArray['UnavailableLocations'])
            && isset($this->ilsConfigArray['UnavailableLocations'][$currLocCode])
        ) {
            $availability = 0;
            $status= $this->ilsConfigArray['UnavailableLocations'][$currLocCode];
        }

        $item = array (
            'status' => $status,
            'availability' => $availability,
            'id' => $catkey,
            'number' => $itemkey3, // copy number
            'duedate' => $this->_formatDateTime($duedate),
            'callnumber' => $callnum,
            'reserve' => ($reserve == '0') ? 'N' : 'Y',
            'location_code' => $location_code,
            'location' => $this->mapLocation($location_code),
            'library_code' => $library_code,
            'library' => $this->mapLibrary($library_code),
            'barcode' => trim($barcode),
            'holdable' => $holdable,
            'holdcount' => $holdcount,
            'current_location_code' => $currLocCode,
            'current_location' => $this->mapLocation($currLocCode),
            'item_type' => $item_type,
            'recirculate_flag' => $recirculate_flag,
            'shelving_key' => $shelving_key,
            'circulation_rule' => $circulation_rule,
            'date_recalled' => $this->_formatDateTime($date_recalled),
            'item_key' => $itemkey1 . '|' . $itemkey2 . '|' . $itemkey3 . '|',
            'format' => $format
            );

            return $item;
    }

    /**
     * Map the location code to friendly name.
     *
     * @param string $code The location code from Unicorn/Symphony
     *
     * @return string      The friendly name if defined, otherwise the code is
     * returned.
     */
    protected function mapLocation($code)
    {
        if (isset($this->ilsConfigArray['Locations'])
            && isset($this->ilsConfigArray['Locations'][$code])
        ) {
            return $this->ilsConfigArray['Locations'][$code];
        }
        return $code;
    }

    /**
     * Maps the library code to friendly library name.
     *
     * @param string $code The library code from Unicorn/Symphony
     *
     * @return string      The library friendly name if defined, otherwise the code
     * is returned.
     */
    protected function mapLibrary($code)
    {
        if (isset($this->ilsConfigArray['Libraries'])
            && isset($this->ilsConfigArray['Libraries'][$code])
        ) {
            return $this->ilsConfigArray['Libraries'][$code];
        }
        return $code;
    }

    /**
     * Send a request to the SIRSI side API script and returns the response.
     *
     * @param array $params Associative array of query parameters to send.
     *
     * @return string
     */
    protected function querySirsi($params)
    {
        $url = $this->_url;
        if (empty($url)) {
            $url = $this->_host;
            if ($this->_port) {
                $url =  "http://" . $url . ":" . $this->_port . "/" .
                    $this->_search_prog;
            } else {
                $url =  "http://" . $url . "/" . $this->_search_prog;
            }
        }

        $httpClient = new Proxy_Request();
        // use HTTP POST so parameters like user id and PIN are NOT logged by web
        // servers
        $httpClient->setMethod(HTTP_REQUEST_METHOD_POST);
        $httpClient->setURL($url);
        $httpClient->setBody(http_build_query($params));
        $result = $httpClient->sendRequest();

        if (!PEAR::isError($result)) {
            // Even if we get a response, make sure it's a 'good' one.
            if ($httpClient->getResponseCode() != 200) {
                PEAR::raiseError("Error response code received from $url");
            }
        } else {
            PEAR::raiseError($result);
        }

        // get the response data
        $response = $httpClient->getResponseBody();

        return rtrim($response);
    }

    /**
     * Take a date/time string from SIRSI seltool and convert it into unix time
     * stamp.
     *
     * @param string $date The input date string. Expected format YYYYMMDDHHMM.
     *
     * @return int         Unix time stamp if successful, false otherwise.
     */
    private function _parseDateTime($date)
    {
        if (strlen($date) >= 8) {
            // format is MM/DD/YYYY HH:MI so it can be passed to strtotime
            $formatted_date = substr($date, 4, 2).'/'.substr($date, 6, 2).
                '/'.substr($date, 0, 4);
            if (strlen($date) > 8) {
                $formatted_date .= ' ' . substr($date, 8, 2) . ':' .
                    substr($date, 10);
            }
            return strtotime($formatted_date);
        }
        return false;
    }

    /**
     * Take a date/time string from SIRSI API response and convert it into unix time
     * stamp.
     *
     * @param string $date The input date string. Expected format 10/6/2011,23:59.
     *
     * @return int         Unix time stamp if successful, false otherwise.
     */
    private function _parseApiDateTime($date)
    {
        list($date, $time) = explode(',', $date);

        if (!empty($date)) {
            list($day, $month, $year) = explode('/', $date);
            $formatted_date = $month . '/' . $day . '/' . $year . ' ' . $time;
            return strtotime($formatted_date);
        }
        return false;
    }

    /**
     * Given the date recalled, calculate the new due date based on circulation
     * policy.
     *
     * @param int $date_recalled Unix time stamp of when the recall was issued.
     *
     * @return int New due date as unix time stamp.
     */
    private function _calculateRecallDueDate($date_recalled)
    {
        if ($date_recalled) {
            $recall_due_period
                = $this->ilsConfigArray['CirculationPolicies']['recall_due_period'];
            $duedate = $date_recalled + ($recall_due_period * 24 * 60 * 60);
            return $duedate;
        }
        return false;
    }

    /**
     * Format the given unix time stamp to a human readable format. The format is
     * configurable in Unicorn.ini
     *
     * @param int $time Unix time stamp.
     *
     * @return string Formatted date/time.
     */
    private function _formatDateTime($time)
    {
        $format = $this->ilsConfigArray['DateTimeFormats']['default'];
        return $time ? strftime($format, $time) : '';
    }

    /**
     * Convert the given ISO-8859-1 string to UTF-8 if it is not already UTF-8.
     *
     * @param string $s The string to convert.
     *
     * @return string   The input string converted to UTF-8
     */
    private function _toUTF8($s)
    {
        return (mb_detect_encoding($s, 'UTF-8') == 'UTF-8') ? $s : utf8_encode($s);
    }

    /**
     * Get textual holdings summary.
     *
     * @param string $marc The raw marc holdings records.
     *
     * @return array       Array of holdings data indexed by library.
     * @access private
     */
    private function _getMarcHoldings($marc)
    {
        $records = array();
        $count = 0;
        $file = new File_MARC($marc, File_MARC::SOURCE_STRING);
        while ($marc = $file->next()) {
            $fields = $marc->getFields('852|866', true);
            foreach ($fields as $field) {
                if ($field->getTag() == '852') {
                    $location = $field->getSubfield('c')->getData();
                    $records[] = array(
                        'library' => $this->mapLibrary($location),
                        'location_code' => $location,
                        'location' => $this->mapLocation($location),
                        'marc852' => $field,
                        'marc866' => array(),
                        'textual_holdings' => array()
                    );
                    $count++;
                } else {
                    if ($count > 0) {
                        $holdings = '';
                        $subfields = $field->getSubfields();
                        foreach ($subfields as $subfield) {
                            if ($subfield->getCode() != 'x') {
                                $holdings .= $subfield->getData() . ' ';
                            }
                        }
                        $records[$count - 1]['marc866'][] = $field;
                        $records[$count - 1]['textual_holdings'][] = trim($holdings);
                    }
                }
            }
        }
        return $records;
    }
}
?>
