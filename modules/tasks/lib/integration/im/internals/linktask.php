<?php

namespace Bitrix\Tasks\Integration\IM\Internals;

class LinkTask extends \Bitrix\Tasks\Integration\IM
{
	public static function delete(int $taskId): void
	{
		if (
			static::includeModule()
			&& method_exists(\Bitrix\Im\V2\Service\Messenger::class, 'deleteTask')
		)
		{
			\Bitrix\Im\V2\Service\Locator::getMessenger()->deleteTask($taskId);
		}
	}
}