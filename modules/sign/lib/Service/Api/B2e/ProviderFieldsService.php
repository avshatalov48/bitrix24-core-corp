<?php

namespace Bitrix\Sign\Service\Api\B2e;

use Bitrix\Main\Result;
use Bitrix\Sign\Item\B2e\RequiredFieldsCollection;
use Bitrix\Sign\Service\ApiService;
use Bitrix\Sign\Service\Result\Sign\Block\B2eRequiredFieldsResult;

class ProviderFieldsService
{
	public function __construct(private readonly ApiService $api) {}

	public function loadRequiredFields(string $companyUid): Result
	{
		$apiResult = $this->api->get('v1/b2e.provider.getFields?' . http_build_query([
				'uid' => $companyUid
			]));
		if (!$apiResult->isSuccess())
		{
			return (new Result())->addErrors($apiResult->getErrors());
		}

		$decodedFields = (array)($apiResult->getData()['fields'] ?? []);
		$fields = RequiredFieldsCollection::createFromJsonArray($decodedFields);

		return new B2eRequiredFieldsResult($fields);
	}

}
