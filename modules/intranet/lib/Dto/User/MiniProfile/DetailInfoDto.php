<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Dto\User\MiniProfile;

class DetailInfoDto
{
	public function __construct(
		public string $personalMobile,
		public string $innerPhone,
		public string $email,
	) {}

	public function jsonSerialize(): array
	{
		return [
			'personalMobile' => $this->personalMobile,
			'innerPhone' => $this->innerPhone,
			'email' => $this->email,
		];
	}
}
