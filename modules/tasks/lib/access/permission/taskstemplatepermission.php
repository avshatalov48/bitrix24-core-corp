<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Permission;


use Bitrix\Main\Access\AccessCode;

class TasksTemplatePermission extends EO_TasksTemplatePermission
{
	private $parsedAC;

	public function getMemberPrefix()
	{
		return $this->getParsedAccessCode()->getEntityPrefix();
	}

	public function getMemberId()
	{
		return $this->getParsedAccessCode()->getEntityId();
	}

	private function getParsedAccessCode()
	{
		if (!$this->parsedAC)
		{
			$this->parsedAC = new AccessCode($this->getAccessCode());
		}
		return $this->parsedAC;
	}
}