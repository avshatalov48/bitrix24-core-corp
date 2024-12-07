<?php

namespace Bitrix\Sign\Service\Api\Document;

use Bitrix\Main;

use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;
use Bitrix\Sign\Service;

class SigningService
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

	public function configure(Item\Api\Document\Signing\ConfigureRequest $request): Item\Api\Document\Signing\ConfigureResponse
	{
		$result = $this->validateConfigureRequest($request);

		if ($result->isSuccess())
		{
			$result = $this->api->post(
				"v1/document.signing.configure/$request->documentUid/",
				$this->serializer->serialize($request)
			);
		}

		$data = $result->getData();

		/*  @todo validate data */
		$members = new Item\Api\Property\Response\Signing\Configure\MemberCollection();
		foreach(($data['members']) ?? [] as $member)
		{
			$member = new Item\Api\Property\Response\Signing\Configure\Member(
				(string)($member['key'] ?? ''),
				(string)($member['id'] ?? ''),
			);
			$members->addItem(
				$member
			);
		}
		$response = new Item\Api\Document\Signing\ConfigureResponse($members);
		$response->addErrors($result->getErrors());
		return $response;
	}

	public function start(Item\Api\Document\Signing\StartRequest $request): Item\Api\Document\Signing\StartResponse
	{
		$result = new Main\Result();
		if (empty($request->documentUid))
		{
			$result->addError(new Main\Error('Request: field `documentUid` is empty'));
		}

		if ($result->isSuccess())
		{
			$result = $this->api->post(
				"v1/document.signing.start/$request->documentUid/",
				$this->serializer->serialize($request)
			);
		}

		$response = new Item\Api\Document\Signing\StartResponse();
		$response->addErrors($result->getErrors());

		return $response;
	}

	public function stop(Item\Api\Document\Signing\StopRequest $request): Item\Api\Document\Signing\StopResponse
	{
		$result = new Main\Result();
		if (empty($request->documentUid))
		{
			$result->addError(new Main\Error('Request: field `documentUid` is empty'));
		}

		if ($result->isSuccess())
		{
			$result = $this->api->post(
				"v1/document.signing.stop/$request->documentUid/",
				$this->serializer->serialize($request)
			);
		}

		$response = new Item\Api\Document\Signing\StopResponse();
		$response->addErrors($result->getErrors());

		return $response;
	}

	public function sendInvite(Item\Api\Document\Signing\SendInviteRequest $request): Item\Api\Document\Signing\SendInviteResponse
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
				"v1/document.signing.sendInvite/$request->documentUid/$request->memberUid/",
				$this->serializer->serialize($request)
			);
		}

		$response = new Item\Api\Document\Signing\SendInviteResponse();
		$response->addErrors($result->getErrors());

		return $response;
	}

	public function resendMessage(Item\Api\Document\Signing\ResendMessageRequest $request): Item\Api\Document\Signing\ResendMessageResponse
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
				"v1/document.signing.resendMessage/$request->documentUid/$request->memberUid/",
				$this->serializer->serialize($request)
			);
		}

		$response = new Item\Api\Document\Signing\ResendMessageResponse();
		$response->addErrors($result->getErrors());

		return $response;
	}

	private function validateConfigureRequest(Item\Api\Document\Signing\ConfigureRequest $request): Main\Result
	{
		$result = new Main\Result();

		if (empty($request->documentUid))
		{
			$result->addError(new Main\Error('Request: field `documentUid` is empty'));
		}
		if (empty($request->scenario))
		{
			$result->addError(new Main\Error('Request: field `scenario` is empty'));
		}
		if (empty($request->owner->name))
		{
			$result->addError(new Main\Error('Request: owner: field `name` is empty'));
		}

		return $result;
	}
}
