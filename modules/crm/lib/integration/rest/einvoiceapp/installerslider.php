<?php

namespace Bitrix\Crm\Integration\Rest\EInvoiceApp;

use Bitrix\Main\Application;
use Bitrix\Main\UI\Extension;
use CUserOptions;

final class InstallerSlider
{
	use Availability;

	private const MODULE_ID = 'crm';
	private const USER_SEEN_COUNT_OPTION = 'einvoice_installer_slider_user_seen_count';
	private const USER_SEEN_COUNT_OPTION_VALUE_NAME = 'count';
	private const MAX_USER_SEEN_COUNT = 3;

	public function build(): string
	{
		if (!$this->canShow())
		{
			return '';
		}

		$this->incrementUserSeenCount();

		return "
			<script>
				BX.ready(() => {
					{$this->buildSlider()}
				});
			</script>
		";
	}

	public function buildSlider(): ?string
	{
		if (!$this->isEInvoiceAvailable())
		{
			return null;
		}

		Extension::load('sidepanel');

		/** @lang JavaScript */
		return "
			BX.SidePanel.Instance.open(
				'{$this->getEinvoiceUrl()}',
				{
					width: 575,
					allowChangeHistory: false
				},
			);
		";
	}

	private function canShow(): bool
	{
		return
			$this->isEInvoiceAvailable()
			&& !$this->isHasInstalledApps()
			&& \CRestUtil::canInstallApplication()
			&& $this->getUserSeenCount() <= self::MAX_USER_SEEN_COUNT
		;
	}

	private function getUserSeenCount(): int
	{
		$userSeenCountOption = CUserOptions::GetOption(
			self::MODULE_ID,
			self::USER_SEEN_COUNT_OPTION,
			[self::USER_SEEN_COUNT_OPTION_VALUE_NAME => null],
		);

		$userSeenCount = $userSeenCountOption[self::USER_SEEN_COUNT_OPTION_VALUE_NAME] ?? 0;

		return (int)$userSeenCount;
	}

	private function incrementUserSeenCount(): bool
	{
		return CUserOptions::SetOption(
			self::MODULE_ID,
			self::USER_SEEN_COUNT_OPTION,
			[
				self::USER_SEEN_COUNT_OPTION_VALUE_NAME => $this->getUserSeenCount() + 1,
			],
		);
	}

	public function getEinvoiceUrl(): string
	{
		$newEinvoicePath = '/marketplace/einvoice/index.php';
		if (file_exists(Application::getDocumentRoot() . $newEinvoicePath))
		{
			return '/marketplace/einvoice/';
		}

		return '/einvoice/install/';
	}
}
