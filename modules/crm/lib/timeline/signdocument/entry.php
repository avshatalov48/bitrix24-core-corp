<?php

namespace Bitrix\Crm\Timeline\SignDocument;

use Bitrix\Crm\Timeline\TimelineEntry;
use Bitrix\Crm\Timeline\TimelineType;
use Bitrix\Crm\Timeline\HistoryDataModel\Presenter;
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Main\Type\DateTime;

class Entry extends TimelineEntry
{
	public const TYPE_CATEGORY_CREATED = 1;
	public const TYPE_CATEGORY_SENT = 2;
	public const TYPE_CATEGORY_VIEWED = 3;
	public const TYPE_CATEGORY_PREPARED_TO_FILL = 4;
	public const TYPE_CATEGORY_FILLED = 5;
	public const TYPE_CATEGORY_SIGNED = 6;
	public const TYPE_CATEGORY_SIGN_COMPLETED = 7;
	public const TYPE_CATEGORY_SENT_FINAL = 8;
	public const TYPE_CATEGORY_COMPLETED = 9;
	public const TYPE_CATEGORY_REQUESTED = 10;
	public const TYPE_CATEGORY_SENT_REPEATEDLY = 11;
	public const TYPE_CATEGORY_INTEGRITY_SUCCESS = 12;
	public const TYPE_CATEGORY_INTEGRITY_FAILURE = 13;
	public const TYPE_CATEGORY_SENT_INTEGRITY_FAILURE = 14;
	public const TYPE_CATEGORY_PRINTED_FORM = 15;
	public const TYPE_CATEGORY_PIN_SEND_LIMIT_REACHED = 16;
	public const TYPE_CATEGORY_NOTIFICATION_DELIVERED = 17;
	public const TYPE_CATEGORY_NOTIFICATION_ERROR = 18;
	public const TYPE_CATEGORY_NOTIFICATION_READ = 19;

	public static function create(array $params): ?int
	{
		$documentData = $params[Presenter\SignDocument::DOCUMENT_DATA_KEY] ?? null;
		if (!($documentData instanceof DocumentData))
		{
			// todo throw exception ?
			return null;
		}

		$entityTypeId = static::getRequiredIntegerParam('ENTITY_TYPE_ID', $params);
		$entityId = static::getRequiredIntegerParam('ENTITY_ID', $params);
		$authorID = static::getRequiredIntegerParam('AUTHOR_ID', $params);
		$typeCategoryId = static::getRequiredIntegerParam('TYPE_CATEGORY_ID', $params);

		$created = $documentData->getCreatedTime() ?? new DateTime();

		$settings = (array)($params['SETTINGS'] ?? []);
		$settings[Presenter\SignDocument::DOCUMENT_DATA_KEY] = $documentData->toArray();
		if (($params[Presenter\SignDocument::MESSAGE_DATA_KEY] ?? null) instanceof MessageData)
		{
			$settings[Presenter\SignDocument::MESSAGE_DATA_KEY] = $params[Presenter\SignDocument::MESSAGE_DATA_KEY]->toArray();
		}

		$result = TimelineTable::add([
			'TYPE_ID' => static::getTypeId(),
			'TYPE_CATEGORY_ID' => $typeCategoryId,
			'CREATED' => $created,
			'AUTHOR_ID' => $authorID,
			'SETTINGS' => $settings,
			'ASSOCIATED_ENTITY_TYPE_ID' => $entityTypeId,
			'ASSOCIATED_ENTITY_ID' => $entityId,
		]);
		if (!$result->isSuccess())
		{
			return 0;
		}

		$id = $result->getId();
		$bindings = $documentData->getBindingsArray();
		if (empty($bindings))
		{
			$bindings[] = [
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_ID' => $entityId
			];
		}
		self::registerBindings($id, $bindings);

		return $id;
	}

	public static function getTypeId(): string
	{
		return TimelineType::SIGN_DOCUMENT;
	}
}
