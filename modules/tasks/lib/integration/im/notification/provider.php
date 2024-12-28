<?php

namespace Bitrix\Tasks\Integration\IM\Notification;

use Bitrix\Main\Loader;
use Bitrix\Tasks\Integration\IM;
use Bitrix\Tasks\Integration\IM\Notification;
use Bitrix\Tasks\Integration\IM\Notification\UseCase\TaskCreated;
use Bitrix\Tasks\Integration\IM\Notification\UseCase\TaskUpdated;
use Bitrix\Tasks\Integration\IM\Notification\UseCase\TaskDeleted;
use Bitrix\Tasks\Integration\IM\Notification\UseCase\TaskPingSent;
use Bitrix\Tasks\Integration\IM\Notification\UseCase\TaskStatusChanged;
use Bitrix\Tasks\Integration\IM\Notification\UseCase\TaskExpired;
use Bitrix\Tasks\Integration\IM\Notification\UseCase\TaskExpiresSoon;
use Bitrix\Tasks\Integration\IM\Notification\UseCase\CommentCreated;
use Bitrix\Tasks\Integration\Mail\User;
use Bitrix\Tasks\Internals\Notification\EntityCode;
use Bitrix\Tasks\Internals\Notification\EntityOperation;
use Bitrix\Tasks\Internals\Notification\Message;
use Bitrix\Tasks\Internals\Notification\ProviderInterface;
use Bitrix\Tasks\Internals\UserOption;

class Provider implements ProviderInterface
{
	/** @var Message[]  */
	private array $messages = [];

	public function addMessage(Message $message): void
	{
		$this->messages[] = $message;
	}

	public function pushMessages(): void
	{
		if (!Loader::includeModule('im'))
		{
			return;
		}

		foreach ($this->messages as $message)
		{
			if(true === User::isEmail($message->getRecepient()->toArray()))
			{
				continue;
			}

			if ($this->isUserOnMute($message))
			{
				continue;
			}

			$entityCodeOperation = $message->getMetaData()->getEntityCode() . ':' . $message->getMetaData()->getEntityOperation();

			switch ($entityCodeOperation)
			{
				case EntityCode::CODE_TASK . ':' . EntityOperation::ADD:
					$this->pushNotification((new TaskCreated())->getNotification($message));
					break;
				case EntityCode::CODE_TASK . ':' . EntityOperation::REPLICATE_REGULAR:
					$this->pushNotification((new Notification\UseCase\Regularity\RegularTaskReplicated())->getNotification($message));
					break;
				case EntityCode::CODE_TASK . ':' . EntityOperation::START_REGULAR:
					$this->pushNotification((new Notification\UseCase\Regularity\RegularTaskStarted())->getNotification($message));
					break;
				case EntityCode::CODE_TASK . ':' . EntityOperation::UPDATE:
					$this->pushNotification((new TaskUpdated())->getNotification($message));
					break;
				case EntityCode::CODE_TASK . ':' . EntityOperation::DELETE:
					$this->pushNotification((new TaskDeleted())->getNotification($message));
					break;
				case EntityCode::CODE_TASK . ':' . EntityOperation::PING_STATUS:
					$this->pushNotification((new TaskPingSent())->getNotification($message));
					break;
				case EntityCode::CODE_TASK . ':' . EntityOperation::EXPIRED:
					$this->pushNotification((new TaskExpired())->getNotification($message));
					break;
				case EntityCode::CODE_TASK . ':' . EntityOperation::EXPIRES_SOON:
					$this->pushNotification((new TaskExpiresSoon())->getNotification($message));
					break;
				case EntityCode::CODE_TASK . ':' . EntityOperation::STATUS_CHANGED:
					$this->pushNotification((new TaskStatusChanged())->getNotification($message));
					break;
				case EntityCode::CODE_COMMENT . ':' . EntityOperation::ADD:
					$this->pushNotification((new CommentCreated())->getNotification($message));
					break;
				case EntityCode::CODE_TASK . ':' . EntityOperation::ADD_TO_FLOW_WITH_MANUAL_DISTRIBUTION:
					$this->pushNotification((new Notification\UseCase\Flow\TaskAddedToFlowWithManualDistribution())->getNotification($message));
					break;
				case EntityCode::CODE_TASK . ':' . EntityOperation::ADD_TO_FLOW_WITH_HIMSELF_DISTRIBUTION:
					$this->pushNotification((new Notification\UseCase\Flow\TaskAddedToFlowWithHimselfDistribution())->getNotification($message));
					break;
			}

		}
	}

	private function pushNotification(?Notification $notification): void
	{
		if ($notification === null)
		{
			return;
		}

		$tag = $this->getNotificationTag($notification);

		$params = [
			'FROM_USER_ID' => $notification->getSender()->getId(),
			'TO_USER_ID' => $notification->getRecepient()->getId(),
			'NOTIFY_TYPE' => IM_NOTIFY_FROM,
			'NOTIFY_MODULE' => 'tasks',
			'NOTIFY_EVENT' => 'manage', // possibly different values
			'NOTIFY_TAG' => $tag->getName(),
			'NOTIFY_SUB_TAG' => $tag->getSubName(),
			'NOTIFY_MESSAGE' => (new Notification\Task\InstantNotification($notification))->getMessage(),
			'NOTIFY_MESSAGE_OUT' => (new Notification\Task\EmailNotification($notification))->getMessage(),
			'PUSH_MESSAGE' => (new Notification\Task\PushNotification($notification))->getMessage(),
			'PUSH_PARAMS' => (new Notification\Task\PushNotification($notification))->getParams($tag),
		];

		$params = array_merge($params, $notification->getParams());

		IM::notifyAdd($params);
	}

	private function getNotificationTag(Notification $notification): Tag
	{
		$message = $notification->getMessage();
		$metadata = $message->getMetaData();
		$task = $metadata->getTask();
		$params = $notification->getParams();

		return (new Tag())
			->setTasksIds($task ? [$task->getId()] : [])
			->setUserId($message->getRecepient()->getId())
			->setEntityCode($metadata->getEntityCode())
			->setActionName($params['action'] ?? '')
			->setEntityId($metadata->getCommentId() ?? 0);
	}

	private function isUserOnMute(Message $message): bool
	{
		if ($message->getMetaData()->getTask() === null)
		{
			return false;
		}

		return UserOption::isOptionSet(
			$message->getMetaData()->getTask()->getId(),
			$message->getRecepient()->getId(),
			UserOption\Option::MUTED
		);
	}
}