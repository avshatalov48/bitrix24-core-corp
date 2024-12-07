<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Mixin\Actionable;

class SmsMessage extends ContentBlock
{
	use Actionable;

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
			'text' => html_entity_decode($this->getText()),
			'action' => $this->getAction(),
		];
	}
}
