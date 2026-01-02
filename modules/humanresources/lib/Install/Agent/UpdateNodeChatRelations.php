<?php

namespace Bitrix\HumanResources\Install\Agent;

use Bitrix\HumanResources\Model\NodeRelationTable;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Item\Collection;
use Bitrix\HumanResources\Type\RelationEntitySubtype;
use Bitrix\HumanResources\Type\RelationEntityType;
use Bitrix\Im\V2\Service\Messenger;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;

final class UpdateNodeChatRelations extends BaseAgent
{
	private const LIMIT = 100;

	public static function run(int $offset = 0): string
	{
		if (!Loader::includeModule('im'))
		{
			return self::finish();
		}

		$nodeRelationRepository = Container::getNodeRelationRepository();
		try
		{
			$chatRelations = $nodeRelationRepository->findRelationsByRelationType(
				RelationEntityType::CHAT,
				self::LIMIT,
				$offset,
			);
		}
		catch (\Exception)
		{
			return self::next($offset);
		}

		if ($chatRelations->empty())
		{
			return self::finish();
		}

		$relationsToUpdate = new Collection\NodeRelationCollection();
		foreach ($chatRelations as $chatRelation)
		{
			if ($chatRelation->entitySubtype)
			{
				continue;
			}

			$relationsToUpdate->add($chatRelation);
		}

		if ($relationsToUpdate->empty())
		{
			return self::next($offset + self::LIMIT);
		}

		$messenger = Messenger::getInstance();
		$relationIdsToUpdate = [];
		$relationIdsToDelete = [];
		$nodeRepository = Container::getNodeRepository();
		try
		{
			foreach ($relationsToUpdate as $relation)
			{
				$chatItem = $messenger->getChat($relation->entityId);
				if (!$chatItem->isExist() || !$nodeRepository->getById($relation->nodeId))
				{
					$relationIdsToDelete[] = $relation->entityId;

					continue;
				}

				$entitySubtype = RelationEntitySubtype::fromChatType($chatItem->getType());
				if ($entitySubtype)
				{
					$relationIdsToUpdate[$entitySubtype->value][] = $relation->id;
				}
			}

			if (!empty($relationIdsToDelete))
			{
				$nodeRelationRepository->deleteRelationByEntityTypeAndEntityIds(
					RelationEntityType::CHAT,
					$relationIdsToDelete
				);
			}

			foreach ($relationIdsToUpdate as $value => $ids)
			{
				self::multiSubtypeUpdate($ids, $value);
			}
		}
		catch (\Exception)
		{
			return self::next($offset);
		}

		return self::next($offset + self::LIMIT);
	}

	/**
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	private static function multiSubtypeUpdate(array $ids, string $entitySubType): void
	{
		NodeRelationTable::updateMulti(
			$ids,
			[
				'ENTITY_SUBTYPE' => $entitySubType,
			],
		);
	}
}