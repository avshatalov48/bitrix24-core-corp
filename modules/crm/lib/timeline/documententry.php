<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Main;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Entity\Query;

class DocumentEntry extends TimelineEntry
{
	public static function create(array $params, $documentId = null)
	{
		[$authorId, $created, $settings, $bindings] = self::fetchParams($params);

		if (is_null($documentId))
		{
			throw new ArgumentNullException('documentId');
		}

		$text = $params['TEXT'] ?? '';
		if ($text === '')
		{
			throw new ArgumentException('Text should not be empty', 'text');
		}

		$settings['DOCUMENT_ID'] = $documentId;

		$result = TimelineTable::add([
			'TYPE_ID' => TimelineType::DOCUMENT,
			'TYPE_CATEGORY_ID' => (int)$params['TYPE_CATEGORY_ID'],
			'CREATED' => $created,
			'AUTHOR_ID' => $authorId,
			'COMMENT' => $text,
			'SETTINGS' => $settings,
			'ASSOCIATED_ENTITY_TYPE_ID' => TimelineType::DOCUMENT,
			'ASSOCIATED_ENTITY_ID' => $documentId,
		]);
		if (!$result->isSuccess())
		{
			return 0;
		}

		$createdId = $result->getId();

		self::registerBindings($createdId, $bindings);
		self::buildSearchContent($createdId);

		return $createdId;
	}

	public static function rebind($entityTypeID, $oldEntityID, $newEntityID)
	{
		Entity\TimelineBindingTable::rebind(
			$entityTypeID,
			$oldEntityID,
			$newEntityID,
			[TimelineType::DOCUMENT]
		);
	}

	public static function attach($srcEntityTypeID, $srcEntityID, $targEntityTypeID, $targEntityID)
	{
		Entity\TimelineBindingTable::attach(
			$srcEntityTypeID,
			$srcEntityID,
			$targEntityTypeID,
			$targEntityID,
			[TimelineType::DOCUMENT]
		);
	}

	public static function update($ID, array $params)
	{
		$result = new Main\Result();

		if ($ID <= 0)
		{
			$result->addError(new Main\Error('Wrong entity ID'));
			return $result;
		}

		$updateData = array();

		if (isset($params['COMMENT']))
		{
			$updateData['COMMENT'] = $params['COMMENT'];
		}

		if (isset($params['SETTINGS']) && is_array($params['SETTINGS']))
		{
			$updateData['SETTINGS'] = $params['SETTINGS'];
		}

		if (!empty($updateData))
		{
			$result = TimelineTable::update($ID, $updateData);
			self::buildSearchContent($ID);
		}

		return $result;
	}

	/**
	 * @param $documentId
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getListByDocumentId($documentId)
	{
		$result = [];

		$query = new Query(TimelineTable::getEntity());
		$query->addFilter('=ASSOCIATED_ENTITY_TYPE_ID', TimelineType::DOCUMENT);
		$query->addFilter('=ASSOCIATED_ENTITY_ID', $documentId);
		$query->addSelect('*');

		$dbResult = $query->exec();
		while($entry = $dbResult->fetch())
		{
			$result[] = $entry;
		}

		return $result;
	}

	final public static function getDocumentCreatedEntryAuthorId(int $documentId): ?int
	{
		$query =
			TimelineTable::query()
				->setSelect(['AUTHOR_ID'])
				->where('ASSOCIATED_ENTITY_TYPE_ID', TimelineType::DOCUMENT)
				->where('ASSOCIATED_ENTITY_ID', $documentId)
				->where('TYPE_ID', TimelineType::DOCUMENT)
				->where('TYPE_CATEGORY_ID', TimelineType::CREATION)
				->setOrder(['ID' => 'DESC'])
				->setLimit(1)
		;

		$entry = $query->exec()->fetchObject();

		return $entry ? $entry->getAuthorId() : null;
	}
}
