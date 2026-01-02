<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Dto\User\MiniProfile;

class BaseInfoDto implements \JsonSerializable
{
	public function __construct(
		public int $id,
		public string $name,
		public string $workPosition,
		public int $utcOffset,
		public array $status,
		public string $role,
		public string $url,
		public ?string $avatar = null,
	) {}

	public function jsonSerialize(): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'workPosition' => $this->workPosition,
			'utcOffset' => $this->utcOffset,
			'avatar' => $this->avatar,
			'status' => $this->status,
			'role' => $this->role,
			'url' => $this->url,
		];
	}
}
