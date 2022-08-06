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
use Bitrix\Tasks\Access\Role\RoleDictionary;

class TemplateReadRule extends \Bitrix\Main\Access\Rule\AbstractRule
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

		if (
			!$template->getId()
		)
		{
			return true;
		}

		if (
			$template->getTemplatePermission($this->user, PermissionDictionary::TEMPLATE_VIEW)
			|| $template->getTemplatePermission($this->user, PermissionDictionary::TEMPLATE_FULL)
		)
		{
			return true;
		}

		$isInDepartment = $template->isInDepartment($this->user->getUserId(), false, [RoleDictionary::ROLE_DIRECTOR]);

		if (
			$this->user->getPermission(PermissionDictionary::TEMPLATE_DEPARTMENT_VIEW)
			&& $isInDepartment
		)
		{
			return true;
		}

		if (
			$this->user->getPermission(PermissionDictionary::TEMPLATE_NON_DEPARTMENT_VIEW)
			&& !$isInDepartment
		)
		{
			return true;
		}

		$this->controller->addError(static::class, 'Access to template denied');
		return false;
	}
}