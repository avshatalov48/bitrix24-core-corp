<?php
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Tasks\Integration\SocialNetwork;

use Bitrix\Main\Loader;
use Bitrix\Tasks\Integration\SocialNetwork\UseCase\TaskCreated;
use Bitrix\Tasks\Integration\SocialNetwork\UseCase\TaskDeleted;
use Bitrix\Tasks\Integration\SocialNetwork\UseCase\TaskStatusChanged;
use Bitrix\Tasks\Integration\SocialNetwork\UseCase\TaskUpdated;
use Bitrix\Tasks\Internals\Notification\EntityCode;
use Bitrix\Tasks\Internals\Notification\EntityOperation;
use Bitrix\Tasks\Internals\Notification\Message;
use Bitrix\Tasks\Internals\Notification\ProviderInterface;

class NotificationProvider implements ProviderInterface
{
	/** @var Message[] */
	protected array $messages = [];

	public function addMessage(Message $message): void
	{
		// process messages in a sync way for socialnetwork
		if (!Loader::includeModule('socialnetwork'))
		{
			return;
		}

		$task = $message->getMetaData()->getTask();
		if ($task === null)
		{
			return;
		}

		$this->messages[] = $message;
		switch ($message->getMetaData()->getEntityCode() . ':' . $message->getMetaData()->getEntityOperation())
		{
			case EntityCode::CODE_TASK . ':' . EntityOperation::ADD:
				(new TaskCreated())->execute($message);
				break;
			case EntityCode::CODE_TASK . ':' . EntityOperation::UPDATE:
				(new TaskUpdated())->execute($message);
				break;
			case EntityCode::CODE_TASK . ':' . EntityOperation::STATUS_CHANGED:
				(new TaskStatusChanged())->execute($message);
				break;
			case EntityCode::CODE_TASK . ':' . EntityOperation::DELETE:
				(new TaskDeleted())->execute($message);
				break;
		}
	}

	public function pushMessages(): void
	{
	}
}
