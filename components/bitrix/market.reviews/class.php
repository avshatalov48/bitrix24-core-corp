<?php

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Rest\Marketplace\Transport;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

Loader::includeModule('market');

class MarketReviews extends CBitrixComponent implements Controllerable
{
	public function executeComponent()
	{
		global $APPLICATION;
		$APPLICATION->SetTitle(Loc::getMessage('MARKET_MY_REVIEWS'));

		$this->arParams['COMPONENT_NAME'] = 'bitrix:market.reviews';

		$this->arResult['REVIEWS'] = $this->getItems();

		$this->arResult['REVIEWS']['ALL_ITEMS'] = [];
		if (!empty($this->arResult['REVIEWS']) && isset($this->arResult['REVIEWS']['ITEMS'])) {
			$this->arResult['REVIEWS']['ALL_ITEMS'] = $this->arResult['REVIEWS']['ITEMS'];
		}

		$this->includeComponentTemplate();
	}

	private function getItems(int $page = 1, string $filter = ''): array
	{
		global $USER;

		$result = [];

		$response = Transport::instance()->call(Transport::METHOD_GET_REVIEWS, [
			'filter_user' => $USER->GetID(),
			'reviews_page' => $page,
			'filter_type' => $filter,
		]);
		if (is_array($response) && is_array($response['ITEMS'])) {
			$result = $response;
		}

		return $result;
	}

	public function configureActions(): array
	{
		return [];
	}

	public function getReviewPageAction($page, $filter): AjaxJson
	{
		return AjaxJson::createSuccess([
			'reviews' => $this->getItems((int)$page, (string)$filter),
		]);
	}
}
