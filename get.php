<?php

make_list();

$rand = mt_rand(0,7);
$url = 'http://c'.$rand.'.xkcd.com/stream/comic/landing?method=EventSource';

$url = parse_url($url);

$sock = fsockopen($url['host'], 80, $errno, $errstr, 180);
if (!$sock) die("failed to connect\n");

fwrite($sock, 'GET '.$url['path'].'?'.$url['query'].' HTTP/1.0'."\r\n");
fwrite($sock, "User-Agent: MagicalTux (compatible; yeah right; http://xkcd1446.org/)\r\n\r\n");

// heartbeat timeout is 45 secs in http://xkcd.com/s/bb3cbd.js
stream_set_timeout($sock, 50); // add 5 secs just in case

$ev = ['event' => 'http_response'];

function make_list() {
	$list = [];
	$dh = opendir('img');
	if (!$dh) return;
	while(($f = readdir($dh)) !== false) {
		if (($f == '.') || ($f == '..')) continue;
		if ($f == '.keep') continue;
		$list[] = $f;
	}
	sort($list);
	file_put_contents('list.json~', json_encode($list));
	rename('list.json~', 'list.json');

	// create static
	$out = fopen('static.html~', 'w');
	if (!$out) return; // why?
	fwrite($out, '<html><head><title>xkcd 1446 - Landing (static)</title></head><body>');
	fwrite($out, '<h1>xkcd 1446 - Landing (static)</h1>');
	fwrite($out, '<p>This is a static version containing all the images. You can also <a href="/">see the dynamic version</a>.</p>');
	fwrite($out, '<p>Source: <a href="http://xkcd.com/1446/">http://xkcd.com/1446/</a></p>');
	fwrite($out, '<p>xkcd is licensed by <a href="http://www.xkcd.com/about/">Randall Munroe</a> under a <a href="http://www.xkcd.com/license.html">Creative Commons Attribution-NonCommercial 2.5 License</a>, please give credit where it is due (because he\'s a cool guy as far as I can tell from being a regular reader of his comic).</p>');
	foreach($list as $file) {
		fwrite($out, '<p><span style="font-family: fixed,monospace;">'.$file.'</span><br/><img src="img/'.$file.'"/></p>');
	}
	fwrite($out, '</body></html>');
	fclose($out);
	rename('static.html~', 'static.html');
}

function do_ev($ev) {
	var_dump($ev);
	if ($ev['event'] == 'comic/landing') {
		$data = json_decode($ev['data'], true);
		$image = $data['image'];
		if (file_exists('img/'.$image)) return;

		$ch = curl_init('http://imgs.xkcd.com/comics/landing/'.$image);
		$f = fopen('img/'.$image.'~', 'w');
		curl_setopt($ch, CURLOPT_FILE, $f);
		if (curl_exec($ch)) {
			curl_close($ch);
			fclose($f);
			rename('img/'.$image.'~', 'img/'.$image);
			echo "Got image: $image\n";
			make_list();
		}
		// http://imgs.xkcd.com/comics/landing/$url
	}
}

while(!feof($sock)) {
	$line = fgets($sock);
	if ($line === false) break;
	$line = trim($line);
	if ($line == '') {
		do_ev($ev);
		$ev = [];
		continue;
	}
	$pos = strpos($line, ':');
	if ($pos === false) {
		if (!isset($ev['header'])) {
			$ev['header'] = $line;
			continue;
		}
		var_dump($line);
		continue;
	}

	$ev[substr($line, 0, $pos)] = trim(substr($line, $pos+1));
}

