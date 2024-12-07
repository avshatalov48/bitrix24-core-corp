<?php

namespace Bitrix\Crm\Service\Communication\Channel\Event;

final class ChannelEventParam
{
	public function __construct(
		private readonly string $code,
		private readonly mixed $value,
		private readonly ?ChannelEventParamSettings $channelEventParamSettings = null,
	)
	{

	}

	public function getCode(): string
	{
		return $this->code;
	}

	public function getValue(): mixed
	{
		return $this->value;
	}

	public function getChannelEventParamSettings(): ?ChannelEventParamSettings
	{
		return $this->channelEventParamSettings;
	}

	public function isProcessAccordingType(): bool
	{
		return (
			$this->channelEventParamSettings === null
			|| $this->channelEventParamSettings->isProcessAccordingType()
		);
	}

	public function toArray(): array
	{
		return [
			'code' => $this->code,
			'value' => $this->value,
			'channelEventParamSettings' => $this->channelEventParamSettings?->toArray(),
		];
	}
}
