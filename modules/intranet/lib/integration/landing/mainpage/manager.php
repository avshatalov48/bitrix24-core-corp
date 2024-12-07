<?php

namespace Bitrix\Intranet\Integration\Landing\MainPage;

use Bitrix\Intranet\MainPage\Url;
use Bitrix\Landing;
use Bitrix\Landing\Site;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;

class Manager
{
	private ?int $siteId;
	private ?int $pageId;
	private ?string $previewImg;
	private ?string $pageTitle;

	public function __construct()
	{
		$landingManager = new Landing\Mainpage\Manager();

		$this->siteId = $landingManager->getConnectedSiteId();
		$this->pageId = $landingManager->getConnectedPageId();
		$this->previewImg = $landingManager->getPreviewImg();
		$this->pageTitle = $landingManager->getPageTitle();
	}

	public const SEF_EDIT_URL_TEMPLATES = [
		'landing_edit' => '#site_show#/#landing_edit#/',
		'landing_view' => '#site_show#/view/#landing_edit#/',
		'site_edit' => '#site_edit#/',
		'site_show' => '#site_show#/',
	];

	public function getEditPath(): string
	{
		return (new Url)->getPublic()->getPath() . 'edit/';
	}

	public function isSiteExists(): bool
	{
		return (int)$this->siteId > 0;
	}

	public function isPageExists(): bool
	{
		return (int)$this->pageId > 0;
	}

	public function getEditUrl(): ?string
	{
		if (!Loader::includeModule('landing'))
		{
			return null;
		}

		return
			($this->siteId && $this->pageId)
				? $this->getEditPath() . str_replace(
					['#site_show#', '#landing_edit#'],
					[$this->siteId, $this->pageId],
					self::SEF_EDIT_URL_TEMPLATES['landing_view']
				)
				: null;
	}

	public function getImportUrl(): string
	{
		return Landing\Transfer\Import\Site::getUrl('MAINPAGE');
	}

	public function getExportUrl(): ?string
	{
		return
			$this->siteId
				? new Uri(Landing\Transfer\Export\Site::getUrl('MAINPAGE', $this->siteId))
				: null
			;
	}

	public function getPreviewImg(): ?string
	{
		return $this->previewImg;
	}

	public function getTitle(): ?string
	{
		return $this->pageTitle;
	}
}