<!-- SPIN FV-1 Decompiler v.03-->

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>WAV to Spin FV-1</title>
</head>

<body style="background: rgb(192, 199, 209); font-family: 'Courier New', Courier, 'Lucida Sans Typewriter', 'Lucida Typewriter', monospace;">
<div style="margin: 16px;">
<?php 

$module="index.php";				// имя модуля куда отправлять файл через форму.

function extract_get($input) {
    global $input,$debug;
    foreach ($input as $k) {
		if (isset($_REQUEST[$k])) {$GLOBALS[$k]=trim($_REQUEST[$k]);} 
		else {$GLOBALS[$k]="";};
		If ($debug==1) echo "  $k=",$GLOBALS[$k];
    }
}


function hex2string($hex) {
    $string='';
    for ($i=0; $i < strlen($hex)-1; $i+=2)	{
        $string .= chr(hexdec($hex[$i].$hex[$i+1]));
    }
    return $string;
}


function bin_hex8($a){	
	$j = str_pad( strtoupper( dechex(ord($a)) ) ,2,"0",STR_PAD_LEFT); 
	return $j;
}


function bin($a){	
	$j = (ord($a)) ; 
	return $j;
}

function dbg($i){
	$i = substr ( $i , 44 , 126*3);
	$i = str_split($i);
	//var_dump($i);
	$len=count($i);

	for ($x=0; $x < $len; $x+=3) {
		$b = ( ord($i[$x]) + 256*ord($i[$x+1]) + 65536*ord($i[$x+2]) );
		if ( ord($i[$x+2]) > 127) $b =  $b - 16777216; // преобразование в отрицательное 

		$hex24 = bin_hex8($i[$x+2])
			.bin_hex8($i[$x+1])
			.bin_hex8($i[$x+0]);
		$dbl = (double)($b/8388608.0);
		$r .= "0x$hex24 ".$dbl."\r\n";	// int / 2^23
	 }
	return $r;
}


function wav2c32($i) {
	$i = substr ( $i , 44 , 126*3);
	$i = str_split($i);
	$len=count($i);

	for ($x=0; $x < $len; $x+=3) {
		if ( $x%12 == 0) $r .= "\r\n";
		$hex24 = "0x".bin_hex8($i[$x+0]) .",0x". bin_hex8($i[$x+1]) .",0x". bin_hex8($i[$x+2]);
		if ( ord($i[$x+2]) > 127) $hex24 =  $hex24.",0xFF , "; // преобразование в отрицательное 
		else $hex24 = $hex24.",0x00 , ";
		$r .= "$hex24";
	 }
	return $r;
}



function wav2c11($i){
	global $filename;

	$i = substr ( $i , 44 , 126*3);
	$i = str_split($i);

	$len=count($i);
	$mem = 0;
	$sum = 0;
	$wrax_c = "";
	$r = "";

	 for ($x=0; $x < $len; $x+=3) {
		$b1 = ord($i[$x+1]) ; 	$b2 = ord($i[$x+2]) ; 
		$w= $b2*256+$b1;
		$w= $w >>5;
		if ($w >1023)  $k2= -(2048 -$w)/512.0;	// negative
		else $k2 = $w/512.0;

		if ($x == 0) $wrax_c = $k2;
		else $r .= "\tRDA\t$mem , $k2\r\n";
		$mem++;
		$sum += $k2;
	 }
	$r = "; s.shift-line.com/wav2spin\r\n; file:$filename\r\n; SUM of coefficients (max volume, needs tuning) = $sum\r\n;\r\n\tRDAX\tadcl , 1/$sum\r\n\tWRAX\t0 , $wrax_c\r\n".$r;  
	$r .= "\tWRAX\tdacl , 0\r\n";
	return $r;
}



function proceed() {
	global $debug;
	global $filename;
	if (trim($_FILES["FV_FILE"]["name"])=="") echo "File not chosen<br />"; 
	
	if ($_FILES["FV_FILE"]["error"] > 0)  echo "R Tape loading error 0:1" . $_FILES["FV_FILE"]["error"] . "<br />";
	else {
		$filename=$_FILES["FV_FILE"]["name"];
		$tmpname=$_FILES["FV_FILE"]["tmp_name"];
		$ext=substr($filename,-3,3);
		$f=file_get_contents($tmpname);

		echo "\n<br><h4> Results for $filename: </h4>";
		echo "\n<br><br><i>Hint: </i>Ctrl+A , Ctrl+C in results area<br><br>";

		$c16=wav2c11($f);
		echo "\n<br><h4> C source (11 bit of 24 (Spin FV-1)): </h4>";
		echo "\n<textarea   wrap=\"off\" cols=\"100\" rows=\"8\" style=\"background-color: #f8f8ff\" >$c16</textarea><br><br>";

//		$o1 = dbg($f);
//		echo "\n<br><h4> debug info: </h4>";
//		echo "\n<textarea   wrap=\"off\" cols=\"100\" rows=\"8\" style=\"background-color: #f8f8ff\" >$o1</textarea>";
	}
}


// main
echo "\n<br /><br><h3>24bit@32768Hz mono WAV to Spin FV-1 FIR funny converter by Igor. Make your FIR cabsim</h3><br />";
$input = array('action');
extract_get($input); 

echo "Choose .wav file<br><br>\n\n";
echo "<form method=\"post\" action=\"$module\" enctype=\"multipart/form-data\">";
echo "\n<input type=\"file\" name=\"FV_FILE\" size=\"50\" value=\"\" ><br /><br />";
echo "\n<input type=\"submit\" name=\"name1\" value=\"Upload\">"; 
echo "\n<input type=\"hidden\" name=\"action\" value=\"add\">";
echo "</form>\n";
if ($action=="add")  proceed() ;	//2nd start - decompile
?>

<hr>
Change log:<br>
feb-2023 +1 coefficient added , small refactoring (<a href="https://github.com/igorpie/wav2spin">source</a>)<br>
2017 initial idea<br>
</div>
</body>
</html>
