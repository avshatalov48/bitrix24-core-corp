<?php

namespace Bitrix\Disk\Document;

use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\Error\IErrorable;
use Bitrix\Disk\SpecificFolder;
use Bitrix\Disk\TypeFile;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Web\HttpClient;

Loc::loadMessages(__FILE__);

abstract class DocumentHandler implements IErrorable
{
	const SPECIFIC_FOLDER_CODE            = SpecificFolder::CODE_FOR_UPLOADED_FILES;

	const ERROR_INVALID_RESPONSE_STATUS   = 'DISK_DOC_HANDLER_22002';
	const ERROR_REQUIRED_PARAMETER        = 'DISK_DOC_HANDLER_22003';

	const ERROR_CODE_INSUFFICIENT_SCOPE   = 'DISK_DOC_HANDLER_220022';
	const ERROR_CODE_INVALID_CREDENTIALS  = 'DISK_DOC_HANDLER_220023';
	const ERROR_CODE_NOT_INSTALLED_APP    = 'DISK_DOC_HANDLER_220024';
	const ERROR_CODE_NOT_GRANTED_APP      = 'DISK_DOC_HANDLER_220025';
	const ERROR_CODE_INVALID_ACCESS_LEVEL = 'DISK_DOC_HANDLER_220026';
	const ERROR_CODE_APP_NOT_CONFIGURED   = 'DISK_DOC_HANDLER_220027';
	const ERROR_CODE_APP_IN_BLACKLIST     = 'DISK_DOC_HANDLER_220028';
	const ERROR_CODE_UNKNOWN              = 'DISK_DOC_HANDLER_220029';
	const ERROR_CODE_NOT_FOUND            = 'DISK_DOC_HANDLER_220030';
	const ERROR_CODE_NOT_FOUND_ETAG       = 'DISK_DOC_HANDLER_220031';


	/** @var  string */
	protected $accessToken;
	/** @var int */
	protected $userId;

	/** @var  ErrorCollection */
	protected $errorCollection;
	/** @var \CSocServAuth */
	protected $oauthService;

	public function __construct($userId)
	{
		$this->errorCollection = new ErrorCollection;
		$this->userId = $userId;
	}

	/**
	 * @return string the fully qualified name of this class.
	 */
	public static function className()
	{
		return get_called_class();
	}

	protected function getOAuthServiceClass(): string
	{
		throw new NotImplementedException();
	}

	/**
	 * Returns OAuth service.
	 *
	 * @return \CSocServAuth
	 */
	protected function getOAuthService(): \CSocServAuth
	{
		if ($this->oauthService === null)
		{
			$authServiceClass = $this->getOAuthServiceClass();

			$this->oauthService = new $authServiceClass($this->userId);
			foreach ($this->getScopes() as $scope)
			{
				$this->oauthService->getEntityOAuth()->addScope($scope);
			}
			foreach ($this->getScopesForRemove() as $scope)
			{
				$this->oauthService->getEntityOAuth()->removeScope($scope);
			}
		}

		return $this->oauthService;
	}

	protected function getScopesForRemove(): array
	{
		return [];
	}

	protected function getScopes(): array
	{
		return [];
	}

	/**
	 * Returns user id.
	 *
	 * @return int
	 */
	public function getUserId()
	{
		return $this->userId;
	}

	/**
	 * Sets user id.
	 *
	 * @param $userId
	 * @return int
	 */
	public function setUserId($userId)
	{
		$this->userId = $userId;

		return $this;
	}

	public static function listEditableExtensions(): array
	{
		return [
			'doc',
			'docx',
			'xls',
			'xlsx',
			'ppt',
			'pptx',
			'xodt',
			'flp',
			'board',
		];
	}

	/**
	 * Detect by extension editable or not
	 * @param $extension
	 * @return bool
	 */
	public static function isEditable($extension)
	{
		$extension = mb_strtolower($extension);
		$editableExtensions = static::listEditableExtensions();

		return
			in_array($extension, $editableExtensions, true)
			|| in_array(ltrim($extension, '.'), $editableExtensions, true)
		;
	}

	/**
	 * Detect by extension needle or not convert?
	 * We work with latest office
	 * @param $extension
	 * @return bool
	 */
	public static function isNeedConvertExtension($extension)
	{
		static $convertFormat = array(
			'doc' => 'docx',
			'.doc' => '.docx',
			'xls' => 'xlsx',
			'.xls' => '.xlsx',
			'ppt' => 'pptx',
			'.ppt' => '.pptx',
		);

		return isset($convertFormat[$extension]) || isset($convertFormat[mb_strtolower($extension)]);
	}

	public static function getConvertExtension($extension)
	{
		static $convertFormat = array(
			'doc' => 'docx',
			'.doc' => '.docx',
			'xls' => 'xlsx',
			'.xls' => '.xlsx',
			'ppt' => 'pptx',
			'.ppt' => '.pptx',
		);
		if(isset($convertFormat[$extension]))
		{
			return $convertFormat[$extension];
		}
		if(isset($convertFormat[mb_strtolower($extension)]))
		{
			return $convertFormat[mb_strtolower($extension)];
		}
		return null;
	}

	/**
	 * Internal code. Identificate document handler
	 *
	 * Max length is 10 chars
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return string
	 */
	public static function getCode()
	{
		throw new NotImplementedException;
	}

	/**
	 * Public name document handler. May show in user interface.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return string
	 */
	public static function getName()
	{
		throw new NotImplementedException;
	}

	/**
	 * Create new blank file in cloud service.
	 * It is not necessary set shared rights on file.
	 * @param FileData $fileData
	 * @return FileData|null
	 */
	abstract public function createBlankFile(FileData $fileData);

	/**
	 * Create file in cloud service by upload from us server.
	 * Necessary set shared rights on file for common work.
	 *
	 * @param FileData $fileData
	 * @return FileData|null
	 */
	abstract public function createFile(FileData $fileData);

	/**
	 * Gets a file's metadata by ID.
	 *
	 * @param FileData $fileData
	 * @return array|null Describes file (id, title, size)
	 */
	abstract public function getFileMetadata(FileData $fileData);

	/**
	 * Download file from cloud service by FileData::id, put contents in FileData::src
	 * @param FileData $fileData
	 * @return FileData|null
	 */
	abstract public function downloadFile(FileData $fileData);

	/**
	 * Download part of file from cloud service by FileData::id, put contents in FileData::src
	 * @param FileData $fileData
	 * @param          $startRange
	 * @param          $chunkSize
	 * @return FileData|null
	 */
	abstract public function downloadPartFile(FileData $fileData, $startRange, $chunkSize);

	/**
	 * Delete file from cloud service by FileData::id
	 * @param FileData $fileData
	 * @return bool
	 */
	abstract public function deleteFile(FileData $fileData);

	/**
	 * Get data for showing preview file.
	 * Array must be contains keys: id, viewUrl, neededDelete, neededCheckView
	 * @param FileData $fileData
	 * @return array|null
	 */
	abstract public function getDataForViewFile(FileData $fileData);

	/**
	 * Tells if file in cloud service was changed. For example, the method compares created date and modified date.
	 *
	 * @param array $currentMetadata Metadata (@see \Bitrix\Disk\Document::getFileMetadata());
	 * @param array $oldMetadata Old metadata.
	 * @return bool
	 */
	public function wasChangedAfterCreation(array $currentMetadata, array $oldMetadata = array())
	{
		if(isset($currentMetadata['etag'], $oldMetadata['etag']))
		{
			return $currentMetadata['etag'] !== $oldMetadata['etag'];
		}

		$this->errorCollection[] = new Error('Could not get etag', self::ERROR_CODE_NOT_FOUND_ETAG);

		return false;
	}

	/**
	 * Check success view file in service.
	 * @param FileData $fileData
	 * @return bool|null
	 */
	public function checkViewFile(FileData $fileData)
	{
		return true;
	}

	/**
	 * Execute this method for check potential possibility get access token.
	 * @return bool
	 */
	abstract public function checkAccessibleTokenService();

	/**
	 * Return link for authorize user in external service.
	 * @param string $mode
	 * @return string
	 */
	abstract public function getUrlForAuthorizeInTokenService($mode = 'modal');

	/**
	 * Request and store access token (self::accessToken) for self::userId
	 * @return $this
	 */
	abstract public function queryAccessToken();

	/**
	 * @return string
	 */
	public function getAccessToken()
	{
		return $this->accessToken;
	}

	/**
	 * @return bool
	 */
	public function hasAccessToken()
	{
		return !empty($this->accessToken);
	}

	public function getErrorContainer()
	{
		return $this->errorCollection;
	}

	/**
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * @inheritdoc
	 */
	public function getErrorsByCode($code)
	{
		return $this->errorCollection->getErrorsByCode($code);
	}

	/**
	 * @inheritdoc
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	/**
	 * Need re-run oauth authorization?
	 * @return bool
	 */
	public function isRequiredAuthorization()
	{
		if(!$this->errorCollection->hasErrors())
		{
			return false;
		}

		return
			(bool)$this->errorCollection->getErrorByCode(self::ERROR_CODE_INSUFFICIENT_SCOPE) ||
			(bool)$this->errorCollection->getErrorByCode(self::ERROR_CODE_INVALID_CREDENTIALS)
		;
	}

	/**
	 * @param HttpClient $http
	 * @return bool
	 */
	public function checkHttpResponse(HttpClient $http)
	{
		$status = (int)$http->getStatus();

		if($status === 401)
		{
			$this->errorCollection->add(array(
				new Error('Invalid credentials (401)', self::ERROR_CODE_INVALID_CREDENTIALS)
			));
			return false;
		}
		elseif($status === 403)
		{
			$headers = $http->getHeaders();

			$headerAuthenticate = $headers->get('WWW-Authenticate');
			if(is_string($headerAuthenticate) && mb_strpos($headerAuthenticate, 'insufficient') !== false)
			{
				$this->errorCollection->add(array(
					new Error('Insufficient scope (403)', self::ERROR_CODE_INSUFFICIENT_SCOPE)
				));
			}
			else
			{
				$this->errorCollection->add(array(
					new Error("Unknown error ({$status})", self::ERROR_CODE_UNKNOWN)
				));
			}

			return false;
		}
		elseif($status === 404)
		{
			$this->errorCollection->add(array(
				new Error('The resource could not be found (404)', self::ERROR_CODE_NOT_FOUND)
			));
			return false;
		}
		elseif( !($status >= 200 && $status < 300) )
		{
			$this->errorCollection->add(array(
				new Error("Invalid response status code ({$status})", self::ERROR_INVALID_RESPONSE_STATUS)
			));
			return false;
		}

		return true;
	}

	protected function recoverExtensionInName(string &$fileName, string $mimeType): bool
	{
		$originalExtension = TypeFile::getExtensionByMimeType($mimeType);
		$newExtension = mb_strtolower(trim(getFileExtension($fileName), '.'));
		if ($originalExtension !== $newExtension)
		{
			$fileName .= '.' . $originalExtension;

			return true;
		}

		return false;
	}

	/**
	 * @param array $inputParams
	 * @param array $required
	 * @return bool
	 */
	protected function checkRequiredInputParams(array $inputParams, array $required)
	{
		foreach ($required as $item)
		{
			if(
				!isset($inputParams[$item]) ||
				(
					!$inputParams[$item] &&
					!(is_string($inputParams[$item]) && $inputParams[$item] !== '') &&
					!($inputParams[$item] === 0)
				)
			)
			{
				$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_DOC_HANDLER_ERROR_REQUIRED_PARAMETER', array('#PARAM#' => $item)), self::ERROR_REQUIRED_PARAMETER)));
				return false;
			}
		}

		return true;
	}

	protected function retryMethod($method, array $args, $numberOfTimesToRetry = 2)
	{
		if($numberOfTimesToRetry <= 0)
		{
			$numberOfTimesToRetry = 1;
		}

		$reflectionMethod = new \ReflectionMethod($this, $method);
		$reflectionMethod->setAccessible(true);

		do
		{
			$numberOfTimesToRetry--;
			$result = $reflectionMethod->invokeArgs($this, $args);
		}
		while($numberOfTimesToRetry > 0 && $result === null);

		return $result;
	}

	/**
	 * Returns normalized metadata.
	 *
	 * @param array $metaData
	 * @return array
	 */
	protected function normalizeMetadata($metaData)
	{
		//todo create class which will describe metaData.
		return $metaData;
	}
}