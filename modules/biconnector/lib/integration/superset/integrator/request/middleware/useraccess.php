<?php

namespace Bitrix\BIConnector\Integration\Superset\Integrator\Request\Middleware;

use Bitrix\BIConnector\Integration\Superset\Integrator\Integrator;
use Bitrix\BIConnector\Integration\Superset\Integrator\Request\IntegratorRequest;
use Bitrix\BIConnector\Integration\Superset\Integrator\Request\IntegratorResponse;
use Bitrix\BIConnector\Integration\Superset\Repository\SupersetUserRepository;
use Bitrix\BIConnector\Integration\Superset\SupersetController;
use Bitrix\BIConnector\Integration\Superset\SupersetInitializer;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;

final class UserAccess extends Base
{
	private const ID = "USER_ACCESS";

	public static function getMiddlewareId(): string
	{
		return self::ID;
	}

	public function beforeRequest(IntegratorRequest $request): ?IntegratorResponse
	{
		$user = $request->getUser();

		if (!$user)
		{
			$userId = CurrentUser::get()->getId();
			if ($userId)
			{
				$user = (new SupersetUserRepository())->getById($userId);
			}
			else
			{
				$user = (new SupersetUserRepository())->getAdmin();
			}
		}

		if (!$user && Integrator::isUserRequired($request->getAction()))
		{
			return new IntegratorResponse(
				IntegratorResponse::STATUS_NOT_FOUND,
				null,
				[new Error('User not found', IntegratorResponse::STATUS_NOT_FOUND)]
			);
		}

		if (
			SupersetInitializer::isSupersetReady()
			&& $user
			&& !$user->clientId
		)
		{
			$superset = new SupersetController(Integrator::getInstance());
			$result = $superset->createUser($user->id);
			if ($result->isSuccess())
			{
				$createUserData = $result->getData();
				$user = $createUserData['user'];
			}
			else
			{
				if (isset($result->getData()['response']))
				{
					return $result->getData()['response'];
				}
				else
				{
					return new IntegratorResponse(
						IntegratorResponse::STATUS_INNER_ERROR,
						$result->getData(),
						$result->getErrors()
					);
				}
			}
		}

		$request->setUser($user);

		return null;
	}
}
