<?php

namespace Bitrix\Extranet\Contract\Service;

use Bitrix\Extranet\Enum\User\ExtranetRole;
use Bitrix\Main\Result;

interface UserService
{
	public function setRoleById(int $id, ExtranetRole $role): Result;

	public function isCurrentExtranetUserById(int $userId): bool;

	public function isFormerExtranetUserById(int $userId): bool;

	public function getCurrentExtranetUserIds(): array;
	public function getUserIdsByRole(ExtranetRole $role): array;

	public function getFormerExtranetUserIds(): array;

	public function deleteById(int $userId): Result;

	public function clearCache(): void;
}
