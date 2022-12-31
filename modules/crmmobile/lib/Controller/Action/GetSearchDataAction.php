<?php

namespace Bitrix\CrmMobile\Controller\Action;

use Bitrix\CrmMobile\Controller\Action;
use Bitrix\CrmMobile\Kanban\Entity;

class GetSearchDataAction extends Action
{

	/**
	 * @param string $entityTypeName
	 * @param int|null $categoryId
	 * @return array
	 */
	public function run(string $entityTypeName, ?int $categoryId = null): array
	{
		$this->checkModules();
		if ($this->hasErrors())
		{
			return $this->showErrors();
		}

		$userId = (int) $this->getCurrentUser()->getId();
		return $this->getSearchPresetsAndCounters($userId, $entityTypeName, $categoryId);
	}

	/**
	 * @param int $userId
	 * @param string $entityTypeName
	 * @param int|null $categoryId
	 * @return array
	 */
	private function getSearchPresetsAndCounters(int $userId, string $entityTypeName, ?int $categoryId): array
	{
		return Entity::getInstance($entityTypeName)->getSearchPresetsAndCounters($userId, $categoryId);
	}
}

