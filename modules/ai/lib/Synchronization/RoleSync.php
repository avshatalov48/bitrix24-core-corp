<?php

declare(strict_types = 1);

namespace Bitrix\AI\Synchronization;

use Bitrix\AI\Container;
use Bitrix\AI\Model\RoleTable;
use Bitrix\AI\Role\RoleManager;
use Bitrix\AI\Synchronization\Repository\RoleDisplayRuleRepository;

class RoleSync extends BaseSync
{
	/**
	 * @inheritDoc
	 */
	protected function getDataManager(): RoleTable
	{
		return $this->dataManager ?? ($this->dataManager = new RoleTable());
	}

	protected function getDisplayRuleRepository(): RoleDisplayRuleRepository
	{
		return Container::init()->getItem(RoleDisplayRuleRepository::class);
	}

	protected function hasRuleForHidden(array $rules, array $item = []): bool
	{
		if (!empty($item['code']) && $item['code'] === RoleManager::getUniversalRoleCode())
		{
			return false;
		}

		return parent::hasRuleForHidden($rules);
	}
}
