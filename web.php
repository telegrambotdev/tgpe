<?php
if (isset($_POST['url'])) {
	require('database.php');
	$db = new MyDB();
	$error = []; // Default no error

	$url = (string) $_POST['url'];
	if ($code = $db->findCodeByUrl($url))
		$error[] = "Already Exists."; // Prevent re-create


	$ip_addr = $_SERVER['REMOTE_ADDR'];
	$author = "WEB{$ip_addr}{$_SERVER["HTTP_CF_IPCOUNTRY"]}";
	$data = $db->findByAuthor($author);
	if ($_SERVER["HTTP_CF_IPCOUNTRY"] != 'TW' && count($data) >= 3) {
		$error[] = "You can only create 3 links in web version";
	}

	if ($_SERVER["HTTP_CF_IPCOUNTRY"] == 'TW' && count($data) >= 5) {
		$last = strtotime(end($data)['created_at']);
		if (time() - $last <= 10 * 60)
			$error[] = "You can only create 5 links in web version";
	}


	if (!preg_match('#^https?://(?P<domain>[^\n\s@%/]+\.[^\n\s@%/]+)(?:/[^\n\s]*)?$#i', $url, $matches))
		$error[] = "Please send a Vaild URL.";

	if (!filter_var($url, FILTER_VALIDATE_URL))
		$error[] = "URL invalid.";

	if (strpos($url, "fbclid="))
		$error[] = "Please remove fbclid before sharing URLs.";

	if (strpos($ip_addr, ':') === false) {
		$long = ip2long($ip_addr);
		$ipv4_blacklist = [
			['54.39.0.0',     '54.39.255.255'  ],
			['78.108.176.0',  '78.108.191.255' ],
			['129.205.113.0', '129.205.113.255'],
			['156.146.58.0',  '156.146.59.255' ],
			['192.241.128.0', '192.241.255.255'],
			['193.9.112.0',   '193.9.112.255'  ],
			['200.25.20.0',   '200.25.23.255'  ],
		];
		foreach ($ipv4_blacklist as $item)
			if (ip2long($item[0]) <= $long && $long <= ip2long($item[1]))
				$error[] = 'Your IP address is banned by admin.';
	}

	$domain = $matches['domain'] ?? 'url broken';
	if (in_array($domain, [
		'tg.pe',
		'han.gl',
		'rebrand.ly',
	]))
		$error[] = 'Short enough';


	if (count($error) === 0) {
		$code = $db->allocateCode('x', 4);
		$result = $db->insert($code, $url, $author);
		if ($result[0] !== '00000')
			$error[] = $result[2];
	}
} ?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>tg.pe URL Shortener by.Sean</title>
	<link rel="icon" type="image/png" href="/logo-192.png" sizes="192x192">
	<link rel="icon" type="image/png" href="/logo-128.png" sizes="128x128">
	<link rel="icon" type="image/png" href="/logo-64.png" sizes="64x64">
	<link rel="icon" type="image/png" href="/logo.png" sizes="680x680">
	<link rel="stylesheet" href="style.css" />
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
	<meta name="keywords" content="url shortener, tgpe">
	<meta name="description" content="Shortest Shortener">
	<meta property="og:title" content="tg.pe URL Shortener">
	<meta property="og:url" content="https://tg.pe/">
	<meta property="og:image" content="/logo.png">
	<meta property="og:image:secure_url" content="/logo.png">
	<meta property="og:image:type" content="image/png">
	<meta property="og:image:width" content="680">
	<meta property="og:image:height" content="680">
	<meta property="og:type" content="website">
	<meta property="og:description" content="Shortest Shortener">
	<meta property="og:site_name" content="URL Shortener by.Sean">
</head>
<body>
<center>
<div class="content">
	<img src="logo_boderless.png" style="height: 40vh; margin-top: 40px;">
	<h1>URL Shortener</h1>
	<h2>Shorten Your URL: <a href="https://tg.pe/bot">tg.pe/bot</a></h2>

	<div id="gen">
		<big>Limited Online Version</big>

<?php
if (!isset($_POST['url'])) {
	echo <<<EOF
		<form method="POST" action="/web">
			<p>Your URL:<br>
			<span class='input'>
				<input name="url" id="url" size="30" placeholder="https://www.sean.taipei/">
				<span></span>
			</span>
			<br>
			<span style="color: darkgray;">Custom Short Link: https://tg.pe/<input name="code" size="4" disabled="1" placeholder="x123"><br>
			<button class="button" type="submit">Shorten!</button>
			</p>
		</form>

		<script>
			var url = document.getElementById("url");
			url.focus();
		</script>
EOF;
	} else if (!empty($code)) {
		echo <<<EOF
<p>Your Link: <input id="link" value="https://tg.pe/$code" size="14"><button id="copyButton" onclick="copyLink()">Copy</button></p>
<script>
function copyLink() {
	var copyText = document.getElementById("link");
	copyText.select();
	copyText.setSelectionRange(0, 99);
	document.execCommand("copy");

	var copyButton = document.getElementById("copyButton");
	copyButton.innerHTML = "Copied!";
	copyText.setSelectionRange(0, 0);

	setTimeout(() => {
		copyButton.innerHTML = "Copy";
	}, 2000);
}
</script>
EOF;
	} else if (count($error)) {
		echo <<<EOF
<p style='color: red;'>ERROR: {$error[0]}</p>
<p>Goto <a href='/'>Homepage</a>.</p>
EOF;
	}
?>
		<small>Note: Online version only allow random short link starts with <code>x</code>.<br>
		Use Telegram Bot to get unlimited access for free.</small>
	</div>
	<br>
</div>
<div class="footer">
	<footer id="footer">
		<p>Source Code: <a href="https://github.com/Sea-n/tgpe">Sea-n/tgpe</a><br>
		Developed by <a href="https://www.sean.taipei/">Sean</a>.</p>
	</footer>
</div>
</center>
</body>
</html>
