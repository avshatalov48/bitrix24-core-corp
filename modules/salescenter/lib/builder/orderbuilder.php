<?php

namespace Bitrix\Salescenter\Builder;

use Bitrix\Main;
use Bitrix\Sale;
use Bitrix\Crm\Order;
use Bitrix\SalesCenter\Integration;

class OrderBuilder extends Order\OrderBuilderCrm
{
	protected function prepareFields(array $fields)
	{
		$fields = parent::prepareFields($fields);

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

		if (!isset($fields['USER_ID']))
		{
			$fields['USER_ID'] = (int)\CSaleUser::GetAnonymousUserID();
		}

		if (!isset($fields['TRADING_PLATFORM']))
		{
			if (Integration\LandingManager::getInstance()->isSiteExists())
			{
				$connectedSiteId = Integration\LandingManager::getInstance()->getConnectedSiteId();
				$fields['TRADING_PLATFORM'] = Sale\TradingPlatform\Landing\Landing::getCodeBySiteId(
					$connectedSiteId
				);
			}
		}

		return $fields;
	}

	protected function setDealBinding()
	{
		$dealId = $this->formData['DEAL_ID'] ?? 0;

		if ((int)$dealId === 0)
		{
			$selector = Integration\SaleManager::getActualEntitySelector($this->order);
			$dealId = (int)$selector->search()->getDealId();

			if ($dealId <= 0)
			{
				$dealData = Order\DealBinding::getList([
					'select' => ['DEAL_ID'],
					'filter' => [
						'ORDER.USER_ID' => $this->formData['USER_ID'],
						'DEAL.CLOSED' => 'N'
					],
					'limit' => 1,
					'order' => ['DEAL_ID' => 'DESC']
				])->fetch();

				$dealId = $dealData['DEAL_ID'] ?? 0;
			}

			$this->formData['DEAL_ID'] = (int)$dealId;
		}

		return parent::setDealBinding();
	}

	public function buildPayments()
	{
		if (isset($this->formData["PAYMENT"]))
		{
			parent::buildPayments();
		}
		else
		{
			$fields = [
				'SUM' => $this->order->getPrice()
			];
			$payment = $this->createEmptyPayment();

			$paySystemList = Sale\PaySystem\Manager::getListWithRestrictions($payment);

			$selectedPaySystem = null;
			$firstPaySystemInList = null;
			foreach ($paySystemList as $paySystem)
			{
				if (
					isset($this->formData['I_MESSAGE'])
					&&
					$this->formData['I_MESSAGE']
					&&
					Integration\SaleManager::getInstance()->isApplePayPayment($paySystem))
				{
					$selectedPaySystem = $paySystem;
					break;
				}

				if ($paySystem['ACTION_FILE'] === 'cash')
				{
					$selectedPaySystem = $paySystem;
					break;
				}

				if (!$firstPaySystemInList
					&&
					(int)$paySystem['ID'] !== (int)Sale\PaySystem\Manager::getInnerPaySystemId())
				{
					$firstPaySystemInList = $paySystem;
				}
			}

			if (!$selectedPaySystem)
			{
				$selectedPaySystem = $firstPaySystemInList;
			}

			if ($selectedPaySystem)
			{
				if (
					isset($this->formData['I_MESSAGE'])
					&&
					$this->formData['I_MESSAGE']
					&& !Integration\SaleManager::getInstance()->isApplePayPayment($selectedPaySystem)
				)
				{
					$this->errorsContainer->addError(
						new Main\Error("Apple Pay not found")
					);
				}

				$fields['PAY_SYSTEM_ID'] = $selectedPaySystem['ID'];
				$fields['PAY_SYSTEM_NAME'] = $selectedPaySystem['NAME'];
			}

			$payment->setFields($fields);
		}

		return $this;
	}

}