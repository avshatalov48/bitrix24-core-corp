<?php

namespace Bitrix\Intranet\License\Widget\Content;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;

class PurchaseHistory extends BaseContent
{
	public function getName(): string
	{
		return 'purchase-history';
	}

	public function getConfiguration(): array
	{
		return [
			'link' => (new Main\License\UrlProvider())->getPurchaseHistoryUrl(),
			'hashKey' => Application::getInstance()->getLicense()->getHashLicenseKey(),
			'text' => $this->getText(),
		];
	}

	private function getText(): string
	{
		return Loc::getMessage('INTRANET_LICENSE_WIDGET_CONTENT_PURCHASE_HISTORY_TEXT') ?? '';
	}
}
