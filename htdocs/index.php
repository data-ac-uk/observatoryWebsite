<?php

$page =  @$_GET["page"]?$_GET["page"]:"All";

$json = file_get_contents( "data/observations/latest.json" );
$data = json_decode( $json, true );

$BOOLS = array( "drupal", "jquery" );
$ACCOUNTS = array( "twitter", "youtube", "facebook", "flickr","vimeo","pinterest","instagram" );

$SETS = array("server","server_version" );
$GROUPS = array();

$lp1 = load_tsv( "learning-providers-plus.tsv" );
# index the lp by domain
$lp = array();
foreach( $lp1 as $row )
{
	$b = preg_split( "/\./", preg_replace( '/\/$/','', $row["WEBSITE_URL"] ));
	array_shift( $b );
	$domain = join( ".", $b );
	$lp[$domain] = $row;
}

$by_url = array();
foreach( $data as $pdomain=>$record )
{
	$by_url[$record["site_url"]]["domains"][$pdomain] = $record;
	if( isset( $lp[$pdomain] ) )
	{
		if( isset( $by_url[$record["site_url"]]["institution"] ) )
		{	
			continue; // bad chris, just need this to work for seminar
			print "dang: $pdomain already has an org set: \n";
			print_r( $by_url[$record["site_url"]]["institution"] );
			exit;
		}
		$by_url[$record["site_url"]]["institution"] = $lp[$pdomain];
	}
}


$stats = array();
foreach( $by_url as $url=>$info )
{
	$cats = array( "All" );
	if( isset( $info["institution"] ) )
	{
		$cats []= "University Sites";
		if( $info["institution"]["GROUPS"] )
		{
			$groups = preg_split( "/\s*,\s*/", $info["institution"]["GROUPS"] );
			foreach( $groups as $group )
			{
				$cats []= "$group";
				$GROUPS["$group"] = true;
			}
		}
	}
	else
	{
		$cats []= "Other Sites";
	}

	$ks = array_keys( $info["domains"] );
	$crawl = $info["domains"][$ks[0]];

	// look at headers	
	foreach( $cats as $cat )
	{
		@$stats[$cat]["full_count"]++; // includes failed requests
		
		$headers = $crawl["crawl"]["crawl_info"]["header"];
		$server = preg_replace( "/ .*$/", "", $headers["Server"] );
		if( $server == "" ) { $server = "unknown"; }
		@list( $server, $version, $stuff ) = preg_split( "/\//", $server );
		@$stats[$cat]["server"][$server]++;
		$major = "";
		if( isset( $version ) )
		{ 
			$version = preg_replace( "/^\(/", "", $version );
			if( preg_match( "/^(\d+)/", $version, $b ))
			{
				$major = "/".$b[1];
			}
		}
		@$stats[$cat]["server_version"]["$server$major"]++;
	}


	# no site profile? don't even bother counting it!
	if( !isset( $crawl["site_profile"] ) ) { continue; } 


	foreach( $cats as $cat )
	{
		@$stats[$cat]["count"]++;

		foreach( $BOOLS as $field )
		{
			if( $crawl["site_profile"][$field] ) { 
				@$stats[$cat][$field]++;
				@$stats[$cat][$field."_users"][]=$url;
			}
			else
			{
				@$stats[$cat][$field."_nonusers"][]=$url;
			}
		}

		foreach( $ACCOUNTS as $field )
		{
			$has_account = false;
			foreach( $crawl["site_profile"][$field."Accounts"] as $account )
			{
				if( preg_match( "/[\/#?]/", $account ) )
				{
					continue;
				}
				$has_account = true;
				$stats[$cat][$field."_accounts"][$url][]=$account;
			}
			if( $has_account )
			{
				@$stats[$cat][$field."_users"][]=$url;
				@$stats[$cat][$field]++;
			}
			else
			{
				$stats[$cat][$field."_nonusers"][]=$url;
			}
		}
	}

	#print sprintf("%d %s :: %s\n", sizeof( $info["domains"] ), $url, join( " ; ", array_keys( $info["domains"] )  ));
}

#print "<pre>".htmlspecialchars( print_r( $stats,1 ));exit;
#print_r( $by_url["http://www.southampton.ac.uk/"] );exit;

// end of prep

// start of actually doing the darn page

$title = "UK University Web Observatory: ".preg_replace("/_/"," ",$page);
$content = function() {
global $GROUPS;
global $ACCOUNTS;
global $BOOLS;
global $SETS;
global $page;
global $stats;
global $by_url;

print "<p><strong>Disclaimer:</strong> all this code is totally new and probably<span style='font-size:70%'>(definitely)</span> has some bugs. The data currently shown should not be regarded as citable just yet. Let's call it \"for entertainment purposes only\", but that will change as we shake out the kinks. All the data collected by this service will be made available for free reuse and all the code will be open source; available for inspection and reuse.</p>";
print "<p>Observations taken each weekend.</p>";
print "<p>What observations would you like us to add? Ideally ones that are easy to do with a regexp on the homepage. <a href='mailto:chris@data.ac.uk'>Email us</a> or <a href='http://twitter.com/dataacuk'>send us a tweet</a>.</p>";
print "<p>Get the data: <a download='observatory-data-ac-uk-latest.json' href='/data/observations/latest.json'>Latest Observations</a>. JSON file, approx 25Mb. License: CC0 - no attribution required, but please attribute observatory.data.ac.uk if convenient.</p>";

$links = array( 
	array( "page"=>"All", "label"=>"All Sites" ),
	array( "page"=>"University Sites", "label"=>"University Sites" ),
	array( "page"=>"Other Sites", "label"=>"Other Sites" ),
);
print render_link_list( $links, $page );

$links = array();
foreach( $GROUPS as $group=>$dummy ) 
{
	$links []= array( "page"=>$group, "label"=>preg_replace("/_/"," ",$group) );
}
print render_link_list( $links, $page );

$cstats = $stats[$page];
print "<p>Total number of .ac.uk sites in this category: <span class='total_count'>".$cstats["count"]."</span></p>";
print "<h2>Social Media</h2>";
foreach( $ACCOUNTS as $account )
{
	print "<h3>$account</h3>";
	$stuff = array( 
		array( "k"=>"Yes", "v"=>$cstats[$account] ), 
		array( "k"=>"No", "v"=>$cstats["count"]-$cstats[$account] )
	);
	include "pie.php";
	if( sizeof( $cstats[$account."_users"] ) < 100 )
	{
		print "<dl>";
		ksort( $cstats[$account."_accounts"] );
		foreach( $cstats[$account."_accounts"] as $url=>$accounts )
		{
			$label = $url;
			$org= @$by_url[$url]["institution"];
			if( isset($org) )	
			{
				$label = $org['PROVIDER_NAME'];
			}
			print "<dt><a href='$url'>$label</a></dt>";
			print "<dd>".join( ", ",$accounts)."</dd>";
		}
		print "</dl>";
	}
	else
	{
		print "<div>Too many to list individually!</div>";	
	}
}


print "<h2>Server Info</h2>";
foreach( $SETS as $set )
{
	print "<h3>$set</h3>";
	$stuff = array();
	foreach( $cstats[$set] as $k=>$v )
	{
		$stuff []= array( "k"=>$k, "v"=>$v );
	}

	include "pie.php";
}
#print "<pre>"; print_r( $cstats ); print "</pre>";
print "<h2>Technologies</h2>";
foreach( $BOOLS as $tech )
{
	print "<h3>$tech</h3>";
	$stuff = array( 
		array( "k"=>"Yes", "v"=>$cstats[$tech] ), 
		array( "k"=>"No", "v"=>$cstats["count"]-$cstats[$tech] )
	);
	include "pie.php";
	if( sizeof( $cstats[$tech."_users"] ) < 200 )
	{
		print "<div>";
		ksort( $cstats[$tech."_users"] );
		foreach( $cstats[$tech."_users"] as $url )
		{
			$label = $url;
			$org= @$by_url[$url]["institution"];
			if( isset($org) )	
			{
				$label = $org['PROVIDER_NAME'];
			}
			print "<span style='display: inline-block'><a href='$url'>$label</a></span> &bull; ";
		}
		print "</div>";
	}
	else
	{
		print "<div>Too many to list individually!</div>";	
	}
}



}; // end of anonymous function

include "template.php";
exit;


function render_link_list( $list, $current_page )
{
	$r = array();
	foreach( $list as $pair )
	{
		if( $pair["page"] == $current_page ) 
		{
			$r []= $pair["label"];
		}
		else
		{	
			$r []= "<a href='?page=".$pair["page"]."'>".$pair["label"]."</a>";
		}
	}
	return "<p>".join( " | ", $r )."</p>";
}


function load_tsv( $file )
{
	$headings = null;
	$r = array();
	foreach( file( $file ) as $line )
	{
		$line = chop( $line );
		$cells = preg_split( '/\t/', $line );
		if( $headings === null )
		{
			$headings = $cells;
			continue;
		}
		$row = array();
		for( $i=0;$i<sizeof( $headings );$i++)
		{
			@$row[$headings[$i]] = $cells[$i];
		}
		$r[]=$row;
	}
	return $r;
}
