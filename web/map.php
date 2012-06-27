<?php

	$lcc = $_REQUEST['lcc'];
	//echo $lcc;

	if (empty($lcc)){
		echo "<br><br><br><br><br><br><br><br><br><br><br><br>";
		echo "<h3><p style='text-align: center'>Dokument je ve skladu.</p></h3>";
	}else {
			
	echo "<!DOCTYPE html>";
	echo "<head>";
	echo "<meta http-equiv='content-type' content='text/html'/>";
	echo "</head>";
	echo "<html style='height: 100%; background: url(http://www.techlib.cz/user-actions/get-location-image/lcc/$lcc) center center no-repeat;'>";
	
	echo "</html>";

	}

?>





