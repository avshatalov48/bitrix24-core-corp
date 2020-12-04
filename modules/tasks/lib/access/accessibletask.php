<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access;


use Bitrix\Main\Access\AccessibleItem;

interface AccessibleTask
	extends AccessibleItem
{
	public function getGroupId(): int;
	public function isClosed(): bool;
	public function isDeleted(): bool;
	public function getStatus(): ?int;
	public function isInDepartment(int $userId, bool $recursive = false, array $roles = []): bool;
	public function getMembers(string $role = null): array;
}