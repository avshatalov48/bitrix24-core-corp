<?php

namespace Bitrix\Crm\Automation\Trigger\Sign\B2e;

use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Sign\Config\Storage;
use Bitrix\Crm\Automation;

class AbstractB2eDocumentTrigger extends Automation\Trigger\BaseTrigger
{
	public static function isEnabled(): bool
	{
		return Loader::includeModule('sign')
			&& method_exists(Storage::instance(), 'isB2eAvailable')
			&& Storage::instance()->isB2eAvailable();
	}

	public static function isSupported($entityTypeId): bool
	{
		return $entityTypeId === \CCrmOwnerType::SmartB2eDocument;
	}

	public static function executeBySmartDocumentId(
		int $smartDocumentId,
		array $inputData = null
	): Result
	{
		$bindings = [
			[
				'OWNER_ID' => $smartDocumentId,
				'OWNER_TYPE_ID' => \CCrmOwnerType::SmartB2eDocument,
			]
		];
		return static::execute($bindings, $inputData);
	}

	public static function getGroup(): array
	{
		return ['paperwork'];
	}

	public static function toArray(): array
	{
		$result = parent::toArray();
		if (
			static::isEnabled()
			&& Loader::includeModule('bitrix24')
			&& !\Bitrix\Bitrix24\Feature::isFeatureEnabled('sign_b2e')
		)
		{
			$result['LOCKED'] = [
				'INFO_CODE' => 'limit_office_e_signature',
			];
		}

		return $result;
	}
}
