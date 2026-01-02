<?php

namespace Bitrix\SignMobile\Contract;

use Bitrix\Main\Type\DateTime;

interface PushNotification
{
	public function getId(): ?int;
	public function getType(): ?int;

	public function getUserId(): int;

	public function getDateUpdate(): ?DateTime;

	public function getDataCreate(): ?DateTime;

	public function getSignMemberId(): ?int;
}