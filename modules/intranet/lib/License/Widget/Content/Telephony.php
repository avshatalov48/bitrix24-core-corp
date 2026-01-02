<?php

namespace Bitrix\Intranet\License\Widget\Content;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class Telephony extends BaseContent
{
	public function getName(): string
	{
		return 'telephony';
	}

	public function getConfiguration(): array
	{
		return [
			'isAvailable' => $this->isAvailable(),
			'isActive' => $this->isActive(),
			'link' => $this->getLink(),
			'title' => $this->getTitle(),
		];
	}

	private function isActive(): bool
	{
		return $this->isAvailable()
			&& (\CVoxImplantPhone::getRentedNumbersCount() > 0
			|| \CVoxImplantSip::hasConnection()
			|| (new \CVoxImplantAccount())->getAccountBalance() > 0);
	}

	private function isAvailable(): bool
	{
		return Loader::includeModule('voximplant');
	}

	private function getLink(): string
	{
		return defined('SITE_DIR') ? SITE_DIR . 'telephony/' : '/telephony/';
	}

	private function getTitle(): string
	{
		return Loc::getMessage('INTRANET_LICENSE_WIDGET_CONTENT_TELEPHONY_TITLE') ?? '';
	}
}
