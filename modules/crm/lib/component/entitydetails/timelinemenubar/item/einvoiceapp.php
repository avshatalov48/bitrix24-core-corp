<?php

namespace Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;

use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;
use Bitrix\Crm\Integration;

class EInvoiceApp extends Item
{
	use Integration\Rest\EInvoiceApp\Availability;

	public function isAvailable(): bool
	{
		return
			$this->getEntityTypeId() === \CCrmOwnerType::SmartInvoice
			&& $this->isEInvoiceAvailable()
			&& !$this->isHasInstalledApps()
			&& \CRestUtil::canInstallApplication()
		;
	}

	public function getId(): string
	{
		return 'einvoice_app_installer';
	}

	public function getName(): string
	{
		return Integration\Rest\EInvoiceApp\ToolbarSettings::getEInvoiceTitle();
	}

	public function prepareSettings(): array
	{
		$einvoiceInstallerSlider = new Integration\Rest\EInvoiceApp\InstallerSlider();

		return [
			'einvoiceUrl' => $einvoiceInstallerSlider->getEinvoiceUrl(),
		];
	}
}
