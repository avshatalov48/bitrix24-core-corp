<?php


namespace Bitrix\Market\ListTemplates;


use Bitrix\Main\Localization\Loc;
use Bitrix\Market\Categories;
use Bitrix\Market\NumberApps;
use Bitrix\Rest\Marketplace\Transport;

Loc::loadMessages(__DIR__.'/../../install/components/bitrix/market.main/class.php');

class Category extends BaseTemplate
{
	private string $categoryCode;

	public function setCategoryCode(string $categoryCode): void
	{
		$this->categoryCode = $categoryCode;
	}

	public function setResult(bool $isAjax = false)
	{
		$title = Loc::getMessage('MARKET_MAIN_PAGE_TITLE');

		$params = [
			'category' => $this->categoryCode,
			'_market_' => 'Y',
			'page' => $this->page,
		];
		if (!empty($this->filter['tag'])) {
			$params['category'] = $this->filter['tag'];
		} else if (!empty($this->filter['categoryTag'])) {
			$params['filter_tag'] = $this->filter['categoryTag'];
		}
		if (!empty($this->order)) {
			$params['custom_sort'] = $this->order;
		}

		$batch = [
			Transport::METHOD_FILTER_APP => [
				Transport::METHOD_FILTER_APP,
				$params,
			],
			Transport::METHOD_TOTAL_APPS => [
				Transport::METHOD_TOTAL_APPS,
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
				$title = $response[Transport::METHOD_FILTER_APP]['CATEGORY_NAME'];
			}

			if (isset($response[Transport::METHOD_FILTER_APP]['SUB_CATEGORIES']) && is_array($response[Transport::METHOD_FILTER_APP]['SUB_CATEGORIES'])) {
				$this->result['FILTER_TAGS'] = $this->prepareFilterTagsByCategory($response[Transport::METHOD_FILTER_APP]['SUB_CATEGORIES']);
			}

			if (isset($response[Transport::METHOD_FILTER_APP]['DEVELOPER_TAGS']) && !empty($response[Transport::METHOD_FILTER_APP]['DEVELOPER_TAGS'])) {
				$this->result['CATEGORY_TAGS'] = 'Y';
				$this->result['FILTER_TAGS'] = $this->prepareFilterTagsByTags($response[Transport::METHOD_FILTER_APP]['DEVELOPER_TAGS']);
			}

			if (isset($response[Transport::METHOD_FILTER_APP]['SORT_INFO']) && is_array($response[Transport::METHOD_FILTER_APP]['SORT_INFO'])) {
				$this->result['SORT_INFO'] = $response[Transport::METHOD_FILTER_APP]['SORT_INFO'];
				$this->result['SHOW_SORT_MENU'] = 'Y';
			}
		}

		if (!empty($response[Transport::METHOD_GET_CATEGORIES_V2])) {
			Categories::saveCache($response[Transport::METHOD_GET_CATEGORIES_V2]);
			$this->result['CATEGORIES'] = Categories::get();
		}

		$this->result['TITLE'] = $title;
		$this->result['CURRENT_APPS_CNT'] = $this->getAppsCount();
		$this->result['TOTAL_APPS'] = NumberApps::get($response[Transport::METHOD_TOTAL_APPS]);

		global $APPLICATION;
		$APPLICATION->SetTitle($title);
	}

	private function prepareFilterTagsByCategory(array $subCategories): array
	{
		$result = [];

		foreach ($subCategories as $subCategory) {
			$result[] = [
				'name' => $subCategory['NAME'],
				'value' => $subCategory['CODE'],
			];
		}

		return $result;
	}

	private function prepareFilterTagsByTags(array $developerTags): array
	{
		$result = [];

		foreach ($developerTags as $developerTag) {
			$result[] = [
				'name' => "{$developerTag['name']}",
				'value' => $developerTag['name'],
			];
		}

		return $result;
	}

	private function getAppsCount(): int
	{
		if (!isset(Categories::get()['ITEMS'])) {
			return 0;
		}

		foreach (Categories::get()['ITEMS'] as $item) {
			if ($item['CODE'] === $this->categoryCode) {
				return (int)$item['CNT'];
			}
		}

		return 0;
	}
}