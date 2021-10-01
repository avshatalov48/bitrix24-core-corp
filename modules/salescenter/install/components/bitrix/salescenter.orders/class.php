<?php

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\SalesCenter\Driver;
use Bitrix\SalesCenter\Integration\Bitrix24Manager;
use Bitrix\SalesCenter\Integration\CrmManager;
use Bitrix\SalesCenter\Integration\ImOpenLinesManager;
use Bitrix\SalesCenter\Integration\LandingManager;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class SalesCenterOrderListComponent extends CBitrixComponent
{
	/**
	 * @param $arParams
	 * @return array
	 */
	public function onPrepareComponentParams($arParams)
	{
		$arParams['sessionId'] = intval($arParams['sessionId']);
		if(!$arParams['sessionId'])
		{
			$arParams['sessionId'] = intval($this->request->get('sessionId'));
		}

		if(!isset($arParams['disableSendButton']))
		{
			$arParams['disableSendButton'] = ($this->request->get('disableSendButton') === 'y');
		}
		else
		{
			$arParams['disableSendButton'] = (bool)$arParams['disableSendButton'];
		}

		if(!isset($arParams['context']))
		{
			$arParams['context'] = $this->request->get('context');
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
			ShowError(Loc::getMessage('SALESCENTER_MODULE_ERROR'));
			return false;
		}

		$this->arResult = Driver::getInstance()->getManagerParams();
		$sessionId = $this->arParams['sessionId'];
		if(!$sessionId)
		{
			$this->arResult['disableSendButton'] = true;
		}
		else
		{
			$this->arResult['disableSendButton'] = $this->arParams['disableSendButton'];
		}
		$this->arResult['isPaymentsLimitReached'] = Bitrix24Manager::getInstance()->isPaymentsLimitReached();
		$this->arResult['messages'] = Loc::loadLanguageFile(__FILE__);
		$this->arResult['sessionId'] = $this->arParams['sessionId'];
		$this->arResult['externalFilter'] = $this->getExternalFilter();
		$this->arResult['orderId'] = (int)Application::getInstance()->getContext()->getRequest()->get('orderId');
		$this->arResult['gridId'] = 'SALESCENTER_ORDER_LIST';

		$this->arResult['isOrderPublicUrlAvailable'] = LandingManager::getInstance()->isOrderPublicUrlAvailable();

		$this->arResult['toolbarButtons'] = $this->getToolbarButtons();
		$this->arResult['isSitePublished'] = LandingManager::getInstance()->isSitePublished();

		$isOrderLimitReached = CrmManager::getInstance()->isOrderLimitReached();
		$this->arResult['addOrderOnClick'] = $isOrderLimitReached ? 'top.BX.UI.InfoHelper.show(\'' . CrmManager::getInstance()->getOrderLimitSliderId() . '\');' : 'BX.Salescenter.Manager.showOrderAdd();';

		$this->includeComponentTemplate();
	}

	/**
	 * @return array
	 */
	protected function getExternalFilter()
	{
		$filter = [];

		if($this->arResult['sessionId'] > 0)
		{
			$filter['USER_ID'] = ImOpenLinesManager::getInstance()->setSessionId($this->arResult['sessionId'])->getUserId();
		}
		elseif($this->arParams['ownerId'] && $this->arParams['ownerTypeId'])
		{
			$filter = CrmManager::getInstance()->getClientInfo($this->arParams['ownerTypeId'], $this->arParams['ownerId']);
		}

		return $filter;
	}

	/**
	 * @return array
	 */
	protected function getToolbarButtons()
	{
		$buttons = [];

		$buttons[] = [
			'ONCLICK' => 'BX.Salescenter.Orders.sendGridOrders()',
			'ICON' => 'webform-small-button-transparent crm-contact-menu-im-icon salescenter-toolbar-communication-button',
		];

//		$buttons[] = [
//			'TYPE' => 'crm-communication-panel',
//			'DATA' => array(
//				'ENABLE_CALL' => \Bitrix\Main\ModuleManager::isModuleInstalled('calendar'),
//				'OWNER_INFO' => isset($arParams['OWNER_INFO']) ? $arParams['OWNER_INFO'] : array(),
//				'MULTIFIELDS' => isset($arParams['MULTIFIELD_DATA']) ? $arParams['MULTIFIELD_DATA'] : array()
//			)
//		];

		$buttons[] = [
			'TEXT' => Loc::getMessage('SALESCENTER_ORDERS_ADD_ORDER'),
			'TITLE' => Loc::getMessage('SALESCENTER_ORDERS_ADD_ORDER'),
			'HIGHLIGHT' => true,
			'ONCLICK' => 'BX.Salescenter.Manager.showOrderAdd()',
		];

		return $buttons;
	}
}