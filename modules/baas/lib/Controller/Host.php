<?php

declare(strict_types=1);

namespace Bitrix\Baas\Controller;

use \Bitrix\Main;
use \Bitrix\Baas;
use Bitrix\Main\Request;

class Host extends Main\Engine\Controller
{
	protected Baas\Service\BillingService $billingService;
	protected Baas\Service\ServiceService $serviceService;

	public function __construct(Request $request = null)
	{
		parent::__construct($request);

		$this->billingService = Baas\Service\BillingService::getInstance();
		$this->serviceService = Baas\Baas::getInstance()->getServiceManager();
	}

	public function registerAction(): Main\Engine\Response\Json
	{
		return $this->fullFill(fn() => $this->billingService->register(true));
	}

	public function refreshAction(): Main\Engine\Response\Json
	{
		return $this->fullFill(function() {
			$result = $this->billingService->synchronizeWithBilling();

			if ($result->isSuccess())
			{
				$services = [];
				foreach ($this->serviceService->getAll() as $service)
				{
					$services[$service->getCode()] = $service->jsonSerialize();
				}
				$result->setData(['services' => $services]);
			}

			return $result;
		});
	}

	public function getBaasStatusAction(): Main\Engine\Response\Json
	{
		return $this->fullFill(function() {
			/** @var Baas\UseCase\External\Response\GetBaasSalesStatusResult $result */
			$result = $this->billingService->getBaasSalesStatus();

			return (new Main\Result())->setData([
				'code' => $result->statusCode,
				'description' => $result->statusDescription,
			]);
		});
	}

	public function getPurchaseReportAction(
		string $packageCode,
		$purchaseCode,
		string $serviceCode,
	): Main\Engine\Response\Json
	{
		return $this->fullFill(function() use ($packageCode, $purchaseCode, $serviceCode) {
			$result = $this->billingService->getPurchasedPackageReport(
				(string) $packageCode,
				(string) $purchaseCode,
				(string) $serviceCode,
			);

			return (new Main\Result())->setData($result->getData());
		});
	}

	private function fullFill(callable $callback): Main\Engine\Response\Json
	{
		try
		{
			$httpClient = null;
			Main\EventManager::getInstance()->addEventHandler(
				'baas',
				'onServerInfoReceived',
				function(Main\Event $event) use (&$httpClient)
				{
					/** @var Main\Web\HttpClient $httpClient */
					$httpClient = $event->getParameter('httpClient');
				},
			);
			$result = $callback();
		}
		catch (Baas\UseCase\External\Exception\BaasControllerException\ExceptionFromError $e)
		{
			$result = (new Main\Result())->addError(
				new Main\Error(
					$e->getMessage(),
					$e->getCode(),
					$e->getCustomData(),
				),
			);
		}
		catch (Baas\UseCase\BaasException $e)
		{
			$result = (new Main\Result())->addError(
				new Main\Error(
					$e->getMessage(),
					$e->getCode(),
				),
			);
		}

		if ($result->isSuccess())
		{
			return Main\Engine\Response\AjaxJson::createSuccess($result->getData());
		}

		return Main\Engine\Response\AjaxJson::createError(
			$result->getErrorCollection(),
			['httpResponse' => [
				'status' => $httpClient?->getStatus(),
				// 'headers' => $httpClient?->getHeaders()->toArray(),
				'body' => $httpClient?->getResult(),
			]],
		);
	}
}
