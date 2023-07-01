<?php


namespace Bitrix\Market\ListTemplates;


use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Market\AppFavoritesTable;
use Bitrix\Market\Categories;
use Bitrix\Market\NumberApps;
use Bitrix\Rest\Marketplace\Transport;

class Favorites extends BaseTemplate
{
	public function setResult(bool $isAjax = false)
	{
		$title = Loc::getMessage('MARKET_FAVORITES_PAGE_TITLE');

		$this->result['TITLE'] = $title;

		global $APPLICATION;
		$APPLICATION->SetTitle($title);

		$this->result['CURRENT_APPS_CNT'] = count(AppFavoritesTable::getUserFavorites());

		$nav = new PageNavigation('market-favorites-nav');
		$nav->setPageSize(20)
			->setCurrentPage($this->page)
			->setRecordCount($this->result['CURRENT_APPS_CNT']);

		$this->result['CUR_PAGE'] = $nav->getCurrentPage();
		$this->result['PAGES'] = $nav->getPageCount();

		$this->result['ALL_CODES'] = AppFavoritesTable::getUserFavoritesForList($nav->getOffset(), $nav->getLimit());
		if (empty($this->result['ALL_CODES'])) {
			$this->result['APPS'] = [];
			return;
		}

		$batch = [
			Transport::METHOD_FILTER_APP => [
				Transport::METHOD_FILTER_APP,
				[
					'app_codes' => $this->result['ALL_CODES'],
					'_market_' => 'Y',
				],
			],
			Transport::METHOD_TOTAL_APPS => [
				Transport::METHOD_TOTAL_APPS,
			],
		];
		if (!$isAjax && empty(Categories::get())) {
			$batch[Transport::METHOD_GET_CATEGORIES_V2] = [Transport::METHOD_GET_CATEGORIES_V2];
		}

		$response = Transport::instance()->batch($batch);
		if (
			isset($response[Transport::METHOD_FILTER_APP]) &&
			isset($response[Transport::METHOD_FILTER_APP]['ITEMS']) &&
			is_array($response[Transport::METHOD_FILTER_APP]['ITEMS'])
		) {
			$this->prepareApps($response[Transport::METHOD_FILTER_APP]['ITEMS']);
		}

		if (!empty($response[Transport::METHOD_GET_CATEGORIES_V2])) {
			Categories::saveCache($response[Transport::METHOD_GET_CATEGORIES_V2]);
			$this->result['CATEGORIES'] = Categories::get();
		}

		$this->result['TOTAL_APPS'] = NumberApps::get($response[Transport::METHOD_TOTAL_APPS]);
	}

	private function prepareApps($apps)
	{
		$publishedApps = [];
		foreach ($apps as $app) {
			$publishedApps[$app['CODE']] = $app;
		}

		foreach ($this->result['ALL_CODES'] as $appCode) {
			if (empty($publishedApps[$appCode])) {
				$this->result['APPS'][] = [
					'CODE' => $appCode,
					'UNPUBLISHED' => 'Y',
					'IS_FAVORITE' => 'Y',
				];
			} else {
				$this->result['APPS'][] = $publishedApps[$appCode];
			}
		}
	}
}