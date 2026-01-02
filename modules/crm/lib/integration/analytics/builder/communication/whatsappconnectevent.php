<?php

namespace Bitrix\Crm\Integration\Analytics\Builder\communication;

use Bitrix\Crm\Integration\Analytics\Builder\AbstractBuilder;
use Bitrix\Crm\Integration\Analytics\Dictionary;

final class WhatsAppConnectEvent extends AbstractBuilder
{
	public static function createDefault(?string $section = null): self
	{
		$self = new self();
		$self->setSection($section);
		$self->setElement(Dictionary::ELEMENT_STREAM_CONTENT_WA);

		return $self;
	}

	protected function getTool(): string
	{
		return Dictionary::TOOL_CRM;
	}

	protected function buildCustomData(): array
	{
		return [
			'category' => Dictionary::CATEGORY_COMMUNICATION,
			'event' => Dictionary::EVENT_WA_CONNECT,
			'type' => Dictionary::TYPE_WA_CONNECT,
		];
	}
}