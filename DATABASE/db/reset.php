<?php
// Load the library file, it contains all of the 
// semantic functions used in this file.
define('APP_START', 1);
require __DIR__ . "/include.php";


echo "<b>Deleting tables:</b><br>";
delete_tables($tables);

echo "<br><b>Creating tables:</b><br>";

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
"posts", "
	id $ID_VALUE,
	user_id $FOREIGN_ID_VALUE,
	description $DESCRIPTION_VALUE,
	time_issued $TIMESTAMP_VALUE,
	parent_id $FOREIGN_ID_VALUE,
	FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
	FOREIGN KEY (parent_id) REFERENCES posts(id) ON DELETE CASCADE
");

// Votes for a given post
create_table(
"votes", "
	id $ID_VALUE,
	is_upvote $BOOL_VALUE,
	user_id $FOREIGN_ID_VALUE,
	post_id $FOREIGN_ID_VALUE,
	FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
	FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
");

// Create the admins modifier table
create_table(
"admins", "
	id $ID_VALUE,
	user_id $FOREIGN_ID_VALUE,
	FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE NO ACTION
");

echo "<br><b>Inserting Data:</b><br>";

$data_amount = 20;

// Create some users
for ($i = 1; $i <= $data_amount; $i++) {
	insert_into_table("users", [
		"username" => "coolguy$i",
		"password" => generate_password("password$i")
	]);
}

// Create some posts
for ($i = 1; $i <= $data_amount; $i++){
	insert_into_table("posts", [
		"user_id" => mt_rand(1, $data_amount),
		"description" => "this is post #$i"
	]);
}

// Create subposts on each post:
for ($i = 1; $i <= $data_amount; $i++){
	for ($j = 1; $j <= $data_amount; $j++){
		insert_into_table("posts", [
			"user_id" => mt_rand(1, $data_amount),
			"description" => "this is SUBpost #$i-$j",
			"parent_id" => mt_rand(1, $data_amount),
		]);
	}
}

$post_count = $data_amount + ($data_amount * $data_amount);
echo "created <i>$post_count</i> posts<br>";

// upvote some posts
for ($i = 1; $i <= $data_amount*$data_amount; $i++){
	insert_into_table("votes", [
		"user_id" => mt_rand(1, $data_amount),
		"post_id" => mt_rand(1, $post_count),
		"is_upvote" => random_int(0, 1) ? true : false
	]);
}

// make user[1] an admin:
insert_into_table("admins", [
	"user_id" => 1
]);

// Change the admins display name
set_property_by_id("users", 1, [
	"display" => "\"Matua Haimay\""
]);

echo "<br><b>Checking permissions:<br>User data:</b>";

// fetch all of the users passwords and usernames:
$user_data = get_properties_from_table("users", ["password", "username", "id"]);

/*
echo "<pre>";
var_dump($user_data);
echo "</pre>";
*/

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