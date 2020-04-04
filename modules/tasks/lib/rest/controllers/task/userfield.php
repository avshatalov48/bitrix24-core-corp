<?php

namespace Bitrix\Tasks\Rest\Controllers\Task;

use Bitrix\Tasks\Rest\Controllers\Base;

use \Bitrix\Main\Error;

class Userfield extends Base
{

	/**
	 * Return all fields
	 *
	 * @return array
	 */
	public function fieldsAction()
	{
		return  [];
	}

	/**
	 * Return all types
	 *
	 * @return array
	 */
	public function typesAction()
	{
		return  [];
	}

	/**
	 * Create new userfield for task
	 *
	 * @param array $fields
	 * [
	 * 	TYPE_ID = string required
	 * 	CODE = string required
	 * 	LABEL = string
	 * 	EDIT_FORM_LABEL = string
	 *
	 * 	XML_ID = string
	 * ]
	 *
	 * @param array $params
	 *
	 * @return int
	 */
	public function addAction(array $fields, array $params = array())
	{
		return 1;
	}

	/**
	 * Update existing userfield for task
	 *
	 * @param string $code
	 *
	 * @param array $fields
	 * [
	 * 	LABEL = string
	 * 	EDIT_FORM_LABEL = string
	 *
	 * 	XML_ID = string
	 * ]
	 *
	 * @param array $params
	 *
	 * @return bool
	 */
	public function updateAction($code, array $fields, array $params = array())
	{
		return false;
	}


	/**
	 * Remove existing userfield for task
	 *
	 * @param string $code
	 *
	 * @param array $params
	 * [
	 * 	check_permissions = bool
	 * ]
	 *
	 * @return bool
	 */
	public function deleteAction($code, array $params = array())
	{
		return false;
	}

	/**
	 * Get list all userfields for task
	 *
	 * @param array $params
	 *
	 *
	 * @return array
	 */
	public function listAction(array $params = array())
	{
		return  [];
	}
}