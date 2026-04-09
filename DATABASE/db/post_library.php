<?php
// #ifndef DB_LIBRARY
// #define DB_LIBRARY
if (!defined('DB_LIBRARY')) {
    define('DB_LIBRARY', true);
	
	// Load the include librarys ONCE.
	require __DIR__ . "/library.php";
	require __DIR__ . "/db_props.php";
	$reset_file = "db_reset.php";
	
	// Confirm that the database has been initialised.
	$tables = ["users", "orders", "admins", "transactions"];
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