<?php

namespace Bitrix\Crm\Agent\Security\DynamicTypes;

use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Crm\Security\AccessAttribute\DynamicBasedAttrTableLifecycle;
use Bitrix\Crm\Security\Controller\DynamicItem;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Config\Option;

class ReFillDynamicTypeAttr
{
	public const DONE = false;

	public const CONTINUE = true;

	public function execute(): bool
	{
		$currentEntityTypeId = $this->getCurrentEntityTypeId();

		if ($currentEntityTypeId === -1)
		{
			self::clearOptions();
			return self::DONE;
		}

		$newLastId = $this->processEntityType($currentEntityTypeId, $this->getItemLastId());

		if ($newLastId !== null)
		{
			$this->setItemLastId($newLastId);

			return self::CONTINUE;
		}

		$this->deleteItemLastId();
		$this->setCurrentEntityTypeId(-1);

		return self::CONTINUE;
	}

	private function processEntityType(int $entityTypeId, int $lastItemId): ?int
	{
		$items = $this->getItems($entityTypeId, $lastItemId, AttrConvertOptions::getLimit());

		if (empty($items))
		{
			return null;
		}

		$helper = new PermissionEntityTypeHelper($entityTypeId);
		$dynCtrl = new DynamicItem($entityTypeId);

		foreach ($items as $item)
		{
			$securityEntityType = $helper->getPermissionEntityTypeForCategory($item->getCategoryId());
			$dynCtrl->register($securityEntityType, $item->getId());
		}

		return end($items)->getId();
	}

	private function getItems(int $entityTypeId, int $lastItemId, int $limit): array
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);

		if (!$factory)
		{
			return [];
		}

		return $factory->getItems([
			'select' => ['ID', 'CATEGORY_ID'],
			'filter' => ['>ID' => $lastItemId],
			'limit' => $limit,
			'order' => ['id' => 'asc'],
		]);
	}

	private function getCurrentEntityTypeId(): int
	{
		$currentId = (int)Option::get('crm', 'dynamic_item_types_refill_attr__current_id', -1);

		if ($currentId === -1)
		{
			$currentId = $this->getNextEntityTypeIdToProcess();
			$this->setCurrentEntityTypeId($currentId);
		}

		return $currentId;
	}

	private function setCurrentEntityTypeId(int $entityTypeId): void
	{
		Option::set('crm', 'dynamic_item_types_refill_attr__current_id', $entityTypeId);
	}

	private function getNextEntityTypeIdToProcess(): int
	{
		$entityTypeIdsStr = Option::get('crm', 'dynamic_item_types_refill_attr__process_ids', null);
		if (empty($entityTypeIdsStr))
		{
			return -1;
		}
		$entityTypeIds = array_map(fn($id) => (int)$id, explode(',', $entityTypeIdsStr));

		$entityTypeId = (int)array_shift($entityTypeIds);

		Option::set(
			'crm',
			'dynamic_item_types_refill_attr__process_ids',
			implode(',', $entityTypeIds)
		);

		if (empty($entityTypeId))
		{
			return -1;
		}

		return $entityTypeId;
	}

	private function getItemLastId(): int
	{
		return (int)Option::get('crm', 'dynamic_item_types_refill_attr__item_last_id', -1);
	}

	private function setItemLastId(int $lastId): void
	{
		Option::set('crm', 'dynamic_item_types_refill_attr__item_last_id', $lastId);
	}

	public static function clearOptions(): void
	{
		Option::delete('crm', ['name' => 'dynamic_item_types_refill_attr__item_last_id']);
		Option::delete('crm', ['name' => 'dynamic_item_types_refill_attr__process_ids']);
		Option::delete('crm', ['name' => 'dynamic_item_types_refill_attr__current_id']);
	}

	private function deleteItemLastId(): void
	{
		Option::delete('crm', ['name' => 'dynamic_item_types_refill_attr__item_last_id']);
	}

	public static function prepareEntityIdsToProcess(): void
	{
		$typesIds = TypeTable::query()->setSelect(['ENTITY_TYPE_ID'])->fetchAll();
		$typesIds = array_column($typesIds, 'ENTITY_TYPE_ID');

		$typeIdsScheduledToProcessByUpdater = AttrConvertOptions::getNotConvertedEntityTypesIds();
		$result = [];
		foreach ($typesIds as $typesId)
		{
			// attr table for this type not exists. skip
			if (!DynamicBasedAttrTableLifecycle::checkByEntityTypeIdIsTableExists($typesId))
			{
				continue;
			}

			// type not processed yet. skip
			if (in_array($typesId, $typeIdsScheduledToProcessByUpdater))
			{
				continue;
			}

			// skip entity type currently processed by updater
			if ($typesId == AttrConvertOptions::getCurrentEntityTypeId())
			{
				continue;
			}

			$result[] = $typesId;
		}

		if (!empty($result))
		{
			Option::set(
				'crm',
				'dynamic_item_types_refill_attr__process_ids',
				implode(',', $result)
			);
		}
	}

	public static function findEntityIdsToProcessOnlyBroken(): array
	{
		$result = [];

		$connection = \Bitrix\Main\Application::getConnection();
		$typesIterator = $connection->query('select ENTITY_TYPE_ID, TABLE_NAME from b_crm_dynamic_type');

		while ($type = $typesIterator->fetch())
		{
			$entityTypeId = (int)$type['ENTITY_TYPE_ID'];
			$table = $type['TABLE_NAME'];

			switch ($entityTypeId)
			{
				case 31: // \CCrmOwnerType::SmartInvoice
					$attrTableName = 'b_crm_access_attr_smart_invoice';
					break;
				case 36: // \CCrmOwnerType::SmartDocument
					$attrTableName = 'b_crm_access_attr_smart_document';
					break;
				case 39: // \CCrmOwnerType::SmartB2eDocument
					$attrTableName = 'b_crm_access_attr_smart_b2e_doc';
					break;
				default:
					$attrTableName = 'b_crm_access_attr_dynamic_' . $entityTypeId;
			}

			$sql = "select ID from $table where ID NOT IN (select ENTITY_ID from $attrTableName) limit 1;";

			try
			{
				$cntRes = $connection->query($sql);
				$row =$cntRes->fetch();
				if ($row)
				{
					$result[] = $entityTypeId;
				}
			}
			catch (\Exception $e)
			{
			}
		}

		return $result;
	}
}