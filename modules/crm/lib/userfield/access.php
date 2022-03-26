<?php

namespace Bitrix\Crm\UserField;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory\SmartInvoice;
use Bitrix\Crm\Settings\InvoiceSettings;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\UserField\UserFieldAccess;
use Bitrix\Main\UserFieldTable;

class Access extends UserFieldAccess
{
	protected function getAvailableEntityIds(): array
	{
		if (!Container::getInstance()->getUserPermissions()->canWriteConfig())
		{
			return [];
		}

		$entityIds = [
			\CCrmLead::GetUserFieldEntityID(),
			\CCrmDeal::GetUserFieldEntityID(),
			\CCrmContact::GetUserFieldEntityID(),
			\CCrmCompany::GetUserFieldEntityID(),
			\CCrmQuote::GetUserFieldEntityID(),
			\CCrmInvoice::GetUserFieldEntityID(),
		];

		if (InvoiceSettings::getCurrent()->isSmartInvoiceEnabled())
		{
			$entityIds[] = SmartInvoice::USER_FIELD_ENTITY_ID;
		}

		$dynamicTypes = Container::getInstance()->getDynamicTypesMap()->getTypesCollection();
		foreach ($dynamicTypes as $type)
		{
			$entityIds[] = ServiceLocator::getInstance()->get('crm.type.factory')->getUserFieldEntityId($type->getId());
		}

		return $entityIds;
	}

	protected function getImmutableFieldNames(): array
	{
		return [
			\CCrmInvoice::GetUserFieldEntityID() => \CCrmInvoice::GetUserFieldsReserved(),
			\CCrmCompany::GetUserFieldEntityID() => array_keys(\CCrmCompany::getMyCompanyAdditionalUserFields()),
		];
	}

	protected function isFieldImmutable(array $field): bool
	{
		$entityId = $field['ENTITY_ID'] ?? '';
		$immutableFields = $this->getImmutableFieldNames();
		if (isset($immutableFields[$entityId]))
		{
			$fieldName = $field['FIELD_NAME'] ?? '';
			return in_array($fieldName, $immutableFields[$entityId], true);
		}

		return false;
	}

	public function canAdd(array $field): bool
	{
		return parent::canAdd($field) && !$this->isFieldImmutable($field);
	}

	public function canUpdate(int $id): bool
	{
		$field = UserFieldTable::getById($id)->fetch();

		return parent::canUpdate($id) && $field && !$this->isFieldImmutable($field);
	}

	public function canDelete(int $id): bool
	{
		$field = UserFieldTable::getById($id)->fetch();

		return parent::canDelete($id) && $field && !$this->isFieldImmutable($field);
	}

	public function getRestrictedTypes(): array
	{
		return array_merge(parent::getRestrictedTypes(), [
			'video',
			'vote',
			'url_preview',
			'string_formatted',
			'disk_file',
			'disk_version',
		]);
	}
}