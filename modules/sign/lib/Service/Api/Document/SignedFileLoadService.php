<?php

namespace Bitrix\Sign\Service\Api\Document;

use Bitrix\Sign\Item;
use Bitrix\Sign\Service;
use Bitrix\Main;

class SignedFileLoadService
{
	private Service\ApiService $api;

	public function __construct(
		Service\ApiService $api,
	)
	{
		$this->api = $api;
	}

	public function load(Item\Api\Document\SignedFileLoadRequest $request): Item\Api\Document\SignedFileLoadResponse
	{
		$result = new Main\Result();
		if ($request->documentId === '')
		{
			$result->addError(new Main\Error('Request: field `documentId` is empty'));
		}

		if ($result->isSuccess())
		{
			$result = $this->api->get(
				$request->memberId
					? "v1/document.signedfile.load/$request->documentId/$request->memberId/"
					: "v1/document.signedfile.load/$request->documentId/",
			);
		}

		$data = $result->getData();
		$response = new Item\Api\Document\SignedFileLoadResponse(
			(bool) ($data['ready'] ?? false),
			!empty($data['file']['url'])
				? new Item\Api\Property\Response\Page\List\Page($data['file']['url'])
				: null
		);

		$response->addErrors($result->getErrors());

		return $response;
	}
}