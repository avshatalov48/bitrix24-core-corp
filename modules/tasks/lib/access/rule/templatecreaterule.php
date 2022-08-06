<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Rule;


use Bitrix\Tasks\Access\Permission\PermissionDictionary;
use Bitrix\Main\Access\AccessibleItem;

class TemplateCreateRule extends \Bitrix\Main\Access\Rule\AbstractRule
{
	public function execute(AccessibleItem $template = null, $params = null): bool
	{
		if (!$template)
		{
			$this->controller->addError(static::class, 'Incorrect template');
			return false;
		}

		if ($this->user->isAdmin())
		{
			return true;
		}

		return (bool) $this->user->getPermission(PermissionDictionary::TEMPLATE_CREATE);
	}
}