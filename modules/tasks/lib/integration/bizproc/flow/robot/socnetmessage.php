<?php

namespace Bitrix\Tasks\Integration\Bizproc\Flow\Robot;

use Bitrix\Tasks\Flow\Notification\Config\Item;
use Bitrix\Tasks\Integration\Bizproc\Flow\Robot;

class SocnetMessage extends Robot
{
	public function getType(): string
	{
		return 'SocNetMessageActivity';
	}

	/**
	 * Sends notifications
	 * @param Item $item
	 * @return array
	 */
	public function build(Item $item): array
	{
		$robots = [];

		foreach ($item->getRecipients() as $recipient)
		{
			$props = [
				'Title' => '',
				'MessageFormat' => 'robot',
				'MessageText' => $item->getTranslatedMessage(),
				'MessageUserTo' => $this->getMessageTo($recipient),
			];

			$robots[] = [
				'Type'	=> $this->getType(),
				'Properties' => $props,
				'Name' => $this->generateActivityName($props),
			];
		}

		return $robots;
	}
}