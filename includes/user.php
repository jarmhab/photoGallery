<?php 

require_once(LIB_PATH.DS.'database.php');

class User extends DatabaseObject{

	protected static $table_name="users";
	protected static $db_fields = array('username', 'password', 'first_name', 'last_name');
	public $id;
	public $username;
	public $password;
	public $first_name;
	public $last_name;

	public static function authenticate($username="", $password=""){
		global $database;
		$username = $database->escape_value($username);
		$password = $database->escape_value($password);

		$sql = "SELECT * FROM users ";
		$sql .= "WHERE username = '{$username}' ";
		$sql .= "AND password = '{$password}' ";
		$sql .= "LIMIT 1";
		$result_array = self::find_by_sql($sql);
		return !empty($result_array) ? array_shift($result_array) : false;
	}

	public function full_name(){
		if(isset($this->first_name) && isset($this->last_name)){
			return $this->first_name . " " . $this->last_name;
		} else{
			return "";
		}
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

	public function save(){
		// A new record won't have an id yet.
		return isset($this->id) ? $this->update() : $this->create();
	}

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
		$sql .= join("', '", array_values($attributes)); //paneb id parameetriks tyhja stringi ja query failib
		$sql .= "')";
		var_dump($sql);
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