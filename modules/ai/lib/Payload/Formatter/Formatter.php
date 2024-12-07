<?php

namespace Bitrix\AI\Payload\Formatter;

use Bitrix\AI\Engine\IEngine;
use Bitrix\AI\Facade\User;

abstract class Formatter
{
	/**
	 * Expects text for replacement.
	 */
	public function __construct(
		protected string $text,
		protected IEngine $engine,
	){}

	/**
	 * Retrieves user data by ID and stores it to the static cache.
	 *
	 * @return array
	 */
	protected function getUserDataById(int $userId): array
	{
		static $user = null;

		if ($user === null)
		{
			$user = User::getUserDataById($userId);
		}

		return $user;
	}
}
