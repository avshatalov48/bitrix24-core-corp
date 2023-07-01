<?php


namespace Bitrix\Market\ListTemplates;


use Bitrix\Main\Localization\Loc;
use Bitrix\Market\Categories;
use Bitrix\Rest\Marketplace\Transport;

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

		$params = [
			'_market_' => 'Y',
			'show_categories' => 'Y',
			'page' => $this->page,
		];
		if (!empty($this->searchText)) {
			$params['q'] = $this->searchText;
		}
		if (!empty($this->order)) {
			$params['custom_sort'] = $this->order;
		}

		$batch = [
			Transport::METHOD_FILTER_APP => [
				Transport::METHOD_FILTER_APP,
				$params,
			],
		];
		if (!$isAjax && empty(Categories::get())) {
			$batch[Transport::METHOD_GET_CATEGORIES_V2] = [Transport::METHOD_GET_CATEGORIES_V2];
		}

		$response = Transport::instance()->batch($batch);

		$this->result['APPS'] = [];

		if (isset($response[Transport::METHOD_FILTER_APP])) {
			if (isset($response[Transport::METHOD_FILTER_APP]['ITEMS']) && is_array($response[Transport::METHOD_FILTER_APP]['ITEMS'])) {
				$this->result['APPS'] = $response[Transport::METHOD_FILTER_APP]['ITEMS'];
				$this->result['PAGES'] = $response[Transport::METHOD_FILTER_APP]['PAGES'];
				$this->result['CUR_PAGE'] = $response[Transport::METHOD_FILTER_APP]['CUR_PAGE'];
			}

			if (isset($response[Transport::METHOD_FILTER_APP]['SORT_INFO']) && is_array($response[Transport::METHOD_FILTER_APP]['SORT_INFO'])) {
				$this->result['SORT_INFO'] = $response[Transport::METHOD_FILTER_APP]['SORT_INFO'];
				$this->result['SHOW_SORT_MENU'] = 'Y';
				$this->result['FILTER_TAGS'] = [];
			}
		}

		if (!empty($response[Transport::METHOD_GET_CATEGORIES_V2])) {
			Categories::saveCache($response[Transport::METHOD_GET_CATEGORIES_V2]);
			$this->result['CATEGORIES'] = Categories::get();
		}
	}
}