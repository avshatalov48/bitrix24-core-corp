<?php

namespace Bitrix\Tasks\Integration\IM\Notification\UseCase;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Integration\IM\Notification;
use Bitrix\Tasks\Internals\Notification\Message;
use Bitrix\Tasks\Internals\Notification\User;
use Bitrix\Tasks\Internals\Notification\UserRepositoryInterface;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Util;

class CommentCreated
{
	public function getNotification(Message $message): ?Notification
	{
		$metadata = $message->getMetaData();
		$task = $metadata->getTask();
		$text = $metadata->getParams()['text'] ?? null;

		if ($task === null || $text === null)
		{
			return null;
		}

		$recepient = $message->getRecepient();
		$text = (string)Util::trim(\CTextParser::clearAllTags($text));
		$textCropped = $this->cropMessage($text);
		$locKey = ($textCropped !== '') ? 'TASKS_COMMENT_MESSAGE_ADD_WITH_TEXT' : 'TASKS_COMMENT_MESSAGE_ADD';

		$notification = new Notification(
			$locKey,
			$message
		);

		$title = new Notification\Task\Title($task);
		$notification->addTemplate(new Notification\Template('#TASK_TITLE#', $title->getFormatted($recepient->getLang())));
		$notification->addTemplate(new Notification\Template('#TASK_COMMENT_TEXT#', $textCropped));

		$notification->setParams([
			'NOTIFY_ANSWER' => true,
			'NOTIFY_EVENT' => 'comment',
		]);

		return $notification;
	}

	private function cropMessage(string $message): string
	{
		// cropped message to instant messenger
		if (mb_strlen($message) >= 100)
		{
			$dot = '...';
			$message = mb_substr($message, 0, 99);

			if (mb_substr($message, -1) === '[')
			{
				$message = mb_substr($message, 0, 98);
			}

			if (
				(($lastLinkPosition = mb_strrpos($message, '[u')) !== false)
				|| (($lastLinkPosition = mb_strrpos($message, 'http://')) !== false)
				|| (($lastLinkPosition = mb_strrpos($message, 'https://')) !== false)
				|| (($lastLinkPosition = mb_strrpos($message, 'ftp://')) !== false)
				|| (($lastLinkPosition = mb_strrpos($message, 'ftps://')) !== false)
			)
			{
				if (mb_strpos($message, ' ', $lastLinkPosition) === false)
				{
					$message = mb_substr($message, 0, $lastLinkPosition);
				}
			}

			$message .= $dot;
		}

		return $message;
	}
}