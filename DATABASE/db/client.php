<?php
/*
This file is the connection system used for when the front-end
needs data from the backend. the ONLY connection that the back-end
has with the front-end is here. This is IT. 

PHP is server side only, PHP is very volatile when using it in 
the front-end as a user can just go and edit the public PHP to run
server-side code. This means that you need more security messures on
both the client and server to try and prevent this issue. This 
approach solves this by simply not using PHP on the front-end.

This file only has a few calls that the front-end can access. This
project assumes that the PHP in this file is READ-ONLY or even better
private resource so that a user cannot see this.
*/

// fetch posted json
header('Content-Type: application/json');
$json = file_get_contents('php://input');
$data = json_decode($json, true);
require __DIR__ .'/include.php';

// return data "struct"
function client_exit($status, $message){
	echo json_encode([
		"status" => $status,
		"message" => $message,
	]);
	exit;
}

// if this file was given any data:
if ($data !== null && is_array($data))
  {
	// the json data should contain a "call" value,
	// this references the function the frontend wants
	// to run. also given must be the "content" value
	// which holds the data given to the function.
	if (!isset($data["call"]) || !isset($data["content"]))
		client_exit(1, "Required parameters not given:");

	// Validate the password and username and admin status peacefully
	$is_validated = false;
	if(isset($data["password"]) != false && isset($data["username"]) != false)
	  {
		if (validate_user($data["username"], $data["password"]) == false)
			client_exit(1, "Username and password is incorrect");
		$is_validated = true;
		$is_admin = is_user_admin($data["username"], $data["password"]);
	  }
	
	// the following is now a three-ring permissions scheme, the three rings are
	// as follows:
	// ring 0 - basic regex and ping calls, does not require a username or password
	// ring 1 - user roles (create orders, view orders blah blah blah), requires a username and password.
	// ring 2 - admin roles (order notes and user notes) requires a username and password AND that the
	//          user is a valid admin.
		
	// run the given mode as a function, then return
	// the value. Below are some basic calls that do
	// not require valid ID.
	$value = match ($data["call"])
	{
		"username" => validate_username($data["content"]),
		"display"  => validate_display($data["content"]),
				 
		// ping!
		"ping" => "",
		default => null
	};
	
	if ($value !== null)
		client_exit(0, $value);
	
	// catch invalid permissions
	if (!$is_validated)
		client_exit(1, "Access is forbidden, maybe the call was wrong..  (ring 1 access denied)");
	
	// Now these are the permission-locked features:
	$value = match ($data["call"])
	{
		"auth_ping" => "",
		default => null
	};
	
	if ($value !== null)
		client_exit(0, $value);
	
	// is the user an admin
	if ($is_admin == false)
		client_exit(1, "Access is forbidden, maybe the call was wrong.. (ring 2 access denied)");
	
	$value = match ($data["call"])
	{
		"set_order_note" => "",
		"set_user_note" => "",
		default => null
	};
	
	if ($value == null)
		client_exit(1, "invalid call");
	
  }
client_exit(1, "No JSON data given");
?>