<?php

namespace Bitrix\Tasks\Integration\Bizproc\Flow;

use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Flow\Notification\Config\Item;
use Bitrix\Tasks\Flow\Notification\Config\Recipient;

abstract class Robot
{
	abstract public function getType(): string;
	abstract public function build(Item $item): array;

	protected function getMessageTo(Recipient $recipient): string
	{
		return match ($recipient->getType()) {
			RoleDictionary::ROLE_RESPONSIBLE => '{=Document:RESPONSIBLE_ID}',
			Recipient::FLOW_OWNER => '{=Document:OWNER}',
			Recipient::TASK_FLOW_OWNER => '{=Document:FLOW_OWNER}',
			default => '',
		};
	}

	protected function generateActivityName(array $payload): string
	{
		return 'A' . md5(Json::encode([$payload, 'type' => $this->getType()]));
	}
}