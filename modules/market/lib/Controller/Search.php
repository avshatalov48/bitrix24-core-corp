<?php

namespace Bitrix\Market\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\Localization\Loc;
use Bitrix\Market\PricePolicy;

class Search extends Controller
{
	private array $result = [];

	public function getAppsAction(string $text, int $page): AjaxJson
	{
		$template = new \Bitrix\Market\ListTemplates\Search();
		$template->setSearchText($text);
		$template->setPage($page);
		$template->setResult(true);
		$this->result = $template->getInfo();
		$this->prepareApps();

		return AjaxJson::createSuccess([
			'apps' => $this->result['APPS'],
			'pages' => $this->result['PAGES'],
			'cur_page' => $this->result['CUR_PAGE'],
		]);
	}

	private function prepareApps()
	{
		if (!is_array($this->result['APPS'])) {
			return;
		}

		foreach ($this->result['APPS'] as &$appItem) {
			$appItem['PRICE_POLICY'] = PricePolicy::getByApp($appItem);
			$appItem['PRICE_POLICY_NAME'] = PricePolicy::getName($appItem['PRICE_POLICY']);
			$appItem['PRICE_POLICY_BLUE'] = ($appItem['PRICE_POLICY'] == PricePolicy::SUBSCRIPTION);
		}
		unset($appItem);
	}
}