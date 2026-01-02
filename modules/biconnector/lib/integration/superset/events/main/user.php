<?php

namespace Bitrix\BIConnector\Integration\Superset\Events\Main;

use Bitrix\Main\Application;
use Bitrix\Main\UserTable;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\BIConnector\Integration\Superset\SupersetInitializer;
use Bitrix\BIConnector\Integration\Superset\Integrator\Integrator;
use Bitrix\BIConnector\Integration\Superset\Integrator\Dto;
use Bitrix\BIConnector\Integration\Superset\Repository\SupersetUserRepository;
use Bitrix\BIConnector\Access\Superset\Synchronizer;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetUserTable;

/**
 * Event handlers for user
 */
class User
{
	private static ?array $currentUserFields = null;

	private static array $changableFields = [
		'ACTIVE',
		'EMAIL',
		'NAME',
		'LAST_NAME',
	];

	/**
	 * Gets and keeps current user fields before update.
	 *
	 * @param array $fields
	 * @return void
	 */
	public static function onBeforeUserUpdate(array $fields): void
	{
		if (
			isset($fields['ID'])
			&& (int)$fields['ID'] > 0
			&& array_intersect(self::$changableFields, array_keys($fields))
		)
		{
			$id = (int)$fields['ID'];
			$currentUser = CurrentUser::get();

			if ($id === (int)$currentUser->getId())
			{
				self::$currentUserFields = [
					'ACTIVE' => 'Y',
					'EMAIL' => $currentUser->getEmail(),
					'NAME' => $currentUser->getFirstName(),
					'LAST_NAME' => $currentUser->getLastName(),
					'LOGIN' => $currentUser->getLogin(),
				];
			}
			else
			{
				self::$currentUserFields = UserTable::getRow([
					'select' => array_merge(self::$changableFields, ['LOGIN']),
					'filter' => [
						'=ID' => $id,
					],
				]);
			}
		}
	}

	/**
	 * Checks and updates superset user after user update.
	 * Uses addBackgroundJob for updating user in background.
	 *
	 * @param array $fields
	 * @return void
	 */
	public static function onAfterUserUpdate(array $fields): void
	{
		$userId = (isset($fields['ID']) && ((int)$fields['ID']) > 0) ? (int)$fields['ID'] : 0;

		if (!self::$currentUserFields || !$userId)
		{
			return;
		}

		$user = self::getUser($userId);
		if (!$user || empty($user->clientId))
		{
			return;
		}

		$isChangedActivity = isset($fields['ACTIVE']) && ($fields['ACTIVE'] !== self::$currentUserFields['ACTIVE']);

		$email = '';
		$name = '';
		$lastName = '';

		$isChangedEmail = isset($fields['EMAIL']) && $fields['EMAIL'] !== self::$currentUserFields['EMAIL'];
		$isChangedName = isset($fields['NAME']) && $fields['NAME'] !== self::$currentUserFields['NAME'];
		$isChangedLastName = isset($fields['LAST_NAME']) && $fields['LAST_NAME'] !== self::$currentUserFields['LAST_NAME'];

		$isChangedFields = $isChangedName || $isChangedLastName || $isChangedEmail;
		if ($isChangedFields)
		{
			// login
			$login = self::$currentUserFields['LOGIN'];
			if (!empty($fields['LOGIN']))
			{
				$login = $fields['LOGIN'];
			}

			// email
			$email = ($login . '@bitrix.bi');
			if (!empty(self::$currentUserFields['EMAIL']))
			{
				$email = self::$currentUserFields['EMAIL'];
			}

			if (!empty($fields['EMAIL']))
			{
				$email = $fields['EMAIL'];
			}

			// name
			$name = $login;
			if (!empty(self::$currentUserFields['NAME']))
			{
				$name = self::$currentUserFields['NAME'];
			}

			if (!empty($fields['NAME']))
			{
				$name = $fields['NAME'];
			}

			// last name
			$lastName = $login;
			if (!empty(self::$currentUserFields['LAST_NAME']))
			{
				$lastName = self::$currentUserFields['LAST_NAME'];
			}

			if (!empty($fields['LAST_NAME']))
			{
				$lastName = $fields['LAST_NAME'];
			}
		}

		if ($isChangedActivity || $isChangedFields)
		{
			if (SupersetInitializer::isSupersetReady())
			{
				if ($isChangedActivity)
				{
					self::changeActivity($user, $fields['ACTIVE'] === 'Y');
				}

				if ($isChangedFields)
				{
					self::updateUser($user, $email, $name, $lastName);
				}

				self::setUpdated($user);
			}
			else
			{
				self::setNotUpdated($user);
			}
		}

		self::$currentUserFields = null;
	}

	private static function changeActivity(Dto\User $user, bool $isActive): void
	{
		$integrator = Integrator::getInstance();

		Application::getInstance()->addBackgroundJob(function() use ($integrator, $user, $isActive) {
			if ($isActive)
			{
				$activeUserResult = $integrator->activateUser($user);
				if (!$activeUserResult->hasErrors())
				{
					(new Synchronizer($user->id))->sync();
				}
			}
			else
			{
				$activeUserResult = $integrator->deactivateUser($user);
				if (!$activeUserResult->hasErrors())
				{
					$integrator->setEmptyRole($user);
				}
			}

			if ($activeUserResult->hasErrors())
			{
				self::setNotUpdated($user);
			}
		});

		self::clearPermissionHash($user);
	}

	private static function updateUser(Dto\User $user, string $email, string $firstName, string $lastName): void
	{
		$user->userName = $email;
		$user->email = $email;
		$user->firstName = $firstName;
		$user->lastName = $lastName;

		$integrator = Integrator::getInstance();

		Application::getInstance()->addBackgroundJob(function() use ($integrator, $user) {
			$updateResult = $integrator->updateUser($user);
			if ($updateResult->hasErrors())
			{
				self::setNotUpdated($user);
			}
		});
	}

	private static function getUser(int $userId): ?Dto\User
	{
		return (new SupersetUserRepository)->getById($userId);
	}

	private static function clearPermissionHash(Dto\User $user): void
	{
		SupersetUserTable::updatePermissionHash($user->id, '');
	}

	private static function setUpdated(Dto\User $user): void
	{
		SupersetUserTable::updateUpdated($user->id, true);
	}

	private static function setNotUpdated(Dto\User $user): void
	{
		SupersetUserTable::updateUpdated($user->id, false);
	}
}
