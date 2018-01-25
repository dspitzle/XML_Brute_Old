<?php

namespace XML_Brute\Builders;

require_once("Helpers\FileHelper.php");

use XML_Brute\Helpers\FileHelper;

abstract class Builder{

	protected $fileInfo = null;
	
	protected function __construct(){
		$this->fileInfo = FileHelper::get_Instance();
	}
	
	abstract function build();

	abstract function populate(\SimpleXMLElement $xml);
	
}