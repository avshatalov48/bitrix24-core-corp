<?php

namespace Bitrix\Disk\Document\OnlyOffice\Filters;

use Bitrix\Disk\Document\OnlyOffice\Models\DocumentSession;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

class DocumentSessionCheck extends ActionFilter\Base
{
	/** @var string */
	private $hashInQuery;

	public function __construct(string $hashInQuery)
	{
		parent::__construct();

		$this->hashInQuery = $hashInQuery;
	}

	public function onBeforeAction(Event $event)
	{
		$hash = $this->getAction()->getController()->getRequest()->getPost($this->hashInQuery);

		foreach ($this->getAction()->getArguments() as $argument)
		{
			if (!($argument instanceof DocumentSession))
			{
				continue;
			}

			$documentSession = $argument;

			if ($documentSession->getUserId() != $this->getAction()->getCurrentUser()->getId())
			{
				$this->addError(new Error('Could not operate by session from another user.'));

				return new EventResult(EventResult::ERROR, null, null, $this);
			}

			if ($documentSession->getExternalHash() !== $hash)
			{
				$this->addError(new Error('Invalid document session id.'));

				return null;
			}

		}

		return null;
	}
}