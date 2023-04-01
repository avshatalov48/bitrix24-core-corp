<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

class SmsMessage extends ContentBlock
{
	protected ?string $text = null;

	public function getRendererName(): string
	{
		return 'SmsMessage';
	}

	public function getText(): ?string
	{
		return $this->text;
	}

	public function setText(?string $text): SmsMessage
	{
		$this->text = $text;
		return $this;
	}

	protected function getProperties(): array
	{
		return [
			'text' => $this->getText(),
		];
	}
}
