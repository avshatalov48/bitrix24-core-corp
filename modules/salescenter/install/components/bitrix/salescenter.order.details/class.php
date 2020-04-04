<?php

use Bitrix\Main;
use Bitrix\Main\Localization;
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

Main\Loader::includeModule('sale');

CBitrixComponent::includeComponentClass("bitrix:sale.personal.order.detail");

class SalesCenterOrderDetails extends CBitrixPersonalOrderDetailComponent
{
	public function onPrepareComponentParams($params)
	{
		self::tryParseInt($params["CACHE_TIME"], 3600, true);

		$params['CACHE_GROUPS'] = (isset($params['CACHE_GROUPS']) && $params['CACHE_GROUPS'] == 'N' ? 'N' : 'Y');

		$params['ID'] = (int)$params['ID'];

		$params['ALLOW_INNER'] = 'N';

		if (empty($params["ACTIVE_DATE_FORMAT"]))
		{
			$params["ACTIVE_DATE_FORMAT"] = Main\Type\Date::getFormat();
		}

		if (!is_array($params["CUSTOM_SELECT_PROPS"]))
		{
			$params["CUSTOM_SELECT_PROPS"] = [];
		}

		// resample sizes
		self::tryParseInt($params["PICTURE_WIDTH"], 110);
		self::tryParseInt($params["PICTURE_HEIGHT"], 110);

		// resample type for images
		if(!in_array($params['RESAMPLE_TYPE'], array(BX_RESIZE_IMAGE_EXACT, BX_RESIZE_IMAGE_PROPORTIONAL, BX_RESIZE_IMAGE_PROPORTIONAL_ALT)))
			$params['RESAMPLE_TYPE'] = BX_RESIZE_IMAGE_PROPORTIONAL;

		return $params;
	}

	/**
	 * @return void
	 */
	protected function checkOrder()
	{
		if (!($this->order))
		{
			$this->doCaseOrderIdNotSet();
		}
	}

	/**
	 * Function could describe what to do when order ID not set. By default, component will redirect to list page.
	 *
	 * @throws Main\SystemException
	 * @return void
	 */
	protected function doCaseOrderIdNotSet()
	{
		throw new Main\SystemException(
			Localization\Loc::getMessage("SPOD_NO_ORDER", array("#ID#" => $this->arParams["ID"])),
			self::E_ORDER_NOT_FOUND
		);
	}

	protected function checkAuthorized()
	{
		$context = Main\Context::getCurrent();
		$request = $context->getRequest();

		$this->loadOrder(urldecode(urldecode($this->arParams["ID"])));
		$this->checkOrder();

		if ($request->get('access') !== $this->order->getHash())
		{
			$msg = Localization\Loc::getMessage("SPOD_ACCESS_DENIED");
			throw new Main\SystemException($msg, self::E_NOT_AUTHORIZED);
		}
	}

	protected function obtainDataPaySystem()
	{
		return;
	}

	/**
	 * @return array
	 */
	protected function createCacheId()
	{
		global $APPLICATION;

		return array(
			$APPLICATION->GetCurPage(),
			$this->dbResult["ID"],
			$this->dbResult["PERSON_TYPE_ID"],
			$this->dbResult["DATE_UPDATE"]->toString(),
			$this->useCatalog,
			false
		);
	}
}