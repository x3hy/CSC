<?php
// This file is hidden anyway, no point overcomplicating things by moving the data to another file.
// having variables for the properties is bad actually because any child files that include the
// dbconnect file will have a variable that has the password and username of the db.
$conn = new mysqli ('localhost', '_22158', 'fvd0SX5VvHmXnzgT', '22158_91902');
if ($conn->connect_error)
  {
	die ("Error Connecting to Database: " . $conn->connect_error);
  }

// This function prevents SQL attacks by simply only allowing
// non-volatile characters, or characters that can't possibly
// escape the scope.
function is_allowed_sql($sql)
{
	return preg_match('/^[a-zA-Z0-9_]+$/', $sql);
}


// small helper function to close the db
// semantically.
function close_db_connection()
{
	global $conn;
	$conn->close();
}


// self-explanatory name.
function run_sql_query($sql)
{
	global $conn;
	$conn->query($sql) or die("Error running sql $sql: $conn->error");
}

// Checks if a given table exists in the db
function exist_table($table_name)
{
    global $conn;
    if (empty($table_name))
        return false;

    $sql = "SELECT 1 
            FROM information_schema.tables 
            WHERE table_name = '$table_name' 
            LIMIT 1";

    $result = $conn->query($sql);
    return ($result && $result->num_rows > 0);
}

// Delete a given table if it exists
function delete_table($name)
{
    global $conn;

    // Sanitize the table name
    if (preg_match('/^[a-zA-Z0-9_]+$/', $name))
	  {
		if (!exist_table($name))
		  {
			echo "Table '$name' not deleted (does not exist)<br>";
			return;
		  }
		
        $conn->query("SET foreign_key_checks = 0");
        $conn->query("DROP TABLE IF EXISTS `$name`");

        // Check for errors
        if ($conn->error)
            echo "Error deleting table: " . $conn->error;
		else
			echo "Table '$name' deleted successfully<br>";

        // Re-enable foreign key checks
        $conn->query("SET foreign_key_checks = 1");
      }
	else echo "Invalid table name: '$name'. Only alphanumeric characters and underscores are allowed.";
}


// This one takes an array of tables and does the prior.
function delete_tables(...$names)
{
    global $conn;

    // If user passed an array, flatten it immediatly!!
    if (isset($names[0]) && is_array($names[0]))
        $names = $names[0];
	
    if (empty($names))
        return false;

    foreach ($names as $table)
        if (is_string($table))
            delete_table($table);
}

function exist_tables(...$names)
{
    global $conn;

    // If user passed an array, flatten it immediatly!!
    if (isset($names[0]) && is_array($names[0]))
        $names = $names[0];
	
    if (empty($names))
        return false;

    $sum = 0;
    foreach ($names as $table)
        if (is_string($table))
            $sum += exist_table($table) ? 1 : 0;
	
    return $sum === count($names);
}


// Creates a table with a given name and inner properties
function create_table($name, $columns)
{
    if (empty($name) || !is_string($columns) || empty($columns)) {
        echo "Invalid input: Please provide a valid table name and column definitions.";
        return;
    }

    // Concatonate the CREATE TABLE query
    $query = "CREATE TABLE `$name` ($columns)";

    // Run the Query.
	global $conn;
    if ($conn->query($query) === TRUE)
	  {
        echo "Table '$name' created successfully<br>";
      }
	else echo "Error creating table '$name': " . $conn->error;
}

// Inserts data into a table
function insert_into_table($table_name, array $data)
{
    global $conn;
    if (empty($data))
	  {
        echo "Error: No data provided.<br>";
        return false;
      }

    $keys = implode(", ", array_keys($data));
    $placeholders = implode(", ", array_fill(0, count($data), "?"));
    $sql = "INSERT INTO `$table_name` ($keys) VALUES ($placeholders)";

    $stmt = $conn->prepare($sql);
    if (!$stmt)
	  {
        echo "Prepare failed: " . $conn->error;
        return false;
      }

    $types = '';
    $values = array_values($data);

    foreach ($values as $value)
	  {
		 $types .= match (true)
		   {
       		is_bool($value) =>'i',
        	is_int($value) => 'i',
        	is_float($value) => 'd',
        	default => 's',
          };
      }

    $stmt->bind_param($types, ...$values);

    if ($stmt->execute())
	  {
		$new_id = $conn->insert_id;
        echo "Data successfully inserted into `$table_name` table.<br>";
        $stmt->close();
        return $new_id;
		
      }
	else
	  {
        echo "Error inserting data: $stmt->error.<br>";
        $stmt->close();
        return false;
      }
}

// wrapper to generate a password based off of
// a string.
function generate_password($raw){
	return hash('sha256', $raw);
}

// Gets a valued array of items from a $table based on the
// given $id. returns the $props as a valued array.
// E.g
// [
//     "my_prop_name" => "value from table!"
// ]
function get_property_by_id($table, $id, array $props) {
    global $conn;

    $columns = implode(', ', $props);

    $stmt = $conn->prepare("
        SELECT $columns 
        FROM $table 
        WHERE id = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $values = $result->fetch_assoc();

    $return = [];

    foreach ($props as $prop) {
        $return[$prop] = $values[$prop] ?? null;
    }

    return $return;
}

// same as the other function, but this one gets properties
// across ALL rows.
function get_properties_from_table($table, array $props) {
    global $conn;

    if (empty($props)) {
        return [];
    }

    $columns = implode(', ', $props);

    $stmt = $conn->prepare("
        SELECT $columns 
        FROM $table
    ");

    if (!$stmt) {
        return ["error" => "Prepare failed: " . $conn->error];
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $item = [];
        foreach ($props as $prop) {
            $item[$prop] = $row[$prop] ?? null;
        }
        $data[] = $item;
    }

    $stmt->close();

    return $data;
}

/*
This function will search for a given session value
in the sessions table, if it finds it then it will return
true meaning that the session is valid (it exists) or if
the session is not found then this function will return 0
meaning the session is INVALID!. The statements of this
function are kept hard-coded to disallow any medling.
*/
function is_valid_session($session){
    global $conn;
    $stmt = $conn->prepare("
        SELECT *
        FROM sessions
    ");

    $stmt->execute();
    $result = $stmt->get_result();
	$ret = 0;
	
    while ($row = $result->fetch_assoc()) {
        $item = [];
		if ($row["hash"] == "$session"){
			$ret = 1;
			break;
		}
    }

	$stmt->close();
    return $ret;
}
?>