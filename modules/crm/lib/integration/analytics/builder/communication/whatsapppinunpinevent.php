<?php

namespace Bitrix\Crm\Integration\Analytics\Builder\communication;

use Bitrix\Crm\Integration\Analytics\Builder\AbstractBuilder;
use Bitrix\Crm\Integration\Analytics\Dictionary;

class WhatsAppPinUnpinEvent extends AbstractBuilder
{
	public static function createDefault(?string $section = null): self
	{
		$self = new self();
		$self->setSection($section);

		return $self;
	}

    protected function getTool(): string
    {
		return Dictionary::TOOL_CRM;
    }

    /**
     * @inheritDoc
     */
    protected function buildCustomData(): array
    {
		return [
			'category' => Dictionary::CATEGORY_COMMUNICATION,
			'event' => Dictionary::EVENT_WA_TIMELINE,
			'type' => Dictionary::TYPE_WA_ACTIVITY_CREATE,
			'sub_section' => Dictionary::SUB_SECTION_DETAILS,
		];
    }
}