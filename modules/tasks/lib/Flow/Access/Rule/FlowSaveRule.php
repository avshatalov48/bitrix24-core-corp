<?php

namespace Bitrix\Tasks\Flow\Access\Rule;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Rule\AbstractRule;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TemplateAccessController;
use Bitrix\Tasks\Flow\Access\FlowAccessController;
use Bitrix\Tasks\Flow\Access\FlowAction;
use Bitrix\Tasks\Flow\Access\FlowModel;
use Bitrix\Tasks\Flow\Access\ValidationTrait;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Internals\Registry\GroupRegistry;

class FlowSaveRule extends AbstractRule
{
	use ValidationTrait;

	/** @var FlowAccessController */
	protected $controller;

	public function execute(AccessibleItem $item = null, $params = null): bool
	{
		if (!$this->checkModel($params))
		{
			return false;
		}

		/** @var FlowModel $params */
		if ($params->isNew() && !$this->controller->check(FlowAction::CREATE, $params))
		{
			return false;
		}

		if ($params->getProjectId() > 0)
		{
			$group = GroupRegistry::getInstance()->get($params->getProjectId());
			if (null === $group)
			{
				$this->controller->addError(static::class, 'Unable to load group info');
				return false;
			}

			// tasks disabled for group
			// the group is archived
			if (
				!$group['TASKS_ENABLED']
				|| $group['CLOSED'] === 'Y'
			)
			{
				$this->controller->addError(static::class, 'Unable to create flow bc group is closed or tasks disabled');
				return false;
			}

			if ($this->user->isAdmin())
			{
				return true;
			}

			if (!Group::isUserMember($group['ID'], $this->user->getUserId()))
			{
				$this->controller->addError(static::class, 'Unable to create flow by group permissions');
				return false;
			}
		}

		if ($this->user->isAdmin())
		{
			return true;
		}

		if (
			$params->getTemplateId() > 0
			&& !TemplateAccessController::can(
				$this->user->getUserId(),
				ActionDictionary::ACTION_TEMPLATE_READ,
				$params->getTemplateId()
			)
		)
		{
			$this->controller->addError(static::class, 'Unable to create flow by template permissions');
			return false;
		}

		return true;
	}
}