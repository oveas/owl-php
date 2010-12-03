<?php
/**
 * \file
 * This file defines the UserHandler class
 * \version $Id: class.userhandler.php,v 1.9 2010-12-03 12:07:42 oscar Exp $
 */

/**
 * \ingroup OWL_SO_LAYER
 * This class creates user sessions and handles logging in and out
 * \brief the user object 
 * \author Oscar van Eijk, Oveas Functionality Provider
 * \version Aug 27, 2008 -- O van Eijk -- initial version
 */
class UserHandler extends _OWL
{
	
	/**
	 * The PHP session object
	 * \private
	 */	
	protected $session;

	/**
	 * Link to a datahandler object. This dataset is used as an interface to all database IO.
	 * \private
	 */	
	protected $dataset;

	/**
	 * An indexed array with the user information as take from the database
	 * \public
	 */
	public $user_data;

	/**
	 * Class constructor; setup the user environment
	 * \public
	 * \param[in] $username Username.
	 */
	public function __construct ($username)
	{
		_OWL::init();

		$this->dataset->set_tablename('owl_userdata');

		$this->session = new Session();

		if (!isset($_SESSION['username'])) {
			$this->set_username ($username);
		}
		$this->read_userdata();
		$this->set_status (OWL_STATUS_OK);
	}

	/**
	 * Log out the current user.
	 * \protected
	 */
	protected function logout ()
	{
		session_destroy();
		$this->dataset->reset (DATA_RESET_FULL);
		$this->session = new Session();
	}

	/**
	 * Attempt to log in with the current user and the given password
	 * \protected
	 * \param[in] $username Given username. Might be taken from the session as well, but given as a
	 * parameter here to suppress the E_STRICT Declaration warning
	 * \param[in] $password The user provided password
	 * \return True on success, False otherwise
	 */
	protected function login ($username, $password)
	{
		$this->dataset->set('username', $username);
		$this->dataset->set('password', $this->hash_password ($password));
		$this->dataset->set_key ('username');
		$this->dataset->set_key ('password');
		$this->dataset->prepare ();
		$this->dataset->db($this->user_data, __LINE__, __FILE__);
		$_dbstat = $this->dataset->db_status();
		if ($_dbstat === DBHANDLE_NODATA || count ($this->user_data) !== 1) {
			$this->set_status (USER_LOGINFAIL, array (
				  $_SESSION['username']
				, (ConfigHandler::get ('logging|hide_passwords') ? '*****' : $this->dataset->get('password'))
			));
		} elseif ($_dbstat === DBHANDLE_ROWSREAD) {
			$this->user_data = $this->user_data[0]; // Shift up one level
			session_unset(); // Clear old data *BUT* ....
			$this->set_username ($this->dataset->get('username')); // .... restore the username!!
			$_SESSION['uid'] = $this->user_data['uid'];
			$this->set_status (USER_LOGGEDIN, array (
				  $_SESSION['username']
				, (ConfigHandler::get ('logging|hide_passwords') ? '*****' : $this->dataset->get('password'))
			));
			return (true);
		} else {
			$this->traceback ();
		}
		return (false);
	}

	/**
	 * When a new session starts for a use that was logged in before
	 * retrieve the userdata back from the database
	 * \private
	 */
	private function read_userdata ()
	{
		if (!isset ($_SESSION['uid'])) {
			return; // Nothing to do
		}
		$this->dataset->reset(DATA_RESET_META);
		$this->dataset->set('uid', $_SESSION['uid']);
		$this->dataset->set_key ('uid');
		$this->dataset->prepare ();
		$this->dataset->db($this->user_data);
		$_dbstat = $this->dataset->db_status();
		if ($_dbstat === DBHANDLE_NODATA || count ($this->user_data) !== 1) {
			$this->set_status (USER_RESTORERR, $_SESSION['uid']);
		} else {
			$this->user_data = $this->user_data[0]; // Shift up one level
		}
	}
	
	/**
	 * Encrypt a given password
	 * \private
	 * \param[in] $password Given password in plain text format
	 * \return The encrypted password
	 */
	private function hash_password ($password)
	{
		return (hash (ConfigHandler::get ('session|password_crypt'), $password));
	}

	/**
	 * Set the username
	 * \param[in] $username Username
	 */
	protected function set_username ($username)
	{
		$_SESSION['username'] = $username;
	}

	/**
	 * Return the username of the current session
	 * \protected
	 */
	protected function get_username ()
	{
		return ($_SESSION['username']);
	}

	/**
	 * Class destructor
	 * \public
	 */
	public function __destruct()
	{
		parent::__destruct();
		if (@is_object ($this->session)) {
			$this->session->__destruct();
			unset ($this->session);
		}
		return true;
	}
}

/*
 * Register this class and all status codes
 */
Register::register_class('UserHandler');

//Register::set_severity (OWL_DEBUG);
//Register::set_severity (OWL_INFO);
//Register::set_severity (OWL_OK);
Register::set_severity (OWL_SUCCESS);
Register::register_code ('USER_LOGGEDIN');

Register::set_severity (OWL_WARNING);
Register::register_code ('USER_INVUSERNAME');
Register::register_code ('USER_INVPASSWORD');
Register::register_code ('USER_LOGINFAIL');

Register::set_severity (OWL_BUG);

Register::set_severity (OWL_ERROR);
Register::register_code ('USER_NODATASET');
Register::register_code ('USER_RESTORERR');

//Register::set_severity (OWL_FATAL);
//Register::set_severity (OWL_CRITICAL);
