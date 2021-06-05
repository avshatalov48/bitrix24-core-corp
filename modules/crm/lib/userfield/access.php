<?php

namespace Bitrix\Crm\UserField;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\UserField\UserFieldAccess;

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

		$dynamicTypes = Container::getInstance()->getDynamicTypesMap()->load([
			'isLoadStages' => false,
			'isLoadCategories' => false,
		])->getTypes();
		foreach ($dynamicTypes as $type)
		{
			$entityIds[] = ServiceLocator::getInstance()->get('crm.type.factory')->getUserFieldEntityId($type->getId());
		}

		return $entityIds;
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