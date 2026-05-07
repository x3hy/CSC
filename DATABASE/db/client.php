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
ob_start();

// return data "struct"
function client_exit($status, $message){
	ob_end_clean();
	echo json_encode([
		"status" => $status,
		"message" => $message,
	]);
	exit; 
}

// gets the comments (and their scores and user values)
function client_get_posts($parent, string $username){
	global $conn;
	$posts = false;
	if ($parent == null)
		$posts = get_root_posts();
	else $posts = get_post_children($parent);
	
	if ($posts === false)
		return [1, "Post does not exist"];
	
    foreach ($posts as $index => $post) {
        $posts[$index]["score"] = get_post_score($post["id"]);
		
		// Get the poster information
		$user = get_username_by_id($post["user_id"]);
		$posts[$index]["username"] = $user[0];
		$posts[$index]["display"] = $user[1];
		$posts[$index]["comment_count"] = get_post_children_count($post["id"]);
		
		// Get the users vote information
		$user_id = username_exist($username);
		if ($viewed_user !== false){
			$vote_info = get_upvote_by_user_id($user_id, $post["id"]);
			if ($post["user_id"] == $user_id)
				$posts[$index]["owned_by_user"] = true;
			else $posts[$index]["owned_by_user"] = false;
			
			if ($vote_info !== false && $vote_info !== null){
				$posts[$index]["user_vote"]["is_upvote"] = $vote_info;
				$posts[$index]["user_vote"]["is_downvote"] = !$vote_info;
			} else 
				$posts[$index]["user_vote"]["is_upvote"] = 
					$posts[$index]["user_vote"]["is_downvote"] = false;
			$posts[$index]["post_info"] = $vote_info;
		}
    }

    return [0, $posts];
}

function client_delete_post(string $username, $post_id){
	$user_id = username_exist($username);
	$ret = delete_post($user_id, $post_id);
	if ($ret == false)
		return [1, "Failed to delete post.."];
	return [0, "Post deleted"];
}

function client_delete_post_admin($post_id){
	if(delete_row_by_id("posts", $post_id))
		return [0, "Post deleted"];
	return [1, "Failed to delete post.."];
}

function client_create_post($username, $body, $parent_id){
	$user_id = username_exist($username);
	$props = [
		"user_id" => $user_id,
		"description" => $body
	];
	
	if (isset($parent_id))
		$props["parent_id"] = $parent_id;
	
	if(insert_into_table("posts", $props) === false)
		return [1, "Failed to post, post"];
	return [0, "Post posted!"];
}

// Sets a new username for a user
function client_change_username(string $old_username, string $password, string $new_username){
	if (username_exist($new_username) !== false)
		return [1, "Username taken."];
	
	$user_id = user_exist($old_username, $password);
	if (set_property_by_id("users", $user_id, ["username" => $new_username]))
		return [0, "Username Updated"];
	return [1, "Failed to set username"];
}

function client_get_post($post_id){
	if (!post_exist($post_id))
		return [1, "Post does not exist"];
	
	$post = get_post($post_id);
	if ($post == false)
		return [1, "Post could not be fetched"];
	
	$post["score"] = get_post_score($post["id"]);
	
	$user = get_username_by_id($post["user_id"]);
	$post["username"] = $user[0];
	$post["display"] = $user[1];
	
	return [0, $post];
}

function client_upvote_post(int $post_id, string $username){
	$user_id = username_exist($username);
	$ret = upvote_post($post_id, $user_id);
	$code = $ret == false ? 1 : 0;
	$msg = $ret == false ? "Failed to upvote post" : "Success";
	return [$code, $msg];
}

function client_downvote_post(int $post_id, string $username){
	$user_id = username_exist($username);
	$ret = downvote_post($post_id, $user_id);
	$code = $ret == false ? 1 : 0;
	$msg = $ret == false ? "Failed to downvote post" : "Success";
	return [$code, $msg];
}
function client_remove_vote(int $post_id, string $username){
	$user_id = username_exist($username);
	$ret = remove_vote($post_id, $user_id);
	$code = $ret == false ? 1 : 0;
	$msg = $ret == false ? "Failed to remove post" : "Success";
	return [$code, $msg];
}


// Very verbose validate_username function
function client_validate_username(string $username)
{
	$ret = validate_username($username);
	$code = ($ret === true) ? 0 : 1;
	$msg = ($ret === true) ? "Username is valid" : $ret;
	return [$code, $msg];
}

function client_delete_user($user_id){
	if (!isset($user_id))
		return [1, "Invalid UserID provided."];
	
	$ret = delete_row_by_id("users", $user_id);
	$code = ($ret === false) ? 1 : 0;
	$msg = ($ret === false) ? "User not deleted" : "User #$user_id deleted";
	return [$code, $msg];
}

// deletes the current user
function client_delete_self(string $username, string $hashed_password){
	$ret = user_exist($username, $hashed_password);
	if ($ret === false)
		return [1, "User does not exist/Error"];
	
	return client_delete_user($ret);
}

function client_get_users_list(){
	$ret = get_user_list();
	$code = ($ret !== false) ? 0 : 1;
	$msg = ($ret !== false) ? $ret : "Failed to fetch users";
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
function client_create_user(string $username, string $hashed_password, $display)
{
	// Suppress output
	$ret = sign_up($username, $hashed_password, $display);
	
	$code = ($ret === false) ? 1 : 0;
	$msg = ($ret === false) ? "Failed to create user (perhaps a user with this username already exists)" : "Created User";
	return [$code, $msg];
}

function client_toggle_admin(int $user_id){
	$admin = is_user_admin_by_id($user_id);
	$ret = false;
	$msg = null;
	
	if ($admin === false){
		// user is not an admin
		$ret = insert_into_table("admins", ["user_id" => $user_id]);
		if ($ret === false)
			$msg = "Failed to make user admin";
		else $msg = "Made user #$user_id admin";
	} else {
		// user is already an admin
		$ret = delete_row_by_id("admins", $admin);
		if ($ret === false){
			$msg = "Failed to revoke admin";
		}
		else $msg = "Revoked admin for user #$user_id";
	}
	
	$code = ($ret === false) ? 1 : 0;
	return [$code, $msg];
}

// checks if a user is an admin
function client_is_admin(string $username, string $hashed_password)
{
	$ret = is_user_admin($username, $hashed_password);
	$code = ($ret === false) ? 1 : 0;
	$msg = ($ret === false) ? "User is not an admin" : "User is an admin at id: $ret";
	return [$code, $msg];
}

function client_is_admin_by_id(int $user_id){
	$ret = is_user_admin_by_id($user_id);
	$code = ($ret === false) ? 1 : 0;
	$msg = ($ret === false) ? "Failed to check admin status" : "Admin is a user at #$ret";
	return [$code, $msg];
}

// gets the display name of a given user
function client_get_display(string $username)
{
	$ret = username_exist($username);
	if ($ret !== false){
		$user_data = get_property_by_id("users", $ret, ["display"]);
		if ($user_data["display"] !== null)
			return [0, $user_data["display"]];
	}
	
	return [1, "No Display name found"];
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
		"create_user" => client_create_user($data["auth"]["username"], $data["auth"]["password"], $data["content"]),
		"is_admin" => client_is_admin($data["auth"]["username"], $data["auth"]["password"]),
		"get_display" => client_get_display($data["content"]),
		"is_admin_id" => client_is_admin_by_id($data["content"]),
		"get_posts" => client_get_posts($data["content"], $data["auth"]["username"]),
		"get_post" => client_get_post($data["content"]),
				 
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
		"delete_self" => client_delete_self($data["auth"]["username"], $data["auth"]["password"]),
		"upvote_post" => client_upvote_post($data["content"], $data["auth"]["username"]),
		"downvote_post" => client_downvote_post($data["content"], $data["auth"]["username"]),
		"remove_vote" => client_remove_vote($data["content"], $data["auth"]["username"]),
		"change_username" => change_username($data["auth"]["username"], $data["auth"]["username"], $data["content"]),
		"delete_post" => client_delete_post($data["auth"]["username"], $data["content"]),
		"create_post" => client_create_post($data["auth"]["username"], $data["content"]["content"], $data["content"]["parent_id"]), // big boi

		default => null
	};
}

// Ring 2, username, passord and ADMIN roles are required (propriatary functions)
function ring_2($data)
{
	return match ($data["call"])
	{
		"admin_ping" => client_ping(),
		"list_users" => client_get_users_list(),
		"delete_user" => client_delete_user($data["content"]),
		"toggle_admin" => client_toggle_admin($data["content"]),
		"delete_post_admin" => client_delete_post_admin($data["content"]),

		default => null
	};
}

// main:
if ($data !== null && is_array($data))
  {
	// The given json should contain a "call" value
	if (!isset($data["call"]))
		client_exit(1, "Required parameters not given:");
	
	if (!isset($data["content"]))
		$data["content"] = NULL;
	
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
	// $data["auth"] = $auth = [];
	
	
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