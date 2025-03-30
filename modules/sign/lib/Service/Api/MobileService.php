<?php

namespace Bitrix\Sign\Service\Api;

use Bitrix\Main;

use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;
use Bitrix\Sign\Service;

class MobileService
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

	public function acceptSigning(Item\Api\Mobile\Signing\SignRequest $request): Item\Api\Mobile\Signing\SignResponse
	{
		$result = new Main\Result();

		if (empty($request->documentUid))
		{
			$result->addError(new Main\Error('Request: field `documentUid` is empty'));
		}

		if (empty($request->memberUid))
		{
			$result->addError(new Main\Error('Request: field `memberUid` is empty'));
		}

		if ($result->isSuccess())
		{
			$result = $this->api->post(
				"v1/b2e.document.signing.sign/$request->documentUid/$request->memberUid/",
				$this->serializer->serialize($request)
			);
		}

		$response = new Item\Api\Mobile\Signing\SignResponse();
		$response->addErrors($result->getErrors());

		return $response;
	}

	public function acceptReview(Item\Api\Mobile\Signing\ReviewRequest $request): Item\Api\Mobile\Signing\ReviewResponse
	{
		$result = new Main\Result();

		if (empty($request->documentUid))
		{
			$result->addError(new Main\Error('Request: field `documentUid` is empty'));
		}

		if (empty($request->memberUid))
		{
			$result->addError(new Main\Error('Request: field `memberUid` is empty'));
		}

		if ($result->isSuccess())
		{
			$result = $this->api->post(
				"v1/b2e.document.reviewer.accept/$request->documentUid/$request->memberUid/",
				$this->serializer->serialize($request)
			);
		}

		$response = new Item\Api\Mobile\Signing\ReviewResponse();
		$response->addErrors($result->getErrors());

		return $response;
	}

	public function refuseSigning(Item\Api\Mobile\Signing\RefuseRequest $request): Item\Api\Mobile\Signing\RefuseResponse
	{
		$result = new Main\Result();

		if (empty($request->documentUid))
		{
			$result->addError(new Main\Error('Request: field `documentUid` is empty'));
		}

		if (empty($request->memberUid))
		{
			$result->addError(new Main\Error('Request: field `memberUid` is empty'));
		}

		if ($result->isSuccess())
		{
			$result = $this->api->post(
				"v1/b2e.document.signing.refuse/$request->documentUid/$request->memberUid/",
				$this->serializer->serialize($request)
			);
		}

		$response = new Item\Api\Mobile\Signing\RefuseResponse();
		$response->addErrors($result->getErrors());

		return $response;
	}

	public function acceptConfirmation(Item\Api\Mobile\Confirmation\AcceptRequest $request): Item\Api\Mobile\Confirmation\AcceptResponse
	{
		$result = new Main\Result();

		if (empty($request->documentUid))
		{
			$result->addError(new Main\Error('Request: field `documentUid` is empty'));
		}

		if (empty($request->memberUid))
		{
			$result->addError(new Main\Error('Request: field `memberUid` is empty'));
		}

		if ($result->isSuccess())
		{
			$result = $this->api->post(
				"v1/b2e.document.member.confirmation.accept/$request->documentUid/$request->memberUid/",
				$this->serializer->serialize($request)
			);
		}

		$response = new Item\Api\Mobile\Confirmation\AcceptResponse();
		$response->addErrors($result->getErrors());

		return $response;
	}

	public function postponeConfirmation(Item\Api\Mobile\Confirmation\PostponeRequest $request): Item\Api\Mobile\Confirmation\PostponeResponse
	{
		$result = new Main\Result();

		if (empty($request->documentUid))
		{
			$result->addError(new Main\Error('Request: field `documentUid` is empty'));
		}

		if (empty($request->memberUid))
		{
			$result->addError(new Main\Error('Request: field `memberUid` is empty'));
		}

		if ($result->isSuccess())
		{
			$result = $this->api->post(
				"v1/b2e.document.member.confirmation.postpone/$request->documentUid/$request->memberUid/",
				$this->serializer->serialize($request)
			);
		}

		$response = new Item\Api\Mobile\Confirmation\PostponeResponse();
		$response->addErrors($result->getErrors());

		return $response;
	}

	public function getExternalSigningUrl(
		Item\Api\Mobile\Signing\ExternalUrlRequest $request
	): Item\Api\Mobile\Signing\ExternalUrlResponse
	{
		$response = new Item\Api\Mobile\Signing\ExternalUrlResponse();

		if (empty($request->documentUid))
		{
			$response->addError(new Main\Error('Request: field `documentUid` is empty'));
		}

		if (empty($request->memberUid))
		{
			$response->addError(new Main\Error('Request: field `memberUid` is empty'));
		}

		if (!$response->isSuccess())
		{
			return $response;
		}

		$result = $this->api->post(
			"v1/b2e.provider.signing.signer.mobile.start/$request->documentUid/$request->memberUid/",
		);

		if (!$result->isSuccess())
		{
			$response->addErrors($result->getErrors());

			return $response;
		}

		$response->url = (string)($result->getData()['url'] ?? '');
		if (empty($response->url))
		{
			$response->addError(new Main\Error('Response: url is empty'));
		}

		return $response;
	}
}
