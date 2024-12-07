<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\Loader;
use Bitrix\Main\Security\Random;
use Bitrix\Main\Web\Uri;
use Bitrix\Market\Categories;
use Bitrix\Market\History;
use Bitrix\Market\Loadable;
use Bitrix\Market\PageRules;
use Bitrix\Market\PricePolicy;
use Bitrix\Market\Rest\Actions;
use Bitrix\Market\Rest\Transport;
use Bitrix\Market\Tag\TagTable;
use Bitrix\Market\AppFavoritesTable;
use Bitrix\Market\Toolbar;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

Loader::includeModule('market');

class MarketMain extends CBitrixComponent implements Controllerable, Loadable
{
	private string $pageTitle;

	private string $mainUri = '';

	private bool $fullLoad = true;

	public function executeComponent()
	{
		$this->prepareResult();

		$marketAction = $this->arResult['ADDITIONAL_MARKET_ACTION'] ?? '';
		$searchAction = $this->arResult['ADDITIONAL_SEARCH_ACTION'] ?? '';
		$this->arResult = array_merge($this->arResult, Toolbar::getInfo($marketAction, $searchAction));

		$this->includeComponentTemplate();

		global $APPLICATION;
		$APPLICATION->SetTitle($this->pageTitle);
	}

	private function prepareResult()
	{
		$this->prepareParams();
		$this->pageTitle = Loc::getMessage('MARKET_MAIN_PAGE_TITLE');
		$this->arResult['TITLE'] = $this->pageTitle;
		$this->arResult['COLLECTIONS'] = [];
		$this->arResult['SLIDER'] = [];

		$batch = $this->getCollectionBatch($this->arParams['PLACEMENT'], $this->arParams['TAGS']);

		if ($this->fullLoad) {
			$this->arResult['MAIN_URI'] = $this->mainUri;

			$this->arResult['CATEGORIES'] = Categories::get();
			if (empty($this->arResult['CATEGORIES'])) {
				$batch[Actions::METHOD_GET_CATEGORIES_V2] = [Actions::METHOD_GET_CATEGORIES_V2];
			}

			$batch[Actions::METHOD_TOTAL_APPS] = [
				Actions::METHOD_TOTAL_APPS,
				[
					'placement' => $this->request->get('placement'),
				]
			];
		}

		if (empty($this->arParams['PLACEMENT']) && empty($this->arParams['TAGS'])) {
			$batch[Actions::METHOD_GET_SLIDER] = [Actions::METHOD_GET_SLIDER];
		}

		$response = Transport::instance()->batch($batch);
		$this->prepareResponse($response);
	}

	private function getCollectionBatch($placement, $tags, $page = 1): array
	{
		return [
			Actions::METHOD_GET_COLLECTIONS => [
				Actions::METHOD_GET_COLLECTIONS,
				[
					'collection_params' => TagTable::getAll(),
					'placement' => (empty($placement)) ? TagTable::MARKET_INDEX_TAG : $placement,
					'placement_tags' => $tags,
					'collection_page' => $page,
				],
			],
		];
	}

	private function prepareResponse($response)
	{
		if (
			isset($response[Actions::METHOD_GET_COLLECTIONS]) &&
			!empty($response[Actions::METHOD_GET_COLLECTIONS]['ITEMS'])
		) {
			$this->arResult['COLLECTIONS'] = $this->prepareCollections($response[Actions::METHOD_GET_COLLECTIONS]['ITEMS']);
			$this->arResult['ENABLE_NEXT_PAGE'] = $response[Actions::METHOD_GET_COLLECTIONS]['ENABLE_NEXT_PAGE'];
			$this->arResult['ADDITIONAL_HIT_ACTION'] = $response[Actions::METHOD_GET_COLLECTIONS]['ADDITIONAL_HIT_ACTION'];
		}

		if (!empty($response[Actions::METHOD_GET_SLIDER])) {
			$this->arResult['SLIDER'] = $response[Actions::METHOD_GET_SLIDER];
			$this->prepareSliderLinks();
		}

		if (!empty($response[Actions::METHOD_GET_CATEGORIES_V2])) {
			Categories::saveCache($response[Actions::METHOD_GET_CATEGORIES_V2]);
			$this->arResult['CATEGORIES'] = Categories::get();
		}

		if (is_array($response[Actions::METHOD_TOTAL_APPS])) {
			$this->arResult = array_merge($this->arResult, Toolbar::getTotalAppsInfo($response[Actions::METHOD_TOTAL_APPS]));
		}
	}

	private function prepareSliderLinks()
	{
		if (!isset($this->arResult['SLIDER']) || !is_array($this->arResult['SLIDER'])) {
			return;
		}

		foreach ($this->arResult['SLIDER'] as &$item) {
			if (isset($item['LINK'])) {
				$uri = new Uri($item['LINK']);
				$uri->addParams([
					'from' => 'main_banner'
				]);
				$item['LINK'] = $uri->getUri();
			}
		}
	}

	private function prepareCollections(array $items): array
	{
		$favoriteApps = AppFavoritesTable::getUserFavorites();
		$backgroundIndex = 0;
		foreach ($items as &$item) {
			$item['APPS'] = $item['APPS'] ?? [];
			$item['CAROUSEL_ID'] = md5($item['NAME'] . "|" . $item['IMAGE']);

			if (!empty($item['APPS'])) {
				$item['NUMBER_SHOW_APPS'] = count($item['APPS']) > 1 ? count($item['APPS']) : 2;
				$item['STYLE_FOR_TOP'] = 100 / $item['NUMBER_SHOW_APPS'];
				$item['STYLE_FOR_TOP2'] = round(($item['NUMBER_SHOW_APPS'] / 2));
			}

			foreach ($item['APPS'] as &$app){
				$app['IS_FAVORITE'] = in_array($app['CODE'], $favoriteApps) ? 'Y' : 'N';

				$app['PRICE_POLICY'] = PricePolicy::getByApp($app);
				$app['PRICE_POLICY_NAME'] = PricePolicy::getName($app['PRICE_POLICY']);
				$app['PRICE_POLICY_BLUE'] = ($app['PRICE_POLICY'] == PricePolicy::SUBSCRIPTION);
			}
			unset($app);

			if (isset($item['ONE_ROW']) && $item['ONE_ROW'] == 'Y') {
				$item['INDEX'] = $backgroundIndex++;
			}
		}
		unset($item);

		return $items;
	}

	private function prepareParams(): void
	{
		$this->arParams['COMPONENT_NAME'] = 'bitrix:market.main';
		$this->arParams['SLIDER_ID'] = Random::getString(8);

		if ($this->fullLoad) {
			$this->arParams['HISTORY'] = History::getFirstPageInfo('main');
		}

		$this->mainUri = urldecode($this->request->getRequestUri());
		if (!empty($this->arParams['PLACEMENT'])) {
			$this->mainUri = PageRules::MAIN_PAGE . '?placement=' . $this->arParams['PLACEMENT'];
		}

		$requestPlacement = (string)$this->request->get('placement');
		if (!empty($requestPlacement)) {
			$this->arParams['PLACEMENT'] = $requestPlacement;
		}

		$this->arParams['TAGS'] = (array)$this->request->get('tag');
		$this->arParams['CATEGORY'] = (array)$this->request->get('category');
		if (isset($this->arParams['REQUEST'])) {
			$this->arParams['PLACEMENT'] = $this->arParams['REQUEST']['placement'] ?? $this->arParams['PLACEMENT'];
			$this->arParams['TAGS'] = $this->arParams['REQUEST']['tag'] ?? $this->arParams['TAGS'];
			$this->arParams['CATEGORY'] = $this->arParams['REQUEST']['category'] ?? $this->arParams['TAGS'];
		}
		if (!empty($this->arParams['CATEGORY']) && empty($this->arParams['PLACEMENT'])) {
			$this->arParams['PLACEMENT'] = $this->arParams['CATEGORY'];
		}
		if (isset($this->arParams['VARIABLES']) && isset($this->arParams['VARIABLES']['booklet_code'])) {
			$this->arParams['PLACEMENT'] = $this->arParams['VARIABLES']['booklet_code'];
		}
	}

	public function configureActions(): array
	{
		return [];
	}

	public function getMoreCollectionsAction($collectionPage, $placement = '', $tags = []): AjaxJson
	{
		$this->prepareParams();
		$batch = $this->getCollectionBatch($placement, $tags, $collectionPage);
		$response = Transport::instance()->batch($batch);
		$this->prepareResponse($response);

		return AjaxJson::createSuccess([
			'items' => $this->arResult['COLLECTIONS'],
			'existNextPage' => $this->arResult['ENABLE_NEXT_PAGE'],
		]);
	}

	public function getAjaxData($params): array
	{
		$this->arParams = array_merge($this->arParams, $params);

		$this->fullLoad = false;
		$this->prepareResult();

		return [
			'params' => $this->arParams,
			'result' => $this->arResult,
		];
	}
}