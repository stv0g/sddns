<?php

require_once 'include/init.php';

if (!isAuthentificated()) {
    header('WWW-Authenticate: Basic realm="Administration area"');
    header('HTTP/1.0 401 Unauthorized');
    echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>401 Authorization Required</title>
</head><body>
<h1>Authorization Required</h1>
<p>This server could not verify that you
are authorized to access the document
requested.  Either you supplied the wrong
credentials (e.g., bad password), or your
browser doesn\'t understand how to supply
the credentials required.</p>
<hr>
<address>' . $_SERVER['SERVER_SIGNATURE'] . '</address>
</body></html>';
} else {
   $output->add('authentificated as', 'notice', $_SERVER['PHP_AUTH_USER']);
}

?>