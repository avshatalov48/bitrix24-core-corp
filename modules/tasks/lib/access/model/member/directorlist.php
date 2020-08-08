<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Model\Member;


use Bitrix\Tasks\Access\Permission\PermissionDictionary;

class DirectorList extends BaseList
{

	/**
	 * @return array|null
	 * @ToDo set correct permission
	 */
	public function getHasRightUsers(): ?array
	{
		return $this->getHasPermissionUsers(PermissionDictionary::TASK_DEPARTMENT_DIRECT);
	}

	public function getAccesibleUsers(): ?array
	{
		return null;
	}

}