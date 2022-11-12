<?php

namespace Bitrix\BIConnector\Controller;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\BIConnector\KeyManager;
use Bitrix\Main\ErrorCollection;
use Bitrix\BIConnector\KeyTable;
use Bitrix\Main\Error;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Rest\AppTable;
use Bitrix\Rest\Engine\ActionFilter\AuthType;
use Bitrix\BIConnector\KeyUserTable;
use Bitrix\BIConnector\LogTable;

/**
 * Class Key
 * @package Bitrix\BIConnector\Controller
 */
class Key extends Controller
{
	private const ALLOW_FILTER_FIELDS = [
		'ID',
		'DATE_CREATE',
		'TIMESTAMP_X',
		'CREATED_BY',
		'ACCESS_KEY',
		'CONNECTION',
		'ACTIVE',
	];

	private const ALLOW_SELECT_FIELDS = [
		'ID',
		'ACTIVE',
		'DATE_CREATE',
		'TIMESTAMP_X',
		'CREATED_BY',
		'ACCESS_KEY',
		'CONNECTION',
	];

	/**
	 * Adds new key.
	 * @param $fields
	 * @param \CRestServer $server
	 * @return array|int
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function addAction($fields, \CRestServer $server)
	{
		$fields['USER_ID'] = $this->prepareUserId((int)$fields['USER_ID']);
		$res = KeyManager::save(
			[
				'APP_ID' => $this->getAppId($server),
				'CONNECTION' => $fields['CONNECTION'],
				'USER_ID' => $fields['USER_ID'],
				'ACCESS_KEY' => $fields['ACCESS_KEY'],
				'ACTIVE' => $fields['ACTIVE'] === 'Y',
			]
		);
		if ($res instanceof ErrorCollection)
		{
			$result = $this->prepareErrorsForRest('ADD', $res);
		}
		else
		{
			$result = (int)$res;
		}

		return $result;
	}

	/**
	 * Returns list of key by app.
	 *
	 * @param array $order
	 * @param array $filter
	 * @param array $select
	 * @param int $offset
	 * @param int $limit
	 * @param \CRestServer|null $server
	 * @return array
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function listAction(array $order = [], array $filter = [], array $select = [], int $offset = 0, int $limit = 50, \CRestServer $server = null)
	{
		$result = [];
		$appId = 0;
		if (!is_null($server))
		{
			$appId = $this->getAppId($server);
		}

		if ($appId > 0)
		{
			$filter = $this->prepareFilter($filter);
			$select = $this->prepareSelect($select);
			$filter['=APP_ID'] = $appId;

			$res = KeyTable::getList(
				[
					'order' => $order ?: [],
					'filter' => $filter,
					'select' => $select,
					'offset' => $offset,
					'limit' => $limit,
				]
			);
			while ($item = $res->fetch())
			{
				$result[] = $item;
			}
		}

		return $result;
	}

	/**
	 * Updates key.
	 *
	 * @param $id
	 * @param $fields
	 * @param \CRestServer $server
	 * @return array|int|string[]
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function updateAction($id, $fields, \CRestServer $server)
	{
		$result = [
			'error' => 'KEY_NOT_FOUND',
			'error_description' => 'Key not found.',
		];

		$appId = $this->getAppId($server);
		if ($appId > 0)
		{
			$list = KeyTable::getList(
				[
					'filter' => [
						'=ID' => $id,
						'=APP_ID' => $appId,
					],
					'select' => [
						'ID', 'CREATED_BY'
					],
				]
			);
			if ($item = $list->fetch())
			{
				$userId = $this->prepareUserId();
				if ($userId !== (int)$item['CREATED_BY'] && !\CRestUtil::isAdmin())
				{
					return [
						'error' => 'ACCESS_DENIED',
						'error_description' => 'Access denied.',
					];
				}

				$save = [];
				if (array_key_exists('CONNECTION', $fields))
				{
					$save['CONNECTION'] = $fields['CONNECTION'];
				}
				if (array_key_exists('ACTIVE', $fields))
				{
					$save['ACTIVE'] = $fields['ACTIVE'] === 'Y';
				}

				$res = KeyTable::update(
					$item['ID'],
					$save
				);
				if (!$res->isSuccess())
				{
					$result = $this->prepareErrorsForRest('UPDATE', $res->getErrorCollection());
				}
				else
				{
					$result = true;
				}
			}
		}

		return $result;
	}

	/**
	 * Returns key.
	 *
	 * @param $id
	 * @param \CRestServer $server
	 * @return array|bool|string[]
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function deleteAction($id, \CRestServer $server)
	{
		$result = [
			'error' => 'KEY_NOT_FOUND',
			'error_description' => 'Key not found.',
		];

		$appId = $this->getAppId($server);
		if ($appId > 0)
		{
			$list = KeyTable::getList(
				[
					'filter' => [
						'=ID' => $id,
						'=APP_ID' => $appId,
					],
					'select' => [
						'ID',
					],
				]
			);
			if ($item = $list->fetch())
			{
				KeyUserTable::deleteByFilter(
					[
						'=KEY_ID' => $item['ID'],
					]
				);
				LogTable::deleteByFilter(
					[
						'=KEY_ID' => $item['ID'],
					]
				);

				$deleteResult = KeyTable::delete($item['ID']);
				if (!$deleteResult->isSuccess())
				{
					$result = $this->prepareErrorsForRest('DELETE', $deleteResult->getErrorCollection());
				}
				else
				{
					$result = true;
				}
			}
		}

		return $result;
	}

	private function prepareFilter(array $filter): array
	{
		$result = [];
		foreach ($filter as $code => $value)
		{
			$filterType = '';
			$matches = [];
			if (preg_match('/^([\W]{1,2})(.+)/', $code, $matches) && $matches[2])
			{
				$filterType = $matches[1];
				$code = $matches[2];
			}

			if (in_array($code, self::ALLOW_FILTER_FIELDS, true))
			{
				if ($code === 'USER_ID')
				{
					if (is_array($value))
					{
						foreach ($value as $k => $val)
						{
							$value[$k] = $this->prepareUserId((int)$val);
						}
						$value = array_unique($value);
					}
					else
					{
						$value = $this->prepareUserId((int)$value);
					}
				}

				$result[$filterType . $code] = $value;
			}
		}

		return $result;
	}

	private function prepareSelect(array $select): array
	{
		$result = [];

		foreach ($select as $code)
		{
			if (in_array($code, self::ALLOW_SELECT_FIELDS, true))
			{
				$result[] = $code;
			}
		}

		return $result ?: ['*'];
	}

	private function prepareErrorsForRest(string $errorCode, ErrorCollection $errors): array
	{
		$message = [];
		$code = '';
		foreach ($errors->getValues() as $error)
		{
			/** @var Error $error */
			$code = (string)$error->getCode();
			$mess = (string)$error->getMessage();
			if ($code !== '')
			{
				$mess = $code . ($mess !== '' ? ':' . $mess : '');
			}
			if ($mess !== '')
			{
				$message[] = $mess;
			}
		}

		return [
			'error' => count($message) > 1 ? $errorCode : $code,
			'error_description' => implode(', ', $message),
		];
	}

	private function getAppId(\CRestServer $server): int
	{
		$clientId = $server->getClientId();

		$app = AppTable::getByClientId($clientId);

		return $app['ID'] ? (int)$app['ID'] : 0;
	}

	private function prepareUserId(int $id = 0): int
	{
		if ($id === 0 || !\CRestUtil::isAdmin())
		{
			$id = 0;
			global $USER;
			if ($USER instanceof \CUser)
			{
				$id = (int)$USER->getId();
			}
		}

		return $id;
	}

	public function getDefaultPreFilters()
	{
		return [
			new ActionFilter\Authentication(),
			new ActionFilter\Scope(ActionFilter\Scope::REST),
			new AuthType(AuthType::APPLICATION),
		];
	}
}
