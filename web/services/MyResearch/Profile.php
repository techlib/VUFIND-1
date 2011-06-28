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

require_once 'services/MyResearch/MyResearch.php';

class Profile extends MyResearch
{
    function launch()
    {
        global $configArray;
        global $interface;
        global $user;

        // Get My Profile
        if ($patron = UserAccount::catalogLogin()) {
            if (isset($_POST['home_library']) &&  $_POST['home_library'] != "") {
                $home_library = $_POST['home_library'];
                $updateProfile = $user->changeHomeLibrary($home_library);
                if ($updateProfile == true) {
                    $interface->assign('userMsg', 'profile_update');
                }
            }
            $result = $this->catalog->getMyProfile($patron);
            if (!PEAR::isError($result)) {
                $result['home_library'] = $user->home_library;
                $libs = $this->catalog->getPickUpLocations($patron);
                $defaultPickUpLocation 
                    = $this->catalog->getDefaultPickUpLocation($patron);
                $interface->assign('defaultPickUpLocation', $defaultPickUpLocation);
                $interface->assign('pickup', $libs);
                $interface->assign('profile', $result);
            }
        }

        $interface->setTemplate('profile.tpl');
        $interface->setPageTitle('My Profile');
        $interface->display('layout.tpl');
    }
    
}

?>