<?php
/**
 * \file
 * This file defines the Group class
 * \author Oscar van Eijk, Oveas Functionality Provider
 * \version $Id: class.group.php,v 1.7 2011-05-18 12:03:48 oscar Exp $
 */

/**
 * \ingroup OWL_BO_LAYER
 * This class handles the OWL groups 
 * \brief the group object 
 * \author Oscar van Eijk, Oveas Functionality Provider
 * \version Apr 14, 2011 -- O van Eijk -- initial version
 */
class Group extends _OWL
{
	/**
	 * Array with rights bitmaps for all applications
	 */
	private $rights;

	/**
	 * Group ID
	 */
	private $id;

	/**
	 * Class constructor
	 * \param[in] $id Optional Group ID. When ommittedm the group can be setup using the name with getGroupByName()
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function __construct ($id = 0)
	{
		_OWL::init();
		$this->dataset = new DataHandler ();
		if (ConfigHandler::get ('owltables', true)) {
			$this->dataset->setPrefix(ConfigHandler::get ('owlprefix'));
		}
		$this->dataset->setTablename('group');
		$this->id = $id;
		$this->rights = array();
		if ($this->id !== 0) {
			$this->getGroupData();
			$this->getGroupRights();
		}
	}

	/**
	 * Read the group information from the database and store it in the internal dataset
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	private function getGroupData()
	{
		$this->dataset->set('gid', $this->id);
		$this->dataset->prepare();
		$this->dataset->db($_data, __LINE__, __FILE__);
		$this->group_data = $_data[0];
	}

	/**
	 * Initialize the group object based on the groupname
	 * \param[in] $name Name of the group
	 * \param[in] $aid Application ID the group belongs to, defaults to OWL
	 * \return The group ID, or false when not found
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function getGroupByName($name, $aid = OWL_ID)
	{
		$this->dataset->set('groupname', $name);
		$this->dataset->set('aid', $aid);
		$this->dataset->prepare();
		$this->dataset->db($_data, __LINE__, __FILE__);
		if (count($_data) == 0) {
			$this->setStatus(GROUP_NOSUCHNAME, array($name));
			return (false);
		}
		$this->group_data = $_data[0];
		$this->id = $this->group_data['gid'];
		$this->getGroupRights();
		return ($this->id);
		
	}
	/**
	 * Read the groupright bitmaps from the database and store them in the internal array
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	private function getGroupRights()
	{
		$dataset = new DataHandler ();
		if (ConfigHandler::get ('owltables', true)) {
			$dataset->setPrefix(ConfigHandler::get ('owlprefix'));
		}
		$dataset->setTablename('grouprights');
		$dataset->set('gid', $this->id);
		$dataset->prepare();
		$dataset->db($_data, __LINE__, __FILE__);
		foreach ($_data as $_r) {
			$this->rights['a'.$_r['aid']] = $_r['right'];
		}
	}

	/**
	 * Return this groups rights bitmap for the given application
	 * \param[in] $aid Application ID
	 * \return Rights bitmap or 0 when not found
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function getRights($aid)
	{
		if (!array_key_exists('a'.$aid, $this->rights)) {
			return (0);
		}
		return ($this->rights['a'.$aid]);
	}

	/**
	 * Return a groupdata item, or the default value if it does not exist.
	 * \param[in] $item The item of which the value should be returned
	 * \param[in] $default Default value it the item does not exist (default is null)
	 * \return Value
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function get($item, $default = null)
	{
		return (
			(array_key_exists($item, $this->group_data))
				? $this->group_data[$item]
				: $default
		);
	}
}
Register::registerClass('Group');
//Register::setSeverity (OWL_DEBUG);
//Register::setSeverity (OWL_INFO);
//Register::setSeverity (OWL_OK);
//Register::setSeverity (OWL_SUCCESS);
Register::setSeverity (OWL_WARNING);
Register::registerCode ('GROUP_NOSUCHNAME');
//Register::setSeverity (OWL_BUG);
//Register::setSeverity (OWL_ERROR);
//Register::setSeverity (OWL_FATAL);
//Register::setSeverity (OWL_CRITICAL);
