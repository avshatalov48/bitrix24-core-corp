<?php

namespace Bitrix\Tasks\Access\Rule;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\Model\TemplateModel;
use Bitrix\Tasks\Access\Model\UserModel;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Access\Rule\Traits\AssignTrait;
use Bitrix\Tasks\Access\Rule\Traits\GroupTrait;

class TemplateSaveRule extends \Bitrix\Main\Access\Rule\AbstractRule
{
	use AssignTrait;
	use GroupTrait;

	public function execute(AccessibleItem $template = null, $params = null): bool
	{
		if (!$this->checkParams($params))
		{
			$this->controller->addError(static::class, 'Incorrect params');
			return false;
		}

		$oldTemplate = $template;
		$newTemplate = $params;

		if (
			!$oldTemplate->getId()
			&& !$this->controller->check(ActionDictionary::ACTION_TEMPLATE_CREATE, $oldTemplate, $params)
		)
		{
			$this->controller->addError(static::class, 'Access to create or update template denied');
			return false;
		}
		elseif (!$this->controller->check(ActionDictionary::ACTION_TEMPLATE_EDIT, $oldTemplate, $params))
		{
			$this->controller->addError(static::class, 'Access to create or update template denied');
			return false;
		}

		if (!$newTemplate->isRegular())
		{
			return true;
		}

		$newMembers = $newTemplate->getMembers();
		$user = UserModel::createFromId($newMembers[RoleDictionary::ROLE_DIRECTOR][0]);

		if (
			$newTemplate->getGroupId()
			&& $oldTemplate->getGroupId() !== $newTemplate->getGroupId()
			&& !$this->canSetGroup($user->getUserId(), $newTemplate->getGroupId())
		)
		{
			$this->controller->addError(static::class, 'Access to set group denied');
			return false;
		}

		foreach ($newMembers[RoleDictionary::ROLE_RESPONSIBLE] as $responsibleId)
		{
			if (!$this->canAssign($user, $responsibleId, [], $template->getGroupId()))
			{
				$this->controller->addError(static::class, 'Access to assign responsible denied');
				return false;
			}
		}

		foreach ($newMembers[RoleDictionary::ROLE_ACCOMPLICE] as $accompliceId)
		{
			if (!$this->canAssign($user, $accompliceId, [], $template->getGroupId()))
			{
				$this->controller->addError(static::class, 'Access to assign accomplice denied');
				return false;
			}
		}

		return true;
	}

	/**
	 * @param $params
	 * @return bool
	 */
	private function checkParams($params = null): bool
	{
		return is_object($params) && $params instanceof TemplateModel;
	}
}