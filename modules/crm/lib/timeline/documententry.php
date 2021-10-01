<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Main;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Type\DateTime;

class DocumentEntry extends TimelineEntry
{
	public static function create(array $params, $documentId = null)
	{
		if (is_null($documentId))
		{
			throw new Main\ArgumentNullException('documentId');
		}

		$text = isset($params['TEXT']) ? $params['TEXT'] : '';
		if($text === '')
		{
			throw new Main\ArgumentException('Text should not be empty', 'text');
		}

		$authorID = isset($params['AUTHOR_ID']) ? (int)$params['AUTHOR_ID'] : 0;
		if($authorID <= 0)
		{
			$authorID = \CCrmSecurityHelper::GetCurrentUserID();
		}

		$settings = isset($params['SETTINGS']) ? $params['SETTINGS'] : [];
		$settings['DOCUMENT_ID'] = $documentId;

		$created = isset($params['CREATED']) && ($params['CREATED'] instanceof DateTime)
			? $params['CREATED'] : new DateTime();

		$result = TimelineTable::add(
			array(
				'TYPE_ID' => TimelineType::DOCUMENT,
				'TYPE_CATEGORY_ID' => (int)$params['TYPE_CATEGORY_ID'],
				'CREATED' => $created,
				'AUTHOR_ID' => $authorID,
				'COMMENT' => $text,
				'SETTINGS' => $settings,
				'ASSOCIATED_ENTITY_TYPE_ID' => TimelineType::DOCUMENT,
				'ASSOCIATED_ENTITY_ID' => $documentId,
			)
		);

		if(!$result->isSuccess())
		{
			return 0;
		}

		$ID = $result->getId();
		$bindings = isset($params['BINDINGS']) && is_array($params['BINDINGS']) ? $params['BINDINGS'] : array();
		self::registerBindings($ID, $bindings);
		self::buildSearchContent($ID);
		return $ID;
	}

	public static function rebind($entityTypeID, $oldEntityID, $newEntityID)
	{
		Entity\TimelineBindingTable::rebind($entityTypeID, $oldEntityID, $newEntityID, array(TimelineType::DOCUMENT));
	}

	public static function attach($srcEntityTypeID, $srcEntityID, $targEntityTypeID, $targEntityID)
	{
		Entity\TimelineBindingTable::attach($srcEntityTypeID, $srcEntityID, $targEntityTypeID, $targEntityID, array(TimelineType::DOCUMENT));
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
}
