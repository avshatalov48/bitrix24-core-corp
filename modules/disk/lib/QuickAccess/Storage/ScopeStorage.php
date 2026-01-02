<?php

declare(strict_types=1);

namespace Bitrix\Disk\QuickAccess\Storage;

/**
 * Class ScopeStorage
 * Implements storage for token scopes with TTL and improved security
 * Can be used with either Redis or Memcache as a backend
 */
abstract class ScopeStorage extends AbstractStorage
{
	public const MAX_USER_SCOPES = 500; // Maximum number of scopes per user
	public const DEFAULT_SCOPE_TTL = 7200; // Default TTL for scopes (2 hours)
	public const DEFAULT_FILE_METADATA_TTL = 604800; // Default TTL for file metadata (1 week)

	protected const FILE_PREFIX = 'file:';
	protected const USER_SCOPES_PREFIX = 'userScopes:';

	/**
	 * Check if the user has the specified scope
	 *
	 * @param string $userToken The user token
	 * @param string $scope The scope to check
	 * @return bool True if the user has the scope, false otherwise
	 */
	public function hasScope(string $userToken, string $scope): bool
	{
		$scopes = $this->getUserScopes($userToken);
		if (!isset($scopes[$scope]))
		{
			return false;
		}

		// Check if scope hasn't expired
		if ($scopes[$scope] < time())
		{
			$this->removeScope($userToken, $scope);

			return false;
		}

		return true;
	}

	/**
	 * Add a scope to a user
	 *
	 * @param string $userToken The user token
	 * @param string $scope The scope to add
	 * @param int $ttl TTL in seconds
	 * @return bool True on success, false on failure
	 */
	public function addScope(string $userToken, string $scope, int $ttl = self::DEFAULT_SCOPE_TTL): bool
	{
		$scopes = $this->getUserScopes($userToken);

		$expirationTime = time() + $ttl;
		$scopes[$scope] = $expirationTime;

		if (\count($scopes) > self::MAX_USER_SCOPES)
		{
			// Remove oldest scopes
			asort($scopes);
			$scopes = \array_slice($scopes, -self::MAX_USER_SCOPES, null, true);
		}

		return $this->saveUserScopes($userToken, $scopes);
	}

	/**
	 * Remove a scope from a user
	 *
	 * @param string $userToken The user token
	 * @param string $scope The scope to remove
	 * @return bool True on success, false on failure
	 */
	public function removeScope(string $userToken, string $scope): bool
	{
		$scopes = $this->getUserScopes($userToken);
		if (!isset($scopes[$scope]))
		{
			return true;
		}

		unset($scopes[$scope]);

		return $this->saveUserScopes($userToken, $scopes);
	}

	/**
	 * Save file metadata
	 *
	 * @param int $fileId File ID (b_file.ID)
	 * @param array $metadata File metadata
	 * @param int $ttl TTL in seconds
	 * @return bool True on success, false on failure
	 */
	public function saveFileMetadata(int $fileId, array $metadata, int $ttl = self::DEFAULT_FILE_METADATA_TTL): bool
	{
		$key = $this->getFileKey($fileId);

		try
		{
			return $this->set($key, json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), $ttl);
		}
		catch (\JsonException)
		{
			return false;
		}
	}

	/**
	 * Get file metadata
	 *
	 * @param int $fileId File ID
	 * @return array|null File metadata or null if not found
	 */
	public function getFileMetadata(int $fileId): ?array
	{
		$key = $this->getFileKey($fileId);
		$data = $this->get($key);
		if ($data === null)
		{
			return null;
		}

		try
		{
			return json_decode($data, true, 5, JSON_THROW_ON_ERROR);
		}
		catch (\JsonException)
		{
			return null;
		}
	}

	/**
	 * Clean up expired scopes for a user
	 *
	 * @param string $userToken The user token
	 * @return bool True on success, false on failure
	 */
	public function cleanupExpiredScopes(string $userToken): bool
	{
		$scopes = $this->getUserScopes($userToken);

		$now = time();
		$changed = false;

		foreach ($scopes as $scope => $expiration)
		{
			if ($expiration < $now)
			{
				unset($scopes[$scope]);
				$changed = true;
			}
		}

		if ($changed)
		{
			return $this->saveUserScopes($userToken, $scopes);
		}

		return true;
	}

	/**
	 * Get all user scopes
	 *
	 * @param string $userToken The user token
	 * @return array Associative array of scope => expiration time or null if not found
	 */
	protected function getUserScopes(string $userToken): array
	{
		$key = $this->getUserScopesKey($userToken);
		$data = $this->get($key);
		if ($data === null)
		{
			return [];
		}

		try
		{
			return json_decode($data, true, 5, JSON_THROW_ON_ERROR) ?: [];
		}
		catch (\JsonException)
		{
			return [];
		}
	}

	/**
	 * Save user scopes
	 *
	 * @param string $userToken The user token
	 * @param array $scopes Associative array of scope => expiration time
	 * @return bool True on success, false on failure
	 */
	protected function saveUserScopes(string $userToken, array $scopes): bool
	{
		$key = $this->getUserScopesKey($userToken);

		// Find the latest expiration time to set as TTL for the entire scopes object
		$latestExpiration = 0;
		foreach ($scopes as $expiration)
		{
			if ($expiration > $latestExpiration)
			{
				$latestExpiration = $expiration;
			}
		}

		$ttl = max(0, $latestExpiration - time());

		try
		{
			return $this->set($key, json_encode($scopes, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), $ttl);
		}
		catch (\JsonException)
		{
			return false;
		}
	}

	/**
	 * Get user scopes key
	 *
	 * @param string $userToken The user token
	 * @return string The storage key for user scopes
	 */
	protected function getUserScopesKey(string $userToken): string
	{
		return self::USER_SCOPES_PREFIX . $userToken;
	}

	/**
	 * Get file metadata key
	 *
	 * @param int $fileId File ID (b_file.ID)
	 * @return string The storage key for file metadata
	 */
	protected function getFileKey(int $fileId): string
	{
		return self::FILE_PREFIX . $fileId;
	}
}