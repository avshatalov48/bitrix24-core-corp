<?php

namespace Bitrix\Disk\Bitrix24Disk;

use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\Model;
use Bitrix\Disk\Internals\TmpFileTable;
use Bitrix\Disk\User;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\IO;
use Bitrix\Main\Loader;
use Bitrix\Main\Security\Random;
use Bitrix\Main\Type\DateTime;
use CCloudStorageUpload;
use CTempFile;

class TmpFile extends Model
{
	const ERROR_BUCKET_ID_EMPTY                 = 'DISK_TMP_FILE_22000';
	const ERROR_INCLUDE_CLOUDS                  = 'DISK_TMP_FILE_22001';
	const ERROR_INIT_BUCKET                     = 'DISK_TMP_FILE_22002';
	const ERROR_DELETE_CLOUD_FILE               = 'DISK_TMP_FILE_22003';
	const ERROR_DELETE_FILE                     = 'DISK_TMP_FILE_22004';
	const ERROR_UPLOAD_MAX_FILE_SIZE            = 'DISK_TMP_FILE_22005';
	const ERROR_UPLOAD_FILE                     = 'DISK_TMP_FILE_22015';
	const ERROR_IS_NOT_UPLOADED_FILE            = 'DISK_TMP_FILE_22016';
	const ERROR_MOVE_UPLOADED_FILE              = 'DISK_TMP_FILE_22017';
	const ERROR_GET_FILE_CONTENTS               = 'DISK_TMP_FILE_22020';
	const ERROR_CLOUD_APPEND_INVALID_CHUNK_SIZE = 'DISK_TMP_FILE_22018';
	const ERROR_CLOUD_START_UPLOAD              = 'DISK_TMP_FILE_22019';
	const ERROR_CLOUD_UPLOAD_PART               = 'DISK_TMP_FILE_22021';
	const ERROR_CLOUD_FINISH_UPLOAD             = 'DISK_TMP_FILE_22022';
	const ERROR_EXISTS_FILE                     = 'DISK_TMP_FILE_22023';
	const ERROR_PUT_CONTENTS                    = 'DISK_TMP_FILE_22024';
	const ERROR_MOVE_FILE                       = 'DISK_TMP_FILE_22025';

	/** @var int */
	protected $id;
	/** @var string */
	protected $token;
	/** @var string */
	protected $filename;
	/** @var string */
	protected $contentType;
	/** @var string */
	protected $path;
	/** @var int */
	protected $bucketId;
	/** @var int */
	protected $size;
	/** @var int */
	protected $receivedSize;
	/** @var int */
	protected $width;
	/** @var int */
	protected $height;
	/** @var bool */
	protected $isCloud;
	/** @var int */
	protected $createdBy;
	/** @var  User */
	protected $createUser;
	/** @var DateTime */
	protected $createTime;
	/** @var bool */
	protected $isAlreadyDeleted = false;
	protected $deleteStatus = null;
	/** @var bool */
	protected $isRegisteredShutdownFunction = false;

	/**
	 * Gets the fully qualified name of table class which belongs to current model.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return string
	 */
	public static function getTableClassName()
	{
		return TmpFileTable::className();
	}

	/**
	 * Returns token.
	 * @return string
	 */
	public function getToken()
	{
		return $this->token;
	}

	/**
	 * Returns file name.
	 * @return string
	 */
	public function getFilename()
	{
		return $this->filename;
	}

	/**
	 * Returns content type.
	 * @return string
	 */
	public function getContentType()
	{
		return $this->contentType;
	}

	/**
	 * Returns path.
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * Returns id of bucket (only for cloud files).
	 * @return int
	 */
	public function getBucketId()
	{
		return $this->bucketId;
	}

	/**
	 * Returns size in bytes.
	 * @return int
	 */
	public function getSize()
	{
		return $this->size;
	}

	/**
	 * Returns received size of file in bytes.
	 * @return int
	 */
	public function getReceivedSize()
	{
		return $this->receivedSize;
	}

	/**
	 * Returns width. If tmp file is not image, then returns null.
	 * @return int
	 */
	public function getWidth()
	{
		return $this->width;
	}

	/**
	 * Returns height. If tmp file is not image, then returns null.
	 * @return int
	 */
	public function getHeight()
	{
		return $this->height;
	}

	/**
	 * Returns time of create object.
	 * @return DateTime
	 */
	public function getCreateTime()
	{
		return $this->createTime;
	}

	/**
	 * Returns id of user, who created object.
	 * @return int
	 */
	public function getCreatedBy()
	{
		return $this->createdBy;
	}

	/**
	 * Returns user model, who created object.
	 * @return User
	 */
	public function getCreateUser()
	{
		if(isset($this->createUser) && $this->createdBy == $this->createUser->getId())
		{
			return $this->createUser;
		}
		$this->createUser = User::getModelForReferenceField($this->createdBy, $this->createUser);

		return $this->createUser;
	}

	/**
	 * Tells if tmp file locates in cloud.
	 * @return bool
	 */
	public function isCloud()
	{
		return $this->isCloud && $this->bucketId;
	}

	/**
	 * @return \CCloudStorageBucket|null
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function getBucket()
	{
		if(!$this->bucketId)
		{
			$this->errorCollection->addOne(new Error('Could not get bucket. BucketId is empty.', static::ERROR_BUCKET_ID_EMPTY));
			return null;
		}
		if(!Loader::includeModule('clouds'))
		{
			$this->errorCollection->addOne(new Error('Could not include module "clouds".', static::ERROR_INCLUDE_CLOUDS));
			return null;
		}
		$bucket = new \CCloudStorageBucket($this->bucketId);
		if (!$bucket->init())
		{
			$this->errorCollection->addOne(new Error('Could not init bucket.', static::ERROR_INIT_BUCKET));
			return null;
		}

		return $bucket;
	}

	/**
	 * Deletes TmpFile.
	 * The method attempts to destroy content (cloud or file from file system).
	 * @return bool
	 */
	public function delete()
	{
		if($this->isCloud())
		{
			if ($this->isAlreadyDeleted)
			{
				return $this->deleteStatus;
			}

			$bucket = $this->getBucket();
			if(!$bucket)
			{
				return false;
			}

			$this->deleteStatus = $bucket->deleteFile($this->path);
			$this->isAlreadyDeleted = true;
			if(!$this->deleteStatus)
			{
				$this->errorCollection->addOne(new Error('Could not delete file from bucket.', static::ERROR_DELETE_CLOUD_FILE));
				return false;
			}
		}
		else
		{
			$file = new IO\File($this->getAbsolutePath());
			if($file->isExists())
			{
				if(!$file->delete())
				{
					$this->errorCollection->addOne(new Error('Could not delete file.', static::ERROR_DELETE_FILE));
					return false;
				}
			}
		}

		return $this->deleteInternal();
	}

	/**
	 * Registers delayed delete which will do on the shutdown.
	 * @return void
	 */
	public function registerDelayedDeleteOnShutdown()
	{
		if ($this->isRegisteredShutdownFunction)
		{
			return;
		}

		Application::getInstance()->addBackgroundJob(function () {
			$this->delete();
		});

		$this->isRegisteredShutdownFunction = true;
	}

	protected static function generateTokenByPath($path)
	{
		$name = bx_basename($path);
		if (preg_match('%^[0-9a-z]{32}$%', $name))
		{
			return $name;
		}

		return Random::getString(32);
	}

	protected static function prepareDataToInsertFromFileArray(array $fileData, array $data, ErrorCollection $errorCollection)
	{
		if (($fileData['error'] = intval($fileData['error'])) > 0)
		{
			if ($fileData['error'] < 3)
			{
				$errorCollection->addOne(new Error('upload_max_filesize: ' . intval(ini_get('upload_max_filesize')), static::ERROR_UPLOAD_MAX_FILE_SIZE));
				return null;
			}
			$errorCollection->addOne(new Error('upload_error ' . $fileData['error'], static::ERROR_UPLOAD_FILE));

			return null;
		}

		if(!is_uploaded_file($fileData['tmp_name']))
		{
			$errorCollection->addOne(new Error('Current file is unsafe (is_uploaded_file check)', static::ERROR_IS_NOT_UPLOADED_FILE));

			return null;
		}

		list($relativePath, $absolutePath) = static::generatePath();
		if (!move_uploaded_file($fileData['tmp_name'], $absolutePath))
		{
			$errorCollection->addOne(new Error('Could not move uploaded file (move_uploaded_file)', static::ERROR_MOVE_UPLOADED_FILE));

			return null;
		}

		//now you can set CREATED_BY
		$data = array_intersect_key($data, array('CREATED_BY' => true, 'SIZE' => true,));

		return array_merge(array(
			'TOKEN' => static::generateTokenByPath($relativePath),
			'FILENAME' => $fileData['name'],
			'CONTENT_TYPE' => empty($fileData['type'])? \CFile::getContentType($absolutePath) : $fileData['type'],
			'PATH' => $relativePath,
			'BUCKET_ID' => '',
			'SIZE' => empty($fileData['size'])? '' : $fileData['size'],
			'RECEIVED_SIZE' => empty($fileData['size'])? '' : $fileData['size'],
			'WIDTH' => empty($fileData['width'])? '' : $fileData['width'],
			'HEIGHT' => empty($fileData['height'])? '' : $fileData['height'],
		), $data);
	}

	/**
	 * Creates TmpFile model from file array ($_FILE).
	 * @param array           $fileData Array like as $_FILE.
	 * @param array           $data Additional fields to TmpFile.
	 * @param ErrorCollection $errorCollection Error collection.
	 * @return Model|null|static
	 */
	public static function createFromFileArray(array $fileData, array $data, ErrorCollection $errorCollection)
	{
		$data = static::prepareDataToInsertFromFileArray($fileData, $data, $errorCollection);
		if(!$data)
		{
			return null;
		}

		return static::add($data, $errorCollection);
	}

	/**
	 * Creates first part of file in bucket.
	 * @param array                $fileData Array like as $_FILE.
	 * @param array                $data Additional fields to TmpFile.
	 * @param \CCloudStorageBucket $bucket Cloud bucket.
	 * @param array                $params Parameters (startRange, endRange, fileSize).
	 * @param ErrorCollection      $errorCollection Error collection.
	 * @return Model|null|static
	 */
	public static function createInBucketFirstPartFromFileArray(array $fileData, array $data, \CCloudStorageBucket $bucket, array $params, ErrorCollection $errorCollection)
	{
		$data = static::prepareDataToInsertFromFileArray($fileData, $data, $errorCollection);
		$data['IS_CLOUD'] = 1;
		/** @noinspection PhpUndefinedFieldInspection */
		$data['BUCKET_ID'] = $bucket->ID;

		$model = static::add($data, $errorCollection);
		if(!$model)
		{
			return null;
		}
		$uploadStatus = $model->appendContentCloud(
			IO\File::getFileContents($model->getAbsoluteNonCloudPath()),
			$params['startRange'],
			$params['endRange'],
			$params['fileSize'],
			$model->getContentType()
		);
		if(!$uploadStatus)
		{
			$errorCollection->add($model->getErrors());
			//todo Are we right?
			$model->delete();
			unset($model);

			return null;
		}

		return $model;
	}

	/**
	 * Appends contents to file.
	 * @param string $fileContent File content.
	 * @param array $params Parameters (startRange, endRange, fileSize).
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function append($fileContent, array $params)
	{
		static::checkRequiredInputParams($params, array('endRange', 'fileSize'));
		if($this->errorCollection->hasErrors())
		{
			return false;
		}

		if($this->isCloud())
		{
			$status = $this->appendContentCloud(
				$fileContent,
				$params['startRange'],
				$params['endRange'],
				$params['fileSize'],
				$this->getContentType()
			);
		}
		else
		{
			$status = $this->appendContentNonCloud($fileContent, $params['startRange'], $params['endRange'], $params['fileSize']);
		}

		if ($status)
		{
			$this->increaseReceivedSize($params['endRange'] - $params['startRange'] + 1);
		}

		return $status;
	}

	private function appendContentCloud($fileContent, $startRange, $endRange, $fileSize, string $contentType = null)
	{
		$isLastChunk = ($endRange + 1) == $fileSize;
		$bucket = $this->getBucket();

		if(!$bucket)
		{
			return false;
		}

		if
		(
			($endRange - $startRange + 1) < $bucket->getService()->getMinUploadPartSize() && !$isLastChunk
		)
		{
			$this->errorCollection->addOne(
				new Error(
					"Could not append content. Size of chunk must be more than {$bucket->getService()->getMinUploadPartSize()} (if chunk is not last)",
					static::ERROR_CLOUD_APPEND_INVALID_CHUNK_SIZE
				)
			);

			return false;
		}

		$upload = new CCloudStorageUpload($this->path);
		if(!$upload->isStarted())
		{
			$contentType = $contentType ?? 'application/octet-stream';
			if(!$upload->start($bucket->ID, $fileSize, $contentType))
			{
				$this->errorCollection->addOne(
					new Error(
						"Could not start cloud upload",
						static::ERROR_CLOUD_START_UPLOAD
					)
				);

				return false;
			}
		}
		$success = false;
		if($fileContent === false)
		{
			$this->errorCollection->addOne(
				new Error(
					'Could not get file contents',
					static::ERROR_GET_FILE_CONTENTS
				)
			);

			return false;
		}

		if ($upload->getPos() == doubleval($endRange+1))
		{
			//we already have this portion of content. Seems to be like that
			if ($isLastChunk && !$upload->finish())
			{
				$this->errorCollection[] = new Error('Could not finish resumable upload',static::ERROR_CLOUD_FINISH_UPLOAD);

				return false;
			}

			return true;
		}

		$fails = 0;
		while ($upload->hasRetries())
		{
			if ($upload->next($fileContent))
			{
				$success = true;
				break;
			}
			$fails++;
		}
		if (!$success)
		{
			$this->errorCollection->addOne(
				new Error(
					"Could not upload part of file for {$fails} times",
					static::ERROR_CLOUD_UPLOAD_PART
				)
			);

			return false;
		}
		elseif($success && $isLastChunk)
		{
			if($upload->finish())
			{
				//we don't  inc and don't dec
				//$bucket->incFileCounter($fileSize);
			}
			else
			{
				$this->errorCollection->addOne(
					new Error(
						'Could not finish resumable upload',
						static::ERROR_CLOUD_FINISH_UPLOAD
					)
				);

				return false;
			}
			return true;
		}

		return $success;
	}

	private function appendContentNonCloud($fileContent, $startRange, $endRange, $fileSize)
	{
		$file = new IO\File($this->getAbsolutePath());
		if(!$file->isExists())
		{
			$this->errorCollection->addOne(
				new Error(
					'Could not find file',
					static::ERROR_EXISTS_FILE
				)
			);

			return false;
		}

		if ($file->getSize() == ($endRange+1))
		{
			//we already have this portion of content. Seems to be like that
			return true;
		}

		if($file->putContents($fileContent, $file::APPEND) === false)
		{
			$this->errorCollection->addOne(
				new Error(
					'Could not put contents to file',
					static::ERROR_PUT_CONTENTS
				)
			);

			return false;
		}

		return true;
	}

	protected function increaseReceivedSize($bytes)
	{
		$bytes = (int)$bytes;
		$success = $this->update([
			'RECEIVED_SIZE' => new SqlExpression("?# + {$bytes}", 'RECEIVED_SIZE'),
		]);

		if($success)
		{
			$this->receivedSize += $bytes;
		}

		return $success;
	}

	protected static function generatePath()
	{
		$tmpName = Random::getString(32);
		$dir = rtrim(CTempFile::getDirectoryName(24, Driver::INTERNAL_MODULE_ID), '/') . '/';
		checkDirPath($dir); //make folder recursive
		$pathItems = explode(CTempFile::getAbsoluteRoot(), $dir . $tmpName);

		return array(array_pop($pathItems), $dir . $tmpName);
	}

	private function getAbsoluteCloudPath()
	{
		$bucket = $this->getBucket();
		if(!$bucket)
		{
			return null;
		}
		return $bucket->getFileSRC($this->path);
	}

	private function getAbsoluteNonCloudPath()
	{
		return \CTempFile::getAbsoluteRoot() . '/' . $this->path;
	}

	/**
	 * Returns absolute path to file.
	 * @return null|string
	 */
	public function getAbsolutePath()
	{
		if($this->isCloud())
		{
			return $this->getAbsoluteCloudPath();
		}
		return $this->getAbsoluteNonCloudPath();
	}

	/**
	 * Returns the list of pair for mapping data and object properties.
	 * Key is field in DataManager, value is object property.
	 * @return array
	 */
	public static function getMapAttributes()
	{
		return array(
			'ID' => 'id',
			'TOKEN' => 'token',
			'FILENAME' => 'filename',
			'CONTENT_TYPE' => 'contentType',
			'PATH' => 'path',
			'BUCKET_ID' => 'bucketId',
			'SIZE' => 'size',
			'RECEIVED_SIZE' => 'receivedSize',
			'WIDTH' => 'width',
			'HEIGHT' => 'height',
			'IS_CLOUD' => 'isCloud',
			'CREATED_BY' => 'createdBy',
			'CREATE_USER' => 'createUser',
			'CREATE_TIME' => 'createTime',
		);
	}

	/**
	 * Returns the list attributes which is connected with another models.
	 * @return array
	 */
	public static function getMapReferenceAttributes()
	{
		return array(
			'CREATE_USER' => array(
				'class' => User::className(),
				'select' => User::getFieldsForSelect(),
			),
		);
	}
}
