<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External;

use \Bitrix\Main;
use \Bitrix\Baas;

class GetPurchaseReport extends BaseClientAction
{
	protected string $packageCode;
	protected string $purchaseCode;
	protected ?string $serviceCode;

	public function __construct(
		Baas\UseCase\External\Request\GetPurchaseReportRequest $request,
	)
	{
		parent::__construct($request);
		$this->packageCode = $request->packageCode;
		$this->purchaseCode = $request->purchaseCode;
		$this->serviceCode = $request->serviceCode;
	}

	/**
	 * @return Response\ConsumeServiceResult
	 * @throws Exception\BaasControllerRespondsInWrongFormatException
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	protected function run(): Response\GetPurchaseReportResult
	{
		$request = [
			'packageCode' => $this->packageCode,
			'purchaseCode' => $this->purchaseCode,
			'serviceCode' => $this->serviceCode,
		];

		$response = $this
			->getSender()
			->performRequest('getPurchaseReport', $request)
			?->getData()
		;

		$result = [];
		if (is_array($response)
			&& !empty($response['data'])
			&& !empty($response['time'])
		)
		{
			$headers = array_shift($response['data']);
			$timeParams = $response['time'];

			foreach ($response['data'] as $item)
			{
				$row = [];
				foreach ($headers as $key => $header)
				{
					$value = match ($header) {
						'datetime' => isset($item[$key]) ?
							(new Main\Type\DateTime($item[$key], $timeParams['format'], new \DateTimeZone($timeParams['timezone'])))->format(
								Baas\Contract\DateTimeFormat::LOCAL_DATETIME->value
							): 'not set'
						,
						'value' => (int) $item[$key],
						default => null,
					};

					if ($value !== null)
					{
						$row[$header] = $value;
					}
				}
				if (!empty($row))
				{
					$result[] = $row;
				}
			}
		}

		return (new Response\GetPurchaseReportResult())->setData($result);
	}
}
