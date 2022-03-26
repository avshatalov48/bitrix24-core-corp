<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;
use Bitrix\Sale;
use Bitrix\Crm;
use Bitrix\SalesCenter;

Main\Localization\Loc::loadMessages(__FILE__);

/**
 * Class SalesCenterPaymentListComponent
 */
class SalesCenterPaymentListComponent extends CBitrixComponent
{
	private const CRM_ORDER_PAYMENT_LIST_GRID_ID = 'CRM_ORDER_PAYMENT_LIST_V12';
	private const GRID_ID = 'SALESCENTER_PAYMENT_LIST';

	public function onPrepareComponentParams($arParams)
	{
		$this->initParamsFromRequest($arParams);

		if (empty($arParams['ownerId']))
		{
			$arParams['ownerId'] = (int)$this->request->get('ownerId');
		}

		if (empty($arParams['ownerTypeId']))
		{
			$arParams['ownerTypeId'] = $this->request->get('ownerTypeId');
		}

		if (empty($arParams['sessionId']))
		{
			$arParams['sessionId'] = (int)$this->request->get('sessionId');
		}

		if (empty($arParams['disableSendButton']))
		{
			$arParams['disableSendButton'] = ($this->request->get('disableSendButton') === 'y');
		}
		else
		{
			$arParams['disableSendButton'] = filter_var($arParams['disableSendButton'], FILTER_VALIDATE_BOOLEAN);
		}

		if (empty($arParams['context']))
		{
			$arParams['context'] = $this->request->get('context');
		}

		return parent::onPrepareComponentParams($arParams);
	}

	private function initParamsFromRequest(array &$arParams): void
	{
		if ($this->request->isPost())
		{
			$params = $this->request->get('PARAMS');
			if ($params && \is_array($params))
			{
				$arParams = array_merge($arParams, $params);
			}
		}
	}

	public function executeComponent()
	{
		if (!$this->checkModules())
		{
			return;
		}

		$this->prepareResult();

		$this->includeComponentTemplate();
	}

	private function checkModules(): bool
	{
		if (!Main\Loader::includeModule('sale'))
		{
			ShowError(Main\Localization\Loc::getMessage('SPL_SALE_MODULE_ERROR'));
			return false;
		}

		if (!Main\Loader::includeModule('crm'))
		{
			ShowError(Main\Localization\Loc::getMessage('SPL_CRM_MODULE_ERROR'));
			return false;
		}

		if (!Main\Loader::includeModule('salescenter'))
		{
			ShowError(Main\Localization\Loc::getMessage('SPL_SALESCENTER_MODULE_ERROR'));
			return false;
		}

		return true;
	}

	private function prepareResult(): void
	{
		$this->arResult = SalesCenter\Driver::getInstance()->getManagerParams();

		$this->arResult['grid']['id'] = self::GRID_ID;
		$this->arResult['grid']['fullId'] = self::CRM_ORDER_PAYMENT_LIST_GRID_ID . '_' . self::GRID_ID;
		$this->arResult['orderList'] = $this->getOrderIdList();
		$this->arResult['isPaymentsLimitReached'] = SalesCenter\Integration\Bitrix24Manager::getInstance()->isPaymentsLimitReached();

		$sessionId = $this->arParams['sessionId'];
		if ($sessionId)
		{
			$this->arResult['disableSendButton'] = $this->arParams['disableSendButton'];
		}
		else
		{
			$this->arResult['disableSendButton'] = true;
		}

		$this->arResult['context'] = $this->arParams['context'];
		$this->arResult['hideSendButton'] = $this->arParams['context'] === 'sms';

		$this->arResult['isPaymentsLimitReached'] = SalesCenter\Integration\Bitrix24Manager::getInstance()->isPaymentsLimitReached();
		$this->arResult['messages'] = Main\Localization\Loc::loadLanguageFile(__FILE__);
		$this->arResult['sessionId'] = $sessionId;

		$this->arResult['isOrderPublicUrlAvailable'] = SalesCenter\Integration\LandingManager::getInstance()->isOrderPublicUrlAvailable();
		$this->arResult['isSitePublished'] = SalesCenter\Integration\LandingManager::getInstance()->isSitePublished();

		$this->arResult['phpSession'] = bitrix_sessid();
	}

	private function getOrderIdList(): array
	{
		$orderIdList = [];

		if ($this->arParams['sessionId'] > 0)
		{
			$userId = SalesCenter\Integration\ImOpenLinesManager::getInstance()->setSessionId($this->arParams['sessionId'])->getUserId();
			if ($userId)
			{
				$orderIdList = $this->getOrderIdListByUserId($userId);
			}
		}
		elseif ((int)$this->arParams['ownerTypeId'] === CCrmOwnerType::Contact || (int)$this->arParams['ownerTypeId'] === CCrmOwnerType::Company)
		{
			$entityTypeId = (int)$this->arParams['ownerTypeId'];
			$orderIdList = Bitrix\Crm\Order\Order::getList([
				'select' => ['ID'],
				'filter' => [
					'=CLIENT.ENTITY_TYPE_ID' => $entityTypeId,
					'=CLIENT.ENTITY_ID' => $this->arParams['ownerId'],
				],
				'runtime' => [
					new Main\Entity\ReferenceField(
						'CLIENT',
						\Bitrix\Crm\Binding\OrderContactCompanyTable::getEntity(),
						[
							'=ref.ORDER_ID' => 'this.ID',
						],
						['join_type' => 'LEFT']
					),
				]
			])->fetchAll();
			$orderIdList = array_column($orderIdList, 'ID');
		}
		elseif ($this->arParams['ownerId'] > 0 && $this->arParams['ownerTypeId'] > 0)
		{
			$orderIdList = Crm\Binding\OrderEntityTable::getOrderIdsByOwner($this->arParams['ownerId'], $this->arParams['ownerTypeId']);
		}

		return $orderIdList;
	}

	private function getOrderIdListByUserId(int $userId): array
	{
		$result = [];

		$registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);
		/** @var Crm\Order\Order $orderClassName */
		$orderClassName = $registry->getOrderClassName();

		$orderIterator = $orderClassName::getList([
			'select' => ['ID'],
			'filter' => ['=USER_ID' => $userId],
		]);
		while ($orderData = $orderIterator->fetch())
		{
			$result[] = $orderData['ID'];
		}

		return $result;
	}
}
