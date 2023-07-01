<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages(__FILE__);

if (!\Bitrix\Main\Loader::includeModule('biconnector'))
{
	ShowError(Loc::getMessage('CC_BBS_ERROR_INCLUDE_MODULE'));
	die();
}

class BIConnectorStartComponent extends CBitrixComponent
{
	public function executeComponent()
	{
		global $APPLICATION, $USER;

		if ($this->arParams['SET_TITLE'] == 'Y')
		{
			$APPLICATION->SetTitle(Loc::getMessage('CC_BBS_TITLE'));
		}

		$this->arResult['SERVER_NAME'] = \Bitrix\Main\Config\Option::get('main', 'server_name', $_SERVER['HTTP_HOST']);

		$accessKey = '';
		if ($USER->CanDoOperation('biconnector_key_manage'))
		{
			$key = \Bitrix\BIConnector\KeyTable::getList([
				'select' => [
					'ACCESS_KEY',
				],
				'filter' => [
					'=ACTIVE' => 'Y',
					'=APP_ID' => false,
				],
				'order' => [
					'ID' => 'DESC',
				],
				'limit' => 1,
			])->fetch();
			if ($key)
			{
				$accessKey = $key['ACCESS_KEY'];
			}
			else
			{
				$key = \Bitrix\BIConnector\KeyManager::generateAccessKey();
				$resultSave = \Bitrix\BIConnector\KeyManager::save([
					'USER_ID' => $USER->GetId(),
					'ACTIVE' => true,
					'ACCESS_KEY' => $key,
				]);
				if (!($resultSave instanceof ErrorCollection))
				{
					$accessKey = $key;
				}
			}
		}

		if (!$accessKey)
		{
			$manager = \Bitrix\BIConnector\Manager::getInstance();
			$key = $manager->getCurrentUserAccessKey();
			if ($key)
			{
				$accessKey = $key['ACCESS_KEY'];
			}
		}

		$this->arResult['ACCESS_KEY'] = $accessKey ? $accessKey . LANGUAGE_ID : '';

		if (\Bitrix\Main\Loader::includeModule('rest'))
		{
			$this->arResult['GDS_MARKET_LINK'] = \Bitrix\Rest\Marketplace\Url::getBookletUrl('bi_data_studio');
			if ($this->arResult['ACCESS_KEY'])
			{
				$this->arResult['PBI_MARKET_LINK'] = \Bitrix\Rest\Marketplace\Url::getBookletUrl('bi_powerbi');
			}
		}

		$this->includeComponentTemplate();
	}
}
