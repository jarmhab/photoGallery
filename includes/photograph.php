<?php

require_once(LIB_PATH.DS.'database.php');

class Photograph extends DatabaseObject{

	protected static $table_name="photographs";
	protected static $db_fields = array('id', 'filename', 'type', 'size', 'caption');
	public $id;
	public $filename;
	public $type;
	public $size;
	public $caption;

	private $temp_path;
	protected $upload_dir="images";
	public $errors=array();

	protected $upload_errors = array(
	0 => 'There is no error, the file uploaded with success',
    1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
    2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
    3 => 'The uploaded file was only partially uploaded',
    4 => 'No file was uploaded',
    6 => 'Missing a temporary folder',
    7 => 'Failed to write file to disk.',
    8 => 'A PHP extension stopped the file upload.',
	);

	//Pass in $_FILE(['uploaded_file']) as an argument
	public function attach_file($file){
		//Perform error checking on the form parameters
		if(!$file || empty($file) || !is_array($file)){
			//error: nothing uploaded or wrong argument usage
			$this->errors[] = "No file was uploaded.";
			return false;
		} elseif ($file['error'] != 0){
			//error: report what PHP says went wrong
			$this->errors[] = $this->upload_errors[$file['error']];
			return false;
		} else {
			//Set object attributes to the form parameters
			$this->temp_path = $file['tmp_name'];
			$this->filename = basename($file['name']);
			$this->type = $file['type'];
			$this->size = $file['size'];
			//Don't worry about saving anything to the database yet
			return true;
		}
	}

	public function save(){
		// A new record won't have an id yet
		if(isset($this->id)){
			// Really just to update the caption
			$this->update();
		} else {
			//make sure there are no errors
			if(!empty($this->errors)){ return false;}

			//make sure the caption is not too long for DB
			if(strlen($this->caption) > 255){
				$this->errors[] = "The caption can only be 255 charaters long.";
				return false;
			}

			//can't save without filename and temp location
			if(empty($this->filename) || empty($this->temp_path)){
				$this->errors[] = "The file location was not avilable.";
				return false;
			}
			//Determine the target_path
			$target_path = SITE_ROOT.DS. 'public' .DS. $this->upload_dir .DS. $this->filename;

			//Make sure the file doesn't esxist in the target location
			if(file_exists($target_path)){
				$this->errors[] = "The file {$this->filename} already exists.";
				return false;
			}
			//attempt to move the file
			if(move_uploaded_file($this->temp_path, $target_path)){
				//success
				if($this->create()){
					unset($this->temp_path);
					return true;
				}
			} else {
				//failure
				$this->errors[] = "The file upload failed, possibly due to incorrect permissions on the upload folder.";
				return false;
			}

			//save a corresponding entry to the database
			$this->create();
		}
	}



	public function destroy(){
		// remove the database entry
		if($this->delete()) {
			// then remove file
			$target_path = SITE_ROOT.DS.'public'.DS.$this->image_path();
			return unlink($target_path) ? true : false;
		} else {
			//database delete failed
			return false;
		}
		
	}

	public function image_path(){
		return $this->upload_dir.DS.$this->filename;
	}

	public function comments() {
		return Comment::find_comments_on($this->id);
	}

	protected function attributes(){
		//return an array of attribute keys and theid values
		$attributes = array();
		foreach(self::$db_fields as $field){
			if(property_exists($this, $field)){
				$attributes[$field] = $this->$field;
			}
		}
		return $attributes;
	}

	protected function sanitized_attributes(){
		global $database;
		$clean_attributes = array();
		//sanitize the values before submitting
		//Note: does not alter the actual value of each attribute
		foreach($this->attributes() as $key => $value){
			$clean_attributes[$key] = $database->escape_value($value);
		}
		return $clean_attributes;
	}

	// Replaced with custom save()
	// public function save(){
	// 	// A new record won't have an id yet.
	// 	return isset($this->id) ? $this->update() : $this->create();
	// }

	public function create(){
		global $database;
		// Don't forget your SQL syntax and good habits:
		// - INSERT INTO table(key, key) VALUES ('value', 'values')
		// - single-quotes around values
		// - escape all values to prevent SQL injection

		$attributes = array_filter($this->sanitized_attributes());
		$sql = "INSERT INTO ".self::$table_name." (";
		// $sql .= "username, password, first_name, last_name";
		$sql .= join(", ", array_keys($attributes));
		$sql .= ") VALUES ('";
		// $sql .= $database->escape_value($this->username) ."', '";
		// $sql .= $database->escape_value($this->password) ."', '";
		// $sql .= $database->escape_value($this->first_name) ."', '";
		// $sql .= $database->escape_value($this->last_name) ."')";
		$sql .= implode("', '", array_values($attributes)); //paneb id parameetriks tyhja stringi ja query failib		
		$sql .= "')";
		if($database->query($sql)){
			$this->id = $database->insert_id();
			return true;
		} else {
			return false;
		}
	}

	public function update(){
		global $database;
		// Don't forget your SQL syntax and good habits:
		// - UPDATE table SET key='value', key='value' WHERE condition
		// - single-quotes around values
		// - escape all values to prevent SQL injection

		$attributes = $this->sanitized_attributes();
		$attribute_pairs = array();
		foreach($attributes as $key => $value){
			$attribute_pairs[] = "{$key}='{$value}'";
		}
		$sql = "UPDATE ".self::$table_name." SET ";
		$sql .= join(", ", $attribute_pairs);
		$sql .= " WHERE id=". $database->escape_value($this->id);
		$database->query($sql);
		return ($database->affected_rows() == 1) ? true : false;
	}

	public function delete(){
		global $database;
		// Don't forget your SQL syntax and good habits:
		// - DELETE FROM table WHERE condition LIMIT 1
		// - escape all values to prevent SQL injection
		// - use LIMIT 1
		$sql = "DELETE FROM ".self::$table_name." ";
		$sql .= "WHERE id=". $database->escape_value($this->id);
		$sql .= " LIMIT 1";
		$database->query($sql);
		return ($database->affected_rows() == 1) ? true : false;
	}
}

?>