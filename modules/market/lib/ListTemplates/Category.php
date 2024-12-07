<?php


namespace Bitrix\Market\ListTemplates;


use Bitrix\Main\Localization\Loc;
use Bitrix\Market\Categories;
use Bitrix\Market\Rest\Actions;
use Bitrix\Market\Toolbar;
use Bitrix\Market\Rest\Transport;

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
			Actions::METHOD_GET_FULL_CATEGORY => [
				Actions::METHOD_GET_FULL_CATEGORY,
				$params,
			],
			Actions::METHOD_TOTAL_APPS => [
				Actions::METHOD_TOTAL_APPS,
			],
		];
		if (!$isAjax && empty(Categories::get())) {
			$batch[Actions::METHOD_GET_CATEGORIES_V2] = [Actions::METHOD_GET_CATEGORIES_V2];
		}

		$response = Transport::instance()->batch($batch);

		$this->result['APPS'] = [];
		if (isset($response[Actions::METHOD_GET_FULL_CATEGORY])) {
			if (isset($response[Actions::METHOD_GET_FULL_CATEGORY]['ITEMS']) && is_array($response[Actions::METHOD_GET_FULL_CATEGORY]['ITEMS'])) {
				$this->result['APPS'] = $response[Actions::METHOD_GET_FULL_CATEGORY]['ITEMS'];
				$this->result['PAGES'] = $response[Actions::METHOD_GET_FULL_CATEGORY]['PAGES'];
				$this->result['CUR_PAGE'] = $response[Actions::METHOD_GET_FULL_CATEGORY]['CUR_PAGE'];
				$title = $response[Actions::METHOD_GET_FULL_CATEGORY]['CATEGORY_NAME'];
			}

			if (isset($response[Actions::METHOD_GET_FULL_CATEGORY]['SUB_CATEGORIES']) && is_array($response[Actions::METHOD_GET_FULL_CATEGORY]['SUB_CATEGORIES'])) {
				$this->result['FILTER_CATEGORIES'] = $this->prepareFilterTagsByCategory($response[Actions::METHOD_GET_FULL_CATEGORY]['SUB_CATEGORIES']);
			}

			if (isset($response[Actions::METHOD_GET_FULL_CATEGORY]['DEVELOPER_TAGS']) && !empty($response[Actions::METHOD_GET_FULL_CATEGORY]['DEVELOPER_TAGS'])) {
				$this->result['CATEGORY_TAGS'] = 'Y';
				$this->result['FILTER_TAGS'] = $this->prepareFilterTagsByTags($response[Actions::METHOD_GET_FULL_CATEGORY]['DEVELOPER_TAGS']);
			}

			if (isset($response[Actions::METHOD_GET_FULL_CATEGORY]['SORT_INFO']) && is_array($response[Actions::METHOD_GET_FULL_CATEGORY]['SORT_INFO'])) {
				$this->result['SORT_INFO'] = $response[Actions::METHOD_GET_FULL_CATEGORY]['SORT_INFO'];
				$this->result['SHOW_SORT_MENU'] = 'Y';
			}
		}

		if (!empty($response[Actions::METHOD_GET_CATEGORIES_V2])) {
			Categories::saveCache($response[Actions::METHOD_GET_CATEGORIES_V2]);
			$this->result['CATEGORIES'] = Categories::get();
		}

		$this->result['TITLE'] = $title;
		$this->result['CURRENT_APPS_CNT'] = $this->getAppsCount();

		if (is_array($response[Actions::METHOD_TOTAL_APPS])) {
			$this->result = array_merge($this->result, Toolbar::getTotalAppsInfo($response[Actions::METHOD_TOTAL_APPS]));
		}

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