<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1\Response;

class MessageSendResponse implements \JsonSerializable
{
	public function __construct(
		public readonly bool $isSuccess,
		public readonly string $errorText = '',
	)
	{
	}

	public function jsonSerialize(): array
	{
		return [
			'isSuccess' => $this->isSuccess,
			'errorText' => $this->errorText,
		];
	}
}
