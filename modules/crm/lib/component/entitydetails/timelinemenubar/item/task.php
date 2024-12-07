<?php

namespace Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;

use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;

class Task extends Item
{
	public function getId(): string
	{
		return 'task';
	}

	public function getName(): string
	{
		return \Bitrix\Main\Localization\Loc::getMessage('CRM_TIMELINE_TASK');
	}

	public function isAvailable(): bool
	{
		if (!\Bitrix\Main\ModuleManager::isModuleInstalled('tasks'))
		{
			return false;
		}

		if ($this->isCatalogEntityType())
		{
			return false;
		}

		if ($this->isMyCompany())
		{
			return false;
		}
		if (!\Bitrix\Crm\UserField\UserFieldManager::isEnabledInTasksUserField(\CCrmOwnerType::ResolveName($this->getEntityTypeId())))
		{
			return false;
		}

		if (\CCrmOwnerType::isUseDynamicTypeBasedApproach($this->getEntityTypeId()))
		{
			$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($this->getEntityTypeId());

			return ($factory && $factory->isUseInUserfieldEnabled());
		}

		return true;
	}

	public function hasTariffRestrictions(): bool
	{
		return !\Bitrix\Crm\Restriction\RestrictionManager::getTaskRestriction()->hasPermission();
	}
}
