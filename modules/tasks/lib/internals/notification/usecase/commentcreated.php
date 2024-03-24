<?php

namespace Bitrix\Tasks\Internals\Notification\UseCase;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Tasks\Internals\Notification\EntityCode;
use Bitrix\Tasks\Internals\Notification\EntityOperation;
use Bitrix\Tasks\Internals\Notification\Message;
use Bitrix\Tasks\Internals\Notification\Metadata;

class CommentCreated extends AbstractCase
{
	public function execute(int $commentId, string $text): bool
	{
		$currentLoggedInUserId = CurrentUser::get()->getId();
		if ($currentLoggedInUserId === null)
		{
			return false;
		}

		$this->createDictionary(['authorId' => $currentLoggedInUserId]);

		foreach ($this->providers as $provider)
		{
			$sender = $this->getCurrentSender();
			if (is_null($sender))
			{
				continue;
			}

			$recipients = $this->getCurrentRecipients();
			foreach ($recipients as $recipient)
			{
				$provider->addMessage(new Message(
					$sender,
					$recipient,
					new Metadata(
						EntityCode::CODE_COMMENT,
						EntityOperation::ADD,
						[
							'task' => $this->task,
							'comment_id' => $commentId,
							'text' => $text
						]
					)
				));
			}

			$this->buffer->addProvider($provider);
		}

		return true;
	}
}