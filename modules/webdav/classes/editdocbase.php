<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

abstract class CWebDavEditDocBase
{
	public static $SCOPE = '';
	public static $internalError = null;

	const INTERNAL_ERROR_INSUFFICIENT_SCOPE  = 2;
	const INTERNAL_ERROR_INVALID_CREDENTIALS = 3;

	protected $accessToken;
	/** @var Closure */
	protected $closureReloadAccessToken = null;

	/**
	 * Hack
	 * @param CHTTP $http
	 */
	protected function checkHttpResponse(CHTTP $http)
	{
		//todo this is terror
		if($http->status == '403')
		{
			if(!empty($http->headers['WWW-Authenticate']) && is_string($http->headers['WWW-Authenticate']))
			{
				if(strpos($http->headers['WWW-Authenticate'], 'insufficient') !== false)
				{
					$this->setInternalError(static::INTERNAL_ERROR_INSUFFICIENT_SCOPE);
				}
			}
		}
		elseif($http->status == '401')
		{
			$this->setInternalError(static::INTERNAL_ERROR_INVALID_CREDENTIALS);
		}
	}

	public static function isInvalidCredentialsError()
	{
		return static::getInternalError() == static::INTERNAL_ERROR_INVALID_CREDENTIALS;
	}

	public static function isInsufficientScopeError()
	{
		return static::getInternalError() == static::INTERNAL_ERROR_INSUFFICIENT_SCOPE;
	}

	public static function isRequiredAuthorization()
	{
		return static::isInsufficientScopeError() || static::isInvalidCredentialsError();
	}

	protected static function setInternalError($error)
	{
		static::$internalError = $error;
	}

	public static function getInternalError()
	{
		return static::$internalError;
	}


	/**
	 * @param string $accessToken
	 * @return $this
	 */
	public function setAccessToken($accessToken)
	{
		$this->accessToken = $accessToken;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getAccessToken()
	{
		return $this->accessToken;
	}

	/**
	 * @return bool|string
	 */
	public function getNewAccessToken()
	{
		$closure = $this->getClosureReloadAccessToken();
		if(is_callable($closure))
		{
			return $closure();
		}

		return false;
	}

	/**
	 * @param Closure $closureReloadAccessToken
	 * @return $this
	 */
	public function setClosureReloadAccessToken($closureReloadAccessToken)
	{
		$this->closureReloadAccessToken = $closureReloadAccessToken;

		return $this;
	}

	/**
	 * @return Closure
	 */
	public function getClosureReloadAccessToken()
	{
		return $this->closureReloadAccessToken;
	}


	public static function isEditable($filename)
	{
		static $allowFormat = array(
			'doc'  => 'doc',
			'.doc' => '.doc',
			'docx'  => 'docx',
			'.docx' => '.docx',
			'xls'  => 'xls',
			'.xls' => '.xls',
			'xlsx'  => 'xlsx',
			'.xlsx' => '.xlsx',
			'ppt'  => 'ppt',
			'.ppt' => '.ppt',
			'pptx'  => 'pptx',
			'.pptx' => '.pptx',
		);
		$ext = GetFileExtension($filename);

		return !empty($allowFormat[$ext]) || !empty($allowFormat[strtolower($ext)]);
	}

	public static function isNeedConvertExtension($filename)
	{
		$ext = GetFileExtension($filename);
		static $convertFormat = array(
			'doc'  => 'docx',
			'.doc' => '.docx',
			'xls'  => 'xlsx',
			'.xls' => '.xlsx',
			'ppt'  => 'pptx',
			'.ppt' => '.pptx',
		);

		return !empty($convertFormat[$ext]);
	}

	protected function recoverExtensionInName(array &$fileData, $mimeType)
	{
		$originalExtension = strtolower(trim(CWebDavIblock::getExtensionByMimeType($mimeType), '.'));
		$newExtension = strtolower(trim(GetFileExtension($fileData['name']), '.'));
		if($originalExtension != $newExtension)
		{
			$fileData['name'] = GetFileNameWithoutExtension($fileData['name']) . '.' . $originalExtension;

			return true;
		}

		return false;
	}

	abstract public function publicFile(array $fileData);

	abstract public function insertPermission(array $fileData);

	abstract public function listPermission(array $fileData);

	abstract public function downloadFile(array $fileData);

	abstract public function removeFile(array $fileData);

	abstract public function createFile(array $fileData);

	abstract public function createBlankFile(array $fileData);
}