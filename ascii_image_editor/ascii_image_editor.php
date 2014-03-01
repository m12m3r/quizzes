<?php

/**
 * Technical Test for Etsy
 * @date 2014-02-13
 * @author Mark Shehata m12m3r@yahoo.com
 */

$image = array();     //the current image

$visited = array();   // used in fill_region function to quickly skip the pixels
$original_color = ""; // used in fill_region function to save the orignal color of X, Y pixel
       
start_cmd(); 
while (FALSE !== ($line = fgets(STDIN))) {
	$cmd_and_args = parse($line);
	if(!empty($cmd_and_args)){
		$result = validate($cmd_and_args);
    	if(!$result['error']){
			run($cmd_and_args);
		} else {
		   echo "{$result['msg']}\n\n";
		   print_help();
		}	
	}
	start_cmd(); 
}

/*
 * Start of Comamnd Line
 */

function start_cmd()
{
  echo ">";
}

/*
 * Parse a line and returns an array of cmd and args
 * 
 * @param string $line
 * @return array $cmdArray
 * 
 */

function parse($line)
{
	$cmd_array = array();
	$line = trim($line);
 	if(empty($line)){
    	return $cmd_array;
 	}
 	$cmd_array = explode(' ', $line);
	$cmd_array = array_values(array_filter($cmd_array));
 	$cmd_array = array_map('trim', $cmd_array);
 	return $cmd_array;
}

/*
 * validate the command and the args and returns error flag with error msg if any
 * 
 * @param array $cmd_array
 * @return array
 * 
 */

function validate($cmd_array)
{
	global $image;
	
	$error = false;
	$msg = "";	
  	$valid_cmds = array("I" => true, "C" => true, "L" => true, "V" => true, "H" => true, "F"=> true, "S" => true, "X" => true, "help" => true);
	$cmd_arg_count = array("I" => 2, "C" => 0, "L" => 3, "V" => 4, "H" => 4, "F"=> 3, "S" => 0, "X" => 0, "help" => 0);
	$cmd = $cmd_array[0];
  	if(!isset($valid_cmds[$cmd])){
		$error = true;
		$msg = "$cmd: command not found";
  	} elseif (count($cmd_array) < $cmd_arg_count[$cmd]+1){
  		$error = true;
		$msg = "$cmd: Invalid number of arguments";
  	} elseif(empty($image) && ($cmd == "L" || $cmd == "F" || $cmd == "H" || $cmd == "V")){
  		$error = true;
		$msg = "please create image first before using $cmd";
  	} elseif($cmd_arg_count[$cmd] > 0){		
  		$numeric_args = array();	
  		if($cmd == "I" || $cmd == "L" || $cmd == "F"){
  			$numeric_args = array($cmd_array[1], $cmd_array[2]);	
		} elseif($cmd == "V" || $cmd == "H"){
			$numeric_args = array($cmd_array[1], $cmd_array[2], $cmd_array[3]); 
		}
		if(!is_positive_int($numeric_args)){
			$error = true;
			$msg = "Please check numeric argumnets - should be postive, integer and at most 250";
		} 
  	} 
  	return array('error' => $error, 'msg' => $msg);
}

/*
 * Check if any array has postive integer numbers and not more than 250
 * 
 * @param array $check
 * @return boolean $no_error
 * 
 */

function is_positive_int(array $check){
	$no_error = true;
	foreach($check as $input){
		if(!is_numeric($input) || intval($input) != $input || $input <= 0 || $input > 250){
			$no_error = false;
			break;
		}
	}
	return $no_error;
}

/*
 * run the cmd 
 * 
 * @param array $cmd_array
 * 
 */

function run($cmd_array)
{
	global $image;
	switch($cmd_array[0]){
		case 'I':
		  $image = create_image($cmd_array[1], $cmd_array[2]);
		  break;
		case 'C':
		  clear();
		  break;
		case 'L':
		  set_pixel_color($cmd_array[1], $cmd_array[2], $cmd_array[3]);
		  break;
		case 'V':
		  set_vertical_segment_color($cmd_array[1], $cmd_array[2], $cmd_array[3], $cmd_array[4]);
		  break;
		case 'H':
		  set_horizontal_segment_color($cmd_array[3], $cmd_array[1], $cmd_array[2], $cmd_array[4]);
		  break;	
		case 'F':
		  fill_region($cmd_array[1], $cmd_array[2], $cmd_array[3]);
		  break;
		case 'S':
		  show_on_screen();
		  break;
		case 'help':
		  print_help();
		  break;
		case 'X':
		  echo "Good Bye.\n";	
		  exit;
	}
}

/*
 * Create an image , take columns, rows count and returns a white (O) image
 * 
 * @param int $col
 * @param int $row
 * @return array $image
 * 
 */

function create_image($col, $row)
{
	$image = array();
  	for($i=0;$i<$row;$i++){
		for($j=0;$j<$col;$j++){
			$image[$i][$j] = 'O';
		}
  	}
  	return $image; 
}

/*
 * change the color of vertical segment in the image
 * 
 * @param int $col
 * @param int $start
 * @param int $end
 * @param string $color 
 * 
 */

function set_vertical_segment_color($col, $start, $end, $color)
{
	global $image;
	
	for($i = $start-1; $i<$end; $i++){
		if(isset($image[$i][$col-1])){	
			$image[$i][$col-1] = $color;
		}	
	}
}

/*
 * change the color of horizontal segment in the image
 * 
 * @param int $row
 * @param int $start
 * @param int $end
 * @param string $color 
 * 
 */

function set_horizontal_segment_color($row, $start, $end, $color)
{
	global $image;
	
	for($i = $start-1; $i<$end; $i++){
		if(isset($image[$row-1][$i])){
			$image[$row-1][$i] = $color;	
		}
	}
}

/*
 * change the color of a region 
 * 
 * @param int $x : col number
 * @param int $y : row number
 * @param string $color 
 * 
 */
 
function fill_region($x, $y, $color)
{
  	global $image, $original_color, $visited;

	// if the pixel is outside the image pixels range, do nothing
	if(isset($image[$y-1][$x-1])){
		$original_color = get_color($x, $y) ;
  		set_all_neighbour_pixels_w_same_color($x, $y, $color);
	}
  	
	$visited = array();
	$original_color = "";
}

/*
 * set the color (recursively) of all neighbors pixels with the same color and same region of $x, $y to $color
 * 
 * @param int $x : col number
 * @param int $y : row number
 * @param string $color 
 * 
 */ 

function set_all_neighbour_pixels_w_same_color($x, $y, $color)
{
	$pixels = get_pixels_around_w_same_original_color($x, $y, $color);
  	if(!empty($pixels)){
        foreach($pixels as $pixel){
			set_all_neighbour_pixels_w_same_color($pixel[1], $pixel[0], $color);
		}
   	} 
}

/*
 * get all pixels a round $x, $y pixels with the same color and then set it to the new $color
 * 
 * @param int $x : col number
 * @param int $y : row number
 * @param string $color 
 * @return $pixels
 * 
 */ 

function get_pixels_around_w_same_original_color($x, $y, $color)
{
	global $image, $visited, $original_color;
	
	$pixels = array();
	
  	$col_start = ($x == 1) ? 0 : ($x - 2);
  	$col_end = isset($image[$y-1][$x]) ? $x : $x-1;

  	$row_start = ($y == 1) ? 0 : ($y - 2);
  	$row_end = isset($image[$y][$x-1]) ? $y : $y-1;

  	for($i = $row_start;$i<=$row_end;$i++){
  		for($j = $col_start; $j<=$col_end;$j++){
        	if(isset($visited[$i+1][$j+1])) continue;
			
			if($image[$i][$j] == $original_color){
				set_pixel_color($j+1, $i+1, $color);
				$pixels[] = array($i+1, $j+1);
			}
        	$visited[$i+1][$j+1] = TRUE;
    	 }
  	}
 	return $pixels;
}

/*
 * Print the image on the screen
 * 
 */

function show_on_screen()
{
	global $image;
	echo "=>\n";
	foreach($image as $i => $line){
		foreach($line as $j => $color){
			echo "$color";
		}
		echo "\n";
	}
}

/*
 * Get the color of one pixel
 * 
 * @param int $x : col number
 * @param int $y : row number
 * @return string $color
 * 
 */

function get_color($x, $y)
{
	global $image;
	
	return $image[$y-1][$x-1];
}

/*
 * Set the color of one pixel, if no color specified it is white
 * 
 * @param int $x : col number
 * @param int $y : row number
 * @param string $color
 * 
 */

function set_pixel_color($x, $y, $c = "O")
{
	global $image;
	
	if(isset($image[$y-1][$x-1])){
		$image[$y-1][$x-1] = $c;
	}
	return $image;
}

/*
 * Clear the image - it resets the color of all th epixels to white "O"
 * 
 */

function clear()
{
	global $image;
	
	foreach($image as $i => $line){
		foreach($line as $j => $color){
			set_pixel_color($j+1, $i+1);
		}
	}

}

/*
 * Print help on the screen 
 * 
 */

function print_help()
{
    $help_array = array("I M N"       => "Create a new M x N image",
	                    "C"           => "Clears the image",
	                    "L X Y C"     => "Colors the pixel (X,Y) with color C.",
	                    "V X Y1 Y2 C" => "Draw a vertical segment of color C in column X between rows Y1 and Y2", 	
		                "H X1 X2 Y C" => "Draw a horizontal segment of color C in row Y between columns X1 and X2",
		                "F X Y C"     => "Fill the region R with the color C",
		                "S"           => "Show the contents of the current image.",
                        "X"	          => "Terminate the session",
						"help"		  => "Show this message");
    $max_cmd_len = 0;
    foreach($help_array as $cmd => $msg){
	$cmd_len = strlen($cmd);
        if($cmd_len > $max_cmd_len){
	    $max_cmd_len = $cmd_len;
        }
    }

    echo "Help:\n";
    foreach($help_array as $cmd => $msg){
	echo str_pad($cmd, $max_cmd_len, " ") . "\t{$msg}\n";
    }
}

?>
