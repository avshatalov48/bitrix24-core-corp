<?php

namespace Bitrix\Tasks\Provider;

class TasksUFManager extends \CUserTypeManager
{
	public const ENTITY_TYPE = 'TASKS_TASK';
	public const ENTITY_ID = 'T.ID';

	private static array $userFields = [];

	private static ?TasksUFManager $instance = null;

	public static function getInstance(): self
	{
		if (is_null(self::$instance))
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	protected function __construct()
	{
		if (empty(self::$userFields))
		{
			self::$userFields = $this->GetUserFields(self::ENTITY_TYPE);
		}
	}

	public function getFields(bool $withType = false): array
	{
		$result = [];

		if ($withType)
		{
			foreach (self::$userFields as $field => $info)
			{
				$result[$field] = $info['USER_TYPE_ID'];
			}

			return $result;
		}

		return array_keys(self::$userFields);
	}
}