<?php

namespace Bitrix\Crm\Search;

use Bitrix\Crm\Order\Order;
use Bitrix\Crm\Order\OrderStatus;
use Bitrix\Sale\Internals\OrderTable;
use CCrmCurrency;
use CCrmOwnerType;

if (!\Bitrix\Main\Loader::includeModule('sale'))
{
	return;
}

final class OrderSearchContentBuilder extends SearchContentBuilder
{
	public function getEntityTypeId(): int
	{
		return CCrmOwnerType::Order;
	}

	protected function getUserFieldEntityId(): string
	{
		return Order::getUfId();
	}

	protected function prepareEntityFields(int $entityId): ?array
	{
		$order = Order::load($entityId);
		if (!$order)
		{
			return null;
		}

		$fields = $order->getFieldValues();
		
		foreach ($order->getContactCompanyCollection()?->getContacts() as $contact)
		{
			$fields['CONTACT_IDS'][] = $contact->getField('ENTITY_ID');
		}

		$company = $order->getContactCompanyCollection()?->getPrimaryCompany();
		if ($company)
		{
			$fields['COMPANY_ID'] = $company->getField('ENTITY_ID');
		}

		$allowedTypes = ['STRING', 'NUMBER', 'DATE'];
		foreach ($order->getPropertyCollection() as $property)
		{
			if (
				in_array($property->getField('TYPE'), $allowedTypes, true) 
				|| empty($code)
			)
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

	protected function prepareSearchMap(array $fields, array $options = null): SearchMap
	{
		$map = new SearchMap();
		$entityId = (int)($fields['ID'] ?? 0);
		if ($entityId <= 0)
		{
			return $map;
		}

		$map->add($entityId);
		$map->addField($fields, 'ACCOUNT_NUMBER');
		$map->addField($fields, 'ORDER_TOPIC');

		$map->addField($fields, 'PRICE');
		$map->add(CCrmCurrency::GetCurrencyName($fields['CURRENCY_ID'] ?? ''));
		$map->addUserByID($fields['USER_ID']);

		if (isset($fields['RESPONSIBLE_ID']))
		{
			$map->addUserByID($fields['RESPONSIBLE_ID']);
		}

		//region Company
		$companyId = (int)($fields['COMPANY_ID'] ?? 0);
		if ($companyId > 0)
		{
			$map->addCompany($companyId);
		}
		//endregion Company

		//region Contact
		$contactIds = isset($fields['CONTACT_IDS']) && is_array($fields['CONTACT_IDS'])
			? $fields['CONTACT_IDS']
			: [];
		$map->addContacts($contactIds);
		//endregion Contact

		if (isset($fields['STATUS_ID']))
		{
			$map->add(self::getStatusNameById($fields['STATUS_ID']));
		}

		if (isset($fields['DATE_INSERT']))
		{
			$map->add($fields['DATE_INSERT']);
		}

		if (isset($fields['COMMENTS']))
		{
			$map->addHtml($fields['COMMENTS'], 1024);
		}

		if (isset($fields['USER_DESCRIPTION']))
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
		$userFields = SearchEnvironment::getUserFields($entityId, $this->getUserFieldEntityId());
		foreach ($userFields as $userField)
		{
			$map->addUserField($userField);
		}
		//endregion

		return $map;
	}

	protected function prepareForBulkBuild(array $entityIds): void
	{
		$orderData = Order::getList([
			'filter' => ['=ID' => $entityIds],
			'select' => ['RESPONSIBLE_ID']
		]);

		while ($fields = $orderData->fetch())
		{
			$userIds[] = (int)$fields['RESPONSIBLE_ID'];
		}

		if (!empty($userIds))
		{
			SearchMap::cacheUsers($userIds);
		}
	}

	protected function save(int $entityId, SearchMap $map): void
	{
		OrderTable::update(
			$entityId,
			$this->prepareUpdateData(OrderTable::getTableName(), $map->getString())
		);
	}

	protected static function getStatusNameById($id): string
	{
		static $statuses = null;
		if ($statuses === null)
		{
			$statuses = [];
			$res = OrderStatus::getList();
			while ($status = $res->fetch())
			{
				$key = $status['STATUS_ID'] ?? null;
				$statuses[$key] = $status['NAME'] ?? null;
			}
		}

		return $statuses[$id] ?? '';
	}
}
