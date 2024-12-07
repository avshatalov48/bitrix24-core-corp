<?php

namespace Bitrix\SignMobile\Item;

use Bitrix\Main\Type\DateTime;
use Bitrix\SignMobile\Contract;

class Notification implements Contract\PushNotification
{
	public function __construct(
		public ?int $type = 0,
		public ?int $userId = 0,
		public ?int $signMemberId = 0,
		public ?DateTime $dateUpdate = null,
		public ?DateTime $dateCreate = null,
	) {}

	public function getType(): int
	{
		return (int)$this->type;
	}

	public function getUserId(): int
	{
		return (int)$this->userId;
	}

	public function getSignMemberId(): int
	{
		return (int)$this->signMemberId;
	}

	public function getDateUpdate(): ?DateTime
	{
		return $this->dateUpdate;
	}

	public function getDataCreate(): ?DateTime
	{
		return $this->dateCreate;
	}
}
