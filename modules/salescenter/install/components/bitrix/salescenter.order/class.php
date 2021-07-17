<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class SalesCenterOrderComponent extends CBitrixComponent
{
	/**
	 * @param $arParams
	 * @return array
	 */
	public function onPrepareComponentParams($arParams)
	{
		$arParams['orderId'] = intval($arParams['orderId']);
		if(!$arParams['orderId'])
		{
			$arParams['orderId'] = intval($this->request->get('orderId'));
		}

		$arParams['sessionId'] = intval($arParams['sessionId']);
		if(!$arParams['sessionId'])
		{
			$arParams['sessionId'] = intval($this->request->get('sessionId'));
		}

		if(!isset($arParams['ownerId']))
		{
			$arParams['ownerId'] = intval($this->request->get('ownerId'));
		}
		if(!isset($arParams['ownerTypeId']))
		{
			$arParams['ownerTypeId'] = $this->request->get('ownerTypeId');
		}

		return parent::onPrepareComponentParams($arParams);
	}

	public function executeComponent()
	{
		if(!\Bitrix\Main\Loader::includeModule('salescenter'))
		{
			return false;
		}

		$this->arResult['orderId'] = $this->arParams['orderId'];
		$this->arResult['extras'] = $this->getExtras();

		$this->includeComponentTemplate();
	}

	/**
	 * @return array
	 */
	protected function getExtras()
	{
		$extras = [
			'IS_SALESCENTER_ORDER_CREATION' => 'Y',
			'SALESCENTER_SESSION_ID' => $this->arParams['sessionId'] ?? '',
		];

		$controller = new \Bitrix\SalesCenter\Controller\Order();
		$extras['CLIENT_INFO'] = $controller->getClientInfo($this->arParams);

		return $extras;
	}
}