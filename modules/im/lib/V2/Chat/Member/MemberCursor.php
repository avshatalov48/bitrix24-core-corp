<?php

declare(strict_types=1);

namespace Bitrix\Im\V2\Chat\Member;

use Bitrix\Im\V2\Chat;
use Bitrix\Im\V2\Rest\RestConvertible;
use Bitrix\Main\Validation\Rule\InArray;
use Bitrix\Main\Validation\Rule\PositiveNumber;

class MemberCursor implements RestConvertible
{
	public function __construct(
		#[InArray([Chat::ROLE_MEMBER, Chat::ROLE_MANAGER, Chat::ROLE_OWNER])]
		public readonly string $role,
		#[PositiveNumber]
		public readonly int $relationId,
	){}

	public static function getRestEntityName(): string
	{
		return 'nextCursor';
	}

	public function toRestFormat(array $option = []): ?array
	{
		return [
			'role' => mb_strtolower($this->role),
			'relationId' => $this->relationId,
		];
	}

	public static function createFromArray(array $parameters): static
	{
		return new static(mb_strtoupper($parameters['role'] ?? ''), (int)($parameters['relationId'] ?? 0));
	}
}
