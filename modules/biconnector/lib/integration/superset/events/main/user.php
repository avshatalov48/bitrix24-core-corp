<?php

namespace Bitrix\BIConnector\Integration\Superset\Events\Main;

use Bitrix\BIConnector\Integration\Superset\SupersetInitializer;
use Bitrix\Main\Application;
use Bitrix\Main\UserTable;
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

	public static function onBeforeUserUpdate(array $fields): void
	{
		if (
			isset($fields['ID'])
			&& (int)$fields['ID'] > 0
			&& array_intersect(self::$changableFields, array_keys($fields))
		)
		{
			global $USER;
			$userData = [];

			$currentUserId = (isset($USER) && $USER instanceof \CUser) ? (int)$USER->GetID() : 0;
			if ((int)$fields['ID'] === $currentUserId)
			{
				$userData = [
					'ACTIVE' => 'Y',
					'EMAIL' => $USER->GetParam('EMAIL'),
					'NAME' => $USER->GetParam('FIRST_NAME'),
					'LAST_NAME' => $USER->GetParam('LAST_NAME'),
					'LOGIN' => $USER->GetParam('LOGIN'),
				];
			}
			else
			{
				$userData = UserTable::getRow([
					'select' => array_merge(self::$changableFields, ['LOGIN']),
					'filter' => [
						'=ID' => (int)$fields['ID'],
					],
				]);
			}

			if ($userData)
			{
				self::$currentUserFields = $userData;
			}
		}
	}

	/**
	 * Update superset user
	 *
	 * @param array $fields
	 * @return void
	 */
	public static function onAfterUserUpdate(array $fields): void
	{
		if (!SupersetInitializer::isSupersetReady())
		{
			return;
		}

		$userId = 0;

		if (!self::$currentUserFields)
		{
			return;
		}

		if (isset($fields['ID']) && ((int)$fields['ID']) > 0)
		{
			$userId = (int)$fields['ID'];
		}
		else
		{
			return;
		}

		$user = self::getUser($userId);
		if (!$user || empty($user->clientId))
		{
			return;
		}

		$isChangedActivity = isset($fields['ACTIVE']) && ($fields['ACTIVE'] !== self::$currentUserFields['ACTIVE']);
		if ($isChangedActivity)
		{
			self::changeActivity($user, $fields['ACTIVE'] === 'Y');
		}

		$isChangedEmail = isset($fields['EMAIL']) && $fields['EMAIL'] !== self::$currentUserFields['EMAIL'];
		$isChangedName = isset($fields['NAME']) && $fields['NAME'] !== self::$currentUserFields['NAME'];
		$isChangedLastName = isset($fields['LAST_NAME']) && $fields['LAST_NAME'] !== self::$currentUserFields['LAST_NAME'];
		if ($isChangedName || $isChangedLastName || $isChangedEmail)
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

			self::updateUser(
				$user,
				$email,
				$name,
				$lastName
			);
		}

		self::$currentUserFields = null;
	}

	private static function changeActivity(Dto\User $user, bool $isActive): void
	{
		$integrator = Integrator::getInstance();

		Application::getInstance()->addBackgroundJob(function() use ($integrator, $user, $isActive) {
			if ($isActive)
			{
				$integrator->activateUser($user);
				(new Synchronizer($user->id))->sync();
			}
			else
			{
				$integrator->deactivateUser($user);
				$integrator->setEmptyRole($user);
			}
		});

		SupersetUserTable::updatePermissionHash($user->id, '');
	}

	private static function updateUser(Dto\User $user, string $email, string $firstName, string $lastName): void
	{
		$user->userName = $email;
		$user->email = $email;
		$user->firstName = $firstName;
		$user->lastName = $lastName;

		$integrator = Integrator::getInstance();

		Application::getInstance()->addBackgroundJob(function() use ($integrator, $user) {
			$integrator->updateUser($user);
		});
	}

	private static function getUser(int $userId): ?Dto\User
	{
		return (new SupersetUserRepository)->getById($userId);
	}
}