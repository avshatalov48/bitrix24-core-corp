<?php
namespace Bitrix\Crm\Search;
use \Bitrix\Crm\CompanyTable;
class CompanySearchContentBuilder extends SearchContentBuilder
{
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Company;
	}
	protected function getUserFieldEntityID()
	{
		return \CCrmCompany::GetUserFieldEntityID();
	}
	public function isFullTextSearchEnabled()
	{
		return CompanyTable::getEntity()->fullTextIndexEnabled('SEARCH_CONTENT');
	}
	protected function prepareEntityFields($entityID)
	{
		$dbResult = \CCrmCompany::GetListEx(
			array(),
			array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('*'/*, 'UF_*'*/)
		);

		$fields = $dbResult->Fetch();
		return is_array($fields) ? $fields : null;
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
	 * Convert entity list filter values.
	 * @param array $filter List Filter.
	 * @return void
	 */
	public function convertEntityFilterValues(array &$filter)
	{
		$this->transferEntityFilterKeys(array('FIND', 'PHONE'), $filter);
	}
	/**
	 * Prepare search map.
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

		$map->add($entityID);

		$title = isset($fields['TITLE']) ? $fields['TITLE'] : '';
		if($title !== '')
		{
			$map->addText($title);
			$map->addText(SearchEnvironment::prepareSearchContent($title));

			$customerNumber = $this->parseCustomerNumber($title, '');
			if($customerNumber != $entityID)
			{
				$map->addTextFragments($customerNumber);
			}
		}

		if(isset($fields['ASSIGNED_BY_ID']))
		{
			$map->addUserByID($fields['ASSIGNED_BY_ID']);
		}

		$multiFields = $this->getEntityMultiFields($entityID);
		if(isset($multiFields[\CCrmFieldMulti::PHONE]))
		{
			foreach($multiFields[\CCrmFieldMulti::PHONE] as $multiField)
			{
				if(isset($multiField['VALUE']))
				{
					$map->addPhone($multiField['VALUE']);
				}
			}
		}
		if(isset($multiFields[\CCrmFieldMulti::EMAIL]))
		{
			foreach($multiFields[\CCrmFieldMulti::EMAIL] as $multiField)
			{
				if(isset($multiField['VALUE']))
				{
					$map->addEmail($multiField['VALUE']);
				}
			}
		}

		if(isset($fields['INDUSTRY']))
		{
			$map->addStatus('INDUSTRY', $fields['INDUSTRY']);
		}

		if(isset($fields['COMMENTS']))
		{
			$map->addHtml($fields['COMMENTS'], 1024);
		}

		//region UserFields
		foreach($this->getUserFields($entityID) as $userField)
		{
			$map->addUserField($userField);
		}
		//endregion

		return $map;
	}
	/**
	 * Prepare required data for bulk build.
	 * @param array $entityIDs Entity IDs.
	 */
	protected function prepareForBulkBuild(array $entityIDs)
	{
		$dbResult = \CCrmCompany::GetListEx(
			array(),
			array('@ID' => $entityIDs, 'CHECK_PERMISSIONS' => 'N'),
			array('ASSIGNED_BY_ID'),
			false,
			array('ASSIGNED_BY_ID')
		);

		$userIDs = array();
		while($fields = $dbResult->Fetch())
		{
			$userIDs[] = (int)$fields['ASSIGNED_BY_ID'];
		}

		if(!empty($userIDs))
		{
			SearchMap::cacheUsers($userIDs);
		}
	}
	protected function save($entityID, SearchMap $map)
	{
		CompanyTable::update($entityID, array('SEARCH_CONTENT' => $map->getString()));
	}
}