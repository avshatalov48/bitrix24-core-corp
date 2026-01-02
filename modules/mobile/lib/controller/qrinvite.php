<?php

namespace Bitrix\Mobile\Controller;

use Bitrix\Intranet\Portal\PortalLogo;
use Bitrix\Main\Error;
use Bitrix\Main\Engine\JsonController;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Mobile\Provider\QrGenerator;
use Bitrix\Intranet;
use Bitrix\Bitrix24;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;

class QrInvite extends JsonController
{
	public function configureActions(): array
	{
		return [
			'generateQr' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	public function generateQrAction(string $url, bool $isDarkMode = false): ?string
	{
		if (empty($url))
		{
			return null;
		}

		$qrGenerator = new QrGenerator($url, $isDarkMode);
		$svgContent = $qrGenerator->getContent();

		if (!$svgContent)
		{
			$this->addError(new Error('QR Code generation failed'));
			return null;
		}

		return $svgContent;
	}

	public function getPortalSettingsAction(): array
	{
		$isBitrix24 = Loader::includeModule('bitrix24');

		$portalSettings = $isBitrix24
			? Bitrix24\Service\PortalSettings::getInstance()
			: Intranet\Service\PortalSettings::getInstance();

		$portalLogo = new PortalLogo($portalSettings);

		$companyName = Option::get('bitrix24', 'site_title', null, SITE_ID) ??
			Option::get('bitrix24', 'site_title', null) ?? '';
		;

		if (trim($companyName) === '')
		{
			$companyName = null;
		}

		return [
			'name' => $companyName,
			'logo' => $portalLogo->getLogo(),
		];
	}
}