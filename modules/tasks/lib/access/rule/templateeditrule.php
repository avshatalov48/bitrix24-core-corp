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
use Bitrix\Tasks\Access\Role\RoleDictionary;

class TemplateEditRule extends \Bitrix\Main\Access\Rule\AbstractRule
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

		if (!$template->getId())
		{
			return $this->controller->check(ActionDictionary::ACTION_TEMPLATE_CREATE, $template, $params);
		}

		if (!$this->controller->check(ActionDictionary::ACTION_TEMPLATE_READ, $template, $params))
		{
			$this->controller->addError(static::class, 'Access to template denied');
			return false;
		}

		if ($template->getTemplatePermission($this->user, PermissionDictionary::TEMPLATE_FULL))
		{
			return true;
		}

		$isInDepartment = $template->isInDepartment($this->user->getUserId(), false, [RoleDictionary::ROLE_DIRECTOR]);

		if (
			$this->user->getPermission(PermissionDictionary::TEMPLATE_DEPARTMENT_EDIT)
			&& $isInDepartment
		)
		{
			return true;
		}

		if (
			$this->user->getPermission(PermissionDictionary::TEMPLATE_NON_DEPARTMENT_EDIT)
			&& !$isInDepartment
		)
		{
			return true;
		}

		$this->controller->addError(static::class, 'Access to template edit denied');
		return false;
	}
}