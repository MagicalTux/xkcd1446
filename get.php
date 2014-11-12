<?php

$rand = mt_rand(0,7);
$url = 'http://c'.$rand.'.xkcd.com/stream/comic/landing?method=EventSource';

$url = parse_url($url);

$sock = fsockopen($url['host'], 80, $errno, $errstr, 180);
if (!$sock) die("failed to connect\n");

fwrite($sock, 'GET '.$url['path'].'?'.$url['query'].' HTTP/1.0'."\r\n");
fwrite($sock, "User-Agent: MagicalTux (compatible; yeah right; http://xkcd1446.org/)\r\n\r\n");

$ev = ['event' => 'http_response'];

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
			$list = [];
			$dh = opendir('img');
			if (!$dh) return;
			while(($f = readdir($dh)) !== false) {
				if (($f == '.') || ($f == '..')) continue;
				$list[] = $f;
			}
			sort($list);
			file_put_contents('list.json', json_encode($list));
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

