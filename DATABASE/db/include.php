<?php defined('APP_START') or die('Access denied.');
// #ifndef DB_LIBRARY
// #define DB_LIBRARY
if (!defined('DB_LIBRARY')) {
    define('DB_LIBRARY', true);
	
	// Load the include librarys ONCE.
	require __DIR__ . "/library.php";
	
	// Some variables for differing sizes so we can
	// easily change them if need be.
	$id_int_size =             8;
	$note_char_size =        128;
	$username_char_size =     32;
	$description_char_size = 256;
	$display_char_size =      64;
	$password_char_size =     64; // cos its a hash..

	// side note: for the hash, to make it a bit smaller you can cross reference the
	// ascii codes of the first and second halfs of the hash to generate a half size hash
	// that can be expanded to the larger hash.
	
	// Value properties, made to be used in the actual sql table statements.
	$ID_VALUE = "INT($id_int_size) UNSIGNED AUTO_INCREMENT PRIMARY KEY";
	$NOTE_VALUE =                            "VARCHAR($note_char_size)";
	$FOREIGN_ID_VALUE =           "INT($id_int_size) UNSIGNED NOT NULL";
	$TIMESTAMP_VALUE =            "TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
	$CURRENCY_VALUE =                                   "DECIMAL(10,2)";
	$DESCRIPTION_VALUE =              "VARCHAR($description_char_size)";
	$USERNAME_VALUE =    "VARCHAR($username_char_size) NOT NULL UNIQUE";
	$DISPLAY_VALUE =                      "VARCHAR($display_char_size)";
	$PASSWORD_VALUE =           "VARCHAR($password_char_size) NOT NULL";
	$reset_file = "reset.php";
	
	// Confirm that the database has been initialised.
	$tables = ["users", "queries", "admins", "comments"];
	if (!exist_tables($tables))
	  {
		echo "Some tables didn't exist, to fix this the database will be reset.<br><br>";
		try {
			require __DIR__ . "/$reset_file";
			echo "<br>The '$reset_file' file has been run, this should have fixed any issues.<br>";
			echo "Please reload the page now, if the page shows up blank then everything has worked.<br>";
			header("Location: " . $_SERVER['PHP_SELF']);
		} catch (Exception $e){
			echo "Fatal error occured ($e) running $reset_file. You're on your own now.<br>";
		}
	  }
	// Library has been loaded properly.
}
// #endif // !DB_LIBRARY
?>