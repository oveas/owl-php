<?php
/**
 * \file
 * This file defines the Database drivers
 * \author Oscar van Eijk, Oveas Functionality Provider
 * \copyright{2007-2011} Oscar van Eijk, Oveas Functionality Provider
 * \license
 * This file is part of Terra-Terra.
 *
 * Terra-Terra is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * Terra-Terra is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Terra-Terra. If not, see http://www.gnu.org/licenses/.
 */

/**
 * \defgroup DBDRIVER_TableLock Table lock type
 * These flags specify the available locktype
 * @{
 */
//! Lock table for read
define ('DBDRIVER_LOCKTYPE_READ',	0);

//! Lock table for write
define ('DBDRIVER_LOCKTYPE_WRITE',	1);

//! @}


/**
 * \ingroup TT_DRIVERS
 * Interface that defines the database drivers. Some of the methods are implemented in class DbDefaults
 * \brief Database driver interface
 * \author Oscar van Eijk, Oveas Functionality Provider
 * \version Apr 12, 2011 -- O van Eijk -- initial version
 */
interface DbDriver
{
	/**
	 * Constructor; must exist but can be empty
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function __construct();

	/**
	 * Create a database table
	 * \param[in] $_resource Link with the database server
	 * \param[in] $_table Quoted table name
	 * \param[in] $_colDefs Array with the field definitions
	 * \param[in] $_idxDefs Array with the index definitions
	 * \param[in] $_engine If supported by the driver, an optional engine
	 * \return True on success
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function dbCreateTable(&$_resource, $_table, array $_colDefs, array $_idxDefs, $_engine = null);

	/**
	 * Get a description of the table fields
	 * \param[in] $_dbHandler Link to the database handler.
	 * \param[in] $_table Table name with prefix but unquoted
	 * \return Array with the fields in the format accepted by the SchemeHandler.
	 * \see SchemeHandler::defineScheme()
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function dbTableColumns(&$_dbHandler, $_table);

	/**
	 * Get a description of the table indexes
	 * \param[in] $_dbHandler Link to the database handler.
	 * \param[in] $_table Table name with prefix but unquoted
	 * \return Array with the indexes in the format accepted by the SchemeHandler.
	 * \see SchemeHandler::defineIndex()
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function dbTableIndexes(&$_dbHandler, $_table);

	/**
	 * Create the SQL code for a field definition
	 * \param[in] $_table Table name for the field, with prefix but without quotes
	 * \param[in] $_name Field name
	 * \param[in] $_desc Indexed array with the field properties
	 * \return string SQL code
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function dbDefineField ($_table, $_name, array $_desc);

	/**
	 * Create the SQL code for an index definition
	 * \param[in] $_table Table name for the index, with prefix but without quotes
	 * \param[in] $_name Index name
	 * \param[in] $_desc Indexed array with the index properties
	 * \return string SQL code or NULL when a a seperate statement is used
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function dbDefineIndex ($_table, $_name, array $_desc);

	/**
	 * Map a datatype as used by Terra-Terra (which is mainly a MySQL datatype) to the database specific type
	 * \param[in,out] $_type Description of the datatype as accepted by the SchemeHandler
	 * \see SchemeHandler::defineScheme()
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function mapType (array &$_type);

	/**
	 * Remove a database table
	 * \param[in] $_resource Link with the database server
	 * \param[in] $_table Table name
	 * \return True on success
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function dbDropTable (&$_resource, $_table);

	/**
	 * Remove a table field
	 * \param[in] $_resource Link with the database server
	 * \param[in] $_table Table name. Note the tablename should NOT be quoted!
	 * \param[in] $_field Field name
	 * \return True on success
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function dbDropField (&$_resource, $_table, $_field);

	/**
	 * Alter a table field
	 * \param[in] $_resource Link with the database server
	 * \param[in] $_table Table name. Note the tablename should NOT be quoted!
	 * \param[in] $_field Field name
	 * \param[in] $_desc Indexed array with the table description
	 * \return True on success
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function dbAlterField (&$_resource, $_table, $_field, array $_desc);

	/**
	 * Add a table field
	 * \param[in] $_resource Link with the database server
	 * \param[in] $_table Table name. Note the tablename should NOT be quoted!
	 * \param[in] $_field Field name
	 * \param[in] $_desc Indexed array with the table description
	 * \return True on success
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function dbAddField (&$_resource, $_table, $_field, array $_desc);

	/**
	 * Create a new database
	 * \param[in] $_resource Link with the database server
	 * \param[in] $_name Name of the new database
	 * \return a negative value on failures, any other integer on success
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function dbCreate (&$_resource, $_name);

	/**
	 * Get the last error number and error text from the database server
	 * \param[in] $_resource Link with the database server
	 * \param[out] $_number Error number
	 * \param[out] $_text Error text
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function dbError (&$_resource, &$_number, &$_text);

	/**
	 * Check is the given error code is a retryable error (e.g. table locked, server starting etc)
	 * and advice how long to wait before retry.
	 * \param[in] $_errorCode The errorcode
	 * \return Adviceable time to wait for a retry in milliseconds, or 0 if no retry is possible
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function isRetryable ($_errorCode);

	/**
	 * Make a connection with a database server
	 * \param[out] $_resource Link with the database server
	 * \param[in] $_server Server to connect to
	 * \param[in] $_name Database name to open
	 * \param[in] $_user Username to connect with
	 * \param[in] $_password Password to connect with
	 * \param[in] $_multiple True when multiple connections are allowed, default is false
	 * \return True on success, false on failures
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function dbConnect (&$_resource, $_server, $_name, $_user, $_password, $_multiple = false);

	/**
	 * Open a database
	 * \param[in,out] $_resource Link with the database server
	 * \param[in] $_server Server to connect to
	 * \param[in] $_name Database name to open
	 * \param[in] $_user Username to connect with
	 * \param[in] $_password Password to connect with
	 * \return True on success, false on failures
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function dbOpen (&$_resource, $_server, $_name, $_user, $_password);

	/**
	 * Update settings in the active database session.
	 * \param[in] $_resource Link with the database server
	 * \param[im] $_settings An array with 'key' => 'value' paires where key is the session item to be set
	 * and 'value' the value. The driver class is responsible for translating the 'key'/'value' pairs
	 * to database-specific commands.
	 */
	public function setSession (&$_resource, array $_settings);

	/**
	 * Get a list with tablenames
	 * \param[in] $_resource Link with the database server
	 * \param[in] $_pattern Search pattern
	 * \param[in] $_views True when views should be included. Default is false
	 * \return Indexed array with matching tables and their attributes
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function dbTableList (&$_resource, $_pattern, $_views = false);

	/**
	 * Execute a database statement that does not return any value
	 * \param[in] $_resource Link with the database server
	 * \param[in] $_statement Statement to execute
	 * \return True on success, false on failures
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function dbExec (&$_resource, $_statement);

	/**
	 * Read from the database
	 * \param[out] $_data Dataset retrieved by the given query
	 * \param[in] $_resource Link with the database server
	 * \param[in] $_query Query to execute
	 * \return True on success, false on failures
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function dbRead (&$_data, &$_resource, $_query);

	/**
	 * Write to the database
	 * \param[in] $_resource Link with the database server
	 * \param[in] $_query Query to execute
	 * \return Number of affected rows, or -1 on failures
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function dbWrite (&$_resource, $_query);

	/**
	 * Retrieve the last auto generated ID value
	 * \param[in] $_resource Link with the database server
	 * \param[in] $_table Table to take the value from
	 * \param[in] $_field Name of the Auto field
	 * \return The last generated ID, or 0 when not found
	 * \todo In the mysql_i driver, mysqli_insert_id() always returns 0 when called in this method.
	 * As a workaround, the $lastInsertedId property is introduced, but now mysqli_insert_id() has
	 * to be called in dbWrite(), after every write action.
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function dbInsertId (&$_resource, $_table, $_field);

	/**
	 * Get the number of rows in a dataset
	 * \param[in] $_data as returned by DbDriver::dbRead()
	 * \return Number of rows in the set
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function dbRowCount (&$_data);

	/**
	 * Get the next record from a dataset
	 * \param[in] $_data as returned by DbDriver::dbRead()
	 * \return Record as an associative array (fieldname => fieldvalue)
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function dbFetchNextRecord (&$_data);

	/**
	 * Clear a dataset
	 * \param[in] $_data as returned by DbDriver::dbRead()
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function dbClear (&$_data);

	/**
	 * Close a database connection
	 * \param[in] $_resource Link with the database server
	 * \return True on success, false on failures
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function dbClose (&$_resource);

	/**
	 * Open a new transaction
	 * \param[in] $_resource Link with the database server
	 * \param[in] $_name Transaction name for databases that support named transactions
	 * \return True on success, false on failures
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function dbTransactionStart (&$_resource, $_name = null);

	/**
	 * Commit transaction
	 * \param[in] $_resource Link with the database server
	 * \param[in] $_name Transaction name for databases that support named transactions
	 * \param[in] $_new Boolean, true when a new transaction should be started after the commit
	 * \return True on success, false on failures
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function dbTransactionCommit (&$_resource, $_name = null, $_new = false);

	/**
	 * Rollback a transaction
	 * \param[in] $_resource Link with the database server
	 * \param[in] $_name Transaction name for databases that support named transactions
	 * \param[in] $_new Boolean, true when a new transaction should be started after the rollback
	 * \return True on success, false on failures
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function dbTransactionRollback (&$_resource, $_name = null, $_new = false);

	/**
	 * Lock one or more tables for read or write
	 * \param[in] $_resource Link with the database server
	 * \param[in] $_table Either a single table name, of a list of tables as array
	 * \param[in] $_type Lock type (read or write, defaults to read)
	 * \return True on success, false on failures
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function tableLock(&$_resource, $_table, $_type = DBDRIVER_LOCKTYPE_READ);

	/**
	 * Unlock (a) previously locked table(s)
	 * \param[in] $_resource Link with the database server
	 * \param[in] $_table Either a single table name, of a list of tables as array. Defaults to an empty array top unlock all
	 * \return True on success, false on failures
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function tableUnlock(&$_resource, $_table = array());

	/**
	 * Empty a table
	 * \param[in] $_resource Link with the database server
	 * \param[in] $_table Table name
	 * \return True on success, false on failures
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function emptyTable (&$_resource, $_table);

	/**
	 * Escape a given string for use in queries
	 * This method is implemented by class DbDefaults
	 * \param[in] $_string The input string
	 * \return String in SQL safe format
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function dbEscapeString ($_string);

	/**
	 * Unescape a string fthat is formatted for use in SQL
	 * This method is implemented by class DbDefaults
	 * \param[in] $_string The input string in SQL safe format
	 * \return String without SQL formatting
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function dbUnescapeString ($_string);

	/**
	 * Enclose a string (field- or table name) with quotes or backticks,
	 * if so specified in the driver.
	 * \param[in] $_string The input string in SQL safe format
	 * \return Quoted textstring
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function dbQuote ($_string);

	/**
	 * Implementation of the SQL COUNT() function.
	 * \param[in] $_field Name of the field
	 * \param[in] $_arguments Array with arguments, which is required by syntax
	 * \return Complete SQL function code
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function functionCount($_field, array $_arguments = array());

	/**
	 * Implementation of the SQL MAX() function.
	 * \param[in] $_field Name of the field
	 * \param[in] $_arguments Array with arguments, which is required by syntax
	 * \return Complete SQL function code
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function functionMax($_field, array $_arguments = array());

	/**
	 * Implementation of the SQL MIN() function.
	 * \param[in] $_field Name of the field
	 * \param[in] $_arguments Array with arguments, which is required by syntax
	 * \return Complete SQL function code
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function functionMin($_field, array $_arguments = array());

	/**
	 * Implementation of the SQL IF() function.
	 * \param[in] $_field Name of the field
	 * \param[in] $_arguments Array with arguments. This array should be
	 * in the format (check, value, then, else), e.g. array('<', 5 , 'Less then 5', '5 or more')
	 * \return Complete SQL function code
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function functionIf($_field, array $_arguments = array());

	/**
	 * Implementation of the SQL IFNULL() function.
	 * \param[in] $_field Name of the field
	 * \param[in] $_arguments Array with arguments. The array should have 1 element, which is the
	 * default value when $_field is empty. Note for literal string values this field must be quoted, e.g. "'value'"!
	 * \return Complete SQL function code
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function functionIfnull($_field, array $_arguments = array());

	/**
	 * Implementation of the SQL CONCAT() function.
	 * \param[in] $_field Name of the field
	 * \param[in] $_arguments Array with arguments.  The array should have 1 element, which is the
	 * value that will be concatenaterd to $_field. Note for literal string values this field must be quoted, e.g. "'value'"!
	 * \return Complete SQL function code
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function functionConcat($_field, array $_arguments = array());
}
