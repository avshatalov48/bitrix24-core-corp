<?php
namespace Bitrix\Tasks\Grid\Task\Row\Content\UserName;

use Bitrix\Main;
use Bitrix\Tasks\Grid\Task\Row\Content\UserName;

/**
 * Class Responsible
 *
 * @package Bitrix\Tasks\Grid\Task\Row\Content
 */
class Responsible extends UserName
{
	protected const USER_ROLE = 'RESPONSIBLE_ID';

	/**
	 * @return string
	 * @throws Main\ArgumentException
	 */
	public function prepare(): string
	{
		return $this->prepareUserName();
	}
}