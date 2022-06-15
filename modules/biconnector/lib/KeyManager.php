<?php

namespace Bitrix\BIConnector;

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

	private static function fillDefaultData($data)
	{
		if (empty($data['ACCESS_KEY']))
		{
			$data['ACCESS_KEY'] = static::generateAccessKey();
		}

		return array_merge(static::DEFAULT_DATA, $data);
	}

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
	 * Saves key with permission
	 *
	 * @param $data array similar to self::DEFAULT_DATA
	 * @return ErrorCollection|int
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

			foreach ($usersForm as $userId => $user)
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

			foreach ($usersDb as $dbId => $dbUserId)
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
}
