<?php

namespace Bitrix\Crm\Service\Communication\Channel\Event;

final class ChannelEventParamSettings
{
	private bool $processAccordingType = true;

	public function isProcessAccordingType(): bool
	{
		return $this->processAccordingType;
	}

	public function setProcessAccordingType(bool $processAccordingType): ChannelEventParamSettings
	{
		$this->processAccordingType = $processAccordingType;

		return $this;
	}


	public function toArray(): array
	{
		return [
			'processAccordingType' => $this->processAccordingType,
		];
	}
}
