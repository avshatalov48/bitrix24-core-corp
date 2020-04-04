<?php
namespace Bitrix\Crm\Search;
use Bitrix\Crm\Invoice\InvoiceStatus;
use Bitrix\Crm\InvoiceTable;
class InvoiceSearchContentBuilder extends SearchContentBuilder
{
	protected static function getStatusNameById($id)
	{
		static $statuses = null;

		if($statuses === null)
		{
			$statuses = [];
			$res = InvoiceStatus::getList();
			while($status = $res->fetch())
			{
				$statuses[$status['STATUS_ID']] = $status['NAME'];
			}
		}

		return (isset($statuses[$id]) ? $statuses[$id] : '');
	}

	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Invoice;
	}
	protected function getUserFieldEntityID()
	{
		return \CCrmInvoice::GetUserFieldEntityID();
	}
	public function isFullTextSearchEnabled()
	{
		return InvoiceTable::getEntity()->fullTextIndexEnabled('SEARCH_CONTENT');
	}
	protected function prepareEntityFields($entityID)
	{
		$dbResult = \CCrmInvoice::GetList(
			[],
			['=ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'],
			false,
			false,
			['*'/*, 'UF_*'*/]
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
		$map->addField($fields, 'ACCOUNT_NUMBER');
		$map->addField($fields, 'ORDER_TOPIC');

		$map->addField($fields, 'PRICE');
		$map->add(
			\CCrmCurrency::GetCurrencyName(
				isset($fields['CURRENCY_ID']) ? $fields['CURRENCY_ID'] : ''
			)
		);

		if(isset($fields['RESPONSIBLE_ID']))
		{
			$map->addUserByID($fields['RESPONSIBLE_ID']);
		}

		//region Company
		$companyID = isset($fields['UF_COMPANY_ID']) ? (int)$fields['UF_COMPANY_ID'] : 0;
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
		//endregion Company

		//region Contact
		$contactID = isset($fields['UF_CONTACT_ID']) ? (int)$fields['UF_CONTACT_ID'] : 0;
		if ($contactID > 0)
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
		//endregion Contact

		if(isset($fields['STATUS_ID']))
		{
			$map->add(
				self::getStatusNameById($fields['STATUS_ID'])
			);
		}

		if(isset($fields['DATE_BILL']))
		{
			$map->add($fields['DATE_BILL']);
		}

		if(isset($fields['DATE_PAY_BEFORE']))
		{
			$map->add($fields['DATE_PAY_BEFORE']);
		}

		if(isset($fields['COMMENTS']))
		{
			$map->addHtml($fields['COMMENTS'], 1024);
		}

		if(isset($fields['USER_DESCRIPTION']))
		{
			$map->addHtml($fields['USER_DESCRIPTION'], 1024);
		}

		if(isset($fields['PAY_VOUCHER_NUM']))
		{
			$map->add($fields['PAY_VOUCHER_NUM']);
		}

		if(isset($fields['REASON_MARKED']))
		{
			$map->add($fields['REASON_MARKED']);
		}

		//region Properties
		$personTypeId = (int)$fields['PERSON_TYPE_ID'];
		if ($personTypeId > 0)
		{
			$allowedProperties = \CCrmInvoice::GetPropertiesInfo($personTypeId, true);
			$allowedProperties = is_array($allowedProperties[$personTypeId]) ?
				array_keys($allowedProperties[$personTypeId]) : [];
			if (!empty($allowedProperties))
			{
				$properties = \CCrmInvoice::GetProperties($entityID, $personTypeId);
				foreach ($properties as $propertyInfo)
				{
					$propertyCode = isset($propertyInfo['FIELDS']['CODE']) ? $propertyInfo['FIELDS']['CODE'] : '';
					if (isset($propertyInfo['VALUE'])
						&& is_string($propertyCode) && $propertyCode !== ''
						&& $propertyCode !== 'LOCATION'
						&& in_array($propertyCode, $allowedProperties))
					{
						$value = $propertyInfo['VALUE'];
						if ($propertyCode === 'PHONE')
						{
							$value = SearchEnvironment::prepareSearchContent($value);
						}
						$map->add($value);
					}
				}
				unset($properties, $propertyCode, $propertyInfo);
			}
			unset($allowedProperties);
		}
		unset($personTypeId);
		//endregion Properties

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
		$dbResult = \CCrmInvoice::GetList(
			[],
			['=ID' => $entityIDs, 'CHECK_PERMISSIONS' => 'N'],
			['RESPONSIBLE_ID'],
			false,
			['RESPONSIBLE_ID']
		);

		$userIDs = array();
		while($fields = $dbResult->Fetch())
		{
			$userIDs[] = (int)$fields['RESPONSIBLE_ID'];
		}

		if(!empty($userIDs))
		{
			SearchMap::cacheUsers($userIDs);
		}
	}
	protected function save($entityID, SearchMap $map)
	{
		InvoiceTable::update($entityID, array('SEARCH_CONTENT' => $map->getString()));
	}
}