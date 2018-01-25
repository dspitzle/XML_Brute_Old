<?php

namespace XML_Brute\Helpers;

/**
* A Singleton class that provides directory information, 
* and moves and imports files
*/
class FileHelper{

	public $uploadDir = "storage\\uploads\\";
	public $downloadDir = "storage\\downloads\\";
	
	public $fullFileName = "";
	public $tmp_name = "";	
	public $fileName = "";
	public $targetFile = "";
	public $exportName = "";
	static $_instance;
	
	private function __construct(){
		$this->fullFileName = $_FILES['target_file']['name'];
		echo 'fullFileName = '.$this->fullFileName.'<br/>';		
		flush();


		$this->tmp_name = $_FILES["target_file"]["tmp_name"];	
		echo 'tmp_name = '.$this->tmp_name.'<br/>';	
		flush();

		( strrpos( $this->fullFileName, '.' ) !== FALSE ) ? $this->fileName = substr( $this->fullFileName, 0, strrpos( $this->fullFileName,'.' ) ) : $this->fileName = $this->fullFileName;
		echo 'fileName = '.$this->fileName.'<br/>';	
		flush();
		$this->targetFile = $this->uploadDir.$this->fullFileName;
		$this->exportName = $this->fileName.'-'.date( 'YmdHis' );
		


		echo 'targetFile = '.$this->targetFile.'<br/>';	
		echo 'exportName = '.$this->exportName.'<br/>';
		flush();
	}

	
	private function __clone(){}

	
	public static function get_Instance(){
		if ( !(self::$_instance instanceof self) ){
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	
	public function importFile(){
		echo "Connecting to Data File '".$this->fullFileName."'...<br/>";
		echo "Moving '".$this->tmp_name."'...<br/>";
		flush();
		move_uploaded_file( $this->tmp_name, $this->targetFile );
		return simplexml_load_file( $this->targetFile );
	}
	
	
}