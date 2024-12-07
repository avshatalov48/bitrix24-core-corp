<?php

namespace Bitrix\Crm\Integration\Rest\EInvoiceApp;

use Bitrix\Main\Loader;
use Bitrix\Rest;

trait Availability
{
	protected function isEInvoiceAvailable(): bool
	{
		return
			$this->isEInvoiceRestExists()
			&& Rest\EInvoice::isAvailable()
			&& !empty(Rest\EInvoice::getApplicationList())
		;
	}

	protected function isEInvoiceRestExists(): bool
	{
		return
			Loader::includeModule('rest')
			&& class_exists(Rest\EInvoice::class)
		;
	}

	protected function isHasInstalledApps(): bool
	{
		return
			$this->isEInvoiceRestExists()
			&& !empty(Rest\EInvoice::getInstalledApplications())
		;
	}
}
