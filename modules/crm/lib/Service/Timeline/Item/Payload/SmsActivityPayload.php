<?php

namespace Bitrix\Crm\Service\Timeline\Item\Payload;

use Bitrix\Crm\Service\Timeline\Item\Payload;

class SmsActivityPayload extends Payload
{
	public function addValueMessage(string $name, int $messageId): self
	{
		$this->addValue(
			$name,
			[
				'id' => $messageId,
			]
		);

		return $this;
	}

	public function addValuePull(string $name, string $moduleId, string $command, string $tagName): self
	{
		$this->addValue(
			$name,
			[
				'moduleId' => $moduleId,
				'command' => $command,
				'tagName' => $tagName,
			]
		);

		return $this;
	}
}
