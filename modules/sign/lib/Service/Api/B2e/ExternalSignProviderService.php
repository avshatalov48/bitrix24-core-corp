<?php

namespace Bitrix\Sign\Service\Api\B2e;

use Bitrix\Main\Result;
use Bitrix\Sign\Item\Api\ExternalSignProvider\FieldsRequest;
use Bitrix\Sign\Service;

final class ExternalSignProviderService
{
	public function __construct(
		private readonly Service\ApiService $api,
	)
	{
	}

	public function add(FieldsRequest $request): Result
	{
		return  $this->api->post(
			'v1/b2e.external-sign-provider.add',
			$request->toArray()
		);
	}

	public function edit(int $id, FieldsRequest $request): Result
	{
		return $this->api->post(
			'v1/b2e.external-sign-provider.edit',
			['id' => $id] + $request->toArray()
		);
	}

	public function delete(int $id): Result
	{
		return $this->api->post(
			'v1/b2e.external-sign-provider.delete', ['id' => $id]
		);
	}

	public function list(int $limit, int $offset = 0): Result
	{
		return $this->api->get(
			'v1/b2e.external-sign-provider.list', ['limit' => $limit, 'offset' => $offset]
		);
	}

}