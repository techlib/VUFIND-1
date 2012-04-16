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
 * author:	Daniel Mareček (National Technical Library CZE, Prague)
 * date:	4/2012
 * description:	Get pictures and their thumbnails from 'aleph-url-with-sysno' and assign these arrays to template. 	
 */

require_once 'Record.php';


class Preview extends Record
{
    function launch()
    {
        global $interface;

        // Do not cache preview's page
        $interface->caching = 0;

	// Set title of page
        $interface->setPageTitle(translate('Preview'). ': ' . $this->recordDriver->getBreadcrumb());

        // Get Pictures
        $id = $this->recordDriver->getUniqueID();
	
		// Links with pictures on this site
		$addr = 'http://aleph.techlib.cz/cgi-bin/obrazek.pl?sn='.$id;
		$links = file_get_contents( $addr );
	
		// Pattern starts with "http" and ends with ".jpg" or ".JPG"
		$pattern = '/http.{0,100}\.(JPG|jpg)/';

		// Each link in 2D-array named url
		$count = preg_match_all( $pattern, $links, $url);

		// One array for small and one for big pics
		$pics= array();
		$thumbs= array();

		for ($i=0; $i<$count; $i++){	
		
			// is this thumbnail ?
			if (strpos($url[0][$i], 'thumb')){
				$thumbs[$i]=$url[0][$i];			
			}else{
				$pics[$i]=$url[0][$i];
			}
		}

	// Alphabetical sorting
	sort($pics);
	sort($thumbs);

	$interface->assign('pics', $pics);
	$interface->assign('thumbs', $thumbs);
        
	// Link with templates
        $interface->assign('subTemplate', 'preview.tpl');
        $interface->setTemplate('view.tpl');
        
        // Display Page
        $interface->display('layout.tpl');
    }
}

?>

