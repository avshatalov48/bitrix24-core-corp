<?php

namespace Bitrix\ImOpenLines\Controller\Widget\Filter;

use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\ActionFilter\Base;

use Bitrix\Main\EventResult;

class DiskFolderAccessCheck extends Base
{
	/**
	 * Prefilter checks if the upload folder ID belongs to the chat and the user has access to this chat.
	 *
	 * @param Event $event
	 *
	 * @return EventResult|void|null
	 */
	public function onBeforeAction(Event $event)
	{
		//for preflight CORS request
		$requestMethod = $this->action->getController()->getRequest()->getRequestMethod();
		if ($requestMethod === 'OPTIONS')
		{
			return null;
		}

		$dialogId = $this->action->getController()->getRequest()->getHeader('livechat-dialog-id');
		if (!$dialogId)
		{
			$this->addError(new Error("Header livechat-dialog-id can't be empty"));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		$chatId = (int)\Bitrix\Im\Dialog::getChatId($dialogId);
		if ($chatId <= 0)
		{
			$this->addError(new Error("Chat ID can't be empty"));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		$chatRelation = \CIMChat::GetRelationById($chatId);
		if (!$chatRelation[\CIMDisk::GetUserId()])
		{
			$this->addError(new Error("You don't have access to this chat"));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}
}