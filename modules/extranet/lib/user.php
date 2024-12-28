<?php

/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2024 Bitrix
 */

namespace Bitrix\Extranet;

class User
{
	private int $userId;

	public function __construct(?int $userId)
	{
		$this->userId = (int) $userId;
	}

	public function getId(): int
	{
		return $this->userId;
	}

	public function isExtranet(): bool
	{
		return $this->hasExtranetUserGroup() === true;
	}

	private function hasExtranetUserGroup(): bool
	{
		return in_array((int) \CExtranet::getExtranetUserGroupId(), $this->getGroups());
	}

	private function getGroups(): array
	{
		global $USER;

		$groups = ($USER instanceof \CUser && $USER->GetID() === $this->userId)
			? $USER->GetUserGroupArray() : \CUser::GetUserGroup($this->userId);

		return array_map('intval', is_array($groups) ? $groups : []);
	}

	public function getFields(): array
	{
		$result = \CUser::GetById($this->userId)->fetch();

		return is_array($result) ? $result : [];
	}
}
