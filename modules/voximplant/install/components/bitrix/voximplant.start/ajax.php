<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class VoximplantStartAjaxController extends \Bitrix\Main\Engine\Controller
{
	public function __construct(\Bitrix\Main\Request $request = null)
	{
		parent::__construct($request);

		\Bitrix\Main\Loader::includeModule('voximplant');
	}

	public function getConfigurationsAction()
	{
		return CVoxImplantConfig::GetConfigurations();
	}

	public function getRestAppAction($code)
	{
		if(!\Bitrix\Main\Loader::includeModule("rest"))
		{
			$this->errorCollection[] = new \Bitrix\Main\Error("REST module is not installer");
			return null;
		}

		$row = \Bitrix\Rest\AppTable::getRow([
			'select' => [
				'ID', 'APP_NAME', 'CLIENT_ID', 'CLIENT_SECRET',
				'URL_INSTALL', 'STATUS',
				'MENU_NAME' => 'LANG.MENU_NAME',
				'MENU_NAME_DEFAULT' => 'LANG_DEFAULT.MENU_NAME',
				'MENU_NAME_LICENSE' => 'LANG_LICENSE.MENU_NAME',
			],

			'filter' => [
				'=CODE' => $code
			],
		]);

		if(!$row)
		{
			$this->errorCollection[] = new \Bitrix\Main\Error("Application is not found");
			return null;
		}

		$isLocal = $row['STATUS'] === \Bitrix\Rest\AppTable::STATUS_LOCAL;
		if($isLocal)
		{
			$onlyApi = empty($row["MENU_NAME"]) && empty($row["MENU_NAME_DEFAULT"]) && empty($row["MENU_NAME_LICENSE"]);
			$result = [
				'TYPE' => $onlyApi ? 'A' : 'N'
			];
			return $result;
		}

		$result = \Bitrix\Rest\Marketplace\Client::getApp($code);

		if(isset($result["ITEMS"]))
		{
			return $result["ITEMS"];
		}
		else
		{
			$this->errorCollection[] = new \Bitrix\Main\Error("App is not found");
			return null;
		}
	}
}