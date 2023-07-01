<?php

namespace Bitrix\Tasks\Access\Rule;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Loader;
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

	private AccessibleItem $oldTemplate;
	private AccessibleItem $newTemplate;

	public function execute(AccessibleItem $template = null, $params = null): bool
	{
		if (!$template)
		{
			$this->controller->addError(static::class, 'Incorrect template');
			return false;
		}

		if (!$this->checkParams($params))
		{
			$this->controller->addError(static::class, 'Incorrect params');
			return false;
		}

		$this->oldTemplate = $template;
		$this->newTemplate = $params;

		if (
			!$this->oldTemplate->getId()
			&& !$this->controller->check(ActionDictionary::ACTION_TEMPLATE_CREATE, $this->oldTemplate, $params)
		)
		{
			$this->controller->addError(static::class, 'Access to create or update template denied');
			return false;
		}
		elseif (!$this->controller->check(ActionDictionary::ACTION_TEMPLATE_EDIT, $this->oldTemplate, $params))
		{
			$this->controller->addError(static::class, 'Access to create or update template denied');
			return false;
		}

		if (!$this->canAssignMembersExtranet())
		{
			return false;
		}

		if (!$this->newTemplate->isRegular())
		{
			return true;
		}

		$members = $this->newTemplate->getMembers();

		$user = UserModel::createFromId($members[RoleDictionary::ROLE_DIRECTOR][0]);

		if (
			$this->newTemplate->getGroupId()
			&& $this->oldTemplate->getGroupId() !== $this->newTemplate->getGroupId()
			&& !$this->canSetGroup($user->getUserId(), $this->newTemplate->getGroupId())
		)
		{
			$this->controller->addError(static::class, 'Access to set group denied');
			return false;
		}

		$responsibleList =  $members[RoleDictionary::ROLE_RESPONSIBLE] ?? [];
		foreach ($responsibleList as $responsibleId)
		{
			if (!$this->canAssign($user, $responsibleId, [], $template->getGroupId()))
			{
				$this->controller->addError(static::class, 'Access to assign responsible denied');
				return false;
			}
		}

		$accompliceList = $members[RoleDictionary::ROLE_ACCOMPLICE] ?? [];
		foreach ($accompliceList as $accompliceId)
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
		return $params instanceof TemplateModel;
	}

	private function canAssignMembersExtranet(): bool
	{

		if (!Loader::includeModule('socialnetwork'))
		{
			$this->controller->addError(static::class, 'Unable to load sonet');
			return false;
		}

		if (!Loader::includeModule('extranet'))
		{
			$this->controller->addError(static::class, 'Unable to load extranet');
		}

		$currentUser = UserModel::createFromId($this->user->getUserId());

		if (!$currentUser->isExtranet())
		{
			return true;
		}

		$memberIds = array_unique(
			array_merge(
				$this->getNewMembers(RoleDictionary::ROLE_ACCOMPLICE),
				$this->getNewMembers(RoleDictionary::ROLE_RESPONSIBLE),
				$this->getNewMembers(RoleDictionary::ROLE_AUDITOR),
			)
		);

		foreach ($memberIds as $id)
		{
			if ($currentUser->getUserId() === $id)
			{
				continue;
			}
			if (!$this->isMemberOfUserGroups($currentUser->getUserId(), $id))
			{
				return false;
			}
		}
		return true;
	}

	private function getNewMembers(string $key): array
	{
		return array_diff($this->newTemplate->getMembers($key), $this->oldTemplate->getMembers($key));
	}
}