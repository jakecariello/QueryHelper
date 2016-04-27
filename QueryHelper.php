<?php

	/**
	* helps streamline the query-making process.
	* uses passed in config file to set database specifics/credentials.
	* example json can be found at: http://alternateassessments.net/projects/accounts/example_config.json
	*/
	class QueryHelper {
	
		private $config = [];
		
		// @parameter name of configuration file (json)
		function __construct($config_file_name) {
			
			$config_file_contents = file_get_contents($config_file_name);
			
			if($config_file_contents !== NULL) {
				$config_json = json_decode($config_file_contents, true); //true ensures use of assoc. arrays
				
				if($config_json !== NULL) {
					if(isset($config_json["database"]["host"]) && isset($config_json["database"]["username"]) 
					&& isset($config_json["database"]["password"]) && isset($config_json["database"]["name"])) {
						
						$this->config = $config_json;
						
					} else {
						die("Error: invalid JSON in config file.");
					}
					
				} else {
					die("Error: failed parsing JSON in config file.");
				}
				
			} else {
				die("Error: failed extracting contents of config file to string.");
			}
		}
		
		/* 
		* Based on query function in CS50 class of pset7 by David J. Malan
		* works similar but with slightly different implementation
		* notably, uses MySQLi framework NOT PDO (used in CS50)
		* the MySQLi framework is more intuitive and easier to read.
		* also, it is the most common framework used for connecting to SQL databases
		*/
		public function query(/* $sql [, ... ] */) {	
			$host = $this->config["database"]["host"];
			$username = $this->config["database"]["username"];
			$password = $this->config["database"]["password"];
			$database = $this->config["database"]["name"];
			
			$mysqli = new mysqli($host, $username, $password, $database);
			
			if($mysqli->connect_errno) {
				die("Error: " . $mysqli->connect_error);
			} else {
				//echo "we're rolling";
			}
			
			// the sql statement
			$sql = func_get_arg(0);
			
			// the extra parameters (corresponding to the placeholders)
			$parameters = array_slice(func_get_args(), 1);
			
			// ensure number of placeholders matches number of values
			// yay! this uses a regex to find all of the placeholders (?)
			// http://stackoverflow.com/a/22273749
			// https://eval.in/116177
			$pattern = "
				/(?:
                '[^'\\\\]*(?:(?:\\\\.|'')[^'\\\\]*)*'
                | \"[^\"\\\\]*(?:(?:\\\\.|\"\")[^\"\\\\]*)*\"
                | `[^`\\\\]*(?:(?:\\\\.|``)[^`\\\\]*)*`
                )(*SKIP)(*F)| \?
                /x
            ";
	            preg_match_all($pattern, $sql, $matches);
		
			if (count($matches[0]) < count($parameters)) {
				die("Error: Too few placeholders (?) in query.");
			} else if (count($matches[0]) > count($parameters)) {
				die("Error: Too many placeholders (?) in query.");
			}
			
			// replace placeholders with quoted, escaped strings
			// this small section is almost taken literally from the CS50 class
			$patterns = [];
			$replacements = [];
			for ($i = 0, $n = count($parameters); $i < $n; $i++) {
				array_push($patterns, $pattern);
				array_push($replacements, "'" . $mysqli->escape_string($parameters[$i]) . "'");
			}
			$query = preg_replace($patterns, $replacements, $sql, 1);
			
			// FINALLY get result of query on sql database
			if(!$result = $mysqli->query($query)) {
				die("Error: query on database failed: " . $mysqli->error . ".");
			}
	
			// if query was SELECT
			if ($result->field_count > 0) {
				// get result set's rows in one (2d) array
				$a = [];
				while($row = $result->fetch_assoc()) {
					array_push($a, $row);
				}
				
				// close result then return array
				$result->close();
				return $a;
			}
			
			// if query was DELETE, INSERT, or UPDATE
			else {
				// return number of rows affected
				return $mysqli->affected_rows;
			}

			// close database connection (good practice)
			$mysqli->close();
		}
	
	}

?>
