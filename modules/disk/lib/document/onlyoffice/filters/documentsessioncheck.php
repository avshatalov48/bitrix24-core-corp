<?php

namespace Bitrix\Disk\Document\OnlyOffice\Filters;

use Bitrix\Disk\Document\Models\DocumentSession;
use Bitrix\Disk\Document\Models\GuestUser;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

class DocumentSessionCheck extends ActionFilter\Base
{
	/** @var \Closure */
	private $getHashValue;
	/** @var bool */
	private $checkHash = false;
	/** @var bool */
	private $strictCheckRight = false;
	/** @var bool */
	private $ownerCheck = false;

	public function enableHashCheck(\Closure $getHashValue): self
	{
		$this->getHashValue = $getHashValue;
		$this->checkHash = true;

		return $this;
	}

	public function enableOwnerCheck(): self
	{
		$this->ownerCheck = true;

		return $this;
	}

	public function enableStrictCheckRight(): self
	{
		$this->strictCheckRight = true;

		return $this;
	}

	public function onBeforeAction(Event $event)
	{
		foreach ($this->getAction()->getArguments() as $argument)
		{
			if (!($argument instanceof DocumentSession))
			{
				continue;
			}

			$documentSession = $argument;

			$currentUserId = $this->getAction()->getCurrentUser()->getId();
			$currentUserId = $currentUserId? (int)$currentUserId : null;

			$isGuestSession = GuestUser::isGuestUserId($documentSession->getUserId()) && !$currentUserId;
			if (!$isGuestSession && $this->ownerCheck)
			{
				if ($currentUserId === null || !$documentSession->belongsToUser($currentUserId))
				{
					$this->addError(new Error('Could not operate by session from another user.'));

					return new EventResult(EventResult::ERROR, null, null, $this);
				}
			}
			if ($this->checkHash)
			{
				$hash = ($this->getHashValue)();
				if ($documentSession->getExternalHash() !== $hash)
				{
					$this->addError(new Error('Invalid document session id.'));

					return new EventResult(EventResult::ERROR, null, null, $this);
				}
			}
			if (!$isGuestSession && $this->strictCheckRight)
			{
				if ($documentSession->isView() && !$documentSession->canUserRead($this->getAction()->getCurrentUser()))
				{
					$this->addError(new Error('Could not read session.'));
				}
				if ($documentSession->isEdit() && !$documentSession->canUserEdit($this->getAction()->getCurrentUser()))
				{
					$this->addError(new Error('Could not edit document by session.'));
				}

				if ($this->getErrors())
				{
					return new EventResult(EventResult::ERROR, null, null, $this);
				}
			}
		}

		return null;
	}
}