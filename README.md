# Codiad-SQLExternalAuth
SQL Database External Authentication Drop-In for Codiad using PHP Data Objects.

Written by Korynkai (Matt Schultz) of QuantuMatriX Technologies.

## Installation

* Download `SQL.php` here: [SQL.php](https://raw.github.com/QMXTech/Codiad-SQLExternalAuth/master/SQL.php) (right-click -> Save Link As).

* Edit `SQL.php` in a text editor, changing configuration values as needed (see below in "Configuration" for a description of these values). Do not edit the core logic (anything under the "Do not edit anything under..." line) -- you can break functionality, corrupt your users.php file, or even accidentally allow anybody to log in and modify your code. Only edit under the line if you're looking to experiment and have a test environment set up.

* Save `SQL.php` somewhere on the webserver, preferably somewhere within the Codiad root (I created a special directory for External Authentication called `auth` on my setup) and ensure your webserver daemon has permissions to read the file.

* Edit Codiad's `config.php` in a text editor, uncommenting and/or adding the line `define("AUTH_PATH", "/path/to/SQL.php");`. Replace "/path/to" with the actual path. You may use the `BASE_PATH` directive if you saved `SQL.php` to somewhere within the Codiad root. For example, on my setup (with the `auth` directory), this is set to `define("AUTH_PATH", BASE_PATH . "/auth/SQL.php");`

:exclamation: Make sure the database and table specified has a username and password column as specified in the configuration and the password column uses a hash format compatible with PHP's "password_verify()" method. The simplest table for Codiad would be created by executing the following SQL: `CREATE TABLE users ( "user" TEXT NOT NULL, "password" TEXT NOT NULL );`. When in doubt about password hashes, you may use the following command from a shell which has PHP in its path to generate a compatible password hash: `php -r 'print password_hash("<PASSWORD>", PASSWORD_DEFAULT)."\n";'`. _**NEVER** use straight password hashes like MD5, SHA1/2, etc... These should be considered insecure regardless of the implementation. PHP's `password_hash()` and `password_verify()` methods automatically salt the password and use a known-secure algorithm._

## Configuration

Codiad-SQLExternalAuth should support most (if not all) database drivers supported by PDO. Please read http://php.net/manual/en/pdo.drivers.php for all the drivers supported and details for each driver.

The following values should be set in accordance with the specific SQL set-up being used:

* `$server` would be your SQL server's connection DSN (`port` is optional if the default port is used); For example:
 * `$server = "pgsql:host=localhost;port=5432;dbname=codiad";` for PostgreSQL running locally with database name `codiad` and port 5432 (default for PostgreSQL, shown as an example).
 * `$server = "mysql:host=localhost;dbname=codiad";` for MySQL running locally with database name `codiad`.
 * `$server = "sqlite:/path/to/sqlite.db"` for an SQLite database file on the local filesystem at `/path/to/sqlite.db`.

* `$dbuser` and `$dbpass` are the username and password to use for Codiad to connect to a networked database server requiring authentication. These should be left blank if connecting to SQLite or another database that does not require a username and password. Example:
 * `$dbuser = "codiad";`
 * `$dbpass = "secret";`
	
* `$users_table` is the database table for Codiad to use when searching for user entries. Example:
 * `$users_table = "users";`
 
* `$username_column` and `$password_column` are the columns within the database table which represent the Codiad username and password fields. The password column _must_ be compatible with PHP's "password_verify" method (as described in the "Installation" section). Example:
 * `$username_column = "user";`  
 * `$password_column = "password";`

* `$createuser` either allows or denies the automatic creation of a Codiad user upon successful authentication. If set to true, a `user` will be created if the user successfully authenticates through the database but is not present within Codiad's `data/users.php` file. If set to `false`, the user will be denied access if they are not present within Codiad's `data/users.php` file, regardless of whether or not the user has successfully authenticated. Default is `true`.
