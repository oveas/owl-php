<?php
/**
 * \file
 * This file defines a container element
 * \version $Id: class.container.php,v 1.3 2011-01-21 16:28:15 oscar Exp $
 */

OWLloader::getClass('container', OWL_PLUGINS . '/containers');

/**
 * \ingroup OWL_UI_LAYER
 * Class for standard containers. It supports several container type, for each of them the methods
 * 'show&lt;Type&gt;Type()' must exist.
 * \brief Container
 * \author Oscar van Eijk, Oveas Functionality Provider
 * \version Jan 11, 2011 -- O van Eijk -- initial version
 */

class Container extends BaseElement
{
	/**
	 * Type specific container object (plugin)
	 * \private
	 */
	private $containerObject;

	/**
	 * Class constructor;
	 * \param[in] $_type The container type. Currently supported are:
	 * 	- div
	 * 	- label
	 * 	- frameset
	 * \param[in] $_content HTML that will be placed in the table cell
	 * \param[in] $_attribs Indexed array with the HTML attributes 
	 * \param[in] $_type_attribs Indexed array with the type specific attributes.
	 * Refer to the 'show&lt;Type&gt;Type()' method for details
	 * \public
	 */
	public function __construct ($_type, $_content = '&nbsp;', array $_attribs = array(), array $_type_attribs = array())
	{
		_OWL::init();
		$this->showMethod = 'show' . ucfirst($_type) . 'Type';

		if (!OWLloader::getClass('container.'.$_type, OWL_PLUGINS . '/containers')) {
			$this->set_status(CONTAINER_IVTYPE, array($_type));
			return null;
		}
		$_className = 'Container' . ucfirst($_type) . 'Plugin';
		if (!($this->containerObject = new $_className)) {
			$this->set_status (CONTAINER_IVCLASSNAME, array($_type, $_className));
			return ($this->severity);
		}
		if (count($_attribs) > 0) {
			parent::setAttributes($_attribs);
		}
		$this->containerObject->setAttributes($_type_attribs);
		$this->setContent($_content);
	}

	/**
	 * Set container specific attributes
	 * \param[in] $_attribs Indexed array with the type specific attributes.
	 */
	public function setContainer(array $_attribs = array())
	{
		$this->containerObject->setAttributes($_attribs);
	}

	/**
	 * Get the HTML code to display the container
	 * \public
	 * \return string with the HTML code
	 */
	public function showElement()
	{
		$_htmlCode = '<' . $this->containerObject->getType();
		$_htmlCode .= $this->getAttributes();
		$_htmlCode .= $this->containerObject->showElement();
		$_htmlCode .= ">\n";
		$_htmlCode .= $this->containerObject->getSubTags();
		$_htmlCode .= $this->getContent();
		$_htmlCode .= '</' . $this->containerObject->getType() . ">\n";
		return $_htmlCode;
	}
}

/*
 * Register this class and all status codes
 */
Register::register_class ('Container');

//Register::set_severity (OWL_DEBUG);

//Register::set_severity (OWL_INFO);
//Register::set_severity (OWL_OK);
//Register::set_severity (OWL_SUCCESS);
//Register::set_severity (OWL_WARNING);
Register::set_severity (OWL_BUG);
Register::register_code ('CONTAINER_IVCLASSNAME');

Register::set_severity (OWL_ERROR);
Register::register_code ('CONTAINER_IVTYPE');

//Register::set_severity (OWL_FATAL);
//Register::set_severity (OWL_CRITICAL);