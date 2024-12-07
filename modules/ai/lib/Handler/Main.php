<?php

namespace Bitrix\AI\Handler;

use Bitrix\AI\History;
use Bitrix\AI\Limiter;

class Main
{
	/**
	 * Called after system user totally delete.
	 *
	 * @param int $userId User id.
	 * @return void
	 */
	public static function onAfterUserDelete(int $userId): void
	{
		History\Manager::deleteForUser($userId);
		Limiter\Usage::deleteForUser($userId);
	}
}
