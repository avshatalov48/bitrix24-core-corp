<?php

namespace Bitrix\BIConnector\Integration\Superset\Integrator\Request\Middleware;

use Bitrix\BIConnector\Access\Superset\Synchronizer;
use Bitrix\BIConnector\Integration\Superset\Integrator\Integrator;
use Bitrix\BIConnector\Integration\Superset\Integrator\Request\IntegratorRequest;
use Bitrix\BIConnector\Integration\Superset\Integrator\Request\IntegratorResponse;
use Bitrix\BIConnector\Integration\Superset\Repository\SupersetUserRepository;
use Bitrix\BIConnector\Integration\Superset\SupersetController;
use Bitrix\BIConnector\Integration\Superset\SupersetInitializer;
use Bitrix\BIConnector\Integration\Superset\Integrator\Dto;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetUserTable;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Result;
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

		if ($user && SupersetInitializer::isSupersetReady())
		{
			if (!$user->clientId)
			{
				$superset = new SupersetController(Integrator::getInstance());
				$result = $superset->createUser($user->id);
				if ($result->isSuccess())
				{
					$createUserData = $result->getData();
					/** @var Dto\User $user */
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

			if (!$user->updated)
			{
				$user->updated = true;

				$updateUserResult = self::updateUser($user);
				if ($updateUserResult->isSuccess())
				{
					SupersetUserTable::updateUpdated($user->id, true);
				}
				else
				{
					$user->updated = false;

					return new IntegratorResponse(
						IntegratorResponse::STATUS_INNER_ERROR,
						$updateUserResult->getData(),
						$updateUserResult->getErrors()
					);
				}
			}
		}

		$request->setUser($user);

		return null;
	}

	private static function updateUser(Dto\User $user): Result
	{
		$result = new Result();

		$integrator = Integrator::getInstance();

		if ($user->active)
		{
			$activeUserResult = $integrator->activateUser($user);
			if ($activeUserResult->hasErrors())
			{
				$result->addErrors($activeUserResult->getErrors());

				return $result;
			}
		}
		else
		{
			$activeUserResult = $integrator->deactivateUser($user);
			if ($activeUserResult->hasErrors())
			{
				$result->addErrors($activeUserResult->getErrors());

				return $result;
			}

			$integrator->setEmptyRole($user);
		}

		$updateResult = $integrator->updateUser($user);
		if ($updateResult->hasErrors())
		{
			$result->addErrors($updateResult->getErrors());

			return $result;
		}

		return $result;
	}
}
