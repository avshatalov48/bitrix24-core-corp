<?php
abstract class CCloudStorageService
{
	protected $verb = '';
	protected $host = '';
	protected $url = '';

	protected $errno = 0;
	protected $errstr = '';

	protected $status = 0;
	protected $headers =/*.(array[string]string).*/array();
	protected $result = '';

	public $tokenHasExpired = false;
	/**
	 * @return CCloudStorageService
	 * @deprecated
	*/
	abstract public function GetObject();
	/**
	 * @return string
	*/
	abstract public function GetID();
	/**
	 * @return string
	*/
	abstract public function GetName();
	/**
	 * @return array[string]string
	*/
	abstract public function GetLocationList();
	/**
	 * @param array[string]string $arBucket
	 * @param bool $bServiceSet
	 * @param string $cur_SERVICE_ID
	 * @param bool $bVarsFromForm
	 * @return string
	*/
	abstract public function GetSettingsHTML($arBucket, $bServiceSet, $cur_SERVICE_ID, $bVarsFromForm);
	/**
	 * @param array[string]string $arBucket
	 * @param array[string]string & $arSettings
	 * @return bool
	*/
	abstract public function CheckSettings($arBucket, &$arSettings);
	/**
	 * @param array[string]string $arBucket
	 * @return bool
	*/
	abstract public function CreateBucket($arBucket);
	/**
	 * @param array[string]string $arBucket
	 * @return bool
	*/
	abstract public function DeleteBucket($arBucket);
	/**
	 * @param array[string]string $arBucket
	 * @return bool
	*/
	abstract public function IsEmptyBucket($arBucket);
	/**
	 * @param array[string]string $arBucket
	 * @param mixed $arFile
	 * @return string
	*/
	abstract public function GetFileSRC($arBucket, $arFile);
	/**
	 * @param array[string]string $arBucket
	 * @param string $filePath
	 * @return bool
	*/
	abstract public function FileExists($arBucket, $filePath);
	/**
	 * @param array[string]string $arBucket
	 * @param mixed $arFile
	 * @param string $filePath
	 * @return bool
	*/
	abstract public function FileCopy($arBucket, $arFile, $filePath);
	/**
	 * @param array[string]string $arBucket
	 * @param mixed $arFile
	 * @param string $filePath
	 * @return bool
	*/
	abstract public function DownloadToFile($arBucket, $arFile, $filePath);
	/**
	 * @param array[string]string $arBucket
	 * @param string $filePath
	 * @return bool
	*/
	abstract public function DeleteFile($arBucket, $filePath);
	/**
	 * @param array[string]string $arBucket
	 * @param string $filePath
	 * @param mixed $arFile
	 * @return bool
	*/
	abstract public function SaveFile($arBucket, $filePath, $arFile);
	/**
	 * @param array[string]string $arBucket
	 * @param string $filePath
	 * @param bool $bRecursive
	 * @return array[string][int]string
	*/
	abstract public function ListFiles($arBucket, $filePath, $bRecursive = false);
	/**
	 * @param array[string]string $arBucket
	 * @param string $sourcePath
	 * @param string $targetPath
	 * @param bool $overwrite
	 * @return bool
	*/
	public function FileRename($arBucket, $sourcePath, $targetPath, $overwrite = true)
	{
		if ($this->FileExists($arBucket, $sourcePath))
		{
			$contentType = $this->headers["Content-Type"];
		}
		else
		{
			return false;
		}

		if ($this->FileExists($arBucket, $targetPath))
		{
			if (!$overwrite)
			{
				return false;
			}

			if (!$this->DeleteFile($arBucket, $targetPath))
			{
				return false;
			}
		}

		$arFile = array(
			"SUBDIR" => '',
			"FILE_NAME" => ltrim($sourcePath, "/"),
			"CONTENT_TYPE" => $contentType,
		);

		if (!$this->FileCopy($arBucket, $arFile, $targetPath))
		{
			return false;
		}

		if (!$this->DeleteFile($arBucket, $sourcePath))
		{
			return false;
		}

		return true;
	}
	/**
	 * @param array[string]string $arBucket
	 * @param mixed & $NS
	 * @param string $filePath
	 * @param float $fileSize
	 * @param string $ContentType
	 * @return bool
	*/
	abstract public function InitiateMultipartUpload($arBucket, &$NS, $filePath, $fileSize, $ContentType);
	/**
	 * @return float
	*/
	abstract public function GetMinUploadPartSize();
	/**
	 * @param array[string]string $arBucket
	 * @param mixed & $NS
	 * @param string $data
	 * @return bool
	*/
	abstract public function UploadPart($arBucket, &$NS, $data);
	/**
	 * @param array[string]string $arBucket
	 * @param mixed & $NS
	 * @return bool
	*/
	abstract public function CompleteMultipartUpload($arBucket, &$NS);
	/**
	 * @param string $name
	 * @param string $value
	 * @return void
	*/
	public function SetHeader($name, $value)
	{
	}
	/**
	 * @param string $name
	 * @return void
	 */
	public function UnsetHeader($name)
	{
	}
	/**
	 * @param bool $state
	 * @return void
	 */
	public function SetPublic($state = true)
	{
	}
	/**
	 * @return array[string]string
	*/
	function getHeaders()
	{
		return $this->headers;
	}
	/**
	 * @return int
	*/
	function GetLastRequestStatus()
	{
		return $this->status;
	}
	/**
	 * @param string $headerName
	 * @return string
	*/
	function GetLastRequestHeader($headerName)
	{
		$loweredName = strtolower($headerName);
		foreach ($this->headers as $name => $value)
		{
			if (strtolower($name) === $loweredName)
				return $value;
		}
		return null;
	}
	/**
	 * @return CCloudStorageService
	*/
	public static function GetObjectInstance()
	{
		return new static();
	}
}
