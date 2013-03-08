<?php

function doAuthentification() {
	header('WWW-Authenticate: Basic realm="Administration Area"');
	header('HTTP/1.0 401 Unauthorized');
}

function isAuthentificated() {
	$config = Registry::get('config');
	$htpasswd = file($config['htpasswd']);

	foreach ($htpasswd as $line) {
		list($user, $crypt) = explode(':', $line);
		$salt = substr($crypt, 0, 2);

		if ($user == @$_SERVER['PHP_AUTH_USER'] &&
			trim($crypt) == crypt(@$_SERVER['PHP_AUTH_PW'], $salt)) {
			return true;
		}
	}

	return false;
}

function randomString($length, $characters='abcdefghijklmnopqrstuvwxyz0123456789') {
	$random_string = '';
	$characters_length = strlen($characters);
	for($i = 0; $i<$length; $i++) {
		$random_string .= $characters[mt_rand(0, $characters_length - 1)];
	}
	return $random_string;
}


function backtrace2xml($traces, DomDocument $doc) {
	$xmlTraces = $doc->createElement('backtrace');

	foreach ($traces as $step => $trace) {
		$xmlTrace = $doc->createElement('trace');
		$xmlTraces->appendChild($xmlTrace);
		$xmlTrace->setAttribute('step', $step);

		foreach ($trace as $key => $value) {
			switch ($key) {
				case 'function':
				case 'line':
				case 'file':
				case 'class':
				case 'type':
					$xmlTrace->appendChild($doc->createElement($key, $value));
					break;
				case 'args':
					$xmlArgs = $doc->createElement($key);
					$xmlTrace->appendChild($xmlArgs);
					foreach ($value as $arg) {
						$xmlArgs->appendChild($doc->createElement('arg', $value));
					}
					break;
			}
		}
	}

	return $xmlTraces;
}

function backtrace2html($traces) {
	$trace = '';

	foreach(debug_backtrace() as $i => $l){
		$trace .= '[' . $i . '] in function <b>' . $l['class'] . $l['type'] . $l['function'] . '</b>';
		if($l['file'])
			$trace .= ' in <b>' . $l['file'] . '</b>';
		if($l['line'])
			$trace .= ' on line <b>' . $l['line'] . '</b>';
	}

	return $trace;
}

?>
