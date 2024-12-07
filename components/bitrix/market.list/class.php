<?php

use Bitrix\Main\Context;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectException;
use Bitrix\Main\Type\Date;
use Bitrix\Market\AppFavoritesTable;
use Bitrix\Market\History;
use Bitrix\Market\Link;
use Bitrix\Market\ListTemplates\BaseTemplate;
use Bitrix\Market\ListTemplates\Category;
use Bitrix\Market\ListTemplates\Collection;
use Bitrix\Market\ListTemplates\Favorites;
use Bitrix\Market\ListTemplates\Installed;
use Bitrix\Market\ListTemplates\Search;
use Bitrix\Market\Loadable;
use Bitrix\Market\PricePolicy;
use Bitrix\Market\Toolbar;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

Loader::includeModule('market');

class MarketList extends CBitrixComponent implements Controllerable, Loadable
{
	public function executeComponent()
	{
		$this->arParams['HISTORY'] = History::getFirstPageInfo('list');

		$this->prepareResult();

		$marketAction = $this->arResult['ADDITIONAL_MARKET_ACTION'] ?? '';
		$searchAction = $this->arResult['ADDITIONAL_SEARCH_ACTION'] ?? '';
		$this->arResult = array_merge($this->arResult, Toolbar::getInfo($marketAction, $searchAction));

		$this->includeComponentTemplate();
	}

	private function prepareResult()
	{
		$this->arParams['COMPONENT_NAME'] = 'bitrix:market.list';

		$this->arResult['APPS'] = [];
		$template = $this->getTemplateClass($this->arParams);
		if ($template instanceof BaseTemplate) {
			$template->setResult();
			$this->arResult = $template->getInfo();
			$this->prepareApps($this->arParams);
		}
	}

	public function getAjaxData($params): array
	{
		$this->arParams = array_merge($this->arParams, $params);

		$this->prepareResult();

		return [
			'params' => $this->arParams,
			'result' => $this->arResult,
		];
	}

	private function getTemplateClass($params): ?BaseTemplate
	{
		$template = null;

		$isCollectionId = isset($params['COLLECTION']) && (int)$params['COLLECTION'] > 0;
		$isCollectionCode = !empty($params['COLLECTION_CODE']);
		if (is_string($params['COLLECTION']) && preg_match("/^[a-z][a-z_0-9]+$/", $params['COLLECTION'])) {
			$isCollectionCode = true;
			$params['COLLECTION_CODE'] = $params['COLLECTION'];
		}

		$rp = $params['REQUEST'] ?? [];
		if (isset($params['IS_FAVORITES']) && $params['IS_FAVORITES'] == 'Y') {
			$template = new Favorites($rp);
		} else if (isset($params['IS_INSTALLED']) && $params['IS_INSTALLED'] == 'Y') {
			$template = new Installed($rp);
		} else if (isset($params['IS_SEARCH']) && $params['IS_SEARCH'] == 'Y') {
			$template = new Search($rp);
		} else if ($isCollectionId || $isCollectionCode) {
			$template = new Collection($rp);
			if ($isCollectionId) {
				$template->setCollectionId((int)$params['COLLECTION']);
			} else {
				$template->setCollectionCode($params['COLLECTION_CODE']);
			}
		} else if (isset($params['CATEGORY']) && !empty($params['CATEGORY'])) {
			$template = new Category($rp);
			$template->setCategoryCode($params['CATEGORY']);
		}

		return $template;
	}

	private function prepareApps($params)
	{
		if (!is_array($this->arResult['APPS'])) {
			return;
		}

		$favoriteApps = AppFavoritesTable::getUserFavorites();
		$culture = Context::getCurrent()->getCulture();

		foreach ($this->arResult['APPS'] as &$appItem) {
			$appItem['IS_FAVORITE'] = in_array($appItem['CODE'], $favoriteApps) ? 'Y' : 'N';

			$appItem['PRICE_POLICY'] = PricePolicy::getByApp($appItem);
			$appItem['PRICE_POLICY_NAME'] = PricePolicy::getName($appItem['PRICE_POLICY']);
			$appItem['PRICE_POLICY_BLUE'] = ($appItem['PRICE_POLICY'] == PricePolicy::SUBSCRIPTION);

			if (is_array($appItem['LABELS'])) {
				foreach ($appItem['LABELS'] as &$label) {
					if (isset($label['PREMIUM_UNTIL'])) {
						try {
							$date = new Date($label['PREMIUM_UNTIL'], 'd.m.Y');
							$label['PREMIUM_UNTIL_FORMAT'] = $date->format($culture->getShortDateFormat());
						} catch (ObjectException $e) {}
					}
				}
			}
		}
		unset($appItem);
	}

	public function configureActions(): array
	{
		return [];
	}

	public function filterAppsAction($params, $filter, $order, $page): AjaxJson
	{
		$template = $this->getTemplateClass($params);
		if ($template instanceof BaseTemplate) {
			$template->setFilter($filter);
			$template->setOrder($order);
			$template->setPage((int)$page);
			$template->setResult(true);
			$this->arResult = $template->getInfo();
			$this->prepareApps($params);

			return AjaxJson::createSuccess([
				'apps' => $this->arResult['APPS'],
				'pages' => $this->arResult['PAGES'],
				'cur_page' => $this->arResult['CUR_PAGE'],
				'apps_count' => (int)$this->arResult['CURRENT_APPS_CNT'],
			]);
		}

		return AjaxJson::createError();
	}
}