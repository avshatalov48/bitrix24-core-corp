<?php
namespace Bitrix\Tasks\Grid\Task\Row\Content\UserName;

use Bitrix\Main;
use Bitrix\Tasks\Grid\Task\Row\Content\UserName;

/**
 * Class Originator
 *
 * @package Bitrix\Tasks\Grid\Task\Row\Content
 */
class Originator extends UserName
{
	protected const USER_ROLE = 'CREATED_BY';

	/**
	 * @return string
	 * @throws Main\ArgumentException
	 */
	public function prepare(): string
	{
		return $this->prepareUserName();
	}
}