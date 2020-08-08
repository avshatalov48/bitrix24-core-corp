<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Rule;


use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\Permission\PermissionDictionary;
use Bitrix\Main\Access\AccessibleItem;

class TemplateRemoveRule extends \Bitrix\Main\Access\Rule\AbstractRule
{
	public function execute(AccessibleItem $template = null, $params = null): bool
	{
		if (!$template)
		{
			return false;
		}

		if ($this->user->isAdmin())
		{
			return true;
		}

		if (!$this->controller->check(ActionDictionary::ACTION_TEMPLATE_READ, $template, $params))
		{
			return false;
		}

		if ($template->getTemplatePermission($this->user, PermissionDictionary::TEMPLATE_FULL))
		{
			return true;
		}

		return (bool) $this->user->getPermission(PermissionDictionary::TEMPLATE_REMOVE);
	}
}