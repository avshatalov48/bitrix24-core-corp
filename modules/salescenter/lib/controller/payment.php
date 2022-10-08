<?php

namespace Bitrix\SalesCenter\Controller;


use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Sale\Registry;
use Bitrix\SalesCenter\Integration\LandingManager;

class Payment extends Base
{
	public function getPrimaryAutoWiredParameter()
	{
		return new ExactParameter(
			\Bitrix\Sale\Payment::class,
			'payment',
			function($className, $id) {

				if (!is_scalar($id))
				{
					$this->addError(new Error('Parameter id must be integer'));
					return false;
				}

				$id = (int)$id;

				$registry = Registry::getInstance(Registry::REGISTRY_TYPE_ORDER);

				/** @var \Bitrix\Sale\Payment $paymentClass */
				$paymentClass = $registry->getPaymentClassName();

				$r = $paymentClass::getList([
					'select' => ['ORDER_ID'],
					'filter' => ['=ID' => $id]
				]);

				if ($row = $r->fetch())
				{
					/** @var \Bitrix\Sale\Order $orderClass */
					$orderClass = $registry->getOrderClassName();

					$order = $orderClass::load($row['ORDER_ID']);
					$payment = $order->getPaymentCollection()->getItemById($id);
					if ($payment)
					{
						return $payment;
					}
				}
				else
				{
					$this->addError(new Error('payment is not exists', 200640400001));
				}
				return null;
			}
		);
	}

	protected function processBeforeAction(Action $action)
	{
		if (!$this->checkModules())
		{
			return false;
		}

		\CFile::DisableJSFunction(true);

		return parent::processBeforeAction($action);
	}

	private function checkModules()
	{
		if (!Loader::includeModule('crm'))
		{
			$this->addError(new Error('module "crm" is not installed.'));
			return false;
		}
		if (!Loader::includeModule('catalog'))
		{
			$this->addError(new Error('module "catalog" is not installed.'));
			return false;
		}
		if (!Loader::includeModule('sale'))
		{
			$this->addError(new Error('module "sale" is not installed.'));
			return false;
		}

		return true;
	}

	/**
	 * @param \Bitrix\Sale\Payment $payment
	 * @return array[]|false
	 */
	public function getPublicUrlAction(\Bitrix\Sale\Payment $payment)
	{
		if (LandingManager::getInstance()->isOrderPublicUrlAvailable())
		{
			$urlInfo = LandingManager::getInstance()->getUrlInfoByOrder(
				$payment->getOrder(),
				['paymentId' => $payment->getId()]
			);

			if (is_array($urlInfo) === false)
			{
				$this->addError(new Error('Error retrieving url info'));
				return false;
			}
		}
		else
		{
			$this->addError(new Error('Public url is not available'));
			return false;
		}

		return [
				'order' => [
					'url' => $urlInfo['url'],
					'shortUrl' => $urlInfo['shortUrl'],
				]
		];
	}
}