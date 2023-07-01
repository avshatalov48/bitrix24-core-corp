<?php


namespace Bitrix\Market\ListTemplates;


use Bitrix\Main\Localization\Loc;
use Bitrix\Market\Categories;
use Bitrix\Market\NumberApps;
use Bitrix\Rest\Marketplace\Transport;

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
			Transport::METHOD_GET_FULL_COLLECTION => [
				Transport::METHOD_GET_FULL_COLLECTION,
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

		if (isset($response[Transport::METHOD_GET_FULL_COLLECTION])) {
			if (isset($response[Transport::METHOD_GET_FULL_COLLECTION]['APPS']) && is_array($response[Transport::METHOD_GET_FULL_COLLECTION]['APPS'])) {
				$this->result['APPS'] = $response[Transport::METHOD_GET_FULL_COLLECTION]['APPS'];
				$this->result['PAGES'] = $response[Transport::METHOD_GET_FULL_COLLECTION]['PAGES'];
				$this->result['CUR_PAGE'] = $response[Transport::METHOD_GET_FULL_COLLECTION]['CUR_PAGE'];
				$this->result['CURRENT_APPS_CNT'] = $response[Transport::METHOD_GET_FULL_COLLECTION]['NUMBER_APPS'];
				$title = $response[Transport::METHOD_GET_FULL_COLLECTION]['NAME'];
			}

			if (isset($response[Transport::METHOD_GET_FULL_COLLECTION]['DEVELOPER_TAGS']) && !empty($response[Transport::METHOD_GET_FULL_COLLECTION]['DEVELOPER_TAGS'])) {
				$this->result['FILTER_TAGS'] = $this->prepareFilterTags($response[Transport::METHOD_GET_FULL_COLLECTION]['DEVELOPER_TAGS']);
			}

			if (isset($response[Transport::METHOD_GET_FULL_COLLECTION]['SORT_INFO']) && is_array($response[Transport::METHOD_GET_FULL_COLLECTION]['SORT_INFO'])) {
				$this->result['SORT_INFO'] = $response[Transport::METHOD_GET_FULL_COLLECTION]['SORT_INFO'];
				$this->result['SHOW_SORT_MENU'] = 'Y';
			}
		}

		if (!empty($response[Transport::METHOD_GET_CATEGORIES_V2])) {
			Categories::saveCache($response[Transport::METHOD_GET_CATEGORIES_V2]);
			$this->result['CATEGORIES'] = Categories::get();
		}

		$this->result['TITLE'] = $title;
		$this->result['TOTAL_APPS'] = NumberApps::get($response[Transport::METHOD_TOTAL_APPS]);

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