<?php
namespace Bitrix\Crm\Search;
use Bitrix\Crm\Preview\Quote;
use Bitrix\Crm\QuoteTable;
use Bitrix\Crm\Binding\QuoteContactTable;
class QuoteSearchContentBuilder extends SearchContentBuilder
{
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Quote;
	}
	protected function getUserFieldEntityID()
	{
		return \CCrmQuote::GetUserFieldEntityID();
	}
	public function isFullTextSearchEnabled()
	{
		return QuoteTable::getEntity()->fullTextIndexEnabled('SEARCH_CONTENT');
	}
	protected function prepareEntityFields($entityID)
	{
		$dbResult = \CCrmQuote::GetList(
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
	 * Prepare search map.
	 * @param array $fields Entity Fields.
	 * @param array|null $options Options.
	 * @return SearchMap
	 * @throws \Bitrix\Main\ArgumentException
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
		$map->addField($fields, 'TITLE');

		$map->addField($fields, 'OPPORTUNITY');
		$map->add(
			\CCrmCurrency::GetCurrencyName(
				isset($fields['CURRENCY_ID']) ? $fields['CURRENCY_ID'] : ''
			)
		);

		if(isset($fields['ASSIGNED_BY_ID']))
		{
			$map->addUserByID($fields['ASSIGNED_BY_ID']);
		}

		//region Company
		$companyID = isset($fields['COMPANY_ID']) ? (int)$fields['COMPANY_ID'] : 0;
		if($companyID > 0)
		{
			$map->add(
				\CCrmOwnerType::GetCaption(\CCrmOwnerType::Company, $companyID, false)
			);

			$map->addEntityMultiFields(
				\CCrmOwnerType::Company,
				$companyID,
				array(\CCrmFieldMulti::PHONE, \CCrmFieldMulti::EMAIL)
			);
		}
		//endregion

		//region Contacts
		$contactIDs = QuoteContactTable::getQuoteContactIDs($entityID);
		foreach($contactIDs as $contactID)
		{
			$map->add(
				\CCrmOwnerType::GetCaption(\CCrmOwnerType::Contact, $contactID, false)
			);

			$map->addEntityMultiFields(
				\CCrmOwnerType::Contact,
				$contactID,
				array(\CCrmFieldMulti::PHONE, \CCrmFieldMulti::EMAIL)
			);
		}
		//endregion

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
		$dbResult = \CCrmQuote::GetList(
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
		QuoteTable::update($entityID, array('SEARCH_CONTENT' => $map->getString()));
	}
}