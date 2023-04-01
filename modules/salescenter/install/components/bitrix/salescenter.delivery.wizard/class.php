<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\SalesCenter\Delivery\Handlers\HandlersRepository;
use Bitrix\Main\Loader;
use Bitrix\Sale\Delivery\Services;
use Bitrix\SalesCenter\Integration\SaleManager;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Loc::loadMessages(__FILE__);

class SalesCenterDeliveryWizard extends CBitrixComponent
{
	public function executeComponent()
	{
		if (!Loader::includeModule('salescenter'))
		{
			ShowError(Loc::getMessage('SALESCENTER_DELIVERY_INSTALLATION_MODULE_ERROR'));
			return;
		}

		if (!Loader::includeModule('sale'))
		{
			ShowError(Loc::getMessage('SALESCENTER_DELIVERY_INSTALLATION_SALE_MODULE_ERROR'));
			return;
		}

		if(!SaleManager::getInstance()->isFullAccess(true))
		{
			ShowError(Loc::getMessage("SALESCENTER_DELIVERY_INSTALLATION_ACCESS_DENIED"));
			return;
		}

		$GLOBALS['APPLICATION']->SetTitle(Loc::getMessage('SALESCENTER_DELIVERY_INSTALLATION_SLIDER_TITLE'));

		$serviceId = (isset($this->arParams['SERVICE_ID']) && (int)$this->arParams['SERVICE_ID'])
			? (int)$this->arParams['SERVICE_ID']
			: 0;

		$restHandlerCode = (!empty($this->arParams['REST_HANDLER_CODE']))
			? $this->arParams['REST_HANDLER_CODE']
			: '';

		$repository = new HandlersRepository();

		/** @var \Bitrix\SalesCenter\Delivery\Handlers\Base $handler */
		$handler = $repository->getByCode($this->arParams['CODE']);
		$this->arResult = [
			'code' => $this->arParams['CODE'],
			'handler' => $handler,
			'knownHandlerCodes' => array_map(
				function ($item) use ($repository) { return $item->getCode(); },
				$repository->getCollection()->getInstallableItems()
			),
		];

		if ($restHandlerCode)
		{
			$this->arResult['restHandler'] = Services\Manager::getRestHandlerList()[$restHandlerCode];

			if ($serviceId)
			{
				$dbRes = Services\Table::getById($serviceId);
				$fields = $dbRes->fetch();
			}
			else
			{
				$fields = [
					'CLASS_NAME' => '\\'.\Sale\Handlers\Delivery\RestHandler::class,
					'REST_CODE' => $restHandlerCode,
				];
			}

			if ($service = Services\Manager::createObject($fields))
			{
				$this->arResult['serviceConfig'] = $service->getConfig();
			}
		}

		$this->arResult['service'] = null;
		if ($serviceId)
		{
			$this->arResult['service'] = Services\Manager::getById($serviceId);
		}

		$this->arResult['edit'] = (bool)$this->arResult['service'];

		$this->includeComponentTemplate();
	}
}
