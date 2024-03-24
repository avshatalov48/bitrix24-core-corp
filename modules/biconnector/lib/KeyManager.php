<?php

namespace Bitrix\BIConnector;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Error;
use Bitrix\Main\Type\DateTime;

/**
 *
 */
class KeyManager
{
	public const ERROR_EMPTY_DATA = 'EMPTY_DATA';
	public const ERROR_EMPTY_USER_ID = 'EMPTY_USER_ID';

	private const DEFAULT_DATA = [
		'ID' => 0,
		'ACTIVE' => false,
		'USER_ID' => 0,
		'CONNECTION' => false,
		'USERS' => [],
	];

	/**
	 * Returns key fields array with default values set.
	 *
	 * @param array $data Access key fields.
	 * @return array
	 */
	private static function fillDefaultData($data)
	{
		if (empty($data['ACCESS_KEY']))
		{
			$data['ACCESS_KEY'] = static::generateAccessKey();
		}

		return array_merge(static::DEFAULT_DATA, $data);
	}

	/**
	 * Checks the key fields.
	 * Returns an empty collection if no errors was found.
	 *
	 * @param  mixed $data Access key fields.
	 *
	 * @return \Bitrix\Main\ErrorCollection
	 */
	private static function check($data): ErrorCollection
	{
		$errorCollection = new ErrorCollection();
		if (empty($data))
		{
			$errorCollection->setError(
				new Error('', static::ERROR_EMPTY_DATA)
			);
		}

		if ($data['USER_ID'] <= 0)
		{
			$errorCollection->setError(
				new Error('', static::ERROR_EMPTY_USER_ID)
			);
		}

		return $errorCollection;
	}

	/**
	 * Saves key with permission.
	 *
	 * @param array $data Array similar to self::DEFAULT_DATA.
	 *
	 * @return \Bitrix\Main\ErrorCollection|int
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function save($data)
	{
		$errorCollection = static::check($data);
		if (!$errorCollection->isEmpty())
		{
			return $errorCollection;
		}

		$data = static::fillDefaultData($data);

		$save = [
			'TIMESTAMP_X' => new DateTime(),
			'ACCESS_KEY' => $data['ACCESS_KEY'],
			'ACTIVE' => $data['ACTIVE'] === true ? 'Y' : 'N',
		];

		if (isset($data['APP_ID']))
		{
			$save['APP_ID'] = (int)$data['APP_ID'] > 0 ? (int)$data['APP_ID'] : null;
		}

		$connections = Manager::getInstance()->getConnections();
		if ($data['CONNECTION'] && isset($connections[$data['CONNECTION']]))
		{
			$save['CONNECTION'] = $data['CONNECTION'];
		}
		else
		{
			$save['CONNECTION'] = key($connections);
		}

		$id = (int)$data['ID'];
		if ($id > 0)
		{
			$updateResult = KeyTable::update($id, $save);
			$updateResult->isSuccess();
		}
		else
		{
			$save['DATE_CREATE'] = new DateTime();
			$save['CREATED_BY'] = $data['USER_ID'];
			if (!empty($data['SERVICE_ID']))
			{
				$save['SERVICE_ID'] = $data['SERVICE_ID'];
			}
			$addResult = KeyTable::add($save);
			if ($addResult->isSuccess())
			{
				$id = $addResult->getId();
			}
			else
			{
				$errorCollection->setValues(
					$addResult->getErrorCollection()->getValues()
				);
			}
		}

		if ($id > 0 && isset($data['USERS']))
		{
			$usersForm = [];
			foreach ($data['USERS'] as $userId)
			{
				$userId = (int)trim($userId, " \t\n\r");
				if ($userId > 0)
				{
					$usersForm[$userId] = [
						'TIMESTAMP_X' => new DateTime(),
						'CREATED_BY' => $data['USER_ID'],
						'KEY_ID' => $id,
						'USER_ID' => $userId,
					];
				}
			}

			$usersDb = [];
			if (!isset($addResult))
			{
				$userList = KeyUserTable::getList([
					'select' => ['ID', 'USER_ID'],
					'filter' => [
						'=KEY_ID' => $id,
					]
				]);
				while ($user = $userList->fetch())
				{
					$usersDb[$user['ID']] = $user['USER_ID'];
				}
			}

			foreach ($usersForm as $user)
			{
				$found = false;
				foreach ($usersDb as $dbId => $dbUserId)
				{
					if ($dbUserId == $user['USER_ID'])
					{
						unset($usersDb[$dbId]);
						$found = true;
						break;
					}
				}

				if (!$found)
				{
					$addResult = KeyUserTable::add($user);
					$addResult->isSuccess();
				}
			}

			foreach ($usersDb as $dbId => $_)
			{
				$deleteResult = KeyUserTable::delete($dbId);
				$deleteResult->isSuccess();
			}
		}

		return $errorCollection->isEmpty() ? $id : $errorCollection;
	}

	/**
	 * Returns new access key
	 * @return string
	 */
	public static function generateAccessKey()
	{
		return \Bitrix\Main\Security\Random::getStringByAlphabet(
			32,
			\Bitrix\Main\Security\Random::ALPHABET_NUM
			| \Bitrix\Main\Security\Random::ALPHABET_ALPHALOWER
			| \Bitrix\Main\Security\Random::ALPHABET_ALPHAUPPER
		);
	}

	protected static function createKeyInner(array $fields): \Bitrix\Main\Result
	{
		$result = new \Bitrix\Main\Result();
		$key = \Bitrix\BIConnector\KeyManager::generateAccessKey();
		$fields['ACCESS_KEY'] = $key;
		$resultSave = \Bitrix\BIConnector\KeyManager::save($fields);
		if (!($resultSave instanceof ErrorCollection))
		{
			$result->setData(['ACCESS_KEY' => $key]);
		}
		else
		{
			$result->addErrors($resultSave->getValues());
		}

		return $result;
	}

	public static function createAccessKey(CurrentUser $user): \Bitrix\Main\Result
	{
		$keyParameters = [
			'USER_ID' => $user->getId(),
			'ACTIVE' => true,
		];

		return static::createKeyInner($keyParameters);
	}

	public static function getAccessKey(): ?string
	{
		$key = \Bitrix\BIConnector\KeyTable::getList([
			'select' => [
				'ACCESS_KEY',
			],
			'filter' => [
				'=ACTIVE' => 'Y',
				'=APP_ID' => false,
			],
			'order' => [
				'ID' => 'DESC',
			],
			'limit' => 1,
		])->fetch();
		if ($key === false)
		{
			return null;
		}

		return $key['ACCESS_KEY'] ?? null;
	}

	public static function getOrCreateAccessKey(CurrentUser $user, $checkPermission = true): ?string
	{
		if (!$checkPermission || static::canManageKey($user))
		{
			$accessKey = \Bitrix\BIConnector\KeyManager::getAccessKey();
			if (!$accessKey)
			{
				$createdResult = \Bitrix\BIConnector\KeyManager::createAccessKey($user);
				if ($createdResult->isSuccess())
				{
					return $createdResult->getData()['ACCESS_KEY'] ?? null;
				}
			}
			else
			{
				return $accessKey;
			}
		}

		return null;
	}

	/**
	 * @param CurrentUser $user
	 * @return bool
	 */
	public static function canManageKey(CurrentUser $user): bool
	{
		return $user->canDoOperation('biconnector_key_manage');
	}
}
