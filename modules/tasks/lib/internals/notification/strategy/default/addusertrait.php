<?php

namespace Bitrix\Tasks\Internals\Notification\Strategy\Default;

use Bitrix\Tasks\Internals\Notification\User;

trait AddUserTrait
{
	private function addUserToRecipients(array $recipients, User $user): array
	{
		foreach ($recipients as $recipient)
		{
			if ($recipient->getId() === $user->getId())
			{
				// already exists
				return $recipients;
			}
		}

		$recipients[] = $user;

		return $recipients;
	}
}