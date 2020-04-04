<?php

namespace Bitrix\Disk\Shortcut;


use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\Error\IErrorable;
use Bitrix\Disk\Security\SecurityContext;

final class Uploader implements IErrorable
{
	/** @var  ErrorCollection */
	protected $errorCollection;

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection;
	}

	/**
	 * Uploads file, which has structure like $_FILE, to user storage in default folder for uploading.
	 *
	 * @param int                  $userId Target user.
	 * @param array                $file Structure like $_FILE
	 * @param null|int             $createdBy Id of user which will create file. If is null, then will use $userId.
	 * @param SecurityContext|null $securityContext Security context.
	 * @return \Bitrix\Disk\File|null
	 */
	public function uploadFileToUserStorage($userId, array $file, $createdBy = null, SecurityContext $securityContext = null)
	{
		$newFiles = $this->uploadBatchFilesToUserStorage($userId, array($file), $createdBy, $securityContext);

		if(!$newFiles)
		{
			return null;
		}

		return array_pop($newFiles);
	}

	/**
	 * Uploads batch of files, which have structure like $_FILE, to user storage in default folder for uploading.
	 *
	 * @param int                  $userId Target user.
	 * @param array                $files List with structure like $_FILE
	 * @param null|int             $createdBy Id of user which will create file. If is null, then will use $userId.
	 * @param SecurityContext|null $securityContext Security context.
	 * @return \Bitrix\Disk\File[]|null
	 */
	public function uploadBatchFilesToUserStorage($userId, array $files, $createdBy = null, SecurityContext $securityContext = null)
	{
		if($createdBy === null)
		{
			$createdBy = $userId;
		}

		$driver = Driver::getInstance();
		$storage = $driver->getStorageByUserId($userId);

		if(!$storage)
		{
			$this->errorCollection[] = new Error('Could not find/create user storage');
			$this->errorCollection->add($driver->getErrors());

			return null;
		}

		if($securityContext === null)
		{
			$securityContext = $storage->getSecurityContext($createdBy);
		}

		$folder = $storage->getFolderForUploadedFiles();

		if(!$folder)
		{
			$this->errorCollection[] = new Error('Could not find/create folder to upload file');
			$this->errorCollection->add($storage->getErrors());

			return null;
		}

		if(!$folder->canAdd($securityContext))
		{
			$this->errorCollection[] = new Error('Could not find/create upload file. Bad permission');

			return null;
		}

		$newFiles = array();

		foreach($files as $file)
		{
			$newFile = $folder->uploadFile($file, array(
				'NAME' => $file['name'],
				'CREATED_BY' => $createdBy,
			), array(), true);

			if(!$newFile)
			{
				$this->errorCollection->add($folder->getErrors());
				continue;
			}

			$newFiles[] = $newFile;
		}

		return $newFiles;
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
	 * @return Error
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}
}