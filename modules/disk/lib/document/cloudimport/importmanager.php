<?php


namespace Bitrix\Disk\Document\CloudImport;


use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\Bitrix24Disk;
use Bitrix\Disk\Document;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\Error\IErrorable;
use Bitrix\Disk\TypeFile;
use Bitrix\Main\IO;

final class ImportManager implements IErrorable
{
	const CHUNK_SIZE = 5242880;

	const ERROR_REQUIRED_PARAMETER          = 'DISK_IMM_22001';
	const ERROR_COULD_NOT_FIND_CLOUD_IMPORT = 'DISK_IMM_22003';

	/** @var  ErrorCollection */
	protected $errorCollection;
	/** @var Document\DocumentHandler */
	protected $documentHandler;
	/** @var UploadFileManager */
	protected $uploadFileManager;

	/**
	 * ImportManager constructor.
	 * @param Document\DocumentHandler $documentHandler
	 */
	public function __construct(Document\DocumentHandler $documentHandler)
	{
		$this->documentHandler = $documentHandler;
		$this->errorCollection = new ErrorCollection;

		$this->uploadFileManager = new UploadFileManager();
		$this->uploadFileManager->setUser($this->documentHandler->getUserId());
	}

	/**
	 * @return Document\DocumentHandler
	 */
	public function getDocumentHandler()
	{
		return $this->documentHandler;
	}

	/**
	 * @param AttachedObject $attachedObject
	 * @return static
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function buildByAttachedObject(AttachedObject $attachedObject)
	{
		/** @var Entry $cloudImport */
		$cloudImport = $attachedObject->getObject()->getLastCloudImportEntry();
		$documentHandler = Driver::getInstance()
			->getDocumentHandlersManager()
			->getHandlerByCode($cloudImport->getService())
		;

		if(!$documentHandler)
		{
			return null;
		}

		return new static($documentHandler);
	}

	/**
	 * Creates cloud import entry to store data about potential new file.
	 * @param string $fileId Id in cloud service.
	 * @return \Bitrix\Disk\Internals\Model|null|static Cloud import entry.
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	public function startImport($fileId)
	{
		$fileData = new Document\FileData;
		$fileData->setId($fileId);

		$fileMetadata = $this->documentHandler->getFileMetadata($fileData);
		if(!$fileMetadata || !$this->checkRequiredInputParams($fileMetadata, array('size', 'mimeType')))
		{
			$this->errorCollection->add($this->documentHandler->getErrors());
			return null;
		}

		$cloudImport = Entry::add(array(
			'USER_ID' => $this->documentHandler->getUserId(),
			'SERVICE' => $this->documentHandler::getCode(),
			'SERVICE_OBJECT_ID' => $fileData->getId(),
			'ETAG' => $fileMetadata['etag'],
			'CONTENT_SIZE' => $fileMetadata['size'],
			'MIME_TYPE' => $fileMetadata['mimeType'],
		), $this->errorCollection);

		return $cloudImport;
	}

	public function forkImport(Entry $cloudImport)
	{
		$fileData = new Document\FileData;
		$fileData->setId($cloudImport->getServiceObjectId());

		$fileMetadata = $this->documentHandler->getFileMetadata($fileData);
		if(!$fileMetadata || !$this->checkRequiredInputParams($fileMetadata, array('size', 'mimeType', 'etag')))
		{
			$this->errorCollection->add($this->documentHandler->getErrors());
			return null;
		}

		$cloudImport = Entry::add(array(
			'OBJECT_ID' => $cloudImport->getObjectId(),
			'USER_ID' => $this->documentHandler->getUserId(),
			'SERVICE' => $this->documentHandler::getCode(),
			'SERVICE_OBJECT_ID' => $fileData->getId(),
			'ETAG' => $fileMetadata['etag'],
			'CONTENT_SIZE' => $fileMetadata['size'],
			'MIME_TYPE' => $fileMetadata['mimeType'],
		), $this->errorCollection);

		return $cloudImport;
	}

	/**
	 * Checks new version in cloud service by Entry. Compares eTag.
	 * @param Entry $cloudImport
	 * @return bool|null
	 */
	public function hasNewVersion(Entry $cloudImport)
	{
		$fileData = new Document\FileData;
		$fileData->setId($cloudImport->getServiceObjectId());

		$fileMetadata = $this->documentHandler->getFileMetadata($fileData);
		if(!$fileMetadata || !$this->checkRequiredInputParams($fileMetadata, array('size', 'mimeType')))
		{
			$this->errorCollection->add($this->documentHandler->getErrors());
			return null;
		}

		return $fileMetadata['etag'] !== $cloudImport->getEtag();
	}

	/**
	 * Uploads new chunk from cloud drive if necessary.
	 * @param Entry $entry Cloud import entry.
	 * @return bool
	 */
	public function uploadChunk(Entry $entry)
	{
		$tmpFile = \CTempFile::getFileName(uniqid('_wd', true));
		checkDirPath($tmpFile);

		$fileData = new Document\FileData;
		$fileData->setId($entry->getServiceObjectId());
		$fileData->setMimeType($entry->getMimeType());
		$fileData->setSrc($tmpFile);

		$chunkSize = self::CHUNK_SIZE;
		$downloadedContentSize = $entry->getDownloadedContentSize();
		$contentSize = $entry->getContentSize();

		if($contentSize == 0 && $this->documentHandler instanceof Document\GoogleHandler)
		{
			return $this->uploadEmptyFileFromGoogle($entry, $fileData);
		}

		if($contentSize - $downloadedContentSize < $chunkSize)
		{
			$chunkSize = $contentSize - $downloadedContentSize;
		}

		$startRange = $downloadedContentSize;
		if(!$this->documentHandler->downloadPartFile($fileData, $startRange, $chunkSize))
		{
			$this->errorCollection->add($this->documentHandler->getErrors());
			return false;
		}

		$token = null;
		if($entry->getTmpFile() && $entry->getTmpFile()->getToken())
		{
			//todo it's strange, fix it
			$token = $entry->getTmpFile()->getToken();
		}

		$uploadFileManager = $this->uploadFileManager;
		$uploadFileManager
			->setFileSize($contentSize)
			->setEntry($entry)
			->setToken($token)
			->setContentRange(array($startRange, $startRange + $chunkSize - 1))
		;
		$tmpFileArray = \CFile::makeFileArray($tmpFile);
		if(!$uploadFileManager->upload($fileData->getId(), $tmpFileArray))
		{
			$this->errorCollection->add($uploadFileManager->getErrors());
			return false;
		}
		if($token === null)
		{
			$entry->linkTmpFile(TmpFile::load(array('=TOKEN' => $uploadFileManager->getToken())));
		}

		return $entry->increaseDownloadedContentSize($chunkSize);
	}

	/**
	 * Fix for Google. It does not get in metadata real size of empty file.
	 * @param Entry             $entry
	 * @param Document\FileData $fileData
	 * @return bool
	 * @throws IO\FileNotFoundException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function uploadEmptyFileFromGoogle(Entry $entry, Document\FileData $fileData)
	{
		$tmpFile = $fileData->getSrc();

		$downloadedContentSize = $entry->getDownloadedContentSize();
		$startRange = $downloadedContentSize;

		//fix for Google. It doesn't get in metadata real size of empty file.
		if(!$this->documentHandler->downloadFile($fileData))
		{
			$this->errorCollection->add($this->documentHandler->getErrors());
			return false;
		}
		$realFile = new IO\File($tmpFile);
		$contentSize = $realFile->getSize();
		$entry->setContentSize($contentSize);
		$chunkSize = $contentSize - $downloadedContentSize;

		$uploadFileManager = $this->uploadFileManager;
		$uploadFileManager
			->setFileSize($contentSize)
			->setEntry($entry)
			->setContentRange(array($startRange, $startRange + $chunkSize - 1))
		;
		$tmpFileArray = \CFile::makeFileArray($tmpFile);
		if(!$uploadFileManager->upload($fileData->getId(), $tmpFileArray))
		{
			$this->errorCollection->add($uploadFileManager->getErrors());
			return false;
		}
		$entry->linkTmpFile(TmpFile::load(array('=TOKEN' => $uploadFileManager->getToken())));

		return $entry->increaseDownloadedContentSize($chunkSize);
	}

	/**
	 * Saves file in destination folder from entry of cloud import.
	 * @param Entry  $entry Cloud import entry.
	 * @param Folder $folder Destination folder.
	 * @return \Bitrix\Disk\File|null New file object.
	 */
	public function saveFile(Entry $entry, Folder $folder)
	{
		if(!$entry->getTmpFile())
		{
			$this->errorCollection->addOne(new Error('Could not find cloud import', self::ERROR_COULD_NOT_FIND_CLOUD_IMPORT));
			return null;
		}

		if($entry->getContentSize() != $entry->getDownloadedContentSize())
		{
			$this->errorCollection->addOne(new Error('Content size != downloaded content size'));
			return null;
		}

		$fileData = new Document\FileData;
		$fileData->setId($entry->getServiceObjectId());
		$fileMetadata = $this->documentHandler->getFileMetadata($fileData);
		if(!$fileMetadata || empty($fileMetadata['name']))
		{
			$this->errorCollection->add($this->documentHandler->getErrors());
			return null;
		}

		$name = $fileMetadata['name'];
		if(!getFileExtension($name))
		{
			$name = $this->recoverExtensionInName($name, $fileMetadata['mimeType']);
		}

		$tmpFile = $entry->getTmpFile();
		$fileArray = \CFile::makeFileArray($tmpFile->getAbsolutePath());
		if (!$fileArray)
		{
			return null;
		}

		$file = $folder->uploadFile(
			$fileArray,
			array(
				'NAME' => $name,
				'CREATED_BY' => $this->documentHandler->getUserId(),
				'CONTENT_PROVIDER' => $this->documentHandler::getCode(),
			),
			array(),
			true
		);

		if(!$file)
		{
			$tmpFile->delete();
			$this->errorCollection->add($folder->getErrors());
			return null;
		}
		$entry->linkObject($file);

		return $file;
	}

	/**
	 * @internal
	 * @param $fileName
	 * @param $mimeType
	 * @return string
	 */
	protected function recoverExtensionInName($fileName, $mimeType)
	{
		$specificMimeTypes = array(
			'application/vnd.google-apps.document' => 'docx',
			'application/vnd.google-apps.spreadsheet' => 'xlsx',
			'application/vnd.google-apps.presentation' => 'pptx',
		);
		if(isset($specificMimeTypes[$mimeType]))
		{
			$originalExtension = $specificMimeTypes[$mimeType];
		}
		else
		{
			$originalExtension = TypeFile::getExtensionByMimeType($mimeType);
		}

		$newExtension = mb_strtolower(trim(getFileExtension($fileName), '.'));
		if ($originalExtension !== $newExtension && $originalExtension !== null)
		{
			return getFileNameWithoutExtension($fileName) . '.' . $originalExtension;
		}

		return $fileName;
	}

	public function uploadVersion(Entry $entry)
	{
		if(!$entry->getTmpFile())
		{
			$this->errorCollection->addOne(new Error('Could not find cloud import', self::ERROR_COULD_NOT_FIND_CLOUD_IMPORT));
			return null;
		}

		if($entry->getContentSize() != $entry->getDownloadedContentSize())
		{
			$this->errorCollection->addOne(new Error('Content size != downloaded content size'));
			return null;
		}

		/** @var File $file */
		$file = $entry->getObject();
		if(!$file)
		{
			$this->errorCollection->addOne(new Error('Could not get file from cloud import record'));
			return null;
		}

		$tmpFile = $entry->getTmpFile();
		$fileArray = \CFile::makeFileArray($tmpFile->getAbsolutePath());
		$version = $file->uploadVersion($fileArray, $this->documentHandler->getUserId());
		if(!$version)
		{
			$tmpFile->delete();
			$this->errorCollection->add($file->getErrors());
			return null;
		}
		$entry->linkVersion($version);

		return $version;
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

	protected function checkRequiredInputParams(array $inputParams, array $required)
	{
		foreach ($required as $item)
		{
			if(!isset($inputParams[$item]) || (!$inputParams[$item] && !(is_string($inputParams[$item]) && mb_strlen($inputParams[$item]))))
			{
				if($item === 'size' && is_numeric($inputParams[$item]) && ((int)$inputParams[$item]) === 0)
				{
					continue;
				}
				$this->errorCollection->add(array(new Error("Error: required parameter {$item}", self::ERROR_REQUIRED_PARAMETER)));
				return false;
			}
		}

		return true;
	}
}