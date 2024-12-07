<?php


namespace Bitrix\Market\ListTemplates;


use Bitrix\Main\Localization\Loc;
use Bitrix\Market\Categories;
use Bitrix\Market\Rest\Actions;
use Bitrix\Market\Toolbar;
use Bitrix\Market\Rest\Transport;

Loc::loadMessages(__DIR__.'/../../install/components/bitrix/market.main/class.php');

class Collection extends BaseTemplate
{
	private int $collectionId = 0;

	private string $collectionCode;

	public function setCollectionId(int $collectionId): void
	{
		$this->collectionId = $collectionId;
	}

	public function setCollectionCode(string $collectionCode): void
	{
		$this->collectionCode = $collectionCode;
	}

	public function setResult(bool $isAjax = false)
	{
		$title = Loc::getMessage('MARKET_MAIN_PAGE_TITLE');

		$params = [
			'collection_id' => $this->collectionId,
			'page' => $this->page,
		];
		if (!empty($this->filter['tag'])) {
			$params['filter_tag'] = $this->filter['tag'];
		}
		if (!empty($this->order)) {
			$params['custom_sort'] = $this->order;
		}
		if (!empty($this->collectionCode)) {
			$params['collection_code'] = $this->collectionCode;
		}

		$batch = [
			Actions::METHOD_GET_FULL_COLLECTION => [
				Actions::METHOD_GET_FULL_COLLECTION,
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

		if (isset($response[Actions::METHOD_GET_FULL_COLLECTION])) {
			if (isset($response[Actions::METHOD_GET_FULL_COLLECTION]['APPS']) && is_array($response[Actions::METHOD_GET_FULL_COLLECTION]['APPS'])) {
				$this->result['APPS'] = $response[Actions::METHOD_GET_FULL_COLLECTION]['APPS'];
				$this->result['PAGES'] = $response[Actions::METHOD_GET_FULL_COLLECTION]['PAGES'];
				$this->result['CUR_PAGE'] = $response[Actions::METHOD_GET_FULL_COLLECTION]['CUR_PAGE'];
				$this->result['CURRENT_APPS_CNT'] = $response[Actions::METHOD_GET_FULL_COLLECTION]['NUMBER_APPS'];
				$title = $response[Actions::METHOD_GET_FULL_COLLECTION]['NAME'];
			}

			if (isset($response[Actions::METHOD_GET_FULL_COLLECTION]['DEVELOPER_TAGS']) && !empty($response[Actions::METHOD_GET_FULL_COLLECTION]['DEVELOPER_TAGS'])) {
				$this->result['FILTER_TAGS'] = $this->prepareFilterTags($response[Actions::METHOD_GET_FULL_COLLECTION]['DEVELOPER_TAGS']);
			}

			if (isset($response[Actions::METHOD_GET_FULL_COLLECTION]['SORT_INFO']) && is_array($response[Actions::METHOD_GET_FULL_COLLECTION]['SORT_INFO'])) {
				$this->result['SORT_INFO'] = $response[Actions::METHOD_GET_FULL_COLLECTION]['SORT_INFO'];
				$this->result['SHOW_SORT_MENU'] = 'Y';
			}
		}

		if (!empty($response[Actions::METHOD_GET_CATEGORIES_V2])) {
			Categories::saveCache($response[Actions::METHOD_GET_CATEGORIES_V2]);
			$this->result['CATEGORIES'] = Categories::get();
		}

		$this->result['TITLE'] = $title;

		if (is_array($response[Actions::METHOD_TOTAL_APPS])) {
			$this->result = array_merge($this->result, Toolbar::getTotalAppsInfo($response[Actions::METHOD_TOTAL_APPS]));
		}

		global $APPLICATION;
		$APPLICATION->SetTitle($title);
	}

	private function prepareFilterTags(array $developerTags): array
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
}