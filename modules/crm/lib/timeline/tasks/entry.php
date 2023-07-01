<?php

namespace Bitrix\Crm\Timeline\Tasks;

use Bitrix\Crm\Activity\Provider\Tasks\Task;
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Crm\Timeline\TimelineEntry;
use Bitrix\Crm\Timeline\TimelineType;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Integration\CRM\Timeline\Bindings;

class Entry extends TimelineEntry
{
	public static function create(array $params): int
	{
		$taskId = $params['SETTINGS']['TASK_ID'] ?? null;
		if (is_null($taskId))
		{
			return 0;
		}
		$settings = $params['SETTINGS'] ?? null;
		$typeCategoryId = $params['TYPE_CATEGORY_ID'] ?? null;
		$authorId = $params['AUTHOR_ID'] ?? 0;
		$associatedEntityTypeId = $params['SETTINGS']['ASSOCIATED_ENTITY_TYPE_ID'] ?? 0;
		$associatedEntityId = $params['SETTINGS']['ASSOCIATED_ENTITY_ID'] ?? 0;
		/** @var Bindings $bindings */
		$bindings = $params['BINDINGS'];
		$result = TimelineTable::add([
			'TYPE_ID' => static::getTypeId(),
			'TYPE_CATEGORY_ID' => $typeCategoryId,
			'CREATED' => new DateTime(),
			'AUTHOR_ID' => $authorId,
			'SETTINGS' => $settings,
			'ASSOCIATED_ENTITY_TYPE_ID' => $associatedEntityTypeId,
			'ASSOCIATED_ENTITY_ID' => $associatedEntityId,
			'ASSOCIATED_ENTITY_CLASS_NAME' => self::getAssociatedClassName(),
			'SOURCE_ID' => $taskId,
		]);
		if (!$result->isSuccess())
		{
			return 0;
		}

		$id = (int)$result->getId();

		self::registerBindings($id, $bindings->toArray());
		self::buildSearchContent($id);

		return $id;
	}

	public static function getTypeId(): string
	{
		return TimelineType::TASK;
	}

	public static function getAssociatedClassName(): string
	{
		return Task::getId();
	}
}