<?php

namespace Bitrix\Sign\Service\Api\Client;

use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;
use Bitrix\Sign\Service;
use Bitrix\Main;

class Domain
{
	public function __construct(
		private Service\ApiService $api,
		private Contract\Serializer $serializer
	)
	{}

	public function change(Item\Api\Client\DomainRequest $request): Item\Api\Client\DomainResponse
	{
		$result = new Main\Result();
		if (empty($request->domain))
		{
			$result->addError(new Main\Error('Request: property `domain` is empty'));
		}

		if ($result->isSuccess())
		{
			$result = $this->api->post(
				"v1/client.domain.set/",
				$this->serializer->serialize($request)
			);
		}

		$response = new Item\Api\Client\DomainResponse();
		$response->addErrors($result->getErrors());
		return $response;
	}
}
