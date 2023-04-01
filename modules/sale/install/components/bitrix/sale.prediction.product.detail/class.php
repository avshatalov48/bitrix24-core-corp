<?php
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

Loc::loadMessages(__FILE__);

class CSalePredictionProductDetailComponent extends CBitrixComponent
{
	public function onPrepareComponentParams($params)
	{
		global $APPLICATION;

		// remember src params for further ajax query
		if (!isset($params['SGP_CUR_BASE_PAGE']))
		{
			$params['SGP_CUR_BASE_PAGE'] = $APPLICATION->GetCurPage();
		}

		if (isset($params['CUSTOM_SITE_ID']))
		{
			$this->setSiteId($params['CUSTOM_SITE_ID']);
		}

		$this->arResult['_ORIGINAL_PARAMS'] = $params;

		if(empty($params["POTENTIAL_PRODUCT_TO_BUY"]))
		{
			$params["POTENTIAL_PRODUCT_TO_BUY"] = array();
		}
		if(!empty($params["POTENTIAL_PRODUCT_TO_BUY"]) && empty($params["POTENTIAL_PRODUCT_TO_BUY"]['QUANTITY']))
		{
			$params["POTENTIAL_PRODUCT_TO_BUY"]['QUANTITY'] = 1;
		}

		$params['POTENTIAL_PRODUCT_TO_BUY']['ELEMENT'] = array(
			'ID' => $params['POTENTIAL_PRODUCT_TO_BUY']['ID'],
		);
		$offerId = $this->request->getPost('offerId');
		if($offerId)
		{
			$params['POTENTIAL_PRODUCT_TO_BUY']['PRIMARY_OFFER_ID'] = $offerId;
		}
		if(!empty($params['POTENTIAL_PRODUCT_TO_BUY']['PRIMARY_OFFER_ID']))
		{
			$params['POTENTIAL_PRODUCT_TO_BUY']['ID'] = $params['POTENTIAL_PRODUCT_TO_BUY']['PRIMARY_OFFER_ID'];
		}

		$pageTemplates = [];
		if (!empty($params['PAGE_TEMPLATES']) && is_array($params['PAGE_TEMPLATES']))
		{
			$templates = $params['PAGE_TEMPLATES'];
			if (!empty($templates['PRODUCT_URL']) && is_string($templates['PRODUCT_URL']))
			{
				$pageTemplates['PRODUCT_URL'] = $templates['PRODUCT_URL'];
			}
			if (!empty($templates['SECTION_URL']) && is_string($templates['SECTION_URL']))
			{
				$pageTemplates['SECTION_URL'] = $templates['SECTION_URL'];
			}
			unset($templates);
		}
		$params['PAGE_TEMPLATES'] = $pageTemplates;
		unset($pageTemplates);

		return $params;
	}

	public function executeComponent()
	{
		if(!Loader::includeModule('sale') || !Loader::includeModule('catalog'))
		{
			return;
		}

		if(!$this->request->isAjaxRequest())
		{
			$this->arResult['REQUEST_ITEMS'] = true;
			$this->arResult['RCM_TEMPLATE'] = $this->getTemplateName();
		}
		else
		{
			$potentialBuy = array_intersect_key($this->arParams['POTENTIAL_PRODUCT_TO_BUY'], array(
				'ID' => true,
				'MODULE' => true,
				'PRODUCT_PROVIDER_CLASS' => true,
				'QUANTITY' => true,
			));

			$manager = Sale\Discount\Prediction\Manager::getInstance();

			$registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);
			/** @var Sale\Basket $basketClass */
			$basketClass = $registry->getBasketClassName();

			/** @var Sale\Basket $basket */
			$basket = $basketClass::loadItemsForFUser(
				Sale\Fuser::getId(),
				$this->getSiteId()
			)->getOrderableItems();

			global $USER;
			if ($USER instanceof \CUser && $USER->getId())
			{
				$manager->setUserId($USER->getId());
			}

			$this->arResult['PREDICTION_TEXT'] = $manager->getFirstPredictionTextByProduct(
				$basket,
				$potentialBuy,
				$this->arParams['PAGE_TEMPLATES']
			);
		}

		$this->includeComponentTemplate();
	}
}