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
 * Class TaskAcceptRule
 * @package Bitrix\Tasks\Access\Rule
 *
 * @deprecated
 */
class TaskAcceptRule extends \Bitrix\Main\Access\Rule\AbstractRule
{
	public function execute(AccessibleItem $task = null, $params = null): bool
	{
		$this->controller->addError(static::class, 'Rule deprecated and should not be use');
		return false;
	}
}