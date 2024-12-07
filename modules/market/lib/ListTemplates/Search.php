<?php


namespace Bitrix\Market\ListTemplates;


use Bitrix\Main\Localization\Loc;
use Bitrix\Market\Categories;
use Bitrix\Market\Rest\Actions;
use Bitrix\Market\Rest\Transport;

class Search extends BaseTemplate
{
	private string $searchText;

	public function setSearchText(string $searchText): void
	{
		$this->searchText = $searchText;
	}

	public function setResult(bool $isAjax = false)
	{
		global $APPLICATION;
		$APPLICATION->SetTitle(Loc::getMessage('MARKET_SEARCH_PAGE_TITLE'));

		if (empty($this->searchText)) {
			return;
		}

		$params = [
			'q' => $this->searchText,
			'page' => $this->page,
			'show_categories' => 'Y',
		];
		if (!empty($this->filter)) {
			$params['filter'] = $this->filter;
		}
		if (!empty($this->order)) {
			$params['custom_sort'] = $this->order;
		}

		$batch = [
			Actions::METHOD_GET_SEARCH_ITEMS => [
				Actions::METHOD_GET_SEARCH_ITEMS,
				$params,
			],
		];
		if (!$isAjax && empty(Categories::get())) {
			$batch[Actions::METHOD_GET_CATEGORIES_V2] = [Actions::METHOD_GET_CATEGORIES_V2];
		}

		$response = Transport::instance()->batch($batch);

		$this->result['APPS'] = [];

		if (isset($response[Actions::METHOD_GET_SEARCH_ITEMS])) {
			if (isset($response[Actions::METHOD_GET_SEARCH_ITEMS]['ITEMS']) && is_array($response[Actions::METHOD_GET_SEARCH_ITEMS]['ITEMS'])) {
				$this->result['APPS'] = $response[Actions::METHOD_GET_SEARCH_ITEMS]['ITEMS'];
				$this->result['PAGES'] = $response[Actions::METHOD_GET_SEARCH_ITEMS]['PAGES'];
				$this->result['CUR_PAGE'] = $response[Actions::METHOD_GET_SEARCH_ITEMS]['CUR_PAGE'];
				$this->result['RESULT_COUNT'] = $response[Actions::METHOD_GET_SEARCH_ITEMS]['RESULT_COUNT'];
			}

			if (isset($response[Actions::METHOD_GET_SEARCH_ITEMS]['SORT_INFO']) && is_array($response[Actions::METHOD_GET_SEARCH_ITEMS]['SORT_INFO'])) {
				$this->result['SORT_INFO'] = $response[Actions::METHOD_GET_SEARCH_ITEMS]['SORT_INFO'];
				$this->result['SHOW_SORT_MENU'] = 'Y';
				$this->result['FILTER_TAGS'] = [];
			}
		}

		if (!empty($response[Actions::METHOD_GET_CATEGORIES_V2])) {
			Categories::saveCache($response[Actions::METHOD_GET_CATEGORIES_V2]);
			$this->result['CATEGORIES'] = Categories::get();
		}
	}
}