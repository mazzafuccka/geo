<?php

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
	$lnk = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD) or die("cant connect to db");
	$db = mysql_select_db(DB_NAME, $lnk) or die("cant select db");
    	mysql_query("set names utf8");

// A* algorithm by aaz, found at 
// http://althenia.net/svn/stackoverflow/a-star.php?rev=7
// Binary min-heap with element values stored separately

function heap_float(&$heap, &$values, $i, $index) {
    for (; $i; $i = $j) {
        $j = ($i + $i%2)/2 - 1;
        if ($values[$heap[$j]] < $values[$index])
            break;
        $heap[$i] = $heap[$j];
    }
    $heap[$i] = $index;
}

function heap_push(&$heap, &$values, $index) {
    heap_float($heap, $values, count($heap), $index);
}

function heap_raise(&$heap, &$values, $index) {
    heap_float($heap, $values, array_search($index, $heap), $index);
}

function heap_pop(&$heap, &$values) {
    $front = $heap[0];
    $index = array_pop($heap);
    $n = count($heap);
    if ($n) {
        for ($i = 0;; $i = $j) {
            $j = $i*2 + 1;
            if ($j >= $n)
                break;
            if ($j+1 < $n && $values[$heap[$j+1]] < $values[$heap[$j]])
                ++$j;
            if ($values[$index] < $values[$heap[$j]])
                break;
            $heap[$i] = $heap[$j];
        }
        $heap[$i] = $index;
    }
    return $front;
}


// A-star algorithm:
//   $start, $target - node indexes
//   $neighbors($i)     - map of neighbor index => step cost
//   $heuristic($i, $j) - minimum cost between $i and $j

function a_star($start, $target, $neighbors, $heuristic) {
    $open_heap = array($start); // binary min-heap of indexes with values in $f
    $open      = array($start => TRUE); // set of indexes
    $closed    = array();               // set of indexes

    $g[$start] = 0;
    $h[$start] = heuristic($start, $target);
    $f[$start] = $g[$start] + $h[$start];

    while ($open) {
        $i = heap_pop($open_heap, $f);
        unset($open[$i]);
        $closed[$i] = TRUE;

        if ($i == $target) {
            $path = array();
            for (; $i != $start; $i = $from[$i])
                $path[] = $i;
            return array_reverse($path);
        }

        foreach (neighbors($i) as $j => $step)
            if (!array_key_exists($j, $closed))
                if (!array_key_exists($j, $open) || $g[$i] + $step < $g[$j]) {
                    $g[$j] = $g[$i] + $step;
                    $h[$j] = heuristic($j, $target);
                    $f[$j] = $g[$j] + $h[$j];
                    $from[$j] = $i;

                    if (!array_key_exists($j, $open)) {
                        $open[$j] = TRUE;
                        heap_push($open_heap, $f, $j);
                    } else
                        heap_raise($open_heap, $f, $j);
                }
    }

    return FALSE;
}


// Example with maze

$width  = 40;
$height = 40;

$pregrad = 0;

$map = array_fill(0, $height, str_repeat(' ', $width));

function node($x, $y) {
    global $width;
    return $y * $width + $x;
}

function coord($i) {
    global $width;
    $x = $i % $width;
    $y = ($i - $x) / $width;
    return array($x, $y);
}

function neighbors($i) {
    global $map, $width, $height;
    list ($x, $y) = coord($i);
    $nbr = array();
    if ($x-1 >= 0      && $map[$y][$x-1] == ' ') $nbr[node($x-1, $y)] = 1;    
    if ($x+1 < $width  && $map[$y][$x+1] == ' ') $nbr[node($x+1, $y)] = 1;

    if ($x+1 < $width && $y+1<$height  && $map[$y+1][$x+1] == ' ') $nbr[node($x+1, $y+1)] = 1;
    if ($x+1 < $width && $y-1>0 && $map[$y-1][$x+1] == ' ') $nbr[node($x+1, $y-1)] = 1;

    if ($x-1 > 0 && $y+1<$height  && $map[$y+1][$x-1] == ' ') $nbr[node($x-1, $y+1)] = 1;
    if ($x-1 > 0 && $y-1>0  && $map[$y-1][$x-1] == ' ') $nbr[node($x-1, $y-1)] = 1;
    
    if ($y-1 >= 0      && $map[$y-1][$x] == ' ') $nbr[node($x, $y-1)] = 1;
    if ($y+1 < $height && $map[$y+1][$x] == ' ') $nbr[node($x, $y+1)] = 1;
    return $nbr;
}

function heuristic($i, $j) {
    list ($i_x, $i_y) = coord($i);
    list ($j_x, $j_y) = coord($j);
    return sqrt( ($i_x - $j_x)*($i_x - $j_x) + ($i_y - $j_y)*($i_y - $j_y) );
}

// fill the grid
function generate($b_x, $b_y) {

	global $map, $width, $height, $pregrad, $polygon_table;

	$tbl_ind = rand(1000,9999);	
	$res = mysql_query("create table tmp_points_".$tbl_ind.
		" (
		id int(12),
                lat decimal(7,4),
                lng decimal(7,4),
                x int(7),
                y int(7)
		)") or die(mysql_error());

	// $sql = "SELECT points.name FROM polygons, points WHERE ST_CONTAINS(polygons.geom, 
        // Point(points.longitude, points.latitude)) AND polygons.name = 'California'";

	$lng = $b_x;
	$lat = $b_y;
	
        for ($i=0; $i<$width; $i++)
	{
		$cur_lng = $lng + 0.1*$i;

		for ($k=0;$k<$height; $k++)
		{
			$cur_lat = $lat + 0.1*$k;
			mysql_query("insert into tmp_points_".$tbl_ind." values (NULL, $cur_lat, $cur_lng, $i, $k);");
		}	
	}

	// now get list of all points what are not passable;
	$res = mysql_query("select x, y, lat, lng from ".$polygon_table." poly, tmp_points_".$tbl_ind." pnt WHERE ST_CONTAINS(poly.points, 
        Point(pnt.lat, pnt.lng))") or die(mysql_error());
	
	for ($i=0; $i<mysql_num_rows($res); $i++)
	{
		$x = mysql_result($res, $i, 0);
		$y = mysql_result($res, $i, 1);
		$pregrad++;
		$map[$y][$x] = 'A'; // mark as non-passable 
	}
	
	mysql_query("drop table tmp_points_".$tbl_ind);
}

	// get input coordinates
	if (isset($_GET['start_x']))
		$s_x = 1*$_GET['start_x'];
	else
		$s_x = 1*$_POST['start_x'];
	
	if (isset($_GET['start_y']))
		$s_y = 1*$_GET['start_y'];
	else	
		$s_y = 1*$_POST['start_y'];
	
	if (isset($_GET['end_x']))
		$e_x = 1*$_GET['end_x'];
	else	
		$e_x = 1*$_POST['end_x'];
	
	if (isset($_GET['end_y']))
		$e_y = 1*$_GET['end_y'];
	else
		$e_y = 1*$_POST['end_y'];

	// calculate coordinates base
	if ($s_x>0 && $e_x>0 && $s_x < $e_x)
   		$base_x = $s_x - 1;
	if ($s_x>0 && $e_x>0 && $s_x > $e_x)
   		$base_x = $e_x - 1;

	if ($s_y>0 && $e_y>0 && $s_y < $e_y)
   		$base_y = $s_y - 1;
	if ($s_y>0 && $e_y>0 && $s_y > $e_y)
   		$base_y = $e_y - 1;

	// generate grid 
	generate($base_x, $base_y);

	$start_x = round(($s_x - $base_x)/0.1);
	$start_y = round(($s_y - $base_y)/0.1);

	$end_x = round(($e_x - $base_x)/0.1);
	$end_y = round(($e_y - $base_y)/0.1);
	
	$start  = node($start_x, $start_y);
	$target = node($end_x, $end_y);

	$path = a_star($start, $target);
	// print_r($path);

	array_unshift($path, $start);
	$response = [];
	$ress['lat'] = $start_y;
	$ress['lng'] = $start_x;
	array_push($response, $ress);

	foreach ($path as $i) {
    		list ($x, $y) = coord($i);

		$clng = $base_x + $x*0.1;
		$clat = $base_y + $y*0.1;

		$ress['lat'] = $clng;
		$ress['lng'] = $clat;  
 		
		array_push($response, $ress);
    		$map[$y][$x] = '*';
	}

	$ress['lat'] = $end_y;
	$ress['lng'] = $end_x;
	print "pregrad: $pregrad<br>\n";
	array_push($response, $ress);
	
	print json_encode($response);

?>

