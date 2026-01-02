<?php

namespace Bitrix\Intranet\License\Widget\Content;

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;

class Partner extends BaseContent
{
	private int $partnerId;

	public function __construct()
	{
		$this->partnerId = Application::getInstance()->getLicense()->getPartnerId();
	}

	public function getName(): string
	{
		return 'partner';
	}

	public function getConfiguration(): array
	{
		if ($this->partnerId > 0)
		{
			return [
				'isAvailable' => false,
			];
		}

		return [
			'isAvailable' => true,
			'title' => $this->getTitle(),
			'landingCode' => 'info_implementation_request',
		];
	}

	private function getTitle(): string
	{
		return Loc::getMessage('INTRANET_LICENSE_WIDGET_CONTENT_WITHOUT_PARTNER_TEXT') ?? '';
	}
}
