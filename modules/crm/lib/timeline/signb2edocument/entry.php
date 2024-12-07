<?php

namespace Bitrix\Crm\Timeline\SignB2eDocument;

use Bitrix\Crm\Timeline\SignDocument\DocumentData;
use Bitrix\Crm\Timeline\SignDocument\MessageData;
use Bitrix\Crm\Timeline\TimelineEntry;
use Bitrix\Crm\Timeline\TimelineType;
use Bitrix\Crm\Timeline\HistoryDataModel\Presenter;
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Main\Type\DateTime;

class Entry extends TimelineEntry
{
	public const TYPE_CATEGORY_CREATED = 1;
	public const TYPE_CATEGORY_STOPPED = 2;
	public const TYPE_CATEGORY_DONE = 3;
	public const TYPE_CATEGORY_STARTED = 4;
	public const TYPE_CATEGORY_SIGNED_BY_RESPONSIBILITY_PERSON = 5; // log
	public const TYPE_CATEGORY_SIGNED_BY_EMPLOYEE = 6; // log
	public const TYPE_CATEGORY_CANCELED_BY_RESPONSIBILITY_PERSON= 7; // log
	public const TYPE_CATEGORY_CANCELED_BY_EMPLOYEE = 8; // log
	public const TYPE_CATEGORY_MESSAGE_SENT = 9; // log
	public const TYPE_CATEGORY_SIGNED_BY_EDITOR = 10; // log
	public const TYPE_CATEGORY_SIGNED_BY_REVIEWER = 11; // log
	public const TYPE_CATEGORY_CANCELED_BY_EDITOR = 12; // log
	public const TYPE_CATEGORY_CANCELED_BY_REVIEWER = 13; // log
	public const TYPE_CATEGORY_MESSAGE_DELIVERED = 14; // log
	public const TYPE_CATEGORY_MESSAGE_DELIVERY_ERROR = 15; // log
	public const TYPE_CATEGORY_MEMBER_SIGNING_EXPIRED = 16; // log
	public const TYPE_CATEGORY_MEMBER_SIGNING_ERROR = 17; // log
	public const TYPE_CATEGORY_MEMBER_SNILS_ERROR = 18; // log
	public const TYPE_CATEGORY_MEMBER_STOPPED_BY_ASSIGNEE = 19; // log
	public const TYPE_CATEGORY_MEMBER_STOPPED_BY_REVIEWER = 20; // log
	public const TYPE_CATEGORY_MEMBER_STOPPED_BY_EDITOR = 21; // log
	public const TYPE_CATEGORY_MEMBER_SIGNED_DELIVERED  = 22; // log
	public const TYPE_CATEGORY_CONFIGURATION_ERROR = 23; // log

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
		return TimelineType::SIGN_B2E_DOCUMENT;
	}
}
