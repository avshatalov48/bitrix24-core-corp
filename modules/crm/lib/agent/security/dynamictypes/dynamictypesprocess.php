<?php

namespace Bitrix\Crm\Agent\Security\DynamicTypes;

use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\Crm\Item;
use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Crm\Security\AccessAttribute\DynamicBasedAttrTableLifecycle;
use Bitrix\Crm\Security\Controller\DynamicItem;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use CCrmOwnerType;

class DynamicTypesProcess
{
	public const DONE = false;

	public const CONTINUE = true;

	public function execute(): bool
	{
		$currentEntityTypeId = $this->obtainCurrentEntityTypeId();

		if ($currentEntityTypeId === -1)
		{
			return self::DONE;
		}

		$newLastId = $this->processEntityType($currentEntityTypeId, AttrConvertOptions::getItemLastId());

		if ($newLastId !== null)
		{
			AttrConvertOptions::setItemLastId($newLastId);

			return self::CONTINUE;
		}

		CleanEntityPermsRecords::scheduleAgent($currentEntityTypeId);

		AttrConvertOptions::deleteItemLastId();
		AttrConvertOptions::setCurrentEntityTypeId(-1);

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

	/**
	 * @param int $entityTypeId
	 * @param int $lastItemId
	 * @param int $limit
	 * @return Item[]
	 */
	public function getItems(int $entityTypeId, int $lastItemId, int $limit): array
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

	private function obtainCurrentEntityTypeId(): int
	{
		$curr = AttrConvertOptions::getCurrentEntityTypeId();

		if ($curr === -1)
		{
			$curr = $this->getNextEntityTypeId();
			AttrConvertOptions::setCurrentEntityTypeId($curr);
		}

		return $curr;
	}

	private function getNextEntityTypeId(): int
	{
		$entityTypeIds = AttrConvertOptions::getNotConvertedEntityTypesIds();

		if (empty($entityTypeIds))
		{
			return -1;
		}

		$entityTypeId = (int)array_shift($entityTypeIds);
		AttrConvertOptions::setNotConvertedEntityTypesIds($entityTypeIds);

		if (empty($entityTypeId))
		{
			return -1;
		}

		$dtl = DynamicBasedAttrTableLifecycle::getInstance();
		$result = $dtl->createTable(CCrmOwnerType::ResolveName($entityTypeId));

		if (!$result->isSuccess())
		{
			return -1;
		}

		return $entityTypeId;
	}
}