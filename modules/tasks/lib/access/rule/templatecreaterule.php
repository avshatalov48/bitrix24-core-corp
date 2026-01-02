<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Rule;


use Bitrix\Tasks\Access\Model\TemplateModel;
use Bitrix\Tasks\Access\Permission\PermissionDictionary;
use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Tasks\Access\Rule\Traits\GroupTrait;

class TemplateCreateRule extends \Bitrix\Main\Access\Rule\AbstractRule
{
	use GroupTrait;

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

		/** @var null|TemplateModel $newTemplate */
		$newTemplate = $params;
		if ($newTemplate === null)
		{
			return (bool)$this->user->getPermission(PermissionDictionary::TEMPLATE_CREATE);
		}

		if (
			$newTemplate->getGroupId() > 0
			&& !$this->canSetGroup($this->user->getUserId(), $newTemplate->getGroupId())
		)
		{
			$this->controller->addError(static::class, 'Access to group denied');

			return false;
		}

		return (bool)$this->user->getPermission(PermissionDictionary::TEMPLATE_CREATE);
	}
}