<?php
namespace Bitrix\Crm\Search;
use Bitrix\Crm\Integrity\DuplicateCommunicationCriterion;
abstract class SearchContentBuilder
{
	protected static $supportedUserFieldTypeIDs = array(
		'address',
		'string',
		'integer',
		'double',
		'boolean',
		'date',
		'datetime',
		'enumeration',
		'employee',
		'file',
		'url',
		'crm',
		'crm_status',
		'iblock_element',
		'iblock_section'
	);

	abstract public function getEntityTypeID();
	abstract public function isFullTextSearchEnabled();
	abstract protected function prepareEntityFields($entityID);
	abstract public function prepareEntityFilter(array $params);
	/**
	 * Prepare search map.
	 * @param array $fields Entity Fields.
	 * @return SearchMap
	 */
	abstract protected function prepareSearchMap(array $fields);

	/**
	 * Prepare required data for bulk build.
	 * @param array $entityIDs Entity IDs.
	 */
	protected function prepareForBulkBuild(array $entityIDs)
	{
	}
	abstract protected function save($entityID, SearchMap $map);

	protected function getSearchFieldName()
	{
		return 'SEARCH_CONTENT';
	}

	/**
	 * Convert entity list filter values.
	 * @param array $filter List Filter.
	 * @return void
	 */
	public function convertEntityFilterValues(array &$filter)
	{
		$this->transferEntityFilterKeys(array('FIND'), $filter);
	}
	/**
	 * Transfer specified filter keys to search content.
	 * @param array $sourceKeys Filter keys for transfer to search
	 * @param array $filter List Filter.
	 */
	protected function transferEntityFilterKeys(array $sourceKeys, array &$filter)
	{
		$searchFieldName = $this->getSearchFieldName();
		foreach($sourceKeys as $key)
		{
			if(!isset($filter[$key]))
			{
				continue;
			}

			if(is_string($filter[$key]))
			{
				$find = trim($filter[$key]);
				if($find !== '')
				{
					$find = SearchEnvironment::prepareSearchContent($find);
					if(!isset($filter[$searchFieldName]))
					{
						$filter[$searchFieldName] = $find;
					}
					else
					{
						$filter[$searchFieldName] .= ' '.$find;
					}
				}
			}
			unset($filter[$key]);
		}
	}

	protected function getEntityMultiFields($entityID)
	{
		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		if($entityID <= 0)
		{
			return array();
		}

		return DuplicateCommunicationCriterion::prepareEntityMultifieldValues($this->getEntityTypeID(), $entityID);
	}

	protected function getUserFieldEntityID()
	{
		return '';
	}

	protected function prepareUserTypeEntity()
	{
		global $USER_FIELD_MANAGER;

		$userFieldEntityID = $this->getUserFieldEntityID();
		return $userFieldEntityID !== '' ? new \CCrmUserType($USER_FIELD_MANAGER, $userFieldEntityID) : null;
	}

	protected function getUserFields($entityID)
	{
		$userTypeEntity = $this->prepareUserTypeEntity();
		if(!$userTypeEntity)
		{
			return array();
		}

		$results = array();
		$userFields = $userTypeEntity->GetEntityFields($entityID);
		$userTypeMap = array_fill_keys(self::$supportedUserFieldTypeIDs, true);
		foreach($userFields as $userField)
		{
			if(isset($userTypeMap[$userField['USER_TYPE_ID']]))
			{
				$results[] = $userField;
			}
		}
		return $results;
	}

	public function build($entityID)
	{
		$fields = $this->prepareEntityFields($entityID);
		if(is_array($fields))
		{
			$this->save($entityID, $this->prepareSearchMap($fields));
		}
	}

	public function bulkBuild(array $entityIDs)
	{
		$this->prepareForBulkBuild($entityIDs);
		foreach($entityIDs as $entityID)
		{
			$fields = $this->prepareEntityFields($entityID);
			if(is_array($fields))
			{
				$this->save($entityID, $this->prepareSearchMap($fields));
			}
		}
	}
}