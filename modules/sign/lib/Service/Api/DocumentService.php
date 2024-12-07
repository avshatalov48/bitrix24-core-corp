<?php

namespace Bitrix\Sign\Service\Api;

use Bitrix\Main;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;
use Bitrix\Sign\Service;
use Bitrix\Sign\Type;

class DocumentService
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

	public function register(Item\Api\Document\RegisterRequest $request): Item\Api\Document\RegisterResponse
	{
		$result = new Main\Result();
		if ($request->lang === '')
		{
			$result->addError(new Main\Error('Request: field `lang` is empty'));
		}
		if (!in_array($request->scenario, Type\DocumentScenario::getAll(), true))
		{
			$result->addError(new Main\Error('Request: field value `scenario` is invalid'));
		}

		if ($result->isSuccess())
		{
			$result = $this->api->post(
				'v1/document.register',
				$this->serializer->serialize($request)
			);
		}
		$data = $result->getData();
		$response = new Item\Api\Document\RegisterResponse(
			(string) ($data['id'] ?? '')
		);

		$response->addErrors($result->getErrors());
		if (!$response->isSuccess())
		{
			return $response;
		}

		if ($response->uid === '')
		{
			return $response->addError(new Main\Error('Empty document id'));
		}

		return $response;
	}

	public function upload(Item\Api\Document\UploadRequest $request): Item\Api\Document\UploadResponse
	{
		$result = new Main\Result();
		if ($request->uid === '')
		{
			$result->addError(new Main\Error('Request: field `uid` is empty'));
		}

		if ($result->isSuccess())
		{
			$result = $this->api->post(
				"v1/document.upload/$request->uid/",
				$this->serializer->serialize($request)
			);
		}
		$response = new Item\Api\Document\UploadResponse();

		return $response->addErrors($result->getErrors());
	}

	public function reuse(Item\Api\Document\ReuseRequest $request): Item\Api\Document\ReuseResponse
	{
		$result = new Main\Result();
		if ($request->documentUid === '')
		{
			$result->addError(new Main\Error('Request: field `documentUid` is empty'));
		}

		if ($request->sourceDocumentUid === '')
		{
			$result->addError(new Main\Error('Request: field `sourceDocumentUid` is empty'));
		}

		if ($result->isSuccess())
		{
			$result = $this->api->post(
				"v1/document.reuse/$request->documentUid/",
				$this->serializer->serialize($request)
			);
		}
		$response = new Item\Api\Document\ReuseResponse();

		return $response->addErrors($result->getErrors());
	}
}
