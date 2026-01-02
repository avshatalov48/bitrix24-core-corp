<?php

declare(strict_types=1);

namespace Bitrix\Im\V2\Chat\Member;

use Bitrix\Im\V2\Entity\User\UserPopupItem;
use Bitrix\Im\V2\Rest\PopupData;
use Bitrix\Im\V2\Rest\PopupDataAggregatable;
use Bitrix\Im\V2\Rest\RestEntity;

class MemberItem implements RestEntity, PopupDataAggregatable
{
	private int $relationId;
	private int $userId;
	private string $role;

	public function __construct(int $relationId, int $userId, string $role)
	{
		$this->relationId = $relationId;
		$this->userId = $userId;
		$this->role = $role;
	}

	public function getId(): ?int
	{
		return $this->relationId;
	}

	public function getRole(): string
	{
		return $this->role;
	}

	public function getPopupData(array $excludedList = []): PopupData
	{
		return new PopupData([new UserPopupItem([$this->userId])], $excludedList);
	}

	public static function getRestEntityName(): string
	{
		return 'member';
	}

	public function toRestFormat(array $option = []): ?array
	{
		return [
			'relationId' => $this->relationId,
			'userId' => $this->userId,
			'role' => mb_strtolower($this->role),
		];
	}
}
