<?php
require __DIR__ . "/library.php";

// Public user class for storing information for a
// user.
class User {
	public $id;
	public $password;
	public $username;
	public $display;
	
	public function load_user($id){
		$this->$user_id = $id;
	}
	
	// Admin stuff
	public function is_admin(){}
	public function make_admin(){}
	public function revoke_admin(){}
	public function note(){}
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
	
	// Information
	public function password(){
		return $this->password;
	}
	public function display_name(){
		return $this->display;
	}
	public function username(){
		return $this->username;
	}
}
?>