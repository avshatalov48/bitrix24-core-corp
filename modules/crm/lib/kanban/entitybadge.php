<?php

namespace Bitrix\Crm\Kanban;

use Bitrix\Crm\Badge\Model\BadgeTable;
use Bitrix\Crm\Badge\SourceIdentifier;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use CCrmOwnerType;

class EntityBadge
{
	private int $entityTypeId;
	private array $entityIds;

	public function __construct(int $entityTypeId, array $entityIds)
	{
		$this->entityTypeId = $entityTypeId;
		$this->entityIds = array_unique($entityIds);
	}

	/** @param $items Item[]|array  */
	public function appendToEntityItems(&$items): void
	{
		$badges = $this->getBadges();

		foreach ($badges as $badgeParams)
		{
			$id = $badgeParams['ENTITY_ID'];
			if (!isset($items[$id]))
			{
				continue;
			}

			$badge = Container::getInstance()->getBadge($badgeParams['TYPE'], $badgeParams['VALUE']);
			if ($items[$id] instanceof Item)
			{
				$items[$id]->addBadge($badge->getConfigFromMap());
			}
			else
			{
				$items[$id]['badges'][] = $badge->getConfigFromMap();
			}
		}
	}

	private function getBadges(): array
	{
		$badgeIds = $this->queryAllBadgeIds($this->entityTypeId, $this->entityIds);

		if (empty($badgeIds))
		{
			return [];
		}

		$badges = $this->queryBadgeByIds($badgeIds);

		$entityIdsWithSuspendedBadges = $this->detectSuspendedBadges($badges);

		if (empty($entityIdsWithSuspendedBadges))
		{
			return $badges;
		}

		$badges = $this->removeSuspendedBadges($badges, $entityIdsWithSuspendedBadges);

		$actualBadgesIds = $this->queryOnlyActualBadgesIds($this->entityTypeId, $entityIdsWithSuspendedBadges);
		if (empty($actualBadgesIds))
		{
			return $badges;
		}

		$clarifiedBadges = $this->queryBadgeByIds($actualBadgesIds);

		return array_merge($badges, $clarifiedBadges);
	}


	private function detectSuspendedBadges(array $badges): array
	{
		$suspendedEntityIds = [];
		foreach ($badges as $badge)
		{
			if (
				(
					$badge['SOURCE_PROVIDER_ID'] === SourceIdentifier::CRM_OWNER_TYPE_PROVIDER
					&& in_array($badge['SOURCE_ENTITY_TYPE_ID'], CCrmOwnerType::getAllSuspended())
				)
			)
			{
				$suspendedEntityIds[] = (int)$badge['ENTITY_ID'];
			}
		}
		return $suspendedEntityIds;
	}

	private function queryAllBadgeIds(int $entityTypeId, array $entityIds): array
	{
		$entityBadgeQ = BadgeTable::query()
			->addSelect('MAX_ID')
			->registerRuntimeField('', new ExpressionField('MAX_ID', 'MAX(%s)', 'ID'))
			->where('ENTITY_TYPE_ID', $entityTypeId)
			->whereIn('ENTITY_ID', $entityIds)
			->setGroup(['ENTITY_ID']);

		return array_column($entityBadgeQ->fetchAll(), 'MAX_ID');
	}

	private function queryOnlyActualBadgesIds(int $entityTypeId, array $entityIds): array
	{
		$suspendCondition = (new ConditionTree())
			->logic(ConditionTree::LOGIC_OR)
			->where(
				(new ConditionTree())
					->where('SOURCE_PROVIDER_ID', SourceIdentifier::CRM_OWNER_TYPE_PROVIDER)
					->whereNotIn('SOURCE_ENTITY_TYPE_ID', CCrmOwnerType::getAllSuspended())
			)
			->where('SOURCE_PROVIDER_ID', '<>',SourceIdentifier::CRM_OWNER_TYPE_PROVIDER);


		$entityBadgeIdsWithoutSuspendedQ = BadgeTable::query()
			->addSelect('MAX_ID')
			->registerRuntimeField('', new ExpressionField('MAX_ID', 'MAX(%s)', 'ID'))
			->where('ENTITY_TYPE_ID', $entityTypeId)
			->whereIn('ENTITY_ID', $entityIds)
			->where($suspendCondition)
			->setGroup(['ENTITY_ID']);

		return array_column($entityBadgeIdsWithoutSuspendedQ->fetchAll(), 'MAX_ID');
	}

	private function queryBadgeByIds(array $ids): array
	{
		return BadgeTable::query()
			->addSelect('ID')
			->addSelect('ENTITY_ID')
			->addSelect('CREATED_DATE', 'MAX_DATE')
			->addSelect('TYPE')
			->addSelect('VALUE')
			->addSelect('SOURCE_PROVIDER_ID')
			->addSelect('SOURCE_ENTITY_TYPE_ID')
			->whereIn('ID', $ids)
			->fetchAll();
	}

	private function removeSuspendedBadges(array $badges, array $suspendedEntityIds): array
	{
		return array_filter($badges, fn($badge) => !in_array($badge['ENTITY_ID'], $suspendedEntityIds));
	}

}
