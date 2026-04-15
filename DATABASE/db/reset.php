<?php defined('APP_START') or die('Access denied.');
// Load the library file, it contains all of the 
// semantic functions used in this file.
require __DIR__ . "/include.php";


echo "<b>Deleting tables:</b><br>";
delete_tables($tables);


echo "<br><b>Creating tables:</b><br>";

// Create the sessions table


// Create the users table
create_table(
"users", "
	id $ID_VALUE,
	note $NOTE_VALUE,
	username $USERNAME_VALUE,
	password $PASSWORD_VALUE,
	display $DISPLAY_VALUE
");

// Create the orders table
create_table(
"orders", "
	id $ID_VALUE,
	note $NOTE_VALUE,
	description $DESCRIPTION_VALUE,
	issued $TIMESTAMP_VALUE,
	is_paid  BOOLEAN
");

// Create the admins modifier table
create_table(
"admins", "
	id $ID_VALUE,
	user_id $FOREIGN_ID_VALUE,
	note $NOTE_VALUE,
	FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
");

// Create the transactions table
create_table(
"transactions", "
    id $ID_VALUE,
    user_id $FOREIGN_ID_VALUE,
	order_id $FOREIGN_ID_VALUE,
    issued $TIMESTAMP_VALUE,
    amount $CURRENCY_VALUE NOT NULL,
    is_paid BOOLEAN,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
	FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
");


echo "<br><b>Inserting Data:</b><br>";

// Create a new customer user
$user_id = 
	insert_into_table("users", [
		"username" => "coolguy1",
		"display" => "Alex Smith",
		"password" => generate_password("test"),
	]
);

// Create a new order
$order_id = 
	insert_into_table("orders", [
		"note" => "test123",
		"description" => "test1234",
		"is_paid" => false
	]
);

// Give the order a transaction
insert_into_table("transactions", [
	"user_id" => $user_id,
	"order_id" => $order_id,
	"amount" => 123.12,
	"is_paid" => true
]);

// Create a new customer user
$user_id = 
	insert_into_table("users", [
		"username" => "coolguy2",
		"display" => "Matua Haimay",
		"password" => generate_password("password123")
	]
);

// make user[1] an admin:
insert_into_table("admins", [
	"user_id" => $user_id
]);


echo "<br><b>Checking permissions:<br>User data:</b>";
# fetch all of the users passwords and usernames:
$user_data = get_properties_from_table("users", ["password", "username", "id"]);

echo "<pre>";
var_dump($user_data);
echo "</pre>";

echo "Is user[0] an admin?<br>";
var_dump(is_user_admin($user_data[0]["username"], $user_data[0]["password"]));

echo "<br><br>Is user[1] an admin?<br>";
var_dump(is_user_admin($user_data[1]["username"], $user_data[1]["password"]));

echo "<br><br>Testing password generator function:<br>";
echo "Using test password <i>\"password\"</i><br>";
echo generate_password("password");

// Close connection to db
close_db_connection();
?>