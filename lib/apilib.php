<?php

# functions to create a local database to power the api

class LocalDB
{
	var $db;
	var $addstmt;
	var $f_site;
	var $f_date;
	var $f_field;
	var $f_value;
	function __construct()
	{
		global $config;
		$d = $config['localdb'];
		$this->db = new mysqli( $d['host'],$d['user'],$d['pass'],$d['db'] );
		if ($this->db->connect_errno) {
    			echo "Failed to connect to MySQL: (" . $this->db->connect_errno . ") " . $this->db->connect_error;
			return;
		}
		$this->addstmt = $this->db->prepare("INSERT INTO data (site,date,field,value) VALUES (?,?,?,?)");
		if( !$this->addstmt )
		{
			echo "Prepare failed: (" . $this->db->errno . ") " . $this->db->error;
			return;
		}
		if (!$this->addstmt->bind_param("ssss", $this->f_site, $this->f_date, $this->f_field, $this->f_value )) {
			echo "Bind failed: (" . $this->db->errno . ") " . $this->db->error;
			return;
		}
	}
	
	// erase and recreate the database
	function resetTables()
	{
		if (!$this->db->query("DROP TABLE IF EXISTS data") ||
    			!$this->db->query("CREATE TABLE data( site varchar(40), date DATE, field VARCHAR(80), value VARCHAR(255), index(date), index(field), index(site) )") ) 
		{
    			echo "Table creation failed: (" . $this->db->errno . ") " . $this->db->error;
		}
	}

	function addRow( $site, $date, $field, $value )
	{
		$this->f_site = $site;
		$this->f_date = $date;
		$this->f_field = $field;
		$this->f_value = $value;
		if( !$this->addstmt->execute() ) 
		{
			echo "Execute failed: (" . $this->db->errno . ") " . $this->db->error;
			return;
		}
	}		

	function getTypeValues( $type )
	{
		$type = preg_replace( '/[^a-z]/', '', $type );
		$sql = "SELECT DISTINCT $type AS v FROM data ORDER BY $type";
		$stmt = $this->db->prepare($sql);
		$stmt->bind_result($value);
		$stmt->execute();

		$values = array();
		while ($stmt->fetch())
		{
			$values []= $value;
		}
		return $values;
	}

	function getData( $type, $typevalue )
	{
		if( $type == 'field' )
		{
			$sql = "SELECT date,site,value FROM data WHERE field=?";
		}
		elseif( $type == 'site' )
		{
			$sql = "SELECT date,field,value FROM data WHERE site=?";
		}
		elseif( $type == 'date' )
		{
			$sql = "SELECT field,site,value FROM data WHERE date=?";
		}
		else
		{
			print "this can't happen!";
			exit;
		}
		$stmt = $this->db->prepare($sql);
		$stmt->bind_param('s',$typevalue);
		$stmt->bind_result($x,$y,$value);
		$stmt->execute();

		$values = array();
		while ($stmt->fetch())
		{
			$values[$y][$x]=$value;
		}
		return $values;
	}
		
}

class SourceDB
{
	var $db;
	function __construct()
	{
		global $config;
		$d = $config['srcdb'];
		$this->db = new mysqli( $d['host'],$d['user'],$d['pass'],$d['db'] );
		if ($this->db->connect_errno) {
    			echo "Failed to connect to MySQL: (" . $this->db->connect_errno . ") " . $this->db->connect_error;
		}
	}
	
	function readProfiles($fn)
	{
		$result = $this->db->query("SELECT count(*) as C FROM websites WHERE site_status='OK'", MYSQLI_USE_RESULT  );
		$row = $result->fetch_assoc();
		$rows_n = $row['C'];	
		$result->free();

		$result = $this->db->query("SELECT site_domain,site_crawled,site_profile FROM websites WHERE site_status='OK'", MYSQLI_USE_RESULT  );

		$i = 0;
    		while ($row = $result->fetch_assoc()) {
			$fn($row, ++$i, $rows_n );
		}
		$result->free();
	}
}
