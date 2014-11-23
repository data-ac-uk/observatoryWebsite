<?php

$path = explode("/", __DIR__);
array_pop( $path ); # lose bin
$base_dir = join( "/", $path );

require_once( "$base_dir/config.php" );
require_once( "$base_dir/lib/apilib.php" );

$localdb = new LocalDB();


$path = substr($_SERVER['REQUEST_URI'],4);
$script = "/api";

// not using F3 but we might want to later on
if( $path == "" )
{
	header( "Location: $script/" );
}
elseif( $path == "/" )
{
	serveAPIHome();
}
elseif( preg_match( "!/(date|site|field)\.(json|html)$!", $path, $bits ))
{
	serveAPIList($bits[1],$bits[2]);
}
elseif( preg_match( "!/(date|site|field)/(.*)\.(json|html|csv)$!", $path, $bits ))
{
	serveAPIData($bits[1],$bits[2],$bits[3]);
}
else
{
	serveAPI404();
}
exit;

function serveAPI404()
{
	header(':', true, 404 );
	$title = "404 Not Found";
	$content = function() { print "<p>Requested thing could not found. Sorry.</p>"; };
	include "template.php";
}

function serveAPIData( $type, $typevalue, $format )
{
	# get values from db
	global $localdb;
	$values = $localdb->getData( $type, $typevalue );

	if( $format=='json' )
	{
		header( "Content-type: text/json" );
		print json_encode( $values );
		return;
	}

	// OK if not JSON we're going to convert this into a grid.
	// lets get the x axis headings
	$xvalues = array();
	foreach( $values as $y=>$row )
	{
		foreach( $row as $x=>$v )
		{
			$xvalues[$x]=true;
		}
	}
	ksort( $xvalues );
	$xvalues = array_keys( $xvalues );

	if( $format=='csv' )
	{
		$out = fopen('php://output', 'w');
		fputcsv($out, $xvalues);
		foreach( $values as $y=>$row )
		{
			$cells = array();
			foreach( $xvalues as $x )
			{
				$cells []= $row[$x];
			}
			fputcsv($out, $cells);
		}
		return;
	}

	// default format is HTML
	$title = "Data for by $type=$typevalue";
	$content = function() use ( $type, $values, $xvalues ) {
		print "<link rel='stylesheet' href='//cdn.datatables.net/1.10.4/css/jquery.dataTables.min.css' />";
		print "<script src='//code.jquery.com/jquery-1.11.1.min.js'></script>";
		print "<script src='//cdn.datatables.net/1.10.4/js/jquery.dataTables.min.js'></script>";
		print "<script>$(document).ready(function(){ $('#dataTable').DataTable(); });</script>";
		print "<table id='dataTable'>";
		print "<thead><tr><th></th>";
		foreach( $xvalues as $heading) 
		{
			print "<th>$heading</th>"; 
		}
		print "</tr></thead>\n";
		print "<tbody>";
		foreach( $values as $y=>$row )
		{
			print "<tr>";
			print "<td>".@$y."</td>";
			foreach( $xvalues as $x )
			{
				print "<td>".@$row[$x]."</td>";
			}
			print "</tr>\n";
		}
		print "</tbody>";
		print "</table>";
	};
	include( "template.php" );
}

function serveAPIList( $type, $format )
{
	# get unique values from db
	global $localdb;
	$values = $localdb->getTypeValues( $type );

	if( $format=='json' )
	{
		header( "Content-type: text/json" );
		print json_encode( $values );
		return;
	}
	
	// otherwise HTML

	$title = "Browse by $type";
	$content = function() use ( $type, $values ) {
		print "<ul>";
		foreach( $values as $value )
		{
			print "<li>";
			print "<a href='$type/$value.html'>$value</a> ";
			print "[<a href='$type/$value.json'>JSON</a>] ";
			print "[<a href='$type/$value.csv'>CSV</a>] ";
			print "</li>";
		}
		print "</ul>";
	};
	include "template.php";
}	

function serveAPIHome()
{
	$title = "Observatory.data.ac.uk API";
	$content = function() {
		print '
<p>Welcome to the data.ac.uk Observatory API. The API allows you get at 3 kinds of tabular data.</p>
<ul>
<li><i>Site</i> is the domain being observed, eg. jisc.ac.uk</li>
<li><i>Date</i> is the date of observation</li>
<li><i>Field</i> is a class of observation, eg. does it appear to use jquery?</li>
</ul>
<h3>Browse to data</h3>
<ul>
<li>
<a href="date.html">by date</a> 
[<a href="date.json">JSON</a>]
- return a table of site:field for a given date.</li>
<li>
<a href="field.html">by field</a> 
[<a href="field.json">JSON</a>]
- return a table of date:site for a given field.</li>
<li>
<a href="site.html">by site</a> 
[<a href="site.json">JSON</a>]
- return a table of date:field for a given site.</li>
</ul>'; 
	};
	include "template.php";
}

##############################
# notes
##############################

# category

# date - default gives site/field + site is uni?
# site - gives date/field 
# field

# list dates
# list sites (inc relevant dates?)
# list fields (inc relevant dates?)

# /date
# /site
# /field
# /date/2014-07-01
# /site/soton.ac.uk/

