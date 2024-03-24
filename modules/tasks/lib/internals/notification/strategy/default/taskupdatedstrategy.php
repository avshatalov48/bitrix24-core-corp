<?php

namespace Bitrix\Tasks\Internals\Notification\Strategy\Default;

use Bitrix\Tasks\Internals\Notification\Dictionary;
use Bitrix\Tasks\Internals\Notification\Strategy\RecipientStrategyInterface;
use Bitrix\Tasks\Internals\Notification\User;
use Bitrix\Tasks\Internals\Notification\UserRepositoryInterface;
use Bitrix\Tasks\Internals\TaskObject;

class TaskUpdatedStrategy implements RecipientStrategyInterface
{
	use StrategyConstructorTrait;

	private UserRepositoryInterface $userRepository;
	private TaskObject $task;
	private Dictionary $dictionary;

	public function getRecipients(): array
	{
		$previousFields = $this->dictionary->get('previousFields');
		$additionalRecipients = [];
		// Pack prev users ids to ADDITIONAL_RECIPIENTS, to ensure,
		// that they all will receive message
		{
			if (isset($previousFields['CREATED_BY']))
			{
				$additionalRecipients[] = $previousFields['CREATED_BY'];
			}

			if (isset($previousFields['RESPONSIBLE_ID']))
			{
				$additionalRecipients[] = $previousFields['RESPONSIBLE_ID'];
			}

			if (isset($previousFields['ACCOMPLICES']) && is_array($previousFields['ACCOMPLICES']))
			{
				foreach ($previousFields['ACCOMPLICES'] as $userId)
				{
					$additionalRecipients[] = $userId;
				}
			}

			if (isset($previousFields['AUDITORS']) && is_array($previousFields['AUDITORS']))
			{
				foreach ($previousFields['AUDITORS'] as $userId)
				{
					$additionalRecipients[] = $userId;
				}
			}
		}

		$options = $this->dictionary->get('options', []);
		$options['additional_recepients'] = $additionalRecipients;

		return $this->userRepository->getRecepients($this->task, $this->getSender(), $options);
	}

	public function getSender(): ?User
	{
		return $this->userRepository->getSender($this->task, $this->dictionary->get('options', []));
	}
}