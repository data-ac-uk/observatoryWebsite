<?php
$title = "UK University Web Observatory";
$content = function() {
$json = file_get_contents( "_junk/latest.json" );
$data = json_decode( $json, true );

$users_of = array();
$stats = array();
$count = 0;
foreach( $data as $url=>$record )
{
	if( !preg_match( "/^[a-z]+:\/\/[^\/]+\/$/", $url ) )
	{
		continue;
	}
	$record = json_decode( $record, true );
	
	$http_code = $record["http_code"];
	if( $http_code == 0 ) { $http_code = "FAIL"; }
	@$stats["http_code"][ $http_code ]++;
#print"<pre>";
#print_r( $record );
	++$count;
	#if( $count == 30 ) { exit; }
}


#{\"jquery\":true,\"softwareWordCount\":0,\"facebookAccounts\":[],\"twitterAccounts\":[],\"wordpress\":false,\"drupal\":false,\"youtubeAccounts\":[\"embed\"],\"youtubeVideo\":true}

$json = file_get_contents( "_junk/extras.json" );
$data = json_decode( $json, true );
foreach( $data as $domain=>$record )
{
	$record = json_decode( $record, true );
	#print "<h3>$domain</h3>"; print htmlspecialchars(print_r( $record ,1));
	foreach( array( "jquery","drupal" ) as $field )
	{
		if( $record[$field] ) 
		{ 
			++$stats[$field]; 
			$users_of[$field][]=$domain;
		}
	}
	if( sizeof( $record["twitterAccounts"] ) ) 
	{ 
		++$stats["twitter"];
	}
	if( sizeof( $record["facebookAccounts"] ) ) 
	{ 
		++$stats["facebook"];
	}
}

print "<p>count = $count</p>";
print "<h3>Homepage HTTP Code</h3>";
$stuff = array();
foreach( $stats["http_code"] as $k=>$v )
{
	$stuff[]=array( "k"=>$k, "v"=>$v );
}
include "pie.php";


print "<h3>Sites which link to a Twitter Account</h3>";
$stuff = array( 
	array( "k"=>"Yes", "v"=>$stats["twitter"] ), 
	array( "k"=>"No", "v"=>$count-$stats["twitter"] )
);
include "pie.php";

print "<h3>Sites which link to a Facebook Account</h3>";
$stuff = array( 
	array( "k"=>"Yes", "v"=>$stats["facebook"] ), 
	array( "k"=>"No", "v"=>$count-$stats["facebook"] )
);
include "pie.php";

print "<h3>Sites which appear to be using JQuery</h3>";
$stuff = array( 
	array( "k"=>"Yes", "v"=>$stats["jquery"] ), 
	array( "k"=>"No", "v"=>$count-$stats["jquery"] )
);
include "pie.php";

print "<h3>Sites which appear to be using Drupal</h3>";
$stuff = array( 
	array( "k"=>"Yes", "v"=>$stats["drupal"] ), 
	array( "k"=>"No", "v"=>$count-$stats["drupal"] )
);
include "pie.php";

$first=true;
ksort( $users_of["drupal"] );
print "<div>";
foreach( $users_of["drupal"] as $domain )
{
	if( !$first )
	{
		print " &bull; ";
	}
	$first = 0;
	print "<span style='display:inline-block'><a href='http://$domain'>$domain</a></span>";
}
print "</div>";

};

include "template.php";
