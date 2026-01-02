<?php

declare(strict_types=1);

namespace Bitrix\Disk\QuickAccess\Storage;

final class NullStorage extends ScopeStorage
{
	public function __construct(array $options = [])
	{
	}

	protected function getRaw(string $key): ?string
	{
		return null;
	}

	protected function setRaw(string $key, string $value, int $ttl = 0): bool
	{
		return true;
	}

	protected function removeRaw(string $key): bool
	{
		return true;
	}

	protected function isExpectedResource($resource): bool
	{
		return false;
	}

	protected function closeConnection(): void
	{
	}

	public function hasScope(string $userToken, string $scope): bool
	{
		return false;
	}

	public function addScope(string $userToken, string $scope, int $ttl = self::DEFAULT_SCOPE_TTL): bool
	{
		return true;
	}

	public function removeScope(string $userToken, string $scope): bool
	{
		return true;
	}

	public function saveFileMetadata(int $fileId, array $metadata, int $ttl = self::DEFAULT_FILE_METADATA_TTL): bool
	{
		return true;
	}

	public function getFileMetadata(int $fileId): ?array
	{
		return null;
	}

	public function cleanupExpiredScopes(string $userToken): bool
	{
		return true;
	}

	protected function getUserScopes(string $userToken): array
	{
		return [];
	}

	protected function saveUserScopes(string $userToken, array $scopes): bool
	{
		return true;
	}

	protected function refreshScopeTtl(string $userToken, string $scope, int $ttl = self::DEFAULT_SCOPE_TTL): bool
	{
		return true;
	}
}