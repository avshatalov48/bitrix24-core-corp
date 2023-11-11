<?php

namespace Bitrix\Tasks\Integration\AI\Event\Message;

use Bitrix\Main\Type\Contract\Arrayable;

class Message implements Arrayable
{
	public function __construct(private string $content, private bool $isMain = false)
	{
	}

	public function toArray(): array
	{
		return [
			'content' => $this->content,
			'is_original_message' => $this->isMain
		];
	}
}
