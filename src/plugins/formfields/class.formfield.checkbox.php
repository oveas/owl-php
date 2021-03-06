<?php
/**
 * \file
 * This file defines a checkbox formfield element
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
 * \ingroup TT_PLUGINS
 * Formfield Checkbox elements
 * \brief Formfield
 * \author Oscar van Eijk, Oveas Functionality Provider
 * \version Oct 19, 2010 -- O van Eijk -- initial version
 */

class FormFieldCheckboxPlugin extends FormFieldPlugin
{
	/**
	 * Boolean set to true when the box is checked
	 */
	private $checked;

	/**
	 * Class constructor;
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function __construct ()
	{
		parent::__construct();
		$this->type = 'checkbox';
	}

	/**
	 * Set the Checked boolean
	 * \param[in] $_value Value indicating true (default) or false
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function setChecked($_value = true)
	{
		$this->checked = toBool($_value, array('yes', 'y', 'true', '1', 'checked', 'selected'));
	}

	/**
	 * Reimplement; value defaults to 1 for checkboxes
	 * \param[in] $_value Field value
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function setValue($_value)
	{
		if ($_value == '') {
			$this->value = 1;
		} else {
			$this->value = $_value;
		}
	}

	/**
	 * Return the HTML code to display the form element
	 * \return Textstring with the complete code for the form element
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function showElement ()
	{
		$_htmlCode = "<input type='$this->type'";
		if ($this->checked === true) {
			$_htmlCode .= " checked='$this->checked'";
		}
		$_htmlCode .= $this->getGenericFieldAttributes() . '/>';
		return $_htmlCode;
	}
}


//Register::setSeverity (TT_DEBUG);

//Register::setSeverity (TT_INFO);
//Register::setSeverity (TT_OK);
//Register::setSeverity (TT_SUCCESS);
//Register::registerCode ('FORM_RETVALUE');

//Register::setSeverity (TT_WARNING);

//Register::setSeverity (TT_BUG);

//Register::setSeverity (TT_ERROR);
//Register::setSeverity (TT_FATAL);
//Register::setSeverity (TT_CRITICAL);
