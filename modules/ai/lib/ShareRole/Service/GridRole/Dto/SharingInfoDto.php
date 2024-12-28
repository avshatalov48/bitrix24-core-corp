<?php declare(strict_types = 1);

namespace Bitrix\AI\ShareRole\Service\GridRole\Dto;

class SharingInfoDto
{
	/** @var ShareDto[]  */
	protected array $codes = [];

	/** @var ShareDto[]  */
	protected array $users = [];

	protected array $userIdList = [];

	public function addForCodes(ShareDto $shareDto): void
	{
		$this->codes[$shareDto->getCode()] = $shareDto;
	}

	public function getByCode(string $code): ?ShareDto
	{
		if ($this->hasCode($code))
		{
			return $this->codes[$code];
		}

		return null;
	}

	public function hasCode(string $code): bool
	{
		return array_key_exists($code, $this->codes);
	}

	public function addByUserData(int $userId, ShareDto $shareDto): void
	{
		$this->users[$userId] = $shareDto;
	}

	public function isEmptyUsersIdList(): bool
	{
		return empty($this->userIdList);
	}

	public function isEmptyUsers(): bool
	{
		return empty($this->users);
	}

	public function getUserById($userId): ?ShareDto
	{
		if (array_key_exists($userId, $this->users))
		{
			return $this->users[$userId];
		}

		return null;
	}

	public function addUserId(int $userId): void
	{
		$this->userIdList[] = $userId;
	}

	public function getUserIdList(): array
	{
		return array_unique($this->userIdList);
	}
}