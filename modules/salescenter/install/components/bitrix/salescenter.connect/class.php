<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\SalesCenter\Driver;
use Bitrix\SalesCenter\Integration\ImManager;
use Bitrix\SalesCenter\Integration\PullManager;

class CSalesCenterConnectComponent extends CBitrixComponent
{
	public function executeComponent()
	{
		if(!Loader::includeModule('salescenter'))
		{
			$this->showError(Loc::getMessage('SALESCENTER_MODULE_ERROR'));
			return;
		}

		if(!Driver::getInstance()->isEnabled())
		{
			$this->arResult['isShowFeature'] = true;
			$this->includeComponentTemplate('limit');
			return;
		}

		if(!ImManager::getInstance()->isApplicationInstalled())
		{
			$this->showError(Loc::getMessage('SALESCENTER_IM_APP_ERROR'));
			return;
		}

		PullManager::getInstance()->subscribeOnConnect();
		$this->arResult = \Bitrix\SalesCenter\Driver::getInstance()->getManagerParams();
		$this->arResult['withRedirect'] = (bool)$this->arParams['withRedirect'];
		$this->arResult['context'] = $this->arParams['context'];

		if($this->arParams['context'] === 'sms' || $this->arParams['context'] === 'salescenter_sms')
		{
			$this->includeComponentTemplate('sms');
		}
		else
		{
			$this->includeComponentTemplate();
		}
	}

	protected function showError($error)
	{
		ShowError($error);
	}
}