<?php

namespace Bitrix\Intranet\Site\FirstPage;

use Bitrix\Intranet\MainPage\Access;
use Bitrix\Intranet\MainPage\Publisher;
use Bitrix\Intranet\MainPage\Url;
use Bitrix\Intranet\PortalSettings;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

// vibe page from landing
class MainFirstPage implements FirstPage
{
	public function getName(): string
	{
		return Loc::getMessage('INTRANET_MAIN_PAGE_NAME') ?? '';
	}

	public function getLink(): string
	{
		return (new Url)->getPublic();
	}

	public function getMenuId(): string
	{
		return 'main_page';
	}

	public function isAvailable(): bool
	{
		return (new Access)->canView();
	}

	public function isEnabled(): bool
	{
		return $this->isAvailable() && (new Publisher)->isPublished();
	}

	public function getSettingsPath(): string
	{
		$settingsUrl = PortalSettings::getInstance()->getSettingsUrl();
		if (str_starts_with($settingsUrl, '/'))
		{
			$settingsUrl = substr($settingsUrl, 1);
		}

		return SITE_DIR . "{$settingsUrl}?page=mainpage";
	}

	public function getUri(): Uri
	{
		return (new Url)->getPublic();
	}
}