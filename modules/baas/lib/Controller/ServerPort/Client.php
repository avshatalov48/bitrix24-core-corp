<?php

namespace Bitrix\Baas\Controller\ServerPort;

use Bitrix\Main;
use Bitrix\Baas;
use Bitrix\Main\Request;

class Client extends Main\Engine\Controller
{
	protected Baas\Service\BillingService $billingService;
	protected Baas\Baas $baasService;
	protected Baas\Service\PurchaseService $purchaseService;

	public function __construct(Request $request = null)
	{
		parent::__construct($request);
		$this->billingService = Baas\Service\BillingService::getInstance();
		$this->purchaseService = Baas\Service\PurchaseService::getInstance();
		$this->baasService = Baas\Baas::getInstance();
	}

	protected function getDefaultPreFilters(): array
	{
		$secret = $this->billingService->getHostSecret();
		if (empty($secret))
		{
			return parent::getDefaultPreFilters();
		}

		return [
			new Baas\Controller\ActionFilter\BaasControllerAuthentication($secret),
		];
	}

	public function pingAction(): Main\Engine\Response\Json
	{
		Baas\Internal\Diag\Logger::getInstance()->notice('The host has been pinged');

		return Main\Engine\Response\AjaxJson::createSuccess('OK');
	}

	public function notifyAboutPurchasedPackageAction(): Main\Engine\Response\Json
	{
		$packageCode = (string)$this->request->getJsonList()->get('packageCode');
		$purchaseCode = (string)$this->request->getJsonList()->get('purchaseCode');

		$balanceData = $this->request->getJsonList()->get('balanceData');

		if (empty($balanceData))
		{
			$result = $this->billingService->synchronizeWithBilling();
		}
		else
		{
			$result = $this->billingService->applyBalance($balanceData);
		}

		Baas\Internal\Diag\Logger::getInstance()->notice(
			'The host has been notified about purchased package via the server port',
			[
				'packageCode' => $packageCode,
				'purchaseCode' => $purchaseCode,
			],
		);

		if ($result->isSuccess())
		{
			$this->purchaseService->notifyAboutPurchase($packageCode, $purchaseCode);

			return Main\Engine\Response\AjaxJson::createSuccess(['OK']);
		}

		return Main\Engine\Response\AjaxJson::createError($result->getErrorCollection());
	}

	public function broadcastServiceBalanceAction(): Main\Engine\Response\Json
	{
		$jsonList = $this->request->getJsonList();

		$serviceCode = (string) $jsonList->get('serviceCode');
		$stateNumber = (int) $jsonList->get('stateNumber');

		if (!empty($serviceCode) && $stateNumber > 0)
		{
			$this->baasService->getService($serviceCode)->applyBillingState($jsonList->toArray());
			Baas\Internal\Diag\Logger::getInstance()->notice(
				'Broadcast: the host has been notified about the current balance'
			);
		}
		else
		{
			Baas\Internal\Diag\Logger::getInstance()->warning(
				'Broadcast: the host has got an empty balance data',
			);
		}

		return Main\Engine\Response\AjaxJson::createSuccess(['OK']);
	}
}
