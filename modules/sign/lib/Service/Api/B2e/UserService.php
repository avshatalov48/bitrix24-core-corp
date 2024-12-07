<?php

namespace Bitrix\Sign\Service\Api\B2e;

use Bitrix\Main\Result;
use Bitrix\Sign\Item\B2e\ServiceUser;
use Bitrix\Sign\Repository\ServiceUserRepository;
use Bitrix\Sign\Service\ApiService;

final class UserService
{
	public function __construct(
		private ApiService $api,
		private ServiceUserRepository $repository,
	) {}

	public function register(int $userId, string $name): Result
	{
		$apiResult = $this->api->post('v1/b2e.user.register', [
			'userId' => $userId,
			'name' => $name,
		]);
		if (!$apiResult->isSuccess())
		{
			return (new Result())->addErrors($apiResult->getErrors());
		}

		$data = $apiResult->getData();
		$uid = $data['uid'] ?? '';
		$addResult = $this->repository->add(new ServiceUser($userId, $uid));
		if (!$addResult->isSuccess())
		{
			return (new Result())->addErrors($addResult->getErrors());
		}

		return (new Result())->setData(['uid' => $uid]);
	}

}
