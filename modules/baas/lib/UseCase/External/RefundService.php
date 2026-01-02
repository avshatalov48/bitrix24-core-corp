<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External;

use \Bitrix\Baas;

class RefundService extends BaseClientAction
{
	protected Baas\Entity\Service $service;
	protected string $consumptionId;
	protected ?array $attributes;

	public function __construct(
		Baas\UseCase\External\Request\RefundServiceRequest $request,
	)
	{
		parent::__construct($request);
		$this->service = $request->service;
		$this->consumptionId = $request->consumptionId;
		$this->attributes = $request->attributes;
	}

	protected function run(): Response\RefundServiceResult
	{
		$data = [
			'serviceCode' => $this->service->getCode(),
			'consumptionId' => $this->consumptionId,
			'attributes' => $this->attributes ?? [],
		];

		$result = $this
			->getSender()
			->performRequest('refund', $data)
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

		return (new Response\RefundServiceResult(
			stateNumber: $res['stateNumber'],
			consumptionId: $this->consumptionId,
			service: Baas\Model\ServiceTable::createObject()
				->setCode($this->service->getCode())
				->setCurrentValue($res['serviceValue'])
			,
			serviceInPurchasedPackages: $affectedPackages,
		))->setData($res);
	}
}
