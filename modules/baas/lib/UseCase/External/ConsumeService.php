<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External;

use \Bitrix\Main;
use \Bitrix\Baas;

class ConsumeService extends BaseClientAction
{
	protected Baas\Entity\Service $service;
	protected int $units;
	protected bool $force;
	protected ?array $attributes;

	public function __construct(
		Baas\UseCase\External\Request\ConsumeServiceRequest $request,
	)
	{
		parent::__construct($request);
		$this->service = $request->service;
		$this->units = $request->units;
		$this->force = $request->force;
		$this->attributes = $request->attributes;
	}

	/**
	 * @return Response\ConsumeServiceResult
	 * @throws Exception\BaasControllerRespondsInWrongFormatException
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	protected function run(): Response\ConsumeServiceResult
	{
		$data = [
			'serviceCode' => $this->service->getCode(),
			'units' => $this->units,
			'force' => $this->force,
			'attributes' => $this->attributes ?? [],
		];

		$result = $this
			->getSender()
			->performRequest('consume', $data)
		;

		$res = $result->getData();
		if (!isset($result->getData()['serviceValue']))
		{
			throw new Exception\BaasControllerRespondsInWrongFormatException(['serviceValue']);
		}
		if (!isset($result->getData()['serviceInPurchasedPackages']))
		{
			throw new Exception\BaasControllerRespondsInWrongFormatException(['serviceInPurchasedPackages']);
		}

		$affectedPackages = Baas\Model\ServiceInPurchasedPackageTable::createCollection();
		foreach ($res['serviceInPurchasedPackages'] as ['PURCHASED_PACKAGE_CODE' => $purchasedPackageCode, 'CURRENT_VALUE' => $currentValue])
		{
			$affectedPackages->add(
				Baas\Model\ServiceInPurchasedPackageTable::createObject()
					->setPurchasedPackageCode($purchasedPackageCode)
					->setServiceCode($this->service->getCode())
					->setCurrentValue($currentValue),
			);
		}

		return (new Response\ConsumeServiceResult(
			stateNumber: $res['stateNumber'],
			consumptionId: $res['consumptionId'],
			service: Baas\Model\ServiceTable::createObject()
				->setCode($this->service->getCode())
				->setCurrentValue($res['serviceValue'])
			,
			serviceInPurchasedPackages: $affectedPackages,
		))->setData($res);
	}
}
