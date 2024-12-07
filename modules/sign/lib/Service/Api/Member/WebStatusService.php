<?php

namespace Bitrix\Sign\Service\Api\Member;

use Bitrix\Sign\Item\Api\Member\WebStatusRequest;
use Bitrix\Sign\Item\Api\Member\WebStatusResponse;
use Bitrix\Sign\Service;
use Bitrix\Main;

class WebStatusService
{
	public function __construct(private readonly Service\ApiService $api) {}

	public function get(WebStatusRequest $request): WebStatusResponse
	{
		$response = new WebStatusResponse();

		$this->validateConfigureRequest($request, $response);
		if (!$response->isSuccess())
		{
			return $response;
		}

		$result = $this->api->get('v1/document.member.status.get/'. $request->documentUid . '/' . $request->memberUid . '/');

		if ($result->isSuccess())
		{
			$data = $result->getData();
			$response->status = $data['status'];
		}
		else
		{
			$response->addErrors($result->getErrors());
		}

		return $response;
	}

	private function validateConfigureRequest(WebStatusRequest $request, WebStatusResponse $response): void
	{
		if (empty($request->documentUid))
		{
			$response->addError(new Main\Error('Request: field `documentUid` is empty'));
		}

		if (empty($request->memberUid))
		{
			$response->addError(new Main\Error('Request: field `memberUid` is empty'));
		}
	}
}