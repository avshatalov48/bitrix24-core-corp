<?php

namespace Bitrix\Intranet\Integration\Landing\MainPage;

use Bitrix\Intranet\MainPage\Url;
use Bitrix\Landing;
use Bitrix\Landing\Site;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;

class Manager
{
	private bool $isLandingIncluded = false;
	private ?int $siteId;
	private ?int $pageId;
	private ?string $previewImg;
	private ?string $pageTitle;

	public function __construct()
	{
		if (Loader::includeModule('landing'))
		{
			$this->isLandingIncluded = true;
			$landingManager = new Landing\Mainpage\Manager();

			$this->siteId = $landingManager->getConnectedSiteId();
			$this->pageId = $landingManager->getConnectedPageId();
			$this->previewImg = $landingManager->getPreviewImg();
			$this->pageTitle = $landingManager->getPageTitle();
		}
	}

	public const SEF_EDIT_URL_TEMPLATES = [
		'landing_edit' => '#site_show#/#landing_edit#/',
		'landing_view' => '#site_show#/view/#landing_edit#/',
		'landing_settings' => '#site_show#/settings/#landing_edit#/',
		'site_edit' => '#site_edit#/',
		'site_show' => '#site_show#/',
	];

	public function getEditPath(): string
	{
		return (new Url)->getPublic()->getPath() . 'edit/';
	}

	public function isSiteExists(): bool
	{
		return isset($this->siteId) && $this->siteId > 0;
	}

	public function isPageExists(): bool
	{
		return isset($this->pageId) && $this->pageId > 0;
	}

	public function getEditUrl(): ?string
	{
		return
			$this->isSiteExists() && $this->isPageExists()
				? $this->getEditPath() . str_replace(
					['#site_show#', '#landing_edit#'],
					[$this->siteId, $this->pageId],
					self::SEF_EDIT_URL_TEMPLATES['landing_view']
				)
				: null
			;
	}

	public function getImportUrl(): string
	{
		return
			$this->isLandingIncluded
				? Landing\Transfer\Import\Site::getUrl('MAINPAGE')
				: ''
			;
	}

	public function getExportUrl(): string
	{
		return
			$this->isSiteExists()
				? new Uri(Landing\Transfer\Export\Site::getUrl('MAINPAGE', $this->siteId))
				: ''
			;
	}

	public function getPreviewImg(): ?string
	{
		return $this->previewImg ?? null;
	}

	public function getTitle(): ?string
	{
		return $this->pageTitle ?? null;
	}
}