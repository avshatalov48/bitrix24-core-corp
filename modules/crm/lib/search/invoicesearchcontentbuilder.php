<?php

namespace Bitrix\Crm\Search;

use Bitrix\Crm\Invoice\InvoiceStatus;
use Bitrix\Crm\InvoiceTable;
use CCrmCurrency;
use CCrmInvoice;
use CCrmOwnerType;

final class InvoiceSearchContentBuilder extends SearchContentBuilder
{
	protected string $entityClassName = CCrmInvoice::class;

	public function getEntityTypeId(): int
	{
		return CCrmOwnerType::Invoice;
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

		if (isset($fields['RESPONSIBLE_ID']))
		{
			$map->addUserByID($fields['RESPONSIBLE_ID']);
		}

		//region Company
		$companyId = (int)($fields['UF_COMPANY_ID'] ?? 0);
		if ($companyId > 0)
		{
			$map->addCompany($companyId);
		}
		//endregion Company

		//region Contact
		$contactId = (int)($fields['UF_CONTACT_ID'] ?? 0);
		if ($contactId > 0)
		{
			$map->addContacts([$contactId]);
		}
		//endregion Contact

		if (isset($fields['STATUS_ID']))
		{
			$map->add(self::getStatusNameById($fields['STATUS_ID']));
		}

		if (isset($fields['DATE_BILL']))
		{
			$map->add($fields['DATE_BILL']);
		}

		if (isset($fields['DATE_PAY_BEFORE']))
		{
			$map->add($fields['DATE_PAY_BEFORE']);
		}

		if (isset($fields['COMMENTS']))
		{
			$map->addHtml($fields['COMMENTS'], 1024);
		}

		if (isset($fields['USER_DESCRIPTION']))
		{
			$map->addHtml($fields['USER_DESCRIPTION'], 1024);
		}

		if (isset($fields['PAY_VOUCHER_NUM']))
		{
			$map->add($fields['PAY_VOUCHER_NUM']);
		}

		if (isset($fields['REASON_MARKED']))
		{
			$map->add($fields['REASON_MARKED']);
		}

		//region Properties
		$personTypeId = (int)($fields['PERSON_TYPE_ID'] ?? 0);
		if ($personTypeId > 0)
		{
			$allowedProperties = CCrmInvoice::GetPropertiesInfo($personTypeId, true);
			$allowedProperties = is_array($allowedProperties[$personTypeId])
				? array_keys($allowedProperties[$personTypeId])
				: [];

			if (!empty($allowedProperties))
			{
				$properties = CCrmInvoice::GetProperties($entityId, $personTypeId);
				foreach ($properties as $propertyInfo)
				{
					$propertyCode = $propertyInfo['FIELDS']['CODE'] ?? '';
					if (
						isset($propertyInfo['VALUE'])
						&& is_string($propertyCode) && $propertyCode !== ''
						&& $propertyCode !== 'LOCATION'
						&& in_array($propertyCode, $allowedProperties)
					)
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
		$userFields = SearchEnvironment::getUserFields($entityId, $this->getUserFieldEntityId());
		foreach($userFields as $userField)
		{
			$map->addUserField($userField);
		}
		//endregion

		return $map;
	}

	protected function prepareForBulkBuild(array $entityIds): void
	{
		$dbResult = CCrmInvoice::GetList(
			[],
			['=ID' => $entityIds, 'CHECK_PERMISSIONS' => 'N'],
			['RESPONSIBLE_ID'],
			false,
			['RESPONSIBLE_ID']
		);

		$userIds = [];
		while ($fields = $dbResult->Fetch())
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
		InvoiceTable::update(
			$entityId,
			$this->prepareUpdateData(InvoiceTable::getTableName(), $map->getString())
		);
	}

	protected static function getStatusNameById($id): string
	{
		static $statuses = null;
		if ($statuses === null)
		{
			$statuses = [];
			$res = InvoiceStatus::getList();
			while ($status = $res->fetch())
			{
				$statuses[$status['STATUS_ID']] = $status['NAME'];
			}
		}

		return $statuses[$id] ?? '';
	}
}
