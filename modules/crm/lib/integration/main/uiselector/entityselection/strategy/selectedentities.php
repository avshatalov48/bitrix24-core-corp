<?php

namespace Bitrix\Crm\Integration\Main\UISelector\EntitySelection\Strategy;

use Bitrix\Crm\Integration\Main\UISelector\EntitySelection\Strategy;

class SelectedEntities extends Strategy
{
	public function getEntities(array $items): array
	{
		$entityType = $this->entity->getType();
		$fullPrefix = $this->entity->getFullPrefix();

		$selectedEntities = $items[$entityType] ?? [];

		if (empty($items[$entityType . '_MULTI']))
		{
			return $selectedEntities;
		}

		$selectedEntitiesIDs = $this->getEntitiesIDs($items);
		return array_map(
			static fn($item) => $fullPrefix . $item,
			$selectedEntitiesIDs,
		);
	}

	public function getEntitiesIDs(array $items): array
	{
		$selectedEntitiesIDs = [];

		$entityType = $this->entity->getType();
		$prefix = $this->entity->getPrefix();

		$selectedItems = $items[$entityType] ?? [];
		foreach ($selectedItems as $selectedItem)
		{
			$selectedEntitiesIDs[] = str_replace(
				$prefix,
				'',
				$selectedItem
			);
		}

		$selectedMultiItems = $items[$entityType . '_MULTI'] ?? [];
		if (!empty($selectedMultiItems))
		{
			$fullPrefix = $this->entity->getFullPrefix();
			$itemCodePattern = '/^' . $fullPrefix . '(\d+)(:([a-fA-F0-9]{8}))$/';

			foreach ($selectedMultiItems as $selectedMultiItem)
			{
				$selectedEntitiesIDs[] = preg_replace(
					$itemCodePattern,
					'$1',
					$selectedMultiItem,
				);
			}
		}

		return $selectedEntitiesIDs;
	}
}
