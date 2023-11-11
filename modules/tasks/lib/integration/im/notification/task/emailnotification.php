<?php

namespace Bitrix\Tasks\Integration\IM\Notification\Task;

use Bitrix\Tasks\Integration\IM\Notification;
use Bitrix\Tasks\Integration\IM\Notification\NotificationInterface;

class EmailNotification implements NotificationInterface
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
			strip_tags($this->notification->getGenderMessage())
		);

		if ($this->notification->getTask()->hasDescriptionInBbcode())
		{
			$preparedMessage = str_replace(
				"\t",
				' &nbsp; &nbsp;',
				(new \CTextParser())->convertText($preparedMessage)
			);
		}

		return (new Link($this->notification->getTask(), $this->notification->getRecepient(), Link::MODE_EMAIL))
			->placeLinkAnchor($preparedMessage);
	}
}