<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Integration;

use Bitrix\Im\V2\Service\Locator;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;

class RunMessenger
{
	use ConfigTrait;

	public function __invoke(array $fields, ?Task\Source $source = null): void
	{
		if ($source && $source->type === 'chat')
		{
			$fields['IM_CHAT_ID'] = $source->data['entityId'] ?? 0;
			$fields['IM_MESSAGE_ID'] = $source->data['subEntityId'] ?? 0;
		}

		if (!isset($fields['IM_CHAT_ID']))
		{
			return;
		}

		if ($fields['IM_CHAT_ID'] <= 0)
		{
			return;
		}

		if (!Loader::includeModule('im'))
		{
			return;
		}

		$chatId = $fields['IM_CHAT_ID'];
		$messageId = isset($fields['IM_MESSAGE_ID']) && $fields['IM_MESSAGE_ID'] > 0 ? $fields['IM_MESSAGE_ID'] : 0;
		$taskId = $fields['ID'];

		Application::getInstance()->addBackgroundJob(
			function() use ($chatId, $messageId, $taskId) {
				$task = TaskRegistry::getInstance()->drop($taskId)->getObject($taskId, true);
				if ($task === null)
				{
					return;
				}

				Locator::getMessenger()->registerTask($chatId, $messageId, $task);
			}
		);
	}
}