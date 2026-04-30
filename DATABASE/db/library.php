<?php defined('APP_START') or die('Access denied.');
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
			echo "Table '<i>$name</i>' not deleted (does not exist)<br>";
			return;
		  }
		
        $conn->query("SET foreign_key_checks = 0");
        $conn->query("DROP TABLE IF EXISTS `$name`");

        // Check for errors
        if ($conn->error)
            echo "Error deleting table: " . $conn->error;
		else
			echo "Table '<i>$name</i>' deleted successfully<br>";

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
        echo "Table '<i>$name</i>' created successfully<br>";
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
        echo "Data successfully inserted into `<i>$table_name</i>` table.<br>";
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
// E.g:
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


function delete_row_by_id($table, $id) {
    global $conn;
	
    $sql = "DELETE FROM `$table` WHERE id = ?";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        if (strpos($e->getMessage(), 're-prepare') !== false) {
            // retry once
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
        } else {
            throw $e;
        }
    }

    $ret = $stmt->affected_rows > 0;
    $stmt->close();
    return $ret;
}


// modifies properties at a given table and id:
function set_property_by_id($table, $id, array $props) {
    global $conn;

    $columns="";
	foreach ($props as $key => $value) {
		$columns="$columns$key = $value," ;
	}
	
	$columns = substr($columns, 0, -1);
    $stmt = $conn->prepare("
        UPDATE $table
        SET $columns
        WHERE id = ?
    ");
	
	if (!$stmt) {
        echo "error Prepare failed: " . $conn->error;
		return false;
	}

    $stmt->bind_param("i", $id);
    $stmt->execute();
	$stmt->close();
	return true;
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
        return false;
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
	

function validate_password($password)
{
    if (!is_string($password)) {
        return "Password must be a string.";
    }

    // IMPORTANT: Do NOT trim passwords (spaces can be valid characters)
    
    // Length checks
    if (strlen($password) < 8) {
        return "Password must be at least 8 characters long.";
    }
    
    if (strlen($password) > 32) {
        return "Password cannot be longer than 32 characters.";
    }

    // Allowed characters only: letters, numbers, and !@#$%^&*
    if (!preg_match('/^[a-zA-Z0-9!@#$%^&*]+$/', $password)) {
        return "Password can only contain letters (a-z, A-Z), numbers (0-9), and these symbols: ! @ # $ % ^ & *";
    }

    return true;
}

function validate_username($username)
{
    if (!is_string($username)) {
        return "Username must be a string.";
    }

    $username = trim($username);

    // Length checks
    if (strlen($username) < 3) {
        return "Username must be at least 3 characters long.";
    }
    if (strlen($username) > 32) {
        return "Username cannot be longer than 32 characters.";
    }

    // Regex: Only a-z, A-Z, 0-9, and _
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        return "Username can only contain letters, numbers, and underscores (_). No spaces or special characters allowed.";
    }

    return true;
}

function validate_display($display)
{
    if (!is_string($display)) {
        return "Display name must be a string.";
    }

    $display = trim($display);

    // Length checks:
    if (strlen($display) > 64) {
        return "Display name cannot be longer than 64 characters.";
    }

    // Regex: Letters, numbers, spaces, and allowed symbols: !@#$%^&*()_+-=.,?
    if (!preg_match('/^[a-zA-Z0-9\s!@#$%^&*()_+\-=\.,?]+$/', $display)) {
        return "Display name can only contain letters, numbers, spaces, and these symbols: ! @ # $ % ^ & * ( ) _ + - = . , ?";
    }

    return true;
}

// returns true or false depending on if a post exists by id
function post_exist(int $post_id){
	global $conn;
	$stmp = $conn->prepare("
		SELECT id
		FROM posts
		WHERE id = ?
		LIMIT 1
	");
	if (!$stmp)
		return false;
	
	$stmp->bind_param('i', $post_id);
	$stmp->execute();
	$result = $stmp->get_result();
	
	if ($result->num_rows === 0){
		$stmp->close();
		return false;
	}
	return true;
}

function get_post_children_count($post_id){
	global $conn;

	$stmt = $conn->prepare("
		SELECT COUNT(*) as count
		FROM posts
		WHERE parent_id = ?
	");

	if (!$stmt)
		return false;

	$stmt->bind_param('i', $post_id);
	$stmt->execute();

	$result = $stmt->get_result();
	$row = $result->fetch_assoc();

	$stmt->close();

	return (int)$row['count'];
}

function get_post_children($post_id){
	global $conn;
	$stmp = $conn->prepare("
		SELECT *
		FROM posts
		WHERE parent_id = ?
	");
	
	if (!$stmp)
		return false;
	
	$stmp->bind_param('i', $post_id);
	$stmp->execute();
	$result = $stmp->get_result();
	
	if ($result->num_rows === 0){
		$stmp->close();
		return false;
	}
	$rows = [];
	while ($row = $result->fetch_assoc())
		$rows[] = $row;
	
	$stmp->close();
	return $rows;
}

function get_post($post_id){
	global $conn;
	$stmp = $conn->prepare("
		SELECT *
		FROM posts
		WHERE id = ?
		LIMIT 1
	");
	
	if (!$stmp)
		return false;
	
	$stmp->bind_param('i', $post_id);
	$stmp->execute();
	$result = $stmp->get_result();
	
	if ($result->num_rows === 0){
		$stmp->close();
		return false;
	}
	$row = $result->fetch_assoc();
	$stmp->close();
	return $row;
}

function get_root_posts(){
	global $conn;
	$stmp = $conn->prepare("
		SELECT *
		FROM posts
		WHERE parent_id IS NULL
	");
	
	if (!$stmp)
		return false;
	
	$stmp->execute();
	$result = $stmp->get_result();
	
	if ($result->num_rows === 0){
		$stmp->close();
		return false;
	}
	$rows = [];
	while ($row = $result->fetch_assoc())
		$rows[] = $row;
	
	$stmp->close();
	return $rows;
}

// gets the upvotes on a post
function get_post_score(int $post_id){
    global $conn;

    $stmt = $conn->prepare("
        SELECT 
            COALESCE(SUM(CASE 
                WHEN is_upvote = 1 THEN 2
                ELSE -1
            END), 0) AS vote_score
        FROM votes
        WHERE post_id = ?
    ");

    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return (int)$row['vote_score'];
}

// returns the username and display name of a user
// given their id.
function get_username_by_id(int $user_id){
	global $conn;
	
	$stmp = $conn->prepare("
		SELECT username, display
		FROM users
		WHERE id = ?
		LIMIT 1
	");
	
	if (!$stmp)
		return false;
	
	$stmp->bind_param("i", $user_id);
	$stmp->execute();
	$result = $stmp->get_result();
	
	if ($result->num_rows === 0){
		$stmp->close();
		return false;
	}
	
	$user = $result->fetch_assoc();
	$stmp->close();
	
	return [$user["username"], $user["display"]];
}

function get_user_list(){
	global $conn;
	$stmp = $conn->prepare("
		SELECT *
		FROM users
	");
	
	$stmp->execute();
	$result = $stmp->get_result();
	$output = [];
	
	while ($row = $result->fetch_assoc()){
		$row["admin"] = is_user_admin_by_id($row["id"]);
		$output[] = $row;
	}
	
	return $output;
}

// returns a users id if they exist
function user_exist(string $username, string $hashed_password)
{
    global $conn;

    // Basic validation
    if (empty($username) || empty($hashed_password)) {
        return false;
    }
	
    // Prepare and execute query
    $stmt = $conn->prepare("
        SELECT id
        FROM users 
        WHERE username = ? AND password = ?
        LIMIT 1
    ");

    if (!$stmt) 
        return false;

    $stmt->bind_param("ss", $username, $hashed_password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        return false;
    }

    $user = $result->fetch_assoc();
    $stmt->close();
    
	return $user['id'];
}


// returns a users id if they exist
function username_exist(string $username)
{
    global $conn;

    // Basic validation
    if (empty($username)) {
        return false;
    }
	
    // Prepare and execute query
    $stmt = $conn->prepare("
        SELECT id
        FROM users 
        WHERE username = ?
        LIMIT 1
    ");

    if (!$stmt) 
        return false;

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        return false;
    }

    $user = $result->fetch_assoc();
    $stmt->close();
    
	return $user['id'];
}


function sign_up(string $username, string $hashed_password, $display)
{
	if (user_exist($username, $hashed_password) != false)
		// User already exists
		return false;
	
	$props = [
		"username" => $username,
		"password" => $hashed_password
	];
	
	// Set the display name if it is given.
	if ($display !== null)
		$props["display"] = $display;
	
	// Create the new user
	return insert_into_table("users", $props);
}

function make_user_admin_by_id(int $user_id){
	return insert_into_table("admins", ["user_id" => $user_id]);
}

// Sets a user as an admin
function make_user_admin(string $username, string $hashed_password){
	$user_id = user_exist($username, $hashed_password);
	if ($user_id == false)
		// User does not exist.
		return false;
	
	return make_user_admin_by_id($user_id);
}

function is_user_admin_by_id(int $user_id){
    global $conn;

    if (!isset($user_id))
        return false;

    $stmt = $conn->prepare("
        SELECT id
        FROM admins
        WHERE user_id = ?
        LIMIT 1
    ");

    if (!$stmt)
        return false;

	$stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        return false;
    }

    $admin = $result->fetch_assoc();
    $stmt->close();

    return $admin['id']; // admin exists
}

// fetches the users ID from the prior function, then if the id
// is found in the admins table then it will return the admins id.
// if not it will return false.
function is_user_admin(string $username, string $hashed_password)
{
	global $conn;
	$user_id = user_exist($username, $hashed_password);
	
	if ($user_id == false)
		return false;
	
	return is_user_admin_by_id($user_id);
}

/*
// Boolian for if post exists or not
function post_exist(int $post_id){

}


function create_post(string $description, int $user_id, int $parent_id){
}
*/
?>