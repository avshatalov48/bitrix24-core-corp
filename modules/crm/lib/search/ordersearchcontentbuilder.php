<?php
namespace Bitrix\Crm\Search;

use Bitrix\Crm\Order\Order;
use Bitrix\Crm\Order\OrderStatus;
use Bitrix\Sale\Internals\OrderTable;

if (!\Bitrix\Main\Loader::includeModule('sale'))
{
	return;
}

class OrderSearchContentBuilder extends SearchContentBuilder
{
	protected static function getStatusNameById($id)
	{
		static $statuses = null;

		if($statuses === null)
		{
			$statuses = [];
			$res = OrderStatus::getList();
			while($status = $res->fetch())
			{
				$key = $status['STATUS_ID'] ?? null;
				$statuses[$key] = $status['NAME'] ?? null;
			}
		}

		return (isset($statuses[$id]) ? $statuses[$id] : '');
	}

	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Order;
	}
	protected function getUserFieldEntityID()
	{
		return Order::getUfId();
	}
	public function isFullTextSearchEnabled()
	{
		return OrderTable::getEntity()->fullTextIndexEnabled('SEARCH_CONTENT');
	}
	protected function prepareEntityFields($entityID)
	{
		$order = Order::load($entityID);
		if (!$order)
		{
			return $order;
		}

		$fields = $order->getFieldValues();
		foreach($order->getContactCompanyCollection()->getContacts() as $contact)
		{
			$fields['CONTACT_IDS'][] = $contact->getField('ENTITY_ID');
		}

		$company = $order->getContactCompanyCollection()->getPrimaryCompany();
		if($company)
		{
			$fields['COMPANY_ID'] = $company->getField('ENTITY_ID');
		}

		foreach ($order->getPropertyCollection() as $property)
		{
			$allowedTypes = ['STRING', 'NUMBER', 'DATE'];
			if (in_array($property->getField('TYPE'), $allowedTypes) || empty($code))
			{
				continue;
			}

			$code = $property->getField('CODE');
			$value = $property->getValue();
			if ($code === 'PHONE')
			{
				$value = SearchEnvironment::prepareSearchContent($value);
			}
			$fields['PROPERTIES'][$code] = $value;
		}

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

		$map->addUserByID($fields['USER_ID']);

		if(isset($fields['RESPONSIBLE_ID']))
		{
			$map->addUserByID($fields['RESPONSIBLE_ID']);
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
		//endregion Company

		//region Contact
		$contactIDs = is_array($fields['CONTACT_IDS']) ? $fields['CONTACT_IDS'] : [];
		foreach ($contactIDs as $contactID)
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

		if(isset($fields['DATE_INSERT']))
		{
			$map->add($fields['DATE_INSERT']);
		}

		if(isset($fields['COMMENTS']))
		{
			$map->addHtml($fields['COMMENTS'], 1024);
		}

		if(isset($fields['USER_DESCRIPTION']))
		{
			$map->addHtml($fields['USER_DESCRIPTION'], 1024);
		}

		if (isset($fields['PROPERTIES']) && is_array($fields['PROPERTIES']))
		{
			foreach ($fields['PROPERTIES'] as $propertyValue)
			{
				$map->add($propertyValue);
			}
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
		$orderData = Order::getList([
			'filter' => ['=ID' => $entityIDs],
			'select' => ['RESPONSIBLE_ID']
		]);

		while($fields = $orderData->fetch())
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
		OrderTable::update($entityID, array('SEARCH_CONTENT' => $map->getString()));
	}
}