<!DOCTYPE html>
<html lang = "en-US">
<head>
<title>Distance Calculator (Zip Code)</title>
<meta charset = "UTF-8"/>

<?php
// The file test.xml contains an XML document with a root element
// and at least an element /[root]/title.

$the_request = null;
$input_the_method = null;

// This check excludes 'HEAD' and 'PUT'.
if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
 $the_request = &$_POST;
 $input_the_method = INPUT_POST;
}
elseif ($_SERVER['REQUEST_METHOD'] === 'GET')
{
 $the_request = &$_GET;
 $input_the_method = INPUT_GET;
}

function IsHiddenValue($f_hidden_value)
{
 global $the_request;
	if (!is_null($the_request))
	{
		if (isset($the_request['hidden_form_name']))
		{
			if ($the_request['hidden_form_name'] === $f_hidden_value)
			{return true;}
		}
	}
 return false;
}

// Declare all variables that are defined in the HTML body section (bottom of file) to avoid ugly undefined warnings.
$distance = 0;
$inputfrom = "";
$inputto = "";
$googledistance = 0;
$googledistancebool = false;
$googleFromExists = false;
$googleToExists = false;
$XMLFromExists = false;
$XMLToExists = false;
$url = "";
//$url_key = "";
$XML_file_name = 'ZipCodeData.xml';

function is_connected()
{
 $connected = @fsockopen("www.maps.googleapis.com", 80); //website, port  (try 80 or 443)
	if ($connected)
	{
	 $is_conn = true; //action when connected
	 fclose($connected);
    }
	else
	{$is_conn = false;} //action in connection failure
 return $is_conn;
}

// These two functions aid in getting a precise decimal value for pi.
function bcfact($n)
{return ($n === 0 || $n === 1) ? 1 : bcmul($n,bcfact($n-1));}

function bcpi($precision)
{
    $num = 0;$k = 0;
    bcscale($precision+3);
    $limit = ($precision+3)/14;
    while($k < $limit)
    {
        $num = bcadd($num, bcdiv(bcmul(bcadd('13591409',bcmul('545140134', $k)),bcmul(bcpow(-1, $k), bcfact(6*$k))),bcmul(bcmul(bcpow('640320',3*$k+1),bcsqrt('640320')), bcmul(bcfact(3*$k), bcpow(bcfact($k),3)))));
        ++$k;
    }
    return bcdiv(1,(bcmul(12,($num))),$precision);
}

// Load this big file that stores most of the location information for each zip code in the Continental United States,
// and load it as sooon as possible so when the submit button is pressed, it can already be loaded.
if (file_exists($XML_file_name))
{$XML_file_data = simplexml_load_file($XML_file_name);}

if (IsHiddenValue("zipform_value")) // check if form was submitted and start trying to calcualate the distance.
{
$inputfrom = filter_input($input_the_method, 'fromzip', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$inputto = filter_input($input_the_method, 'tozip', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

$inputfrom = preg_replace(
    "/(\t|\n|\v|\f|\r| |\xC2\x85|\xc2\xa0|\xe1\xa0\x8e|\xe2\x80[\x80-\x8D]|\xe2\x80\xa8|\xe2\x80\xa9|\xe2\x80\xaF|\xe2\x81\x9f|\xe2\x81\xa0|\xe3\x80\x80|\xef\xbb\xbf)+/",
    "",
    $inputfrom
);

$inputto = preg_replace(
    "/(\t|\n|\v|\f|\r| |\xC2\x85|\xc2\xa0|\xe1\xa0\x8e|\xe2\x80[\x80-\x8D]|\xe2\x80\xa8|\xe2\x80\xa9|\xe2\x80\xaF|\xe2\x81\x9f|\xe2\x81\xa0|\xe3\x80\x80|\xef\xbb\xbf)+/",
    "",
    $inputto
);

// LOCAL XML FILE SECTION ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if (file_exists($XML_file_name))
{
	foreach($XML_file_data->ZipCode as $checkzip) // Checks to make sure both zip codes are listed in XML file.
	{
		if ((string) $checkzip->Code === $inputfrom)
		{
			$LongitudeFrom = $checkzip->Longitude;
			$LatitudeFrom = $checkzip->Latitude;
			$XMLFromExists = true;
		}
		
		if ((string) $checkzip->Code === $inputto)
		{
			$LongitudeTo = $checkzip->Longitude;
			$LatitudeTo = $checkzip->Latitude;
			$XMLToExists = true;
		}
	}
	
if ($XMLFromExists === false and $XMLToExists === false)
{echo 'Neither zip codes matched any stored locations in the XML file.<br>';}
  elseif ($XMLFromExists === false and $XMLToExists === true)
  {echo 'The beginning zip code does not match any stored location in the XML file.<br>';}
    elseif ($XMLFromExists === true and $XMLToExists === false)
	{echo 'The ending zip code does not match any stored location in the XML file.<br>';}
		else
		{
		    $earthsRadius = 3956.087107103049;
            $latitude1Radians = (bcdiv(strval($LatitudeFrom),strval(180),15)) * bcpi(14);
            $longitude1Radians = (bcdiv(strval($LongitudeFrom),strval(180),15)) * bcpi(14);
            $latitude2Radians = (bcdiv(strval($LatitudeTo),strval(180),15)) * bcpi(14);
            $longitude2Radians = (bcdiv(strval($LongitudeTo),strval(180),15)) * bcpi(14);

            $distance = ($earthsRadius * 2) *
            asin(
            sqrt(
            pow(
            sin(($latitude1Radians - 
                 $latitude2Radians) / 2), 2) +
            cos($latitude1Radians) *
            cos($latitude2Radians) *
            pow(
            sin(($longitude1Radians - 
                 $longitude2Radians) / 2), 2)
            ));
			
			$distance = round($distance, 2, PHP_ROUND_HALF_UP);
		}
}
else
{exit("Failed to open $XML_file_name.");}
// END LOCAL XML FILE SECTION ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// GOOGLE API SECTION /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			if (is_connected()) // No need to try continuing with these steps if you're not even connected to the interent!
			{
				$url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=$inputfrom&destinations=$inputto&mode=driving&language=en-EN&sensor=false&units=imperial";
				//$url_key = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=$inputfrom&destinations=$inputto&mode=driving&language=en-EN&sensor=false&units=imperial&key=YOUR_KEY";
				$data = @file_get_contents($url); // $data = @file_get_contents($url_key);
				$result = json_decode($data, true);
				
				if ($result !== null && json_last_error() === JSON_ERROR_NONE) // Is there actually any information in JSON format from the URL that we requested?
				{
					if (isset($result['rows'][0]['elements'][0]['distance']['text'])) // This will only be true if Google recognizes the zip code and returns the 'distance' in 'text'.
					{
					 $googledistance = preg_replace("/[^0-9.]/", "", $result['rows'][0]['elements'][0]['distance']['text']); // store JUST the number (take out commas, letters, spaces etc.)
					 $googledistancebool = true;
					 $googleFromExists = true;
					 $googleToExists = true;
					}
					else
					{
						if (isset($result['origin_addresses'][0]) && isset($result['destination_addresses'][0]))
						{
							if (($result['origin_addresses'][0] === "") && ($result['destination_addresses'][0] === ""))
							{echo 'Google did not recognize the origin or destination address.<br>';}
							elseif (($result['origin_addresses'][0] === "") && ($result['destination_addresses'][0] !== ""))
							{
							 $googleToExists = true;
							 echo 'Google did not recognize the origin address.<br>';
							}
							elseif (($result['destination_addresses'][0] === "") && ($result['origin_addresses'][0] !== ""))
							{
							 $googleFromExists = true;
							 echo 'Google did not recognize the destination address.<br>';
							}
						}
						else // If either origin or destination addresses are left blank, Google decides not to send back any data at all, even if one of the addresses was valid!
						{
							if (($inputfrom === "") && ($inputto === ""))
							{echo 'Google: Both the origin and destination address is blank.<br>';}
							elseif (($inputfrom === "") && ($inputto !== ""))
							{
								$url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=$inputto&destinations=$inputto&mode=driving&language=en-EN&sensor=false&units=imperial";
								//$url_key = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=$inputto&destinations=$inputto&mode=driving&language=en-EN&sensor=false&units=imperial&key=YOUR_KEY";
								$data = @file_get_contents($url); // $data = @file_get_contents($url_key);
								$result = json_decode($data, true);
								if ($result !== null && json_last_error() === JSON_ERROR_NONE) // Is there actually any information in JSON format from the URL that we requested?
								{
									if (isset($result['rows'][0]['elements'][0]['distance']['text'])) // This will only be true if Google recognizes the zip code and returns the 'distance' in 'text'.
									{$googleToExists = true;}
								}
								if ($googleToExists === true)
								{echo 'Google: The origin address is blank.<br>';}
								else
								{echo 'Google: The origin address is blank and the destination address was not recognized by Google.';}
							}
							elseif (($inputfrom !== "") && ($inputto === ""))
							{
								$url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=$inputfrom&destinations=$inputfrom&mode=driving&language=en-EN&sensor=false&units=imperial";
								//$url_key = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=$inputfrom&destinations=$inputfrom&mode=driving&language=en-EN&sensor=false&units=imperial&key=YOUR_KEY";
								$data = @file_get_contents($url); // $data = @file_get_contents($url_key);
								$result = json_decode($data, true);
								if ($result !== null && json_last_error() === JSON_ERROR_NONE) // Is there actually any information in JSON format from the URL that we requested?
								{
									if (isset($result['rows'][0]['elements'][0]['distance']['text'])) // This will only be true if Google recognizes the zip code and returns the 'distance' in 'text'.
									{$googleFromExists = true;}
								}
								if ($googleFromExists === true)
								{echo 'Google: The destination address is blank.<br>';}
								else
								{echo 'Google: The destination address is blank and the origin address was not recognized by Google.<br>';}
							}
						}
					}
				}
				else
				{echo "There was a problem retrieving information from Google's servers.<br>The website might have changed.<br>";}
			}
			else
			{echo 'Can only calculate driving distance when connected to the internet. Please check your connection.<br>';}
// END GOOGLE API SECTION ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

}
?>

<script type = "text/javascript">
// These are declarations of global variables for use with   http://www.jslint.com/
/*global window */
/*global document */
/*global alert */

// This is not necessary, but helps catch errors if they occur. This must remain above all other source code (excluding comments) if used.
"use strict";

// This function returns the element passed to it by using its ID. It's used to simply improve the efficiency of coding event handlers.
function $(id)
{return document.getElementById(id);}

// This funtion will run when the page fully loads, and without causing any errors.
function afterAllLoadsGoGoGo()
{
 $('submitbutton_1').disabled = false;
 getfocus();
}

function getfocus()
{
 <?php
	if (($googleFromExists === true && $googleToExists === false && !($XMLFromExists === false && $XMLToExists === true)) ||
	   ($XMLFromExists === true && $XMLToExists === false && !($googleFromExists === false && $googleToExists === true)))
	{echo "$('tozip').focus();\n";}
	else
	{echo "$('fromzip').focus();\n";}
 ?>
}

function disableSubmit1(thisform)
{
 $('submitbutton_1').disabled = true;
 //thisform.action = "<?php echo htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES, "UTF-8"); ?>"; // this action attribute can be changed to any existing PHP file.
 thisform.submit();
}

// This is used to make sure the correct function (onload or load) is used and appended correctly, instead of recreating it (which can cause errors).
if (window.attachEvent)
{window.attachEvent('onload', afterAllLoadsGoGoGo);}
else if (window.addEventListener)
{window.addEventListener('load', afterAllLoadsGoGoGo, false);}
else
{document.addEventListener('load', afterAllLoadsGoGoGo, false);}
</script>

</head>
<body>
<noscript>
<div style="border: 1px solid purple; padding: 10px">
<span style="color:red">JavaScript is not enabled! This page needs JavaScript in order to function.</span>
</div>
</noscript>
<form id="zipform" method="post" action="" onsubmit="disableSubmit1(this)">
  <input type="hidden" name="hidden_form_name" value="zipform_value"/>
  <label for="fromzip">From: </label>
  <input type="text" name="fromzip" autocomplete="off" id="fromzip" value="<?php if ($googleFromExists || $XMLFromExists) echo $inputfrom; ?>"/>
  <br>
  <label for="tozip">To: </label>
  <input type="text" name="tozip" autocomplete="off" id="tozip" value="<?php if ($googleToExists || $XMLToExists) echo $inputto; ?>"/>
  <br>
  <label for='crowdistance'>Crow Distance: </label>
  <td><input type='text' id='crowdistance' value="<?php if ($XMLFromExists === true && $XMLToExists === true) echo $distance; ?>" readonly ="true" style="cursor:text;"/></td>
  <br>
  <label for="drivedistance">Driving Distance: </label>
  <td><input type='text' id='drivedistance' value="<?php if ($googledistancebool === true) echo $googledistance; ?>" readonly ="true" style="cursor:text;"/></td>
  <br>
  <input type="submit" id="submitbutton_1" name="SubmitButton" value="Submit" disabled="disabled"/>
</form>

</body>
</html>