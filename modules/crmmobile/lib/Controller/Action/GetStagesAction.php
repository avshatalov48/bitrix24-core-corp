<?php

namespace Bitrix\CrmMobile\Controller\Action;

use Bitrix\CrmMobile\Controller\Action;
use Bitrix\CrmMobile\Kanban\Kanban;
use Bitrix\Crm\Service\Container;

class GetStagesAction extends Action
{
	public function run(string $entityType, array $extra = [])
	{
		$this->checkModules();
		if ($this->hasErrors())
		{
			return $this->showErrors();
		}

		if ($this->checkUserPermissions($entityType, $extra))
		{
			$kanban = Kanban::getInstance($entityType, ($extra['filterParams'] ?? []));
			$columns = array_values($kanban->getColumns());
		}
		else
		{
			$columns = [];
		}

		return [
			'columns' => $columns,
		];
	}

	/**
	 * @param string $entityType
	 * @param array $extra
	 * @return bool
	 */
	protected function checkUserPermissions(string $entityType, array $extra = []): bool
	{
		$entityTypeId = \CCrmOwnerType::ResolveID($entityType);
		$categoryId = (int)($extra['CATEGORY_ID'] ?? 0); // @todo check this

		return (
			Container::getInstance()
				->getUserPermissions()
				->checkReadPermissions($entityTypeId, 0, $categoryId)
		);
	}
}
