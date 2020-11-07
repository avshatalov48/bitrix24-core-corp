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

			$applePayPaySystem = null;
			$cashPaySystem = null;
			$firstPaySystemInList = null;
			foreach ($paySystemList as $paySystem)
			{
				if ($this->isAllowApplePay($paySystem))
				{
					$applePayPaySystem = $paySystem;
				}

				if ($paySystem['ACTION_FILE'] === 'cash')
				{
					$cashPaySystem = $paySystem;
				}

				if (!$firstPaySystemInList && (int)$paySystem['ID'] !== (int)Sale\PaySystem\Manager::getInnerPaySystemId())
				{
					$firstPaySystemInList = $paySystem;
				}
			}

			if ($applePayPaySystem)
			{
				$selectedPaySystem = $applePayPaySystem;
			}
			elseif ($cashPaySystem)
			{
				$selectedPaySystem = $cashPaySystem;
			}
			else
			{
				$selectedPaySystem = $firstPaySystemInList;
			}

			if ($selectedPaySystem)
			{
				$fields['PAY_SYSTEM_ID'] = $selectedPaySystem['ID'];
				$fields['PAY_SYSTEM_NAME'] = $selectedPaySystem['NAME'];
			}

			$payment->setFields($fields);
		}

		return $this;
	}

	/**
	 * @param array $paySystem
	 * @return bool
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\LoaderException
	 */
	private function isAllowApplePay(array $paySystem): bool
	{
		return isset($this->formData['CONNECTOR'])
			&& $this->formData['CONNECTOR'] === 'imessage'
			&& Integration\SaleManager::getInstance()->isApplePayPayment($paySystem);
	}
}