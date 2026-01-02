<?php

namespace Bitrix\Crm\Integration\Analytics\Builder\communication;

use Bitrix\Crm\Integration\Analytics\Builder\AbstractBuilder;
use Bitrix\Crm\Integration\Analytics\Dictionary;

class WhatsAppDeleteEvent extends AbstractBuilder
{
	private string $eventName = Dictionary::EVENT_WA_TIMELINE;
	private string $type = Dictionary::TYPE_WA_ACTIVITY_CREATE;

	public static function createDefault(?string $section = null): self
	{
		$self = new self();
		$self->setSection($section);
		$self->setElement(Dictionary::ELEMENT_STREAM_CONTENT_WA);

		return $self;
	}

	public function setType(string $type): self
	{
		$this->type = $type;

		return $this;
	}

	protected function getTool(): string
	{
		return Dictionary::TOOL_CRM;
	}

	public function setEvent(string $event): self
	{
		$this->eventName = $event;

		return $this;
	}

	/**
	 * @inheritDoc
	 */
	protected function buildCustomData(): array
	{
		return [
			'category' => Dictionary::CATEGORY_COMMUNICATION,
			'sub_section' => Dictionary::SUB_SECTION_DETAILS,
			'event' => $this->eventName,
			'type' => $this->type,
		];
	}
}