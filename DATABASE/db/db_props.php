<?php
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
$USERNAME_VALUE =           "VARCHAR($username_char_size) NOT NULL";
$DISPLAY_VALUE =                      "VARCHAR($display_char_size)";
$PASSWORD_VALUE =           "VARCHAR($password_char_size) NOT NULL";
?>