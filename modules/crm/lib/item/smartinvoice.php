<?php

namespace Bitrix\Crm\Item;

use Bitrix\Main\Localization\Loc;

/**
 * @method string|null getAccountNumber()
 * @method SmartInvoice setAccountNumber(string $accountNumber)
 */
class SmartInvoice extends Dynamic
{
	public const FIELD_NAME_ACCOUNT_NUMBER = 'ACCOUNT_NUMBER';

	public function getTitle(): ?string
	{
		// this is necessary to avoid \Bitrix\Crm\Model\Dynamic\Item::getTitle() behavior
		return $this->entityObject->sysGetValue(static::FIELD_NAME_TITLE);
	}

	public function getTitlePlaceholder(): ?string
	{
		return Loc::getMessage('CRM_SMART_INVOICE_TITLE', [
			'#NUMBER#' => $this->getAccountNumber(),
			'#BEGINDATE#' => $this->getBegindate() ?? '-',
		]);
	}
}
