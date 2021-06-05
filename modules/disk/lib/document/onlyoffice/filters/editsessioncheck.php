<?php

namespace Bitrix\Disk\Document\OnlyOffice\Filters;

use Bitrix\Disk\Document\OnlyOffice\OnlyOfficeHandler;
use Bitrix\Disk\EditSession;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

class EditSessionCheck extends ActionFilter\Base
{
	/** @var string */
	private $editSessionKeyInQuery;

	public function __construct(string $editSessionKeyInQuery)
	{
		parent::__construct();

		$this->editSessionKeyInQuery = $editSessionKeyInQuery;
	}

	public function onBeforeAction(Event $event)
	{
		$editSessionKey = $this->getAction()->getController()->getRequest()->getPost($this->editSessionKeyInQuery);

		foreach ($this->getAction()->getArguments() as $argument)
		{
			if (!($argument instanceof EditSession))
			{
				continue;
			}

			$editSession = $argument;

			if ($editSession->getUserId() != $this->getAction()->getCurrentUser()->getId())
			{
				$this->addError(new Error('Could not operate by session from another user.'));

				return new EventResult(EventResult::ERROR, null, null, $this);
			}

			if ($editSession->getService() !== OnlyOfficeHandler::getCode())
			{
				$this->addError(new Error('Invalid document handler. Edit session should use OnlyOffice only'));

				return new EventResult(EventResult::ERROR, null, null, $this);
			}

			if ($editSession->getServiceFileId() !== $editSessionKey)
			{
				$this->addError(new Error('Invalid edit session id.'));

				return null;
			}

		}

		return null;
	}
}