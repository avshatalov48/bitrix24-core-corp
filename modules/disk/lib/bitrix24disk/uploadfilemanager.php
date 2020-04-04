<?php

namespace Bitrix\Disk\Bitrix24Disk;

use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\Error\IErrorable;
use Bitrix\Disk\User;
use Bitrix\Main\Loader;
use Bitrix\Main\IO;
use CFile;

class UploadFileManager implements IErrorable
{
	const ERROR_CHUNK_ERROR                = 'DISK_UPL_M_22001';
	const ERROR_CREATE_TMP_FILE_BUCKET     = 'DISK_UPL_M_22002';
	const ERROR_CREATE_TMP_FILE_NON_BUCKET = 'DISK_UPL_M_22003';
	const ERROR_EMPTY_TOKEN                = 'DISK_UPL_M_22004';
	const ERROR_CREATE_TMP_FILE_LAST_HERO  = 'DISK_UPL_M_22005';
	const ERROR_UNKNOWN_TOKEN              = 'DISK_UPL_M_22006';

	const DEFAULT_CHUNK_SIZE = 5242880;

	/** @var string */
	protected $token;
	/** @var int */
	protected $fileSize;
	/** @var array */
	protected $contentRange;
	/** @var int */
	protected $userId;
	/** @var \Bitrix\Disk\Bitrix24Disk\TmpFile */
	protected $tmpFileClass;
	/** @var  ErrorCollection */
	protected $errorCollection;

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection;
		$this->tmpFileClass = TmpFile::className();
	}

	/**
	 * @param string $tmpFileClass
	 *
	 * @return UploadFileManager
	 */
	public function setTmpFileClass($tmpFileClass)
	{
		$this->tmpFileClass = $tmpFileClass;

		return $this;
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
	 * @return int
	 */
	public function getFileSize()
	{
		return $this->fileSize;
	}

	/**
	 * @param int $fileSize
	 * @return $this
	 */
	public function setFileSize($fileSize)
	{
		$this->fileSize = $fileSize;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getContentRange()
	{
		return $this->contentRange;
	}

	/**
	 * @param array $contentRange
	 * @return $this
	 */
	public function setContentRange(array $contentRange)
	{
		$this->contentRange = $contentRange;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getToken()
	{
		return $this->token;
	}

	/**
	 * @param string $token
	 * @return $this
	 */
	public function setToken($token)
	{
		$this->token = $token;

		return $this;
	}

	protected function hasToken()
	{
		return isset($this->token);
	}

	/**
	 * @return int
	 */
	public function getUserId()
	{
		return $this->userId;
	}

	/**
	 * @param int $userId
	 * @return $this
	 */
	public function setUserId($userId)
	{
		$this->userId = $userId;

		return $this;
	}

	/**
	 * @param \CUser|User|int $user
	 * @return $this
	 */
	public function setUser($user)
	{
		$this->userId = User::resolveUserId($user);

		return $this;
	}

	public function upload($filename, array $fileData)
	{
		$tmpFileClass = $this->tmpFileClass;
		list($startRange, $endRange) = $this->getContentRange();
		$fileSize = $this->getFileSize();

		if(
			$startRange === null ||
			(
				$this->isValidChunkSize($startRange, $endRange, $fileData) &&
				$startRange == 0 &&
				($endRange - $startRange + 1) == $fileSize
			)
		)
		{
			$fileData['name'] = $filename;
			list($fileData['width'], $fileData['height']) = CFile::getImageSize($fileData['tmp_name']);

			$tmpFile = $tmpFileClass::createFromFileArray(
				$fileData,
				array(
					'CREATED_BY' => $this->getUserId(),
					'SIZE' => $fileSize,
				),
				$this->errorCollection
			);
			if(!$tmpFile)
			{
				$this->errorCollection->addOne(
					new Error(
						"Could not create tmpFile model (simple mode)",
						self::ERROR_CREATE_TMP_FILE_NON_BUCKET
					)
				);
				return false;
			}
			$this->token = $tmpFile->getToken();

			return true;
		}

		if($this->isInvalidChunkSize($startRange, $endRange, $fileData))
		{
			$this->errorCollection->addOne(
				new Error(
					'Size of file: ' . $fileData['size'] . ' not equals size of chunk: ' . ($endRange - $startRange + 1),
					self::ERROR_CHUNK_ERROR
				)
			);
			return false;
		}

		if($this->isFirstChunk($startRange))
		{
			$fileData['name'] = $filename;
			list($fileData['width'], $fileData['height']) = \CFile::getImageSize($fileData['tmp_name']);

			//attempt to decide: cloud? not cloud?
			$bucket = $this->findBucketForFile(array(
				'name' => $filename,
				'fileSize' => $fileSize,
			));
			if($bucket)
			{
				/** @var TmpFile $tmpFile */
				$tmpFile = $tmpFileClass::createInBucketFirstPartFromFileArray(
					$fileData,
					array(
						'CREATED_BY' => $this->getUserId(),
						'SIZE' => $fileSize,
					),
					$bucket,
					compact('startRange', 'endRange', 'fileSize'),
					$this->errorCollection
				);
				if(!$tmpFile)
				{
					$this->errorCollection->addOne(
						new Error(
							"Could not create tmpFile model (cloud bucket {$bucket->ID})",
							self::ERROR_CREATE_TMP_FILE_BUCKET
						)
					);
					return false;
				}
			}
			else
			{
				$tmpFile = $tmpFileClass::createFromFileArray($fileData, array(
					'CREATED_BY' => $this->getUserId(),
					'SIZE' => $fileSize,
				), $this->errorCollection);
				if(!$tmpFile)
				{
					$this->errorCollection->addOne(
						new Error(
							"Could not create tmpFile model (simple mode)",
							self::ERROR_CREATE_TMP_FILE_NON_BUCKET
						)
					);
					return false;
				}
			}
			$this->token = $tmpFile->getToken();
			return true;
		}

		//if run resumable upload we needed token.
		if(!$this->hasToken())
		{
			$this->errorCollection->addOne(
				new Error(
					"Could not append content to file. Have to set token parameter.",
					self::ERROR_EMPTY_TOKEN
				)
			);
			return false;
		}
		$tmpFile = $this->findUserSpecificTmpFileByToken();
		if(!$tmpFile)
		{
			$this->errorCollection->addOne(
				new Error(
					"Could not find file by token",
					self::ERROR_UNKNOWN_TOKEN
				)
			);
			return false;
		}

		$success = $tmpFile->append(IO\File::getFileContents($fileData['tmp_name']), compact(
			'startRange', 'endRange', 'fileSize'
		));
		if(!$success)
		{
			$this->errorCollection->add($tmpFile->getErrors());
			return false;
		}

		$this->token = $tmpFile->getToken();
		return true;
	}

	protected function isInvalidChunkSize($startRange, $endRange, array $fileData)
	{
		return ($endRange - $startRange + 1) != $fileData['size'];
	}

	protected function isValidChunkSize($startRange, $endRange, array $fileData)
	{
		return !$this->isInvalidChunkSize($startRange, $endRange, $fileData);
	}

	/**
	 * Remove irrelevant tmp files.
	 * @return string
	 */
	public static function removeIrrelevant()
	{
		$query = TmpFile::getList(array(
			'filter' => array(
				'IRRELEVANT' => true,
			),
			'order' => array(
				'ID' => 'ASC',
			),
			'limit' => 20,
		));
		/** @noinspection PhpAssignmentInConditionInspection */
		while($row = $query->fetch())
		{
			$tmpFile = TmpFile::buildFromArray($row);
			if(!$tmpFile)
			{
				continue;
			}
			$tmpFile->delete();
		}

		return get_called_class() . '::removeIrrelevant();';
	}

	/**
	 * Rollback upload by token. Destroy temporary content.
	 * @return bool Status of delete.
	 */
	public function rollbackByToken()
	{
		if(!$this->hasToken())
		{
			$this->errorCollection->addOne(
				new Error(
					"Could not delete content file by token. Have to set token parameter.",
					self::ERROR_EMPTY_TOKEN
				)
			);
			return false;
		}
		$tmpFile = $this->findUserSpecificTmpFileByToken();
		if(!$tmpFile)
		{
			$this->errorCollection->addOne(
				new Error(
					"Could not find file by token",
					self::ERROR_UNKNOWN_TOKEN
				)
			);
			return false;
		}
		$success = $tmpFile->delete();
		if(!$success)
		{
			$this->errorCollection->add($tmpFile->getErrors());
		}

		return $success;
	}

	/**
	 * If enable module clouds, then find bucket for file
	 * @param array $params
	 * @return null|\CCloudStorageBucket
	 */
	private function findBucketForFile(array $params)
	{
		if(!Loader::includeModule("clouds"))
		{
			return null;
		}
		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		$bucket = \CCloudStorage::findBucketForFile(array('FILE_SIZE' => $params['fileSize'], 'MODULE_ID' => Driver::INTERNAL_MODULE_ID), $params['name']);
		if(!$bucket || !$bucket->init())
		{
			return null;
		}
		return $bucket;
	}

	/**
	 * @return TmpFile|null
	 */
	public function findUserSpecificTmpFileByToken()
	{
		$tmpFileClass = $this->tmpFileClass;
		$filter = array(
			'=TOKEN' => (string)$this->getToken()
		);
		$userId = $this->getUserId();
		if($userId)
		{
			$filter['CREATED_BY'] = $userId;
		}

		return $tmpFileClass::load($filter);
	}

	/**
	 * @param $startRange
	 * @return bool
	 */
	private function isFirstChunk($startRange)
	{
		return $startRange == 0;
	}

	/**
	 * Returns size for chunk-upload.
	 *
	 * @param string $filename Filename.
	 * @param integer $fileSize Size of file in bytes
	 * @return int
	 */
	public function getChunkSize($filename, $fileSize)
	{
		$bucket = $this->findBucketForFile(array(
			'name' => $filename,
			'fileSize' => $fileSize,
		));
		if(!$bucket)
		{
			return self::DEFAULT_CHUNK_SIZE;
		}

		return $bucket->getService()->getMinUploadPartSize();
	}
}