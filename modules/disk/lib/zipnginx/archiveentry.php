<?php

namespace Bitrix\Disk\ZipNginx;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Text\Encoding;

class ArchiveEntry
{
	const ERROR_REQUIRED_PARAMETER = 'DISK_ZE_22001';

	protected $name;
	protected $path;
	protected $size;
	protected $crc32;

	/** @var ErrorCollection */
	protected $errorCollection;

	/**
	 * Entry constructor.
	 */
	protected function __construct()
	{
		$this->errorCollection = new ErrorCollection;
	}

	/**
	 * Creates Entry from File.
	 *
	 * @param File $file File.
	 * @param null|string $name Name.
	 * @return ArchiveEntry
	 */
	public static function createFromFile(File $file, $name = null)
	{
		return static::createFromFileArray($file->getFile(), $name?: $file->getName());
	}

	/**
	 * Creates Entry from attached object.
	 *
	 * @param AttachedObject $attachedObject Attached object.
	 * @param null|string $name Name.
	 * @return ArchiveEntry
	 */
	public static function createFromAttachedObject(AttachedObject $attachedObject, $name = null)
	{
		if($attachedObject->isSpecificVersion())
		{
			$version = $attachedObject->getVersion();

			return static::createFromFileArray($version->getFile(), $name?: $version->getName());
		}

		return static::createFromFile($attachedObject->getFile(), $name);
	}

	/**
	 * Creates Entry from file array (as \CAllFile).
	 *
	 * @param array $fileArray Array of file from b_file.
	 * @param string $name Name of file.
	 * @return static
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function createFromFileArray(array $fileArray, $name)
	{
		$zipEntry = new static;
		$zipEntry->name = $name;
		$zipEntry->size = $fileArray['FILE_SIZE'];

		if(empty($fileArray['SRC']))
		{
			$fileArray['SRC'] = \CFile::getFileSrc($fileArray);
		}
		$filename = $fileArray['SRC'];
		$fromClouds = false;
		if(!empty($fileArray['HANDLER_ID']))
		{
			$fromClouds = true;
		}
		if($fromClouds)
		{
			$filename = preg_replace('~^(http[s]?)(\://)~i', '\\1.' , $filename);
			$cloudUploadPath = Option::get('main', 'bx_cloud_upload', '/upload/bx_cloud_upload/');
			$zipEntry->path = $cloudUploadPath . $filename;
		}
		else
		{
			$zipEntry->path = Driver::getInstance()->getUrlManager()->encodeUrn(
				Encoding::convertEncoding($filename, LANG_CHARSET, 'UTF-8')
			);
		}

		return $zipEntry;
	}

	/**
	 * Returns name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Returns path.
	 *
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * Returns size in bytes.
	 *
	 * @return int
	 */
	public function getSize()
	{
		return $this->size;
	}

	/**
	 * Returns crc32.
	 *
	 * @return string
	 */
	public function getCrc32()
	{
		return $this->crc32;
	}

	/**
	 * Returns representation zip entry as string.
	 *
	 * @return string
	 */
	public function __toString()
	{
		$crc32 = $this->getCrc32()?: '-';
		$location = $this->getPath();
		$name = Encoding::convertEncoding($this->getName(), LANG_CHARSET, 'UTF-8');;

		return "{$crc32} {$this->getSize()} {$location} {$name}";
	}

	private function checkRequiredInputParams(array $inputParams, array $required)
	{
		foreach ($required as $item)
		{
			if(!isset($inputParams[$item]) || (!$inputParams[$item] && !(is_string($inputParams[$item]) && strlen($inputParams[$item]))))
			{
				$this->errorCollection->add(array(new Error("Error: required parameter {$item}", self::ERROR_REQUIRED_PARAMETER)));
				return false;
			}
		}

		return true;
	}

	/**
	 * Getting array of errors.
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * Getting array of errors with the necessary code.
	 * @param string $code Code of error.
	 * @return Error[]
	 */
	public function getErrorsByCode($code)
	{
		return $this->errorCollection->getErrorsByCode($code);
	}

	/**
	 * Getting once error with the necessary code.
	 * @param string $code Code of error.
	 * @return Error[]
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}
}
