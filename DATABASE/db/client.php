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
define("APP_START", true);
require __DIR__ .'/include.php';

// return data "struct"
function client_exit($status, $message){
	echo json_encode([
		"status" => $status,
		"message" => $message,
	]);
	exit;
}

// Very verbose validate_username function
function client_validate_username(string $username)
{
	$ret = validate_username($username);
	$code = ($ret === true) ? 0 : 1;
	$msg = ($ret === true) ? "Username is valid" : $ret;
	return [$code, $msg];
}

// Very verbose validate_display function
function client_validate_display(string $display)
{
	$ret = validate_display($display);
	$code = ($ret === true) ? 0 : 1;
	$msg = ($ret === true) ? "Display is valid" : $ret;
	return [$code, $msg];
}

// Very verbose validate_password function
function client_validate_password(string $password)
{
	$ret = validate_password($password);
	$code = ($ret === true) ? 0 : 1;
	$msg = ($ret === true) ? "Password is valid" : $ret;
	return [$code, $msg];
}

// Creates a user (verbose)
function client_create_user(string $username, string $hashed_password)
{
	// Suppress output
	ob_start();
	$ret = sign_up($username, $hashed_password);
	ob_end_clean();
	
	$code = ($ret === false) ? 1 : 0;
	$msg = ($ret === false) ? "Failed to create user (perhaps a user with this username already exists)" : "Created User";
	return [$code, $msg];
}

// Simple ping function
function client_ping()
{
	return [0, "Ping successful"];
}

// The following is now a three-ring permissions scheme, the three rings are
// as follows:
// Ring 0 - Basic regex and ping calls, does not require a username or password
// Ring 1 - User roles (create orders, view orders blah blah blah), requires a username and password.
// Ring 2 - Admin roles (order notes and user notes) requires a username and password AND that the
//          user is a valid admin.

// Ring 0, no username or password is required (public functions)
function ring_0($data)
{
	return match ($data["call"])
	{
		"username" => client_validate_username($data["content"]),
		"display"  => client_validate_display($data["content"]),
		"password" => client_validate_password($data["content"]),
		"create_user" => client_create_user($data["auth"]["username"], $data["auth"]["password"]),
				 
		// ping!  (server function)
		"ping" => client_ping(),
		default => null
	};
}

// Ring 1, username and password are required (private functions)
function ring_1($data)
{
	return match ($data["call"])
	{
		"auth_ping" => client_ping(),
		default => null
	};
}

// Ring 2, username, passord and ADMIN roles are required (propriatary functions)
function ring_2($data)
{
	return match ($data["call"])
	{
		"admin_ping" => client_ping(),
		default => null
	};
}

// main:
if ($data !== null && is_array($data))
  {
	// The given json should contain a "call" value
	if (!isset($data["call"]))
		client_exit(1, "Required parameters not given:");

	
	// Ring 0;
	$ret = ring_0($data);
	if ($ret !== null)
		client_exit($ret[0], $ret[1]);
   
	
	// Validate the password and username and admin status peacefully
	// in preparation for Ring 1.
	$is_validated = false;
	$is_admin = false;
	$auth = $data["auth"];
	if(isset($auth["password"]) != false && isset($auth["username"]) != false)
	  {
		if (user_exist($auth["username"], $auth["password"]) == false)
			client_exit(1, "Username and password is incorrect (ring 1 access denied)");
		$is_validated = true;
		$is_admin = is_user_admin($auth["username"], $auth["password"]);
	  }
	
	// Remove the data from memory when its not needed..
	$data["auth"] = $auth = [];
	
	
	// Now these are the permission-locked features:
	// Ring 1;
	$ret = ring_1($data);
	if ($ret !== null)
		client_exit($ret[0], $ret[1]);
	
	
	// is the user an admin
	if ($is_admin === false)
		client_exit(1, "Access is forbidden, maybe the call was wrong.. (ring 2 access denied)");
	
	
	// Ring 2;
	$ret = ring_2($data);
	if ($ret !== null)
		client_exit($ret[0], $ret[1]);
  }
client_exit(1, "No/Invalid JSON data given");
?>