<?php

	// AJAX script, check point located in restricted area or no

	$lines = file("../../../wp-config.php");

	foreach ($lines as $line)
	{
		if (strstr($line, 'DB_NAME') || strstr($line, 'DB_USER') || strstr($line, 'DB_PASSWORD') 
			|| strstr($line, 'DB_HOST') || strstr($line, 'table_prefix'))
			eval($line);

	}
	
	$lines = file("class.geosets.php");
	
	foreach ($lines as $line)
	{
		if (strstr($line, 'const DB_USERS_POINTS'))
			eval($line);
	}

	$polygon_table = $table_prefix.DB_USERS_POINTS;

	// init connection to MySQL
	$lnk = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD) or die("cant connect to db ".DB_HOST." ".DB_USER." ".DB_PASSWORD );
	$db = mysql_select_db(DB_NAME, $lnk) or die("cant select db");
    	mysql_query("set names utf8");

	// get input coordinates
	if (isset($_GET['lng']))
		$s_x = 1*$_GET['lng'];
	else
		$s_x = 1*$_POST['lng'];
	
	if (isset($_GET['lat']))
		$s_y = 1*$_GET['lat'];
	else	
		$s_y = 1*$_POST['lat'];

	$res = mysql_query("select id from $polygon_table poly WHERE ST_CONTAINS(poly.points, 
        Point($s_y, $s_x))") or die(mysql_error(''));

	if (mysql_num_rows($res) > 0)
		$response['restricted'] = 1;
	else
		$response['restricted'] = 0;

	echo json_encode($response);
?>
