<?php
namespace Bitrix\Tasks\Rest\Controllers\Template;

use Bitrix\Tasks\Rest\Controllers\Base;

use \Bitrix\Main\Error;

class Files extends Base
{
	/**
	 * Return all DB and UF_ fields of file
	 *
	 * @return array
	 */
	public function fieldsAction()
	{
		return  [];
	}

	/**
	 * Add new file
	 *
	 * @param int $taskId
	 * @param array $fields
	 *
	 * @param array $params
	 *
	 * @return int
	 */
	public function addAction($taskId, array $fields, array $params = array())
	{
		return 1;
	}

	/**
	 * Remove existing file
	 *
	 * @param int $taskId
	 * @param int $fileId
	 *
	 * @param array $params
	 *
	 * @return bool
	 */
	public function deleteAction($taskId, $fileId, array $params = array())
	{
		return false;
	}

	/**
	 * Get list all files at task
	 *
	 * @param int $taskId
	 *
	 * @param array $params
	 *
	 *
	 * @return array
	 */
	public function listAction($taskId, array $params = array())
	{
		return [];
	}

	/**
	 * Get file data
	 *
	 * @param int $fileId
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	public function getAction($fileId, array $params = array())
	{
		return [];
	}
}