<?php

namespace Bitrix\Tasks\Integration\IM\Notification\Task;

use Bitrix\Tasks\Integration\IM\Notification;
use Bitrix\Tasks\Integration\IM\Notification\NotificationInterface;

class InstantNotification implements NotificationInterface
{
	private Notification $notification;

	public function __construct(Notification $notification)
	{
		$this->notification = $notification;
	}

	public function getMessage(): string
	{
		$search = [];
		$replace = [];

		foreach($this->notification->getTemplates() as $template)
		{
			$search[] = $template->getSearch();
			$replace[] = $template->getReplace();
		}

		$preparedMessage = str_replace(
			$search,
			$replace,
			$this->notification->getGenderMessage()
		);

		$link = new Link(
			$this->notification->getTask(),
			$this->notification->getRecepient(),
			Link::MODE_BBCODE,
			$this->notification->getMessage()->getMetaData()->getCommentId()
		);

		return $link->placeLinkAnchor($preparedMessage);
	}
}