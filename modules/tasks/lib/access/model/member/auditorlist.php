<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Model\Member;

class AuditorList extends BaseList
{

	public function getHasRightUsers(): ?array
	{
		return null;
	}

	public function getAccesibleUsers(): ?array
	{
		return null;
	}

}