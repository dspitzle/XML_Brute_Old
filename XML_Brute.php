<?php

	// Copyright (c) 2017 David A Spitzley. All rights reserved.
	// License: BSD 3-Clause License
	// Website: https://github.com/dspitzle/XML_Brute

	$GLOBALS["multi"] = array();

	/**
	 * Generate an array representation of the data structure implied by the file structure,
	 * flattened out to capture all variations between individual records
	 * NOTE:  This function makes use of eval() numerous times.  This is a necessary evil due to the use of the $zoomName string to track where we are in the data structure.
	 * 
	 * @param SimpleXMLElement $branch Current subsection of main document
	 * @param array $mapZooms Array representation of the current location within $tree
	 * @param array $tree Array representation of flattened data structure implied by source XML file
	 * @param boolean $multiples Flag indicating whether $branch is an instance of a multi-instance level of the hierarchy
	 * @param boolean $component Flag indicating whether $branch is a multi-element component
	 *
	 * @return array $tree
	 */	
	function mapBranch( SimpleXMLElement $branch, $mapZooms, $tree, $multiples=false, $component=false ){

		//This function is called recursively, with $mapZooms serving as a roving pointer into the data hierarchy
	
		$currentLayer = $branch->getName(); //Get the XML element name for the head of the current branch of the XML file
		$mapZooms[] = $currentLayer; //Stores the current XML element name in $mapZooms[]
		$zoomName = '$tree'; //Initialize a string representation of the the node in $tree referenced by the series of array indexes stored in $mapZooms[]
		foreach ( $mapZooms as $zoomLayer ){//For each level of the hierarchy traversed so far
			$zoomName .= "[\"".$zoomLayer."\"]"; //Append the name of the level as a string representation of an array key
		}
		$zoomParentName = substr( $zoomName,0,strrpos( $zoomName,"[" ) );//Working backwards from the end of $zoomName, drop the last level name to construct the parent level
		$multiZoomName = $zoomParentName."[\"".$currentLayer."__multi\"]";//Construct the multi-instance variant of $zoomName
		
		if( $multiples and !$component ){//If the current location is a multi-instance element
			$parentLayer = substr( $zoomParentName,strrpos( $zoomParentName,"[" )+2,-2 );//Grab parent layer name, minus wrapping brackets and quotes
			$GLOBALS["multi"][$currentLayer][$parentLayer]=true; //Mark the current layer as being a multiple in the $GLOBALS array ( indexed in "[child][parent]" order for ease of access later )
			
			eval("if(isset(".$zoomName.")){unset(".$zoomName.");}"); //If the current location exists in $tree as a non-multi, remove it
			eval("if(!isset(".$multiZoomName.")){".$multiZoomName."=array('__MULTI'=>true);}"); //If the the multi-instance version of the current location has not been been added to $tree yet, add it to $tree as an array
		}
		else{
			eval( "if( !( isset( ".$zoomName." ) or isset( ".$multiZoomName." ) ) ){".$zoomName."=array();}" ); //If the current location has not been added to $tree yet, add it to $tree as an empty array
		}
		
		if( $branch->attributes()->count()>0 ){ //If the current location has attributes
			//Set the corresponding attributes $mapZooms entry to an array if it isn't already set		
			eval( "if( !isset( ".$zoomName."[\"".$currentLayer."__attributes\"] ) ){".$zoomName."[\"".$currentLayer."__attributes\"]=array();}" ); 
			foreach( $branch->attributes() as $attr ){//For each attribute set the corresponding attribute's $mapZooms entry to true ( as a placeholder )
				eval( $zoomName."[\"".$currentLayer."__attributes\"][\"".$attr->getName()."\"]=true;" );
			}
		}
		
		if( $branch->count()>0 ){//If the current layer has children
			$childListMultiples = array();
			$childListComponent = array();
			foreach( $branch->children() as $child ){//For each child determine whether it has multiple instances
				$childName = $child->getName();
				if ( isset( $childListMultiples[$childName] ) ){//If this child's name has already been seen
					$childListMultiples[$childName]=true;//update multiple flag for the layer to true
				}
				else{
					$childListMultiples[$childName]=false;//initialize multiple flag for the layer to false
				}
				if ( $child->count()>0 ){//If this child has its own children
					$childListComponent[$childName]=true;//set or update component flag for the layer to true
				}
				elseif ( !isset( $childListComponent[$childName] ) ){//otherwise, if the child hasn't been seen before
					$childListComponent[$childName]=false;//initialize component flag for the layer to false
				}
			}
			foreach( $branch->children() as $child ){//For each child make a recursive call to this function
				$childName = $child->getName();
				$tree = mapBranch( $child,$mapZooms,$tree,$childListMultiples[$childName],$childListComponent[$childName] );
			}
		}
		return $tree;
	}


	/**
	 * Populate an MS Access .accdb database with tables representing the data structure $tree
	 * 
	 * @param PDO $connection Connection to target MS Access file
	 * @param array $tree Array representation of flattened data structure implied by source XML file
	 * @param boolean $multiples Flag indicating whether $branch is an instance of a multi-instance level of the hierarchy
	 * @param string $parent Name of parent table
	 *
	 * @return string $fieldsList
	 */	
	function buildAccessDB( PDO $connection, array $tree, $parent="" ){
		
		$fieldsList = "";
		foreach( $tree as $branch=>$leaf ){//For each entry in the hierarchy map, separate the entry from anything branching off of it
			if( is_array( $leaf ) and count( $leaf )>0 ){//If the entry has any children 
				$table = $branch;
				if( substr( $branch,-7 )=="__multi" ){//If the table is for storing repeated elements
					$table = $parent."__".$branch;
				}
				if ( $parent<>"" and $table == $parent ){//If the parent and child levels have the same name, double them up for the table name
					$table .= "__".$table;
				}
				$createAccessFields = "CREATE TABLE ".$table." ( __ID AUTOINCREMENT PRIMARY KEY, __TENTPOLE YESNO"; //initiate a new CREATE TABLE command
				$createAccessFields .= buildAccessDB( $connection, $leaf, $branch );//Make a recursive call to this function to get the fields
				if( $parent<>"" ){//If this new table is a child of another table
					$createAccessFields .= ", ".$parent."__ID INTEGER";//Add a field for the foreign key
				}
				if( substr( $branch,-7 )=="__multi" ){//If the table is for storing repeated elements
					$createAccessFields .= ", ".substr( $branch,0,-7 )." TEXT( 255 )";//Add a field for the repeated elements content
				}
				$createAccessFields .= " )";//Close CREATE TABLE command
				echo $createAccessFields."<br/><br/>";
				flush();				
				$affected = $connection->exec( $createAccessFields );//Execute CREATE TABLE command
				if( $affected === FALSE ) {
					echo "<pre>".print_r( $connection->errorInfo() )."</pre><p>See <a href='http://www.ibm.com/support/knowledgecenter/SSGU8G_11.70.0/com.ibm.sqls.doc/ids_sqs_0809.htm'>SQLSTATE Codes list</a> for clarification</p>";
				}
			}
			else{//otherwise, if there are no children
				$fieldsList .= ", ".$branch." TEXT( 255 )";//Append the field name to the list to be included in the parent CREATE TABLE command
			}
		}
		return $fieldsList;//Return the fields list ( if any ) for inclusion in any parent CREATE TABLE command

	}


	/**
	 * Places data into MS Access .accdb tables created by buildAccessDB()
	 *
	 * @param PDO $connection 	 
	 * @param SimpleXMLElement $branch Current subsection of main document
	 * @param string $indent Prefix string used when displaying progress 
	 * @param string $parent Name of parent table
	 * @param int $parent_id ID of associated record in parent table
	 *
	 * @return string Name and count of newly stored record for displaying progress
	 */
	function populateAccessDB( PDO $connection, SimpleXMLElement $branch, $indent = "", $parent="", $parent_id = null ){
	
		$level = $branch->getName();
		$table = $level; //Current level in file hierarchy sets target table for writing data
		if( $parent <> "" and $table == $parent ){//If the target table has a parent of the same name, double up the name
			$table .= "__".$table;
		}
		if( $branch->count()>0 or isset( $GLOBALS["multi"][$level][$parent] ) ){//If $branch has children or is a multi-instance element

			//Initialize the variables used to construct the INSERT statement
			$insertAccessFields = "[__TENTPOLE], ";
			$insertAccessPlaceholders = "?, ";
			$insertAccessValues = array( TRUE );

			foreach( $branch->children() as $child ){
				if( $child->count()==0 and !isset( $GLOBALS["multi"][$child->getName()][$level] ) ){//If the child has no children and is not a multi-instance element
					$field = populateAccessDB( $connection,$child,$indent."-" );//make a recursive call to this function
					foreach( $field as $key=>$value ){//add each recursively returned field to the INSERT variables
						$insertAccessFields .= "[".$key."], ";
						$insertAccessPlaceholders .= "?, ";										
						$insertAccessValues[] = $value;
					}
				}
			}

			if( $parent<>"" ){//If this branch has a foreign key
			
				//Append parent__ID data to the INSERT variables
				$insertAccessFields .= "[".$parent."__ID], ";
				$insertAccessPlaceholders .= "?, ";				
				$insertAccessValues[] = $parent_id;
				
				if( isset( $GLOBALS["multi"][$level][$parent] ) ){
					$insertAccessFields .= "[".$level."], ";//Append repeated element field
					$insertAccessPlaceholders .= "?, ";				
					$insertAccessValues[] = $branch->__toString();//Append repeated element value
					$table = $parent."__".$level."__multi";
				}
			}
			$insertAccessFields = substr( $insertAccessFields,0,-2 );//Prune final ", " combo
			$insertAccessPlaceholders = substr( $insertAccessPlaceholders,0,-2 );//Prune final ", " combo	
			//echo "<pre>"."INSERT INTO [".$table."] ( ".$insertAccessFields." ) VALUES ( ".$insertAccessPlaceholders." )".print_r( $insertAccessValues )."</pre><br/>";
			$insertQuery = $connection->prepare( "INSERT INTO [".$table."] ( ".$insertAccessFields." ) VALUES ( ".$insertAccessPlaceholders." )" );
			$success = $insertQuery->execute( $insertAccessValues );
			$maxQuery = $connection->prepare( "SELECT MAX( [__ID] ) AS [ID] FROM [".$table."]" );			
			$maxQuery->execute();
			$max = $maxQuery->fetch();
			$insertID = $max["ID"];
			echo $indent.$level." ".$insertID."<br/>";
			flush();			

			foreach( $branch->children() as $child ){//For each child 
				if( $child->count()>0 or isset( $GLOBALS["multi"][$child->getName()][$level] ) ){//If the child has children or is a solitary multi-instance element
					populateAccessDB( $connection,$child,$indent."-",$level,$insertID );//make a recursive call to this function
				}
			}
		}

		if( $branch->attributes()->count()>0 ){ //If the current location has attributes
			$insertAccessFields = "INSERT INTO [".$level."__attributes] ( __TENTPOLE, "; //initiate a new INSERT INTO command
			$insertAccessPlaceholders = " VALUES ( ?, ";
			$insertAccessValues = array( TRUE );
			foreach( $branch->attributes() as $attr=>$attrValue ){//For each attribute insert the value into the appropriate field
				$insertAccessFields .= "[".$attr."], ";
				$insertAccessPlaceholders .= "?, ";	
				$insertAccessValues[] = $attrValue;
			}
			$insertAccessFields .= "[".$level."__ID] )";//Append parent__ID field and closing parentheses
			$insertAccessPlaceholders .= "? )";//Prepare parent_id placeholder and closing parentheses
			$insertAccessValues[] = $insertID;//Append parent_id value
			$insertQuery = $connection->prepare( $insertAccessFields.$insertAccessPlaceholders );			
			$success = $insertQuery->execute( $insertAccessValues );
			//echo $level."__attributes: ".$insertAccessFields.$insertAccessPlaceholders."<br/>";
			flush();			
		}

		if( $branch->count()==0 ){
			return array( $level=>$branch->__toString() );
		}
	}
	
	

	$uploadDir = "storage\\uploads\\";
	$downloadDir = "storage\\downloads\\";
	
	//Define XML source file
	$fullFileName = $_FILES["target_file"]["name"];
	echo "fullFileName = ".$fullFileName."<br/>";
	$fileName = ( strrpos( $fullFileName, "." ) !== FALSE ) ? substr( $fullFileName, 0, strrpos( $fullFileName,"." ) ) : $fullFileName;
	echo "fileName = ".$fileName."<br/>";
	$targetFile = $uploadDir.$fullFileName;
	echo "targetFile = ".$targetFile."<br/>";	
	$exportName = $fileName."-".date( 'YmdHis' );
	echo "exportName = ".$exportName."<br/>";

	
	//Load the source XML file as a SimpleXML object
	echo "Connecting to Data File '".$fullFileName."'...<br/>";
	echo "Moving '".$_FILES["target_file"]["tmp_name"]."'...<br/>";
	flush();
	move_uploaded_file( $_FILES["target_file"]["tmp_name"], $targetFile );
	$xml = simplexml_load_file( $targetFile );
	if ( $xml === FALSE ){
		echo "<h1>".$fullFileName." is not a valid XML file. Please inspect it with an XML validator such as <a href=\"https://xmlnotepad.codeplex.com/\">XML Notepad</a></h1>.";
	}
	else{
		//Construct a map of the data structure of the XML file
		$map = array();
		echo "Mapping Data Structure...<br/>";
		flush();
		$tree = mapBranch( $xml,$map,null );
		echo "<pre>".print_r( $tree,true )."</pre><br/>";
		flush();

		
		//Create database to populate with contents of XML file
		switch ( $_POST["export_format"] ) {
			case "accdb":
				$accessExt = ".accdb";
				echo "Creating Fresh ".$accessExt." File from Template...<br/>";
				echo "Copying '".realpath("./")."\\templates\\DatabaseTemplate".$accessExt."' to '".realpath( "./" ).'\\'.$downloadDir.$exportName.$accessExt."'...<br/>";
				flush();
				copy ( "templates\\DatabaseTemplate".$accessExt , $downloadDir.$exportName.$accessExt );
				echo 'PDO Drivers Available: '.print_r(PDO::getAvailableDrivers(),true).'<br/>';
				$connectionString = "odbc:Driver={Microsoft Access Driver (*.mdb, *.accdb)};Dbq=".realpath("./")."\\".$downloadDir.$exportName.$accessExt.";Uid=Admin";
				echo "PDO Connecting to: ".$connectionString."<br/><br/>";			
				$connection = new PDO($connectionString);
				//Construct tables in target MS Access database
				echo "Constructing Data Tables...<br/>";
				flush();
				buildAccessDB( $connection, $tree );
				echo "Populating Data Tables...<br/>";
				flush();
				$multis = $GLOBALS["multi"];
				echo "<pre>Multis: ".print_r( $multis,true )."</pre><br/>";
				populateAccessDB( $connection, $xml );			
				break;
			default:
				throw new Exception ( "export_format '".$_POST["export_format"]."' not recognized" );
				break;
		echo "<a href=\"".$downloadDir.$exportName.$accessExt."\">Download ".$exportName.$accessExt."</a>";
		}
	flush();
	}
?>