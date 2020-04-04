<?php
namespace Bitrix\Crm\Search;
use Bitrix\Crm;
use Bitrix\Crm\Timeline\Entity\TimelineSearchTable;
class TimelineSearchContentBuilder extends SearchContentBuilder
{
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Undefined;
	}
	protected function getUserFieldEntityID()
	{
		return '';
	}
	public function isFullTextSearchEnabled()
	{
		return TimelineSearchTable::getEntity()->fullTextIndexEnabled('SEARCH_CONTENT');
	}
	protected function prepareEntityFields($entityID)
	{
		return Crm\Timeline\TimelineEntry::getByID($entityID);
	}
	public function prepareEntityFilter(array $params)
	{
		$value = isset($params['SEARCH_CONTENT']) ? $params['SEARCH_CONTENT'] : '';
		if(!is_string($value) || $value === '')
		{
			return array();
		}

		$operation = $this->isFullTextSearchEnabled() ? '*' : '*%';
		return array("{$operation}SEARCH_CONTENT" => SearchEnvironment::prepareToken($value));
	}

	/**
	 * Prepare required data for bulk build.
	 * @param array $entityIDs Entity IDs.
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function prepareForBulkBuild(array $entityIDs)
	{
		$dbResult = Crm\Timeline\Entity\TimelineTable::getList(
			array(
				'select' => array('AUTHOR_ID'),
				'filter' => array('@ID' => $entityIDs)
			)
		);
		$userIDs = array();
		while($fields = $dbResult->Fetch())
		{
			$userIDs[] = (int)$fields['AUTHOR_ID'];
		}

		if(!empty($userIDs))
		{
			SearchMap::cacheUsers($userIDs);
		}
	}
	/**
	 * Prepare Search Map.
	 * Map is source of text for fulltext search index.
	 * @param array $fields Entity Fields.
	 * @param array|null $options Options.
	 * @return SearchMap
	 */
	protected function prepareSearchMap(array $fields, array $options = null)
	{
		$map = new SearchMap();

		$entityID = isset($fields['ID']) ? (int)$fields['ID'] : 0;
		if($entityID <= 0)
		{
			return $map;
		}

		$controller = Crm\Timeline\TimelineManager::resolveController($fields);
		if($controller)
		{
			$map->add($controller->prepareSearchContent($fields));
		}
		return $map;
	}

	protected function save($entityID, SearchMap $map)
	{
		Crm\Timeline\Entity\TimelineSearchTable::upsert(
			array(
				'OWNER_ID' => $entityID,
				'SEARCH_CONTENT' => $map->getString()
			)
		);
	}
}