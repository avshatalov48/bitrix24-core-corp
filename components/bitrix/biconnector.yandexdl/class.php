<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

class YandexDlCollectionComponent extends CBitrixComponent
{
	public function executeComponent()
	{
		$licence = Application::getInstance()->getLicense();
		if ($licence->getRegion() !== 'ru' && $licence->getRegion() !== 'kz')
		{
			ShowError(Loc::getMessage('CC_BBY_ERROR_NOT_ACCESS'));
			return;
		}
		$user = \Bitrix\Main\Engine\CurrentUser::get();
		$this->arResult['SERVER_NAME'] = Option::get('main', 'server_name', $_SERVER['HTTP_HOST']);

		$accessKey = \Bitrix\BIConnector\KeyManager::getOrCreateAccessKey($user);
		if (!$accessKey)
		{
			$manager = \Bitrix\BIConnector\Manager::getInstance();
			$key = $manager->getCurrentUserAccessKey();
			if ($key)
			{
				$accessKey = $key['ACCESS_KEY'];
			}
		}

		$this->arResult['SHOW_TITLE'] = $this->arParams['SHOW_TITLE'];
		$this->arResult['ACCESS_KEY'] = $accessKey ? $accessKey . LANGUAGE_ID : '';
		$this->arResult['CONNECT_LINK'] = 'https://datalens.yandex.ru/connections/new/bitrix24';

		$this->includeComponentTemplate();
	}
}
