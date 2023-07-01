<?php

namespace Bitrix\Crm\Timeline\CalendarSharing;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Crm\Timeline\TimelineEntry;
use Bitrix\Crm\Timeline\TimelineType;
use Bitrix\Main\Type\DateTime;

final class Entry extends TimelineEntry
{
	public const SHARING_TYPE_INVITATION_SENT = 1;

	public static function create(array $params)
	{
		$authorId = $params['AUTHOR_ID'] ?? null;
		$settings = $params['SETTINGS'] ?? null;
		$bindings = $params['BINDINGS'] ?? null;

		$result = TimelineTable::add([
			'TYPE_ID' => $params['TYPE_ID'],
			'TYPE_CATEGORY_ID' => $params['TYPE_CATEGORY_ID'],
			'CREATED' => new DateTime(),
			'AUTHOR_ID' => $authorId,
			'SETTINGS' => $settings,
			'ASSOCIATED_ENTITY_TYPE_ID' => $params['ASSOCIATED_ENTITY_TYPE_ID'] ?? 0,
			'ASSOCIATED_ENTITY_ID' => $params['ASSOCIATED_ENTITY_ID'] ?? 0,
		]);

		if (!$result->isSuccess())
		{
			return 0;
		}
		$id = $result->getId();

		if ($bindings)
		{
			$bindingsArray = self::getBindingsArray($bindings);

			self::registerBindings($id, $bindingsArray);
		}

		return $id;
	}

	public static function getTypeId(): string
	{
		return TimelineType::CALENDAR_SHARING;
	}

	private static function getBindingsArray(array $bindings): array
	{
		$result = [];
		/** @var ItemIdentifier $binding */
		foreach ($bindings as $binding)
		{
			$result[] = $binding->toArray();
		}

		return $result;
	}
}