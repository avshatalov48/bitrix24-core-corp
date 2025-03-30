<?php

namespace Bitrix\Crm\Feature;

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Settings\InvoiceSettings;
use Bitrix\Crm\Feature\Category\BaseCategory;
use Bitrix\Crm\Feature\Category\Legacy;


class OldInvoice extends BaseFeature
{
	public function getName(): string
	{
		return Loc::getMessage('OLD_INVOICE_FEATURE_NAME');
	}

	public function getCategory(): BaseCategory
	{
		return Legacy::getInstance();
	}

	public function isEnabled(): bool
	{
		return InvoiceSettings::getCurrent()->isOldInvoicesEnabled();
	}

	public function enable(): void
	{
		InvoiceSettings::getCurrent()->setOldInvoicesEnabled(true);
		$this->logEnabled();
	}

	public function disable(): void
	{
		InvoiceSettings::getCurrent()->setOldInvoicesEnabled(false);
		$this->logDisabled();
	}

	public function getSort(): int
	{
		return 4;
	}
}
