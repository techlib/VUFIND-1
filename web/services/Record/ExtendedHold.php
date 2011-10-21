<?php
/**
 *
 * Copyright (C) Villanova University 2007.
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
                                                                                                                                                                                                                   
require_once 'CatalogConnection.php';                                                                                                                                                                              
require_once 'Record.php';                                                                                                                                                                                         
require_once 'Drivers/Aleph.php';                                                                                                                                                                                  
                                                                                                                                                                                                                   
class ExtendedHold extends Record                                                                                                                                                                                       
{                                                                                                                                                                                                                  
    private $user;                                                                                                                                                                                                 

    function __construct()
    {
        $this->user = UserAccount::isLoggedIn();
    }
    
    function launch()
    {
        global $interface;

       // error_log("record/extendedHold-> launch() id:".$_GET['id']." barcode:".$_GET['barcode']." recordId:".$_GET['id'] . "," . $_GET['lookfor']);

        if (!$this->user) {
            // return new PEAR_Error('Prihlaste se.');
            // Needed for "back to record" link in view-alt.tpl:
            $interface->assign('id', $_GET['id']);
            $interface->assign('barcode', $_GET['barcode']);
            // Needed for login followup:
            $interface->assign('recordId', $_GET['id'] . "," . $_GET['lookfor']);
            if (isset($_GET['lightbox'])) {
                $interface->assign('title', $_GET['message']);
                $interface->assign('message', 'You must be logged in first');
                $interface->assign('followup', true);
                $interface->assign('followupModule', 'Record');
                $interface->assign('followupAction', "ExtendedHold");
                return $interface->fetch('AJAX/login.tpl');
            } else {
                $interface->assign('followup', true);
                $interface->assign('followupModule', 'Record');
                $interface->assign('followupAction', 'ExtendedHold');
                $interface->setPageTitle('You must be logged in first');
                $interface->assign('subTemplate', '../MyResearch/login.tpl');
                $interface->setTemplate('view-alt.tpl');
                $interface->display('layout.tpl', 'ExtendedHold' . $_GET['id']); //'RecordPutHold'
            }
            exit();
        }

        if (isset($_POST['submit'])) {
            $result = $this->putHold();
            if (PEAR::isError($result)) {
                $interface->assign('error', true);
                $interface->assign('error_str', $result->getMessage());
            }
            $interface->assign('subTemplate', 'extendedhold-status.tpl');
            $interface->setTemplate('view-alt.tpl');
            $interface->display('layout.tpl');
        } else {
            return $this->display();
        }
    }
    
    function display()
    {
        global $interface;
        try {
            $catalog = new Aleph(); // CatalogConnection($configArray['Catalog']['driver']);
        } catch (PDOException $e) {
            return new PEAR_Error('Cannot connect to ILS');
        }
        $id = $_GET['id'];
// <MJ.>       $group = $_REQUEST['lookfor'];
         $group = $_GET['barcode'];

        if (strpos($id, ",") !== false) {
           list($id, $group) = split(",", $id); 
        }
        $patron = $catalog->patronLogin($this->user->cat_username, $this->user->cat_password);
        // error_log("display patron:".$patron['id'].", id:$id, group:$group");

        $info = $catalog->getHoldingInfoForItem($patron['id'], $id, $group);
        $interface->assign('order', $info['order']);
        $interface->assign('locations', $info['pickup-locations']);
        $interface->assign('last_interest_date', $info['last-interest-date']);
// <MJ.> - bylo zakomentovane obracene..)
//       $interface->assign('item', $group);
        $interface->assign('item', $_GET['barcode']);
        $interface->assign('formTargetPath',
            '/Record/' . urlencode($id) . '/ExtendedHold');
        if (isset($_GET['lightbox'])) {
            // Use for lightbox
            $interface->assign('title', $_GET['message']);
            return $interface->fetch('Record/extendedhold.tpl');
        } else {
            // Display Page
            $interface->setPageTitle('Hold');
            $interface->assign('subTemplate', 'extendedhold.tpl');
            $interface->setTemplate('view-alt.tpl');
            $interface->display('layout.tpl', 'ExtendedHold' . $_GET['id']);
        }
    }

    function putHold() {
        global $configArray;
        global $interface;
        $id = $_REQUEST['id'];
        $to = $_REQUEST['to'];
        $comment = $_REQUEST['comment'];
        $item = $_REQUEST['item'];
        $location = $_REQUEST['location'];
        $interface->assign('id', $_GET['id']);
        list($day, $month, $year) = split("\.", $to);
        $to = $year . str_pad($month, 2, "0", STR_PAD_LEFT) . str_pad($day, 2, "0", STR_PAD_LEFT);
        try {
            $catalog = new Aleph(); // CatalogConnection($configArray['Catalog']['driver']);
        } catch (PDOException $e) {
            return new PEAR_Error('Cannot connect to ILS');
        }
        if ($id && $to && $item && $location) {
            $patron = $catalog->patronLogin($this->user->cat_username, $this->user->cat_password);
             // error_log("putHold to Aleph-placeHold patronID:".$patron['id']." id:".$id." item:".$item." location:".$location." to:".$to." comment:".$comment);
            return $catalog->placeHold($patron['id'], $id, $item, $location, $to, $comment);
        } else {
            return new PEAR_Error('Cannot connect to ILS');
        }
    }

}
?>
