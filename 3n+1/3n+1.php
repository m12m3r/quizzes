<?php

$seq = array();

if(count($argv) <= 1){
	echo "Missing argument: Please pass an input file\n";
	exit;
}

$file = $argv[1];
if(!file_exists($file)){
	echo "File not found: $file \n";
	exit;
}

$fh = fopen("output", "w");

$content_array = file($file);
foreach($content_array as $line){
	if($line_elements = parse_line($line)){
	    $max_count = 0;
		$i = $line_elements[0];
		$j = $line_elements[1];
		for($i;$i<=$j;$i++){
			set_conjecture_sequence($i);
			$seq_count = count($seq);
			clear_seq();
			if($max_count < $seq_count){
				$max_count = $seq_count;
			}
		}	
		write_to_file($line_elements[0], $line_elements[1], $max_count);	
	}
}

if(file_exists("output")){
	echo "Check the result in 'output' \n";
	exit;
}

function write_to_file($fn, $sn, $max_count)
{
	global $fh;
	fwrite($fh, "{$fn} {$sn} {$max_count}\n");

}

function parse_line($line)
{
  $line = trim($line);
  if(empty($line)){
  	return false;
  } else {
  	$line_array = explode(" ", $line);
  	if(count($line_array) == 2 && is_int((int) $line_array[0]) && is_int((int) $line_array[1])){
  		return $line_array;	
  	} else{
  		return false;
  	}
  }
}

function set_conjecture_sequence($num)
{
	global $seq;
	$seq[] = $num;
	
	if($num == 1){
		return;
	}
	
	if($num % 2 == 0){
		$num = $num / 2;
	} else{
		$num = $num * 3 + 1;
	}
	set_conjecture_sequence($num);
}

function clear_seq()
{
	global $seq;
	$seq = array();
}

?>