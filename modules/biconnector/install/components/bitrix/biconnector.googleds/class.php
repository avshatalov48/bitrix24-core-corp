<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class GoogleDataLensComponent extends CBitrixComponent
{
	public function executeComponent()
	{
		$user = \Bitrix\Main\Engine\CurrentUser::get();
		$this->arResult['SERVER_NAME'] = \Bitrix\Main\Config\Option::get('main', 'server_name', $_SERVER['HTTP_HOST']);

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
		$this->arResult['QUERY_STRING'] = '{"server_name":"' . urlencode($this->arResult['SERVER_NAME']) . '","key":"' . urlencode($this->arResult['ACCESS_KEY']) . '"}';
		$this->arResult['CONNECT_LINK'] = 'https://datastudio.google.com/datasources/create?connectorId=AKfycbzUoCZuMLzQ2-MsxxJdnFUtrEVgWN5UMRl6XT_MD8hc3Ixm9_lanhMZnPlYOrp7epGv&connectorConfig=' . urlencode($this->arResult['QUERY_STRING']);

		$this->includeComponentTemplate();
	}
}
