<?php

namespace Bitrix\Salescenter\Builder;

use Bitrix\Main;
use Bitrix\Sale;
use Bitrix\Crm\Order;
use Bitrix\SalesCenter\Integration;

class OrderBuilder extends Order\Builder\OrderBuilderCrm
{
	protected function prepareFields(array $fields)
	{
		$fields = parent::prepareFields($fields);

		if (empty($fields['SITE_ID']))
		{
			$fields['SITE_ID'] = SITE_ID;
		}

		if (!empty($fields['CLIENT']['COMPANY_ID']))
		{
			$fields['PERSON_TYPE_ID'] = Order\PersonType::getCompanyPersonTypeId();
		}
		else
		{
			$fields['PERSON_TYPE_ID'] = Order\PersonType::getContactPersonTypeId();
		}

		if (!isset($fields['RESPONSIBLE_ID']))
		{
			$fields['RESPONSIBLE_ID'] = \CCrmSecurityHelper::GetCurrentUserID();
		}

		return $fields;
	}

	public function buildTradeBindings()
	{
		if (
			!isset($this->formData["TRADING_PLATFORM"])
			&& (!$this->order || $this->order->getId() === 0)
			&& Integration\LandingManager::getInstance()->isSiteExists()
		)
		{
			$connectedSiteId = Integration\LandingManager::getInstance()->getConnectedSiteId();
			$code = Sale\TradingPlatform\Landing\Landing::getCodeBySiteId($connectedSiteId);
			$platform = Sale\TradingPlatform\Landing\Landing::getInstanceByCode($code);

			$this->formData['TRADING_PLATFORM'] = $platform->getId();
		}

		return parent::buildTradeBindings();
	}

	public function buildPayments()
	{
		if (
			empty($this->formData["PAYMENT"])
			&& $this->needCreateDefaultPayment()
		)
		{
			$fields = ['SUM' => 0];

			$paySystem = $this->getDefaultPaySystem();
			if ($paySystem)
			{
				$fields['PAY_SYSTEM_ID'] = $paySystem['ID'];
				$fields['PAY_SYSTEM_NAME'] = $paySystem['NAME'];
			}

			foreach ($this->formData['PRODUCT'] as $index => $item)
			{
				$fields['SUM'] += Sale\PriceMaths::roundPrecision($item['QUANTITY'] * $item['PRICE']);

				$fields['PRODUCT'][] = [
					'BASKET_CODE' => $index,
					'QUANTITY' => $item['QUANTITY']
				];
			}

			$this->formData["PAYMENT"] = [$fields];
		}

		if (isset($this->formData["PAYMENT"]))
		{
			foreach ($this->formData["PAYMENT"] as &$item)
			{
				if (!isset($item['PAY_SYSTEM_ID']))
				{
					$paySystem = $this->getDefaultPaySystem();
					if ($paySystem)
					{
						$item['PAY_SYSTEM_ID'] = $paySystem['ID'];
						$item['PAY_SYSTEM_NAME'] = $paySystem['NAME'];
					}
				}
			}
		}

		return parent::buildPayments();
	}

	protected function getDefaultPaySystem()
	{
		$paySystem = [];

		$paySystemList = Sale\PaySystem\Manager::getListWithRestrictionsByOrder($this->order);

		if ($this->isIMessageConnector())
		{
			foreach ($paySystemList as $item)
			{
				if (Integration\SaleManager::getInstance()->isApplePayPayment($item))
				{
					$paySystem = $item;
				}
			}
		}

		if (!$paySystem)
		{
			$paySystem = $this->findCashPaySystem($paySystemList);
			if (!$paySystem)
			{
				$paySystem = current($paySystemList);
			}
		}

		return $paySystem;
	}

	/**
	 * @param array $paySystemList
	 * @return array
	 */
	private function findCashPaySystem(array $paySystemList) : array
	{
		foreach ($paySystemList as $item)
		{
			if ($item['ACTION_FILE'] === 'cash')
			{
				return $item;
			}
		}

		return [];
	}

	/**
	 * @param array $paySystem
	 * @return bool
	 */
	private function isIMessageConnector(): bool
	{
		return isset($this->formData['CONNECTOR']) && $this->formData['CONNECTOR'] === 'imessage';
	}

	public function buildShipments()
	{
		$this->prepareShipmentProducts();

		return parent::buildShipments();
	}

	private function prepareShipmentProducts(): void
	{
		$basketFormData = $this->getBasketBuilder()->getFormData();

		if (isset($this->formData['PRODUCT'], $basketFormData['PRODUCT']))
		{
			$this->formData['PRODUCT'] = $basketFormData['PRODUCT'];
		}

		if (isset($this->formData['SHIPMENT'], $basketFormData['SHIPMENT']))
		{
			foreach ($basketFormData['SHIPMENT'] as $index => $shipment)
			{
				if (isset($this->formData['SHIPMENT'][$index], $shipment['PRODUCT']))
				{
					$this->formData['SHIPMENT'][$index]['PRODUCT'] = $shipment['PRODUCT'];
				}
			}
		}
	}
}