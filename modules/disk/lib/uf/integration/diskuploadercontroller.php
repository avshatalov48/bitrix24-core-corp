<?php
namespace Bitrix\Disk\Uf\Integration;

use Bitrix\Disk;
use Bitrix\Disk\Document\DocumentHandler;
use Bitrix\Disk\Uf\FileUserType;
use Bitrix\Disk\Ui\Text;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\UI\FileUploader\Contracts\CustomLoad;
use Bitrix\UI\FileUploader\Contracts\CustomRemove;
use Bitrix\UI\FileUploader\FileData;
use Bitrix\UI\FileUploader\FileInfo;
use Bitrix\UI\FileUploader\FileOwnershipCollection;
use Bitrix\UI\FileUploader\Configuration;
use Bitrix\UI\FileUploader\LoadResult;
use Bitrix\UI\FileUploader\LoadResultCollection;
use Bitrix\UI\FileUploader\PreviewImage;
use Bitrix\UI\FileUploader\PreviewImageOptions;
use Bitrix\UI\FileUploader\RemoveResult;
use Bitrix\UI\FileUploader\RemoveResultCollection;
use Bitrix\UI\FileUploader\UploaderController;
use Bitrix\UI\FileUploader\UploadRequest;
use Bitrix\UI\FileUploader\UploadResult;
use Bitrix\Main\Engine;

Loader::requireModule('ui');

class DiskUploaderController extends UploaderController implements CustomLoad, CustomRemove
{
	public function __construct(array $options)
	{
		$controllerOptions = [
			'folderId' => 0,
		];

		if (isset($options['folderId']) && is_int($options['folderId']))
		{
			$controllerOptions['folderId'] = $options['folderId'];
		}

		parent::__construct($controllerOptions);
	}

	public function isAvailable(): bool
	{
		return $GLOBALS['USER']->isAuthorized() && Loader::includeModule('disk');
	}

	public function getConfiguration(): Configuration
	{
		$configuration = new Configuration();
		$configuration->setMaxFileSize(null);
		$configuration->setTreatOversizeImageAsFile(true);
		$configuration->setIgnoreUnknownImageTypes(true);

		return $configuration;
	}

	public function canUpload(UploadRequest $uploadRequest = null): bool
	{
		[$folder, $storage] = $this->getFolderAndStorage($uploadRequest ? $uploadRequest->getName() : '');

		if ($folder === null || $storage === null)
		{
			return false;
		}

		return $folder->canAdd($storage->getCurrentUserSecurityContext());
	}

	public function onUploadComplete(UploadResult $uploadResult): void
	{
		$tempFile = $uploadResult->getTempFile();
		[$folder, $storage] = $this->getFolderAndStorage($tempFile->getFilename());
		if ($folder === null || $storage === null || !$folder->canAdd($storage->getCurrentUserSecurityContext()))
		{
			$uploadResult->addError(new Error('Access denied'));

			return;
		}

		$tempFile = $uploadResult->getTempFile();
		$diskFile = $folder->addFile(
			[
				'NAME' => Text::correctFilename($tempFile->getFilename()),
				'FILE_ID' => $tempFile->getFileId(),
				'SIZE' => $tempFile->getSize(),
				'CREATED_BY' => $tempFile->getCreatedBy(),
			],
			[],
			true
		);

		if ($diskFile)
		{
			$fileInfo = $this->createFileInfo($diskFile);
			$uploadResult->setFileInfo($fileInfo);
			$tempFile->makePersistent();
		}
		else
		{
			if (is_array($folder->getErrors()))
			{
				$uploadResult->addErrors($folder->getErrors());
			}
			else
			{
				$uploadResult->addError(new Error('The file has not been saved'));
			}

			$tempFile->delete();
		}
	}

	public function load(array $ids): LoadResultCollection
	{
		$results = new LoadResultCollection();
		$userId = CurrentUser::get()->getId();
		foreach ($ids as $id)
		{
			$loadResult = new LoadResult($id);
			[$type, $realValue] = FileUserType::detectType($id);
			if ($type == FileUserType::TYPE_NEW_OBJECT)
			{
				$fileModel = Disk\File::loadById($realValue, ['STORAGE']);
				if (!$fileModel)
				{
					$loadResult->addError(new Error('Could not find file'));
				}
				elseif (!$fileModel->canRead($fileModel->getStorage()->getCurrentUserSecurityContext()))
				{
					$loadResult->addError(new Error('Bad permission. Could not read this file'));
				}
				else
				{
					$fileInfo = $this->createFileInfo($fileModel);
					$loadResult->setFile($fileInfo);
				}
			}
			else
			{
				$attachedModel = Disk\AttachedObject::loadById($realValue, ['OBJECT', 'VERSION']);
				if (!$attachedModel)
				{
					$loadResult->addError(new Error('Could not find attached object'));
				}
				elseif (!$attachedModel->canRead($userId))
				{
					$loadResult->addError(new Error('Bad permission. Could not read this file'));
				}
				else
				{
					$fileInfo = $this->createFileInfo($attachedModel);
					$loadResult->setFile($fileInfo);
				}
			}

			$results->add($loadResult);
		}

		return $results;
	}

	public function remove(array $ids): RemoveResultCollection
	{
		$results = new RemoveResultCollection();
		$userId = (int)CurrentUser::get()->getId();;
		foreach ($ids as $id)
		{
			$removeResult = new RemoveResult($id);
			[$type, $realValue] = FileUserType::detectType($id);
			if ($type === FileUserType::TYPE_NEW_OBJECT)
			{
				$file = Disk\File::loadById($realValue, ['STORAGE']);
				if (!$file)
				{
					$removeResult->addError(new Error('Could not find file'));
				}
				elseif (!$file->canDelete($file->getStorage()->getCurrentUserSecurityContext()))
				{
					$removeResult->addError(new Error('Bad permission. Could not read this file'));
				}
				else if ($file->countAttachedObjects() != 0)
				{
					$removeResult->addError(new Error('Could not delete file which attached to entities'));
				}
				else if ($file->getGlobalContentVersion() != 1)
				{
					$removeResult->addError(new Error('Could not delete file which has a few versions'));
				}
				else
				{
					/** @var Disk\Folder $folder */
					[$folder] = $this->getFolderAndStorage($file->getOriginalName());
					if (!$file->getParent() || !$folder || $file->getParentId() !== $folder->getId())
					{
						$removeResult->addError(new Error('Could not delete file which is not located in folder for uploaded files.'));
					}
					else if (!$file->delete($userId))
					{
						$removeResult->addErrors($file->getErrors());
					}
				}
			}
			else
			{
				$removeResult->addError(new Error('Could not delete attached object'));
			}

			$results->add($removeResult);
		}

		return $results;
	}

	public static function getFileInfo(array $ids): array
	{
		$result = [];
		$controller = new static([]);
		$loadResults = $controller->load(array_unique($ids));
		foreach ($loadResults as $loadResult)
		{
			if ($loadResult->isSuccess() && $loadResult->getFile() !== null)
			{
				$result[] = $loadResult->getFile()->jsonSerialize();
			}
		}

		return $result;
	}

	public static function shouldTreatImageAsFile(array $fileData): bool
	{
		if (empty($fileData['FILE_SIZE']))
		{
			return false;
		}

		$controller = new static([]);
		$config = $controller->getConfiguration();

		$imageData = new FileData($fileData['ORIGINAL_NAME'], $fileData['CONTENT_TYPE'], $fileData['FILE_SIZE']);
		$imageData->setWidth($fileData['WIDTH'] ?? 0);
		$imageData->setHeight($fileData['HEIGHT'] ?? 0);

		return $config->shouldTreatImageAsFile($imageData);
	}

	/**
	 * @param Disk\AttachedObject | Disk\File $fileModel
	 *
	 * @return FileInfo|null
	 */
	private function createFileInfo($fileModel): ?FileInfo
	{
		$fileInfo = FileInfo::createFromBFile($fileModel->getFileId());
		if ($fileInfo === null)
		{
			return null;
		}

		$id =
			$fileModel instanceof Disk\File
				? FileUserType::NEW_FILE_PREFIX . $fileModel->getId()
				: (int)$fileModel->getId()
		;

		$fileInfo->setId($id);
		$fileInfo->setName($fileModel->getName());

		$customData = [
			'fileId' => (int)$fileModel->getId(),
			'fileType' => null,
			'canRename' => false,
			'canMove' => false,
			'storage' => null,
			'objectId' => null,
			'isEditable' => false,
		];

		if ($fileModel instanceof Disk\File)
		{
			// File Model
			$customData['canRename'] = true;
			$customData['canMove'] = true;
			$customData['objectId'] = (int)$fileModel->getId();
			$customData['allowEdit'] = \Bitrix\Disk\Configuration::isEnabledDefaultEditInUf();
			$customData['isEditable'] = DocumentHandler::isEditable($fileModel->getExtension());

			$downloadUrl = Engine\UrlManager::getInstance()->create(
				'disk.file.download',
				['fileId' => $fileModel->getId()]
			);

			$fileInfo->setDownloadUrl($downloadUrl);

			$storage = $fileModel->getStorage();
			$customData['canUpdate'] = $storage && $fileModel->canUpdate($storage->getCurrentUserSecurityContext());
		}
		else
		{
			// Attached Object
			$customData['objectId'] = (int)$fileModel->getObjectId();
			$customData['allowEdit'] = (bool)$fileModel->getAllowEdit();
			$customData['isEditable'] =
				$fileModel->getFile() && DocumentHandler::isEditable($fileModel->getFile()->getExtension())
			;

			$downloadUrl = Engine\UrlManager::getInstance()->create(
				'disk.attachedObject.download',
				['attachedObjectId' => $id]
			);

			$fileInfo->setDownloadUrl($downloadUrl);

			$user = CurrentUser::get();
			$userId = $user ? $user->getId() : \Bitrix\Disk\Security\SecurityContext::GUEST_USER;
			$customData['canUpdate'] = $fileModel->canUpdate($userId);
		}

		$file = $fileModel instanceof Disk\File ? $fileModel : $fileModel->getFile();
		if ($file)
		{
			$customData['fileType'] = $file->getView()->getEditorTypeFile() ?: null;

			$storage = $file->getStorage();
			$folder = $file->getParent();
			if ($storage && $folder)
			{
				$currentUserId = (int)CurrentUser::get()->getId();
				$isMyDisk =
					$storage->getProxyType() instanceof \Bitrix\Disk\ProxyType\User
					&& (int)$storage->getEntityId() === $currentUserId
				;

				$storageName =
					$isMyDisk
						? $storage->getProxyType()->getTitleForCurrentUser()
						: $storage->getProxyType()->getEntityTitle()
				;

				$customData['storage'] = $storageName . ' / ' . ($folder->isRoot() ? '' : $folder->getName());
			}
		}

		$fileInfo->setCustomData($customData);

		if ($fileInfo->isImage())
		{
			$config = $this->getConfiguration();
			if ($config->shouldTreatImageAsFile($fileInfo))
			{
				$fileInfo->setTreatImageAsFile(true);
			}
			else
			{
				$previewOptions = ['width' => 1200, 'height' => 1200]; // double size (see edit.php and html-parser.js)
				if ($fileModel instanceof Disk\File)
				{
					$urlManager = \Bitrix\Disk\Driver::getInstance()->getUrlManager();
					$previewUrl = $urlManager->getUrlForShowFile($fileModel, $previewOptions);
				}
				else
				{
					$previewUrl = \Bitrix\Disk\UrlManager::getUrlToActionShowUfFile($fileModel->getId(), $previewOptions);
				}

				$rectangle = PreviewImage::getSize($fileInfo, new PreviewImageOptions($previewOptions));
				$fileInfo->setPreviewUrl($previewUrl, $rectangle->getWidth(), $rectangle->getHeight());
			}
		}

		return $fileInfo;
	}

	private function getFolderAndStorage(string $filename): array
	{
		$folder = null;
		$storage = null;

		$folderId = $this->getOption('folderId', 0);
		if ($folderId > 0)
		{
			$folder = Disk\Folder::load(['ID' => $folderId]);
			if ($folder !== null)
			{
				$storage = $folder->getStorage();
			}
		}
		else
		{
			$userId = (int)CurrentUser::get()->getId();
			$storage = Disk\Driver::getInstance()->getStorageByUserId($userId);
			if ($storage !== null)
			{
				if (mb_strpos($filename, 'videomessage') === 0)
				{
					$folder = $storage->getFolderForRecordedFiles();
				}
				else
				{
					$folder = $storage->getFolderForUploadedFiles();
				}
			}
		}

		return [$folder, $storage];
	}

	public function canView(): bool
	{
		return false;
	}

	public function canRemove(): bool
	{
		return false;
	}

	public function verifyFileOwner(FileOwnershipCollection $files): void
	{

	}
}
