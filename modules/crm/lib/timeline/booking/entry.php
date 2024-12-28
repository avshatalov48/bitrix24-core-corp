<?php

namespace Bitrix\Crm\Timeline\Booking;

use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Crm\Timeline\TimelineEntry;
use Bitrix\Crm\Timeline\TimelineType;
use Bitrix\Main\Type\DateTime;

final class Entry extends TimelineEntry
{
	public static function create(array $params)
	{
		[$authorId, $created, $settings, $bindings] = self::fetchParams($params);

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

		if (!empty($bindings))
		{
			self::registerBindings($id, self::getBindingsArray($bindings));
		}

		return $id;
	}

	public static function getTypeId(): string
	{
		return TimelineType::BOOKING;
	}

	private static function getBindingsArray(array $bindings): array
	{
		$result = [];

		foreach ($bindings as $binding)
		{
			$result[] = [
				'ENTITY_TYPE_ID' => $binding['OWNER_TYPE_ID'],
				'ENTITY_ID' => $binding['OWNER_ID'],
			];
		}

		return $result;
	}
}