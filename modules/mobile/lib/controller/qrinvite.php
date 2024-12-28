<?php

namespace Bitrix\Mobile\Controller;

use Bitrix\Main\Error;
use Bitrix\Main\Engine\JsonController;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Mobile\Provider\QrGenerator;

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
}