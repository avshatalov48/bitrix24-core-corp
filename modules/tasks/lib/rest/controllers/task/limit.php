<?php
namespace Bitrix\Tasks\Rest\Controllers\Task;

use Bitrix\Main;
use Bitrix\Tasks\Rest\Controllers\Base;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\TaskLimit;

/**
 * Class Limit
 * @package Bitrix\Tasks\Rest\Controllers\Task
 */
class Limit extends Base
{
	/**
	 * @return bool
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function isExceededAction(): bool
	{
		return TaskLimit::isLimitExceeded();
	}
}