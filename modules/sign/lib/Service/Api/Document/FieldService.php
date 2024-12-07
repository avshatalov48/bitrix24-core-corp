<?php

namespace Bitrix\Sign\Service\Api\Document;

use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;
use Bitrix\Sign\Service;

use Bitrix\Main;

class FieldService
{
	private Service\ApiService $api;
	private Contract\Serializer $serializer;

	public function __construct(
		Service\ApiService $api,
		Contract\Serializer $serializer
	)
	{
		$this->api = $api;
		$this->serializer = $serializer;
	}

	public function fill(Item\Api\Document\Field\FillRequest $request): Item\Api\Document\Field\FillResponse
	{
		$result = new Main\Result();
		if ($request->documentUid === '')
		{
			$result->addError(new Main\Error('Request: field `documentUid` is empty'));
		}

		if ($result->isSuccess())
		{
			$result = $this->api->post(
				"v1/document.field.fill/$request->documentUid/",
				$this->serializer->serialize($request)
			);
		}

		$response = new Item\Api\Document\Field\FillResponse();
		$response->addErrors($result->getErrors());
		return $response;
	}
}