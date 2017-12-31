<?php

//curl ini
$ch = curl_init();
curl_setopt($ch, CURLOPT_HEADER,0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT,20);
curl_setopt($ch, CURLOPT_REFERER, 'http://www.bing.com/');
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8');
curl_setopt($ch, CURLOPT_MAXREDIRS, 5); // Good leeway for redirections.
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // Many login forms redirect at least once.
curl_setopt($ch, CURLOPT_COOKIEJAR , "cookie.txt");

//curl get
$x='error';
$url='http://deandev.com/php/debug.php';
curl_setopt($ch, CURLOPT_HTTPGET, 1);
curl_setopt($ch, CURLOPT_URL, trim($url));
 
$headers = array();
//$headers[] = "Host: www.google.nl";
$headers[] = "User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:46.0) Gecko/20100101 Firefox/46.0";
$headers[] = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
$headers[] = "Accept-Language: en-US,en;q=0.5";
//$headers[] = "Accept-Encoding: gzip, deflate, br";
$headers[] = "Referer: https://www.google.nl";
$headers[] = "Cookie: NID=79=C0nQBcPOoM5y_CQFZWhhmImDfiAbu5rMRcnhLsHfzg8lcPNmvCx6uHKrezZqT-z6VEmu2fhRGOf5-f5LAnHUxt1eIV8KUfcEmP5eY-uXHRHdfNiY10HtPnLLirygVooX; CONSENT=WP.2534a4; OGPC=5062106-1:";
$headers[] = "Connection: keep-alive";

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$exec=curl_exec($ch);
$x=curl_error($ch);
echo $exec.$x;