<?php

namespace Bitrix\SignMobile\Item;

use Bitrix\Main\Type\DateTime;
use Bitrix\SignMobile\Contract;

class Notification implements Contract\PushNotification
{
	public function __construct(
		public ?int $type = null,
		public int $userId = 0,
		public ?int $signMemberId = null,
		public ?DateTime $dateUpdate = null,
		public ?DateTime $dateCreate = null,
		public ?int $id = null,
	) {}

	public function getType(): ?int
	{
		return $this->type;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getSignMemberId(): ?int
	{
		return $this->signMemberId;
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
