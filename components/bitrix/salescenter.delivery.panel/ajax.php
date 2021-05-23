<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main,
	Bitrix\Rest;

/**
 * Class SalesCenterDeliveryPanelAjaxController
 */
class SalesCenterDeliveryPanelAjaxController extends Main\Engine\Controller
{
	/**
	 * @param $code
	 * @return array|mixed|null
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getRestAppAction($code)
	{
		if(!Main\Loader::includeModule("rest"))
		{
			$this->errorCollection[] = new Main\Error("REST module is not installer");
			return null;
		}

		$row = Rest\AppTable::getRow([
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
			$this->errorCollection[] = new Main\Error("Application is not found");
			return null;
		}

		$isLocal = $row['STATUS'] === Rest\AppTable::STATUS_LOCAL;
		if($isLocal)
		{
			$onlyApi = empty($row["MENU_NAME"]) && empty($row["MENU_NAME_DEFAULT"]) && empty($row["MENU_NAME_LICENSE"]);
			$result = [
				'TYPE' => $onlyApi ? 'A' : 'N'
			];
			return $result;
		}

		$result = Rest\Marketplace\Client::getApp($code);

		if(isset($result["ITEMS"]))
		{
			return $result["ITEMS"];
		}

		$this->errorCollection[] = new Main\Error("App is not found");
		return null;
	}

	/**
	 * @param $signedParameters
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\LoaderException
	 * @throws Main\Security\Sign\BadSignatureException
	 * @throws Main\SystemException
	 */
	public function getComponentResultAction($signedParameters): array
	{
		Main\Loader::includeModule('sale');
		Main\Loader::includeModule('salescenter');

		CBitrixComponent::includeComponentClass('bitrix:salescenter.delivery.panel');

		$params = Main\Component\ParameterSigner::unsignParameters('bitrix:salescenter.delivery.panel', $signedParameters);
		$salesCenterDeliveryPanelComponent = new \SalesCenterDeliveryPanel();
		$salesCenterDeliveryPanelComponent->initComponent('bitrix:salescenter.delivery.panel');
		$salesCenterDeliveryPanelComponent->onPrepareComponentParams($params);

		$arResult = $salesCenterDeliveryPanelComponent->prepareResult();

		return [
			"deliveryPanelParams" => $arResult["deliveryPanelParams"]["items"],
		];
	}
}