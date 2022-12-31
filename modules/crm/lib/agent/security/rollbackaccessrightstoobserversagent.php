<?php

namespace Bitrix\Crm\Agent\Security;

use Bitrix\Crm\Observer\Entity\ObserverTable;
use Bitrix\Main\Config\Option;

final class RollbackAccessRightsToObserversAgent extends  \Bitrix\Crm\Agent\AgentBase
{
	private const CHUNK_SIZE = 50;

	private const STATE_STORY_KEY = '~CRM_ROLLBACK_OBSERVERS_ACCESS_ATTR__STATE';

	public static function doRun()
	{
		[$lastEntityId, $typeId] = self::getState();

		$observers = self::loadObservers($lastEntityId, $typeId);

		if (!empty($observers))
		{
			self::processItems($observers, $typeId);
			$lastEntityId = end($observers)['ENTITY_ID'];
		}
		else if ($typeId === \CCrmOwnerType::Lead)
		{
			$typeId = \CCrmOwnerType::Deal;
			$lastEntityId = -1;
		}
		else
		{
			self::done();
			return false;
		}

		self::setState($lastEntityId, $typeId);
		return true;

	}

	private static function processItems(array $observers, int $typeId): void
	{
		$controller = \Bitrix\Crm\Security\Manager::getEntityController($typeId);
		$itemIds = array_column($observers, 'ENTITY_ID');
		$typeName = \CCrmOwnerType::ResolveName($typeId);
		foreach($itemIds as $itemId)
		{
			$controller->register($typeName, $itemId);
		}
	}

	private static function loadObservers(int $lastEntityId, int $typeId): array
	{
		$listResult = ObserverTable::getList([
			'select' => [
				'ENTITY_ID',
				'ENTITY_TYPE_ID'
			],
			'filter' => [
				'@ENTITY_TYPE_ID' => [\CCrmOwnerType::Deal, \CCrmOwnerType::Lead],
				'>ENTITY_ID' => $lastEntityId,
				'=ENTITY_TYPE_ID' => $typeId,
			],
			'group' => ['ENTITY_ID', 'ENTITY_TYPE_ID'],
			'order' => ['ENTITY_ID', 'ENTITY_TYPE_ID'],
			'limit' => self::CHUNK_SIZE,
			'offset' => 0,
		]);

		$data = [];
		while ($row = $listResult->fetch())
		{
			$data[] = [
				'ENTITY_ID' => (int) $row['ENTITY_ID'],
				'ENTITY_TYPE_ID' => (int) $row['ENTITY_TYPE_ID']
			];
		}
		return $data;
	}


	private static function done(): void
	{
		Option::delete('crm', ['name' => self::STATE_STORY_KEY]);
	}

	private static function setState(int $entityId, int $typeId): void
	{
		$data = ['ENTITY_ID' => $entityId, 'TYPE_ID' => $typeId];
		Option::set('crm', self::STATE_STORY_KEY, serialize($data));
	}

	private static function getState(): array
	{
		$raw = Option::get('crm', self::STATE_STORY_KEY, '');
		$data = unserialize($raw, ['allowed_classes' => false]);
		$data = is_array($data) ? $data : [];
		return [
			$data['ENTITY_ID'] ?? -1,
			$data['TYPE_ID'] ?? \CCrmOwnerType::Lead
		];
	}
}
