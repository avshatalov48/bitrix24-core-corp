<?php

namespace Bitrix\Crm\Integration\Main\UISelector\EntitySelection\Strategy;

use Bitrix\Crm\Integration\Main\UISelector\EntitySelection\Strategy;

class LastEntities extends Strategy
{
	public function getEntities(array $items): array
	{
		$lastEntities = $this->getLastEntities($items);
		$lastMultiEntities = $this->getLastMultiEntities($items);

		return array_merge($lastEntities, $lastMultiEntities);
	}

	public function getEntitiesIDs(array $items): array
	{
		$lastEntitiesIds = $this->getLastEntitiesIds($items);
		$lastMultiEntitiesIds = $this->getLastMultiEntitiesIds($items);

		return array_merge($lastEntitiesIds, $lastMultiEntitiesIds);
	}

	protected function getLastEntities(array $items): array
	{
		$lastEntities = [];
		$entity = $this->entity;

		$lastItems = $items[$entity->getType()] ?? [];
		if (!empty($lastItems))
		{
			//for example: CRMCONTACT308 -> C_308
			$itemCodePattern = '/^' . $entity->getFullPrefix() . '(\d+)$/';
			$toEntityReplace = $entity->getPrefix() . '$1';

			foreach ($lastItems as $lastItem)
			{
				$lastEntities[] = preg_replace(
					$itemCodePattern,
					$toEntityReplace,
					$lastItem,
				);
			}
		}

		return $lastEntities;
	}

	protected function getLastMultiEntities(array $items): array
	{
		$lastMultiEntities = [];
		$entity = $this->entity;

		$lastMultiItems = $items[$entity->getType() . '_MULTI'] ?? [];
		if (!empty($lastMultiItems))
		{
			$itemCodePattern = '/^' . $entity->getFullPrefix() . '(\d+)( . +)$/';
			$toEntityReplaceCallback =
				static fn(array $matches) => $entity->getPrefix() . $matches[1] . mb_strtolower($matches[2])
			;

			foreach ($lastMultiItems as $multiLastItem)
			{
				$lastMultiEntities[] = preg_replace_callback(
					$itemCodePattern,
					$toEntityReplaceCallback,
					$multiLastItem,
				);
			}
		}

		return $lastMultiEntities;
	}

	protected function getLastEntitiesIds(array $items): array
	{
		$lastEntitiesIds = [];
		$entity = $this->entity;

		$lastItems = $items[$entity->getType()] ?? [];
		foreach ($lastItems as $lastItem)
		{
			//for example: CRMCONTACT308 -> 308
			$lastEntitiesIds[] = str_replace(
				$entity->getFullPrefix(),
				'',
				$lastItem,
			);
		}

		return $lastEntitiesIds;
	}

	protected function getLastMultiEntitiesIds(array $items): array
	{
		$lastMultiEntitiesIds = [];
		$entity = $this->entity;

		$lastMultiItems = $items[$entity->getType() . '_MULTI'] ?? [];
		if (!empty($lastMultiItems))
		{
			$itemCodePattern = '/^' . $entity->getFullPrefix() . '(\d+)(:([A-F0-9]{8}))$/';
			foreach ($lastMultiItems as $lastMultiItem)
			{
				$lastMultiEntitiesIds[] = preg_replace(
					$itemCodePattern,
					'$1',
					$lastMultiItem,
				);
			}
		}

		return $lastMultiEntitiesIds;
	}
}
