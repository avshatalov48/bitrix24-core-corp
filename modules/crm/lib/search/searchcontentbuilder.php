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
	 * Prepare Search Map.
	 * Map is source of text for fulltext search index.
	 * @param array $fields Entity Fields.
	 * @param array|null $options Options.
	 * @return SearchMap
	 */
	abstract protected function prepareSearchMap(array $fields, array $options = null);

	/**
	 * Prepare required data for bulk build.
	 * @param array $entityIDs Entity IDs.
	 */
	protected function prepareForBulkBuild(array $entityIDs)
	{
	}
	abstract protected function save($entityID, SearchMap $map);

	protected function parseCustomerNumber($str, $template)
	{
		$regex = $template !== ''
			? '/^'.str_replace(array('%NUMBER%', ' '), array('\s*(.+)\s*', '\s*'), $template).'$/'
			: '/(?:#|Nr.?)\s*(.+)/';

		if(preg_match($regex, $str, $m) === 1 && isset($m[1]))
		{
			return $m[1];
		}
		return '';
	}

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

	/**
	 * Prepare text for fulltext search index.
	 * @param int $entityID Entity ID.
	 * @param array|null $options Options.
	 * @return string
	 */
	function getSearchContent($entityID, array $options = null)
	{
		$fields = $this->prepareEntityFields($entityID);
		if(!is_array($fields))
		{
			return '';
		}

		$map = $this->prepareSearchMap($fields, $options);
		return $map ? $map->getString() : '';
	}

	/**
	 * Prepare and save search content for entity specified by ID.
	 * @param int $entityID Entity ID.
	 * @param array|null $options Options.
	 */
	public function build($entityID, array $options = null)
	{
		$fields = $this->prepareEntityFields($entityID);
		if(is_array($fields))
		{
			if (!isset($options['onlyShortIndex']) || !$options['onlyShortIndex'])
			{
				$this->save($entityID, $this->prepareSearchMap($fields, $options));
			}

			$options['isShortIndex'] = true;
			$this->saveShortIndex(
				(int)$entityID,
				$this->prepareSearchMap($fields, $options),
				($options['checkExist'] ?? false)
			);
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
				$this->saveShortIndex(
					(int)$entityID,
					$this->prepareSearchMap($fields, ['isShortIndex' => true]),
					true
				);
			}
		}
	}

	protected function saveShortIndex(int $entityId, SearchMap $map, bool $checkExist = false): ?\Bitrix\Main\DB\Result
	{
		// not implemented by default
		return null;
	}

	public function removeShortIndex(int $entityId): \Bitrix\Main\ORM\Data\Result
	{
		// not implemented by default
		return new \Bitrix\Main\ORM\Data\Result();
	}
}
