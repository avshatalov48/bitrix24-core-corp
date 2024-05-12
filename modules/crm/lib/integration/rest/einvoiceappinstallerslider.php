<?php

namespace Bitrix\Crm\Integration\Rest;

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\UI\Extension;
use Bitrix\Rest;
use CRestUtil;
use CUserOptions;

final class EInvoiceAppInstallerSlider
{
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

		$moduleId = 'crm';
		$optionName = self::USER_SEEN_COUNT_OPTION;
		$optionValName = self::USER_SEEN_COUNT_OPTION_VALUE_NAME;

		$incrementedUserSeenCount = $this->getUserSeenCount() + 1;

		Extension::load('sidepanel');

		return "
			<script>
				BX.ready(() => {
					const onLoad = () => {
						BX.userOptions.save('{$moduleId}', '{$optionName}', '{$optionValName}', {$incrementedUserSeenCount});
					};

					BX.SidePanel.Instance.open(
						'{$this->getEinvoiceUrl()}',
						{
							width: 575, 
							allowChangeHistory: false,
							events: {
								onLoad,
							},
						},
					);
				});
			</script>
		";
	}

	private function canShow(): bool
	{
		if (!$this->isAvailable())
		{
			return false;
		}

		if ($this->isInstalled())
		{
			return false;
		}

		return $this->getUserSeenCount() <= self::MAX_USER_SEEN_COUNT;
	}

	private function isRestEInvoiceExists(): bool
	{
		return
			Loader::includeModule('rest')
			&& class_exists(Rest\EInvoice::class)
		;
	}

	private function isAvailable(): bool
	{
		return
			$this->isRestEInvoiceExists()
			&& Rest\EInvoice::isAvailable()
			&& !empty(Rest\EInvoice::getApplicationList())
			&& CRestUtil::canInstallApplication()
		;
	}

	private function isInstalled(): bool
	{
		return
			$this->isRestEInvoiceExists()
			&& !empty(Rest\EInvoice::getInstalledApplications())
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
