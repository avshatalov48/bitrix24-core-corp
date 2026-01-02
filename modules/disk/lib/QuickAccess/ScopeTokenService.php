<?php

declare(strict_types=1);

namespace Bitrix\Disk\QuickAccess;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\BaseObject;
use Bitrix\Disk\File;
use Bitrix\Disk\QuickAccess\Storage\ScopeStorage;
use Bitrix\Disk\TypeFile;
use Bitrix\Main\Config\Option;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Security\Random;
use Bitrix\Main\Security\Sign\BadSignatureException;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Main\Web\Cookie;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;

/**
 * Class ScopeTokenService
 * Implements scope-based token management with improved security and reduced storage requirements
 */
class ScopeTokenService
{
	public const COOKIE_NAME = 'DTOKEN';
	public const DEFAULT_COOKIE_TTL = 3600 * 8;
	public const DEFAULT_TOKEN_LENGTH = 16;
	public const DEFAULT_SCOPE_TTL = 7200; // 2 hours
	private const DEFAULT_SIGN_SALT = 'disk-scope-access';
	private const DEFAULT_SCOPE_PREFIX = 'scope:';
	private const DEFAULT_FILE_PREFIX = 'file:';
	private const PROBABILITY_CLEANUP = 5; // % chance of cleanup on each request

	private string $userToken;
	private bool $fastDownload;
	private bool $cleanupTriggered = false;
	private ?Signer $signer = null;
	private array $addedScopes = [];
	private array $savedFileMetadata = [];

	/**
	 * @param ScopeStorage $storage The scope-based storage
	 * @param HttpRequest $httpRequest Current HTTP request
	 * @param HttpResponse $httpResponse HTTP response to send cookies with
	 * @param string|null $signerKey Key for signing tokens
	 */
	public function __construct(
		public readonly ScopeStorage  $storage,
		private readonly HttpRequest  $httpRequest,
		private readonly HttpResponse $httpResponse,
		private readonly ?string      $signerKey
	)
	{
		$this->fastDownload = $this->isFastDownloadEnabled();

		if (!$this->isReady())
		{
			return;
		}

		$userToken = $this->retrieveUserToken();
		if ($userToken)
		{
			$this->userToken = $userToken;
			$this->tryCleanupExpiredScopes();
		}
	}

	/**
	 * Check if Fast Download option is enabled
	 *
	 * @return bool True if fast download is enabled
	 */
	private function isFastDownloadEnabled(): bool
	{
		return Option::get('main', 'bx_fast_download', 'N') === 'Y';
	}

	/**
	 * Attempt cleanup of expired scopes based on probability
	 *
	 * @return void
	 */
	private function tryCleanupExpiredScopes(): void
	{
		if (random_int(1, 100) <= self::PROBABILITY_CLEANUP)
		{
			$this->cleanupExpiredScopes();
		}
	}

	/**
	 * Check if the system is ready to provide token access
	 *
	 * @return bool True if the system is ready, false otherwise
	 */
	private function isReady(): bool
	{
		if (!$this->fastDownload)
		{
			return false;
		}
		if (empty($this->signerKey))
		{
			return false;
		}

		return true;
	}

	/**
	 * Get or create a Signer instance
	 *
	 * @return Signer
	 */
	private function getSigner(): Signer
	{
		if ($this->signer === null)
		{
			$this->signer = new Signer();
			$this->signer->setKey(hash('sha512', $this->signerKey));
		}

		return $this->signer;
	}

	/**
	 * Retrieve and validate user token from cookie
	 *
	 * @return string|null Raw user token or null if not found or invalid
	 */
	private function retrieveUserToken(): ?string
	{
		$signedToken = $this->httpRequest->getCookie(self::COOKIE_NAME);
		if (!$signedToken)
		{
			return null;
		}

		try
		{
			$rawToken = $this->getSigner()->unsign($signedToken);
		}
		catch (BadSignatureException)
		{
			return null;
		}

		if (!\is_string($rawToken) || mb_strlen($rawToken) !== self::DEFAULT_TOKEN_LENGTH)
		{
			return null;
		}

		return $rawToken;
	}

	/**
	 * Generate a cookie with the signed user token
	 *
	 * @param string $token The raw token to sign and set
	 * @return Cookie Cookie instance
	 */
	private function generateCookieWithUserToken(string $token): Cookie
	{
		$secure = (Option::get('main', 'use_secure_password_cookies', 'N') === 'Y' && $this->httpRequest->isHttps());

		$cookie = new Cookie(self::COOKIE_NAME, $token, time() + self::DEFAULT_COOKIE_TTL);
		$cookie
			->setHttpOnly(true)
			->setSecure($secure)
		;

		return $cookie;
	}

	/**
	 * Ensures the user token exists, creating it if necessary
	 *
	 * @return void
	 */
	private function ensureUserToken(): void
	{
		if (!isset($this->userToken))
		{
			[$this->userToken, $signedToken] = $this->generateUserToken();
			$cookie = $this->generateCookieWithUserToken($signedToken);
			$this->httpResponse->addCookie($cookie);
		}
	}

	/**
	 * Grants access for current user to the given file within the specified scope
	 *
	 * @param AttachedObject|BaseObject $object File object
	 * @param string $scope Scope identifier (e.g. 'chat_123')
	 * @return array|null Result information or null if failed
	 */
	public function grantAccessWithScope(AttachedObject|BaseObject $object, string $scope): ?array
	{
		if (!$this->isReady())
		{
			return null;
		}

		if ($this->grantAccessToScope($scope) === false)
		{
			return null;
		}

		$encryptedScopeData = $this->getEncryptedScopeForObject($object, $scope);
		if ($encryptedScopeData === null)
		{
			return null;
		}

		return [
			'scopeId' => $scope,
			'encryptedScope' => $encryptedScopeData,
		];
	}

	/**
	 * Grant access to the specified scope for the current user
	 *
	 * @param string $scope Scope identifier (e.g. 'chat_123')
	 * @return bool
	 */
	public function grantAccessToScope(string $scope): bool
	{
		if (!$this->isReady())
		{
			return false;
		}

		$this->ensureUserToken();

		if (isset($this->addedScopes[$scope]))
		{
			return true;
		}

		if (!$this->storage->addScope($this->userToken, $scope))
		{
			return false;
		}

		$this->addedScopes[$scope] = true;

		return true;
	}

	/**
	 * Returns the encrypted scope for the given file object. Will be used as _esd={} in URL.
	 *
	 * @param AttachedObject|BaseObject $object File object
	 * @param string $scope Scope identifier (e.g. 'chat_123')
	 * @return string|null Encrypted scope or null if failed
	 */
	public function getEncryptedScopeForObject(AttachedObject|BaseObject $object, string $scope): ?string
	{
		if (!$this->isReady())
		{
			return null;
		}

		$fileInfo = $this->extractFileInfo($object);
		if (empty($fileInfo) || !isset($fileInfo['id']))
		{
			return null;
		}

		$fileId = $fileInfo['id'];
		if (!isset($this->savedFileMetadata[$fileId]))
		{
			if (!$this->storage->saveFileMetadata($fileId, $fileInfo))
			{
				return null;
			}
			
			$this->savedFileMetadata[$fileId] = true;
		}

		return $this->encryptScopeData($scope, $fileId, $object->getName());
	}

	/**
	 * Extract file information for quick access
	 *
	 * @param AttachedObject|BaseObject $attachedObject The object to extract info from
	 * @return array|null File information or null if extraction failed
	 */
	private function extractFileInfo(AttachedObject|BaseObject $attachedObject): ?array
	{
		if (!$this->fastDownload)
		{
			return null;
		}

		$fileObject = $this->extractFileObject($attachedObject);

		if (!$fileObject instanceof File)
		{
			return null;
		}

		$fileData = $fileObject->getFile();

		if (!$fileData)
		{
			return null;
		}

		if (!$this->isMediaFile($attachedObject, $fileData))
		{
			return null;
		}

		$previewFileData = [];
		if (TypeFile::isVideo($fileObject))
		{
			$previewFileData = $fileObject->getView()->getPreviewData();
		}

		$infoForAccelRedirect = $this->getInfoForAccelRedirect($fileData);

		if ($infoForAccelRedirect === null)
		{
			return null;
		}

		if (!empty($previewFileData) && is_array($previewFileData) && isset($previewFileData['ID']))
		{
			$infoForAccelRedirect['preview'] = $this->getInfoForAccelRedirect($previewFileData);
		}
		else
		{
			$infoForAccelRedirect['preview'] = $infoForAccelRedirect;
		}

		return $infoForAccelRedirect;
	}

	private function extractFileObject(AttachedObject|BaseObject $attachedObject): ?BaseObject
	{
		if ($attachedObject instanceof AttachedObject)
		{
			if ($attachedObject->isSpecificVersion())
			{
				return $attachedObject->getVersion()?->getObject();
			}

			return $attachedObject->getFile();
		}

		return $attachedObject;
	}

	/**
	 * Check if the object is an image or media file like video/audio
	 *
	 * @param AttachedObject|BaseObject $attachedObject The object to check
	 * @param array $fileData File data
	 * @return bool True if the object is an image or media file, false otherwise
	 */
	private function isMediaFile(AttachedObject|BaseObject $attachedObject, array $fileData): bool
	{
		if (TypeFile::isVideo($attachedObject))
		{
			return true;
		}

		if (!TypeFile::isImage($attachedObject))
		{
			return false;
		}

		return \CFile::IsImage($attachedObject->getName(), $fileData['CONTENT_TYPE']);
	}

	/**
	 * Get information for X-Accel-Redirect
	 *
	 * @param array $fileData File data
	 * @return array|null Information for redirect or null if failed
	 */
	private function getInfoForAccelRedirect(array $fileData): ?array
	{
		if (!$this->fastDownload)
		{
			return null;
		}

		$cloudHandlerId = (int)($fileData['HANDLER_ID'] ?? 0);
		$fromClouds = $cloudHandlerId > 0;

		$filename = $fileData['SRC'];
		$filenameEncoded = Uri::urnEncode($filename, 'UTF-8');

		if ($fromClouds)
		{
			$filenameDisableProto = preg_replace('~^(https?)(\://)~i', '\\1.', $filenameEncoded);
			$cloudUploadPath = Option::get('main', 'bx_cloud_upload', '/upload/bx_cloud_upload/');
			$filePath = rawurlencode($cloudUploadPath . $filenameDisableProto);
		}
		else
		{
			$filePath = $filenameEncoded;
		}

		return [
			'handlerId' => $fileData['HANDLER_ID'] ?? 0,
			'width' => $fileData['WIDTH'],
			'height' => $fileData['HEIGHT'],
			'path' => $filePath,
			'dir' => $fileData['SUBDIR'],
			'filename' => $fileData['FILE_NAME'],
			'contentType' => TypeFile::normalizeMimeType($fileData['CONTENT_TYPE'], $filePath),
			'expirationTime' => time() + ScopeStorage::DEFAULT_FILE_METADATA_TTL,
			'id' => (int)$fileData['ID'],
		];
	}

	/**
	 * Generate a secure user token
	 *
	 * @return array{string, string} Generated raw token and its signed value
	 */
	private function generateUserToken(): array
	{
		$randValue = Random::getString(self::DEFAULT_TOKEN_LENGTH, true);
		$signedValue = $this->getSigner()->sign($randValue);

		return [$randValue, $signedValue];
	}

	/**
	 * Generate scope cipher for URL parameters
	 *
	 * @param string $scope Scope identifier
	 * @param int $bFileId File ID (b_file.ID)
	 * @return string
	 */
	private function encryptScopeData(string $scope, int $bFileId, string $filename): string
	{
		$scopeData = [
			'v' => 1,
			'scope' => $scope,
			'fileId' => $bFileId,
			'l' => $filename,
		];

		$packedData = Json::encode($scopeData);

		$cipher = new DeterministicCipher();
		$cipher->setIvSalt(\CMain::GetServerUniqID());
		$encryptedData = $cipher->encrypt($packedData, $this->signerKey);

		return base64_encode($encryptedData);
	}

	/**
	 * Clean up expired scopes for the current user
	 * This is called probabilistically to avoid performance impact on every request
	 */
	private function cleanupExpiredScopes(): void
	{
		if (!isset($this->userToken) || $this->cleanupTriggered)
		{
			return;
		}

		$this->cleanupTriggered = true;
		$this->storage->cleanupExpiredScopes($this->userToken);
	}
}
