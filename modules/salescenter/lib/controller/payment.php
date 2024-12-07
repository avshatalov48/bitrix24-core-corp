<?php

namespace Bitrix\SalesCenter\Controller;


use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Sale\Registry;
use Bitrix\SalesCenter\Integration\Bitrix24Manager;
use Bitrix\SalesCenter\Integration\LandingManager;
use Bitrix\Sale;

class Payment extends Base
{
	public function getPrimaryAutoWiredParameter()
	{
		return new ExactParameter(
			Sale\Payment::class,
			'payment',
			function($className, $id) {

				if (!is_scalar($id))
				{
					$this->addError(new Error('Parameter id must be integer'));
					return false;
				}

				$id = (int)$id;
				$payment = Sale\Repository\PaymentRepository::getInstance()->getById($id);

				if ($payment)
				{
					return $payment;
				}

				$this->addError(new Error('payment is not exists', 200640400001));

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

	public function getPublicUrlAction(Sale\Payment $payment, array $options = []): ?array
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

				return null;
			}
		}
		else
		{
			if (
				LandingManager::getInstance()->getConnectedSiteId() > 0
				&& !LandingManager::getInstance()->isPhoneConfirmed()
			)
			{
				return [
					'connectedSiteId' => LandingManager::getInstance()->getConnectedSiteId(),
					'isPhoneConfirmed' => LandingManager::getInstance()->isPhoneConfirmed(),
				];
			}

			$this->addError(new Error('Public url is not available'));

			return null;
		}

		return [
			'payment' => [
				'url' => $urlInfo['url'],
				'shortUrl' => $urlInfo['shortUrl'],
				'qr' => base64_encode(
					(new Sale\PaySystem\BarcodeGenerator($options['qr'] ?? null))
						->generate($urlInfo['shortUrl'])
				),
			]
		];
	}
}
