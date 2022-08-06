<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Rule;

use Bitrix\Main\Access\AccessibleItem;

/**
 * Class TaskDeclineRule
 * @package Bitrix\Tasks\Access\Rule
 *
 * @deprecated
 */
class TaskDeclineRule extends \Bitrix\Main\Access\Rule\AbstractRule
{
	public function execute(AccessibleItem $task = null, $params = null): bool
	{
		$this->controller->addError(static::class, 'Rule is deprecated');
		return false;
	}
}