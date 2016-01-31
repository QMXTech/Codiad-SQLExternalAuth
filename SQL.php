<?php

/*
 * Codiad SQL Database External Authentication Bridge using PHP Data Objects
 *
 * Copyright (C) 2016 Matt Schultz (Korynkai) & QuantuMatriX Technologies (qmxtech.com)
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
 
///////////////////
// CONFIGURATION //
///////////////////
	
// Server connection string
//	This defines the database connection string (dsn). The 'port' attribute is optional 
// 	unless your database server is running on a non-standard port.
//  Examples:
//		PostgreSQL (with port): "pgsql:host=localhost;port=5432;dbname=codiad";
//		MySQL:					"mysql:host=localhost;dbname=codiad";
//		SQLite:					"sqlite:/path/to/sqlite.db"
//	  Other database schemes supported by PDO may also work. Your mileage may vary.
	$server = "mysql:host=localhost;dbname=codiad";
	
// Database Connection Username and Password
//	Note: leave these blank if attempting to connect to an SQLite or other database 
//	that does not require a username and password.
	$dbuser = "codiad";
	$dbpass = "secret";
	
// User table
//	The database table where the Codiad usernames and passwords are stored.
//	Default is 'users';
	$users_table = "users";
	
// Table layout
//	The columns within the above defined user table which correspond to the Codiad username 
//  and password.
//	Defaults: $username_column = "user";
//			  $password_column = "password";
	$username_column = "user";
	$password_column = "password";
	
// Example simple SQL user table:
//	CREATE TABLE "users" ( "user" TEXT NOT NULL, "password" TEXT NOT NULL );

// Note: Passwords should be stored in a manner equivalent to PHP's "password_hash()"
//	method and compatible with PHP's "password_verify()" method. When in doubt while
//	generating a new password hash, use the following command (from shell or equivalent)
//	to generate a password hash (replacing "<PASSWORD>" with your password of choice):
//		php -r 'print password_hash("<PASSWORD>", PASSWORD_DEFAULT)."\n";'
	
// Optionally create Codiad user if it doesn't already exist. This can be set
// to 'false' if the administrator would like to manually control access to 
// Codiad from within Codiad itself, rather than let the search filter fully
// dictate user access control. 
// 	Default is 'true'.
    $createuser = true;

/////////////////////////////////////////////////////////////////////////////
// DO NOT EDIT ANYTHING UNDER THIS LINE UNLESS YOU KNOW WHAT YOU'RE DOING! //
/////////////////////////////////////////////////////////////////////////////

    // Ensure we have class.user.php so we may use this class.
    require_once( COMPONENTS . "/user/class.user.php" );

    // Check if our session is not logged in.
    if ( !isset( $_SESSION['user'] ) ) {

		// Check if a username and password were posted.
		if ( isset( $_POST['username'] ) && isset( $_POST['password'] ) ) {

			// Create user object.
			$User = new User();

			// Initialize values of user object.
			$User->username = $_POST['username'];
			$User->password = $_POST['password'];

			// Attempt to connect to the database. Die with message on failure.
			try {
				
				$socket = new PDO( $server, $dbuser, $dbpass, array( PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false ) );
				
			} catch ( RuntimeException $e ) {
				
				// Note: SQLSTATE and JSON do not mix properly... If we do not escape perfectly, Codiad doesn't tell us what's going on...
				die( formatJSEND( "error", "Database connection failed: " . str_replace( array( "\t", "\n", "\r\n", "\r" ), " ", addcslashes( $e->getMessage(), '"' ) ) ) );
				
			}
			
			// Construct query string
			$query_string = "SELECT " . $password_column . " FROM " . $users_table . " WHERE \"" . $username_column . "\" = :username LIMIT 1";
			
			// Attempt to prepare and execute query
			try {
				
				$query = $socket->prepare($query_string);
				$query->bindparam(":username", $User->username);
				$query->execute();
				
			} catch ( RuntimeException $e ) {
				
				// Note: SQLSTATE and JSON do not mix properly... If we do not escape perfectly, Codiad doesn't tell us what's going on...
				die( formatJSEND( "error", "Database query failed: " . str_replace( array( "\t", "\n", "\r\n", "\r" ), " ", addcslashes( $e->getMessage(), '"' ) ) ) );
				
			}
			
			$response = $query->fetch(PDO::FETCH_NUM);
			
			if ( is_array($response) && ( count($response) === 1 ) ) {
			
				// Check password
				if ( password_verify( $User->password, $response[0] ) ) {
					
					// Check if user already exists within users.php.
					if ( $User->CheckDuplicate() ) {

						// Check if we can create a user within users.php.
						if ( $createuser == true ) {

							// Save array back to JSON and set the session username.
							$User->users[] = array( 'username' => $User->username, 'password' => null, 'project' => "" );
							saveJSON( "users.php", $User->users );
							$_SESSION['user'] = $User->username;

						} else {

							// Deny login and send message, the user doesn't exist within users.php.
							die( formatJSEND( "error", "User " . $User->username . " does not exist within Codiad." ) );

						}

					} else {

						// Set the session username.
						$_SESSION['user'] = $User->username;

					}

					// Set the session language, if given, or set it to english as default.
					if ( isset( $_POST['language'] ) ) {

						$_SESSION['lang'] = $_POST['language'];

					} else {

						$_SESSION['lang'] = "en";

					}

					// Set the session theme and project.
					$_SESSION['theme'] = $_POST['theme'];
					$_SESSION['project'] = $_POST['project'];

					// Respond by sending verification tokens on success.
					echo formatJSEND( "success", array( 'username' => $User->username ) );
					header( "Location: " . $_SERVER['PHP_SELF'] . "?action=verify" );

				} else {

					// Invalid login.
					die( formatJSEND( "error", "Invalid user name or password." ) );

				}
			}
		}
    }

?>
