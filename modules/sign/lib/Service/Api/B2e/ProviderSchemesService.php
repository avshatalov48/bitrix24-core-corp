<?php

namespace Bitrix\Sign\Service\Api\B2e;

use Bitrix\Sign\Service;
use Bitrix\Sign\Type;
use Bitrix\Main;

class ProviderSchemesService
{
	public function __construct(
		private Service\ApiService $api,
	) {}

	public function loadAvailableSchemes(string $companyUid): Main\Result
	{
		$apiResult = $this->api->get('v1/b2e.provider.getAvailableSchemes?' . http_build_query([
			'uid' => $companyUid
		]));

		if (!$apiResult->isSuccess())
		{
			return (new Main\Result())->addErrors($apiResult->getErrors());
		}

		$decodedSchemes = (array)($apiResult->getData()['schemes'] ?? []);
		$schemes = [];
		foreach ($decodedSchemes as $scheme)
		{
			if (!Type\Document\SchemeType::isValid($scheme))
			{
				continue;
			}

			$schemes[] = $scheme;
		}

		return (new Main\Result())
			->setData($schemes)
		;
	}
}