<?php

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Currency\CurrencyManager;
use Bitrix\SalesCenter\Driver;
use Bitrix\SalesCenter\Integration\ImOpenLinesManager;
use Bitrix\SalesCenter\Integration\LandingManager;
use Bitrix\SalesCenter\Integration\PullManager;
use Bitrix\SalesCenter\Integration\SaleManager;
use Bitrix\Sale\Internals\SiteCurrencyTable;
use Bitrix\Sale\PaySystem;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class CSalesCenterAppComponent extends CBitrixComponent
{
	/**
	 * @param $arParams
	 * @return array
	 */
	public function onPrepareComponentParams($arParams)
	{
		if(!$arParams['dialogId'])
		{
			$arParams['dialogId'] = $this->request->get('dialogId');
		}

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
		if(!\Bitrix\Main\Loader::includeModule("salescenter"))
		{
			ShowError(Loc::getMessage('SALESCENTER_MODULE_ERROR'));
			Application::getInstance()->terminate();
		}

		if(!Driver::getInstance()->isEnabled())
		{
			$this->arResult['isShowFeature'] = true;
			$this->includeComponentTemplate('limit');
			return;
		}

		PullManager::getInstance()->subscribeOnConnect();
		$this->arResult = Driver::getInstance()->getManagerParams();
		$this->arResult['isFrame'] = Application::getInstance()->getContext()->getRequest()->get('IFRAME') === 'Y';
		$this->arResult['isCatalogAvailable'] = (\Bitrix\Main\Config\Option::get('salescenter', 'is_catalog_enabled', 'N') === 'Y');
		$this->arResult['dialogId'] = $this->arParams['dialogId'];
		$this->arResult['sessionId'] = $this->arParams['sessionId'];
		$this->arResult['context'] = $this->arParams['context'];
		$this->arResult['orderAddPullTag'] = PullManager::getInstance()->subscribeOnOrderAdd();
		$this->arResult['landingUnPublicationPullTag'] = PullManager::getInstance()->subscribeOnLandingUnPublication();
		$this->arResult['landingPublicationPullTag'] = PullManager::getInstance()->subscribeOnLandingPublication();
		$this->arResult['isOrderPublicUrlExists'] = (LandingManager::getInstance()->isOrderPublicUrlExists());
		$this->arResult['isOrderPublicUrlAvailable'] = (LandingManager::getInstance()->isOrderPublicUrlAvailable());
		$this->arResult['disableSendButton'] = $this->arParams['disableSendButton'];
		$this->arResult['ownerTypeId'] = $this->arParams['ownerTypeId'];
		$this->arResult['ownerId'] = $this->arParams['ownerId'];
		$this->arResult['isPaymentsLimitReached'] = \Bitrix\SalesCenter\Integration\Bitrix24Manager::getInstance()->isPaymentsLimitReached();
		if($this->arResult['sessionId'] > 0)
		{
			$sessionInfo = ImOpenLinesManager::getInstance()->setSessionId($this->arResult['sessionId'])->getSessionInfo();
			if($sessionInfo)
			{
				$this->arResult['connector'] = $sessionInfo['SOURCE'];
			}
		}

		if (Loader::includeModule('sale')
			&& Loader::includeModule('currency')
			&& Loader::includeModule('catalog')
		)
		{
			$this->arResult['orderCreationOption'] = 'order_creation';
			$this->arResult['paySystemBannerOptionName'] = 'hide_paysystem_banner';
			$this->arResult['showPaySystemSettingBanner'] = true;
			$baseCurrency = SiteCurrencyTable::getSiteCurrency(SITE_ID);
			if (empty($baseCurrency))
			{
				$baseCurrency = CurrencyManager::getBaseCurrency();
			}
			$this->arResult['currencyCode'] = $baseCurrency;
			$currencyDescription = \CCurrencyLang::GetFormatDescription($baseCurrency);
			$this->arResult['CURRENCIES'][] = [
				'CURRENCY' => $currencyDescription['CURRENCY'],
				'FORMAT' => [
					'FORMAT_STRING' => $currencyDescription['FORMAT_STRING'],
					'DEC_POINT' => $currencyDescription['DEC_POINT'],
					'THOUSANDS_SEP' => $currencyDescription['THOUSANDS_SEP'],
					'DECIMALS' => $currencyDescription['DECIMALS'],
					'THOUSANDS_VARIANT' => $currencyDescription['THOUSANDS_VARIANT'],
					'HIDE_ZERO' => $currencyDescription['HIDE_ZERO']
				]
			];

			$this->arResult['currencyName'] = $currencyDescription['FULL_NAME'];

			$dbMeasureResult = \CCatalogMeasure::getList(
				array('CODE' => 'ASC'),
				array(),
				false,
				array('nTopCount' => 100),
				array('CODE', 'SYMBOL_RUS', 'SYMBOL_INTL', 'IS_DEFAULT')
			);

			$this->arResult['measures'] = [];
			while($measureFields = $dbMeasureResult->Fetch())
			{
				$this->arResult['measures'][] = [
					'CODE' => intval($measureFields['CODE']),
					'IS_DEFAULT' => $measureFields['IS_DEFAULT'],
					'SYMBOL' => isset($measureFields['SYMBOL_RUS'])
						? $measureFields['SYMBOL_RUS'] : $measureFields['SYMBOL_INTL']
				];
			}

			$options = \CUserOptions::GetOption('salescenter', $this->arResult['orderCreationOption'], array());
			$paySystemList = PaySystem\Manager::getList([
				'select' => ['ID', 'NAME', 'ACTION_FILE', 'ACTIVE'],
				'filter' => [
					SaleManager::getInstance()->getPaySystemFilter(),
					'ACTIVE' => 'Y'
				],
			]);
			while ($paySystem = $paySystemList->fetch())
			{
				if ($paySystem['ACTION_FILE'] !== 'cash')
				{
					$this->arResult['showPaySystemSettingBanner'] = false;
					break;
				}
			}

			if ($this->arResult['showPaySystemSettingBanner'])
			{
				$this->arResult['showPaySystemSettingBanner'] = ($options[$this->arResult['paySystemBannerOptionName']] !== 'Y');
			}
		}

		global $APPLICATION;
		$APPLICATION->SetTitle(Loc::getMessage('SALESCENTER_APP_TITLE'));
		$this->includeComponentTemplate();
	}
}