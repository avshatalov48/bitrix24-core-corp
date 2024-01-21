<?php

namespace Bitrix\Tasks\Provider;

use Exception;

class TasksUFManager extends \CUserTypeManager
{
	public const ENTITY_TYPE = 'TASKS_TASK';
	public const ENTITY_ID = 'T.ID';

	private static array $userFields = [];

	private static ?TasksUFManager $instance = null;

	public static function getInstance(): self
	{
		if (is_null(static::$instance))
		{
			static::$instance = new static();
		}

		return static::$instance;
	}

	protected function __construct()
	{
		if (empty(self::$userFields))
		{
			static::$userFields = $this->GetUserFields(static::ENTITY_TYPE);
		}
	}

	public function getFields(bool $withType = false): array
	{
		$result = [];

		if ($withType)
		{
			foreach (static::$userFields as $field => $info)
			{
				$result[$field] = $info['USER_TYPE_ID'];
			}

			return $result;
		}

		return array_keys(static::$userFields);
	}

	public function get(string $fieldName): ?array
	{
		return static::$userFields[$fieldName] ?? null;
	}

	/**
	 * @throws Exception
	 */
	public function __unserialize($data)
	{
		throw new Exception('Cannot unserialize singleton');
	}
}