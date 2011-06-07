<?php
/**
 * \file
 * \ingroup OWL_CONTRIB
 * This file provides some helper functions for lazy developers like me setting up form tables
 * \version $Id: formtable.helpers.php,v 1.2 2011-05-27 12:42:20 oscar Exp $
 * \author Oscar van Eijk, Oveas Functionality Provider
 */

/**
 * This function adds a new table row to an exising form with 2 cells; in the right cell a new formfield
 * that is created as well, left the label that should be displayed.
 * To make sure no mistakes are made in the long list of arguments, no defaults are accepted.
 * \param[in] $table Reference to the table container object to which the row is added
 * \param[in] $form Reference to the form object to which the field is added
 * \param[in] $fieldType Form field type (text, password, textarea etc)
 * \param[in] $fieldName Name of the field as it will appear in the form
 * \param[in] $fieldValue Optional fieldvalue
 * \param[in] $fieldAttributes Optional HTML attributes for the field
 * \param[in] $label Label as it will appear in the form (must be translated already!)
 * \param[in] $labelAttributes Optional HTML attributes for the label
 * \author Oscar van Eijk, Oveas Functionality Provider
 */
function addFormRow (
			  Container &$table
			, Form &$form
			, $fieldType
			, $fieldName
			, $fieldValue
			, array $fieldAttributes
			, $label
			, array $labelAttributes
		)
{
	// Add a new row for the field
	$_row = $table->addContainer('row');

	// Add the field to the form
	$_fld = $form->addField($fieldType, $fieldName, $fieldValue, $fieldAttributes);

	// Create a <label> container for the field with the translation as content
	$_contnr = new Container('label', $label, $labelAttributes, array('for' => &$_fld));

	// Add a new cell to the tablerow
	$_cell = $_row->addContainer('cell');

	// Set the <label> containter as content for the new cell
	$_cell->setContent($_contnr);

	// Add a new cell and set the form field as container
	$_row->addContainer('cell', $form->showField($fieldName));
}