<?php

$title = "UK University Web Observatory: Observations JSON Format";
$content = function() {
?>
<p>This page explains how to interpret the 
<a download='observatory-data-ac-uk-latest.json' href='/data/observations/latest.json'>weekly observations log JSON encoded file</a>.</p>

<p>The top level is a set of key value pairs where the key is the domain name (<i>something</i>.ac.uk) and the value is the observations of that domain for the week.</p>


<p>Each observation includes a 'crawl' section which describes how the observation was made, and what the HTTP response was, and a 'site_profile' section which includes information derived from the homepage content and http request, such as links to social networking sites, detectable tools, use of disinctive old and new HTML elements, and reading-age statistics.</p>

<div style='border: solid 2px #193965; background-color: #193965; color: #fff; padding: 0.2em 1em 0.2em 1em'>Example record:</div>
<pre style='border: solid 2px #193965; padding: 1em; background-color: #ffc; overflow-x: scroll; height: 500px; font-family: fixed'>
<?php
print htmlspecialchars( file_get_contents( "example.json" ) );
?>

</pre>

<?php

}; // end of anonymous function

include "../template.php";
exit;
