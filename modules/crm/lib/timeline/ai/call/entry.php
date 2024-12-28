<?php

namespace Bitrix\Crm\Timeline\AI\Call;

use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Timeline\Entity\TimelineBindingTable;
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Crm\Timeline\TimelineEntry;
use Bitrix\Crm\Timeline\TimelineType;

final class Entry extends TimelineEntry
{
	public static function create(array $params)
	{
		[$authorId, $created, $settings, $bindings] = self::fetchParams($params);
		$entityId = self::fetchEntityId($params);
		if ($entityId <= 0)
		{
			return 0;
		}

		$entityTypeId = self::fetchEntityTypeId($params);
		if (!in_array($entityTypeId, AIManager::SUPPORTED_ENTITY_TYPE_IDS, true))
		{
			return 0;
		}

		$categoryId = self::fetchCategoryId($params);
		if (
			!in_array(
				$categoryId,
				[
					CategoryType::RECORD_TRANSCRIPT_FINISHED,
					CategoryType::RECORD_TRANSCRIPT_SUMMARY_FINISHED,
					CategoryType::FILLING_ENTITY_FIELDS_FINISHED,
					CategoryType::CALL_SCORING_FINISHED,
				],
				true
			)
		)
		{
			return 0;
		}

		$result = TimelineTable::add([
			'TYPE_ID' => TimelineType::AI_CALL_PROCESSING,
			'TYPE_CATEGORY_ID' => $categoryId,
			'CREATED' => $created,
			'AUTHOR_ID' => $authorId,
			'SETTINGS' => $settings,
			'SOURCE_ID' => $params['SOURCE_ID'] ?? '',
			'ASSOCIATED_ENTITY_TYPE_ID' => $params['ASSOCIATED_ENTITY_TYPE_ID'] ?? $entityTypeId,
			'ASSOCIATED_ENTITY_ID' => $params['ASSOCIATED_ENTITY_ID'] ?? $entityId,
		]);
		if (!$result->isSuccess())
		{
			return 0;
		}

		$createdId = $result->getId();

		if (empty($bindings))
		{
			$bindings[] = ['ENTITY_TYPE_ID' => $entityTypeId, 'ENTITY_ID' => $entityId];
		}

		self::registerBindings($createdId, $bindings);

		return $createdId;
	}

	public static function rebind($entityTypeId, $oldEntityId, $newEntityId): void
	{
		TimelineBindingTable::rebind(
			$entityTypeId,
			$oldEntityId,
			$newEntityId,
			[TimelineType::AI_CALL_PROCESSING]
		);
	}
}
