<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1\Response;

class MessageStatusGetResponse implements \JsonSerializable
{
	public function __construct(
		public readonly string $title,
		public readonly string $description,
		public readonly string $semantic,
		public readonly bool $isDisabled = false,
	)
	{
	}

	public function jsonSerialize(): array
	{
		return [
			'title' => $this->title,
			'description' => $this->description,
			'semantic' => $this->semantic,
			'isDisabled' => $this->isDisabled,
		];
	}
}
