<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Dto\User\MiniProfile;

class AccessDto implements \JsonSerializable
{
	public function __construct(
		public bool $canChat = false,
	) {}

	public function jsonSerialize(): array
	{
		return [
			'canChat' => $this->canChat,
		];
	}
}
