<?php

namespace XML_Brute\Builders;


//Import configuration variables
$config = parse_ini_file('config.ini.php');

//import required database formats
foreach($config['DbFormats'] as $key=>$value){
	echo 'Requiring '.$key.'Builder.php<br/>';
	require_once('Builders\\'.$key.'Builder.php');
}


/**
* A Factory class that instantiates the database builder
* matching the format chosen by the user
*/
class BuilderFactory{

	public static function build($format, array $tree){
		$builder = 'XML_Brute\\Builders\\'.$format."Builder";
		if(class_exists($builder)){
			return new $builder($tree);
		}
		else{
			throw new \Exception("Invalid database format ".$builder." given.");
		}
	}

}
