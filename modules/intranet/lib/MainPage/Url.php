<?php

namespace Bitrix\Intranet\MainPage;

use Bitrix\Main\Web\Uri;
use Bitrix\Intranet\Binding\Marketplace;
use Bitrix\Intranet\Integration\Landing\MainPage;

class Url
{
	private const MAIN_PAGE_PUBLIC_PATH = '/vibe/';
	private const MAIN_PAGE_CREATE_PATH = '/vibe/new/';
	private const MAIN_PAGE_MARKET_CATEGORY_PATH = 'category/vibe/';


	public function getPublic(): Uri
	{
		return new Uri(self::MAIN_PAGE_PUBLIC_PATH);
	}

	public function getEdit(): Uri
	{
		return new Uri(($this->getIntegrationManager())->getEditUrl());
	}

	public function getCreate(): Uri
	{
		$createUri = new Uri(self::MAIN_PAGE_CREATE_PATH);

		$url = new Uri(Marketplace::getMainDirectory() . self::MAIN_PAGE_MARKET_CATEGORY_PATH);
		$url->addParams(['create_uri' => $createUri->getUri()]);

		return $url;
	}

	public function getPartners(): Uri
	{
		// todo: hardcode form url?
		return new Uri('/');
	}

	public function getImport(): Uri
	{
		return new Uri(($this->getIntegrationManager())->getImportUrl());
	}

	public function getExport(): Uri
	{
		return new Uri(($this->getIntegrationManager())->getExportUrl());
	}

	protected function getIntegrationManager(): MainPage\Manager
	{
		static $manager = null;
		if (!$manager)
		{
			$manager = new MainPage\Manager();
		}

		return $manager;
	}
}