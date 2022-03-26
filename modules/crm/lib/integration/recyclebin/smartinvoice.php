<?php

namespace Bitrix\Crm\Integration\Recyclebin;

use Bitrix\Main\Localization\Loc;

class SmartInvoice extends Dynamic
{
	public static function prepareSurveyInfo(): array
	{
		return [
			static::getEntityName(\CCrmOwnerType::SmartInvoice) => [
				'NAME' => \CCrmOwnerType::GetDescription(\CCrmOwnerType::SmartInvoice),
				'HANDLER' => __CLASS__,
			],
		];
	}

	public static function getNotifyMessages(): array
	{
		return [
			'NOTIFY' => [
				'RESTORE' => Loc::getMessage('CRM_RECYCLE_BIN_SMART_INVOICE_RESTORED'),
				'REMOVE' => Loc::getMessage('CRM_RECYCLE_BIN_SMART_INVOICE_REMOVED')
			],
			'CONFIRM' => [
				'RESTORE' => Loc::getMessage('CRM_RECYCLE_BIN_SMART_INVOICE_RECOVERY_CONFIRMATION'),
				'REMOVE' => Loc::getMessage('CRM_RECYCLE_BIN_SMART_INVOICE_REMOVAL_CONFIRMATION')
			]
		];
	}
}
