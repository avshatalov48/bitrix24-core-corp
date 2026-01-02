<?php

namespace Bitrix\Baas\UseCase\Proxy;

use \Bitrix\Main;
use \Bitrix\Baas;

class BalanceDataConvert
{
	protected Baas\Entity\Service $service;
	protected array $rawData;

	public function __construct(
		protected Request\BalanceDataConvertRequest $request,
	)
	{
		$this->service = $request->service;
		$this->rawData = $request->rawData;
	}

	protected function run(): Response\BalanceDataConvertResult|Main\Result
	{
		$rawData = $this->rawData;
		if (
			isset($rawData['stateNumber'])
			&& isset($rawData['serviceValue'])
			&& isset($rawData['serviceInPurchasedPackages'])
		)
		{
			$affectedPackages = Baas\Model\ServiceInPurchasedPackageTable::createCollection();
			foreach (
				$rawData['serviceInPurchasedPackages']
				as ['PURCHASED_PACKAGE_CODE' => $purchasedPackageCode, 'CURRENT_VALUE' => $currentValue]
			)
			{
				$affectedPackages->add(
					Baas\Model\ServiceInPurchasedPackageTable::createObject()
						->setPurchasedPackageCode($purchasedPackageCode)
						->setServiceCode($this->service->getCode())
						->setCurrentValue($currentValue),
				);
			}

			return new Response\BalanceDataConvertResult(
				stateNumber: $rawData['stateNumber'],
				service: Baas\Model\ServiceTable::createObject()
					->setCode($this->service->getCode())
					->setCurrentValue($rawData['serviceValue']),
				serviceInPurchasedPackages: $affectedPackages,
			);
		}

		$exception = new Exception\ProxyRespondsInWrongFormatException([
			'stateNumber', 'serviceValue', 'serviceInPurchasedPackages',
		]);

		return (new Main\Result())->addError(
			new Main\Error($exception->getMessage(), $exception->getCode(), $exception->getCustomData()),
		);
	}

	public function __invoke(): Main\Result
	{
		return $this->run();
	}
}
