<?php
require __DIR__ . "/post_library.php";

// Public user class for storing information for a
// user.
class User {
	// Information
	public $id;
	public $password;
	public $username;
	public $display_name;
	public $note;
	
	public function load_user($id){
		$this->$user_id = $id;
	}
	
	// Admin stuff
	public function is_admin(){
		
	}
	public function make_admin(){
		// Makes user an admin
		return insert_into_table("admins",[
			"user_id" => $id			
		]);
	}
	public function revoke_admin(){
	}
	public function set_note($text){}
	
	// Orders
	public function orders_count(){}
	public function order_ids(){}
	
	// Settings
	public function delete(){}
	public function change_username($username){}
	public function change_password($raw){}
	public function change_display($display){}
	
	// Money stuff
	public function transactions_count(){}
	public function transaction_ids(){}
	public function total_debt(){}
	public function total_paid(){}
	

}
?>