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
		$permissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
		if (!$permissions->canModifyLines())
		{
			$this->addError(new \Bitrix\Main\Error('Permission to modify line settings required', 'access_denied'));
			return null;
		}

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

	public function getComponentResultAction()
	{
		CBitrixComponent::includeComponentClass("bitrix:voximplant.start");
		$component = new VoximplantStartComponent();
		$component->arParams = $component->onPrepareComponentParams([]);

		$arResult = $component->prepareResult();

		if($arResult['ERROR_MESSAGE'])
		{
			$this->addError(new \Bitrix\Main\Error($arResult['ERROR_MESSAGE']));
			return null;
		}

		return [
			"lines" => $arResult['NUMBERS_LIST'],
			"mainMenuItems" => $arResult['MENU']['MAIN'],
			"settingsMenuItems" => $arResult['MENU']['SETTINGS'],
			"partnersMenuItems" => $arResult['MENU']['PARTNERS'],
			"applicationUrlTemplate" => $arResult['MARKETPLACE_DETAIL_URL_TPL'],
			"tariffsUrl" => $arResult['LINK_TO_TARIFFS'],
			"isRestOnly" => $arResult['IS_REST_ONLY'] ? 'Y' : 'N'
		];
	}
}