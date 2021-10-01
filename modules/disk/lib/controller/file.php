<?php

namespace Bitrix\Disk\Controller;

use Bitrix\Disk;
use Bitrix\Disk\Configuration;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\Engine;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Security\ParameterSigner;
use Bitrix\Disk\TypeFile;
use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\Response;
use Bitrix\Main\Localization\Loc;

class File extends BaseObject
{
	public function configureActions()
	{
		$configureActions = parent::configureActions();

		$configureActions['showImage'] =
		$configureActions['showPreview'] = [
			'-prefilters' => [
				Main\Engine\ActionFilter\Csrf::class,
				Authentication::class,
			],
			'+prefilters' => [
				new Authentication(true),
				new Engine\ActionFilter\CheckImageSignature(),
				new Main\Engine\ActionFilter\CloseSession(),
			]
		];

		$configureActions['download'] = [
			'-prefilters' => [
				Main\Engine\ActionFilter\Csrf::class,
				Authentication::class,
			],
			'+prefilters' => [
				new Authentication(true),
				new Main\Engine\ActionFilter\CloseSession(),
			]
		];

		return $configureActions;
	}

	public function getPrimaryAutoWiredParameter()
	{
		return new ExactParameter(Disk\File::class, 'file', function($className, $id){
			return Disk\File::loadById($id);
		});
	}

	public function getAction(Disk\File $file)
	{
		return $this->get($file);
	}

	/**
	 * @param Disk\File $file
	 * @return array
	 * @deprecated
	 * @throws ArgumentTypeException
	 */
	public function getMetaDataForCreatedFileInUfAction(Disk\File $file)
	{
		if ($file->getCreatedBy() != $this->getCurrentUser()->getId())
		{
			return [];
		}

		$folder = $file->getParent();
		if (!$folder || $folder->getCode() !== Disk\SpecificFolder::CODE_FOR_CREATED_FILES)
		{
			return [];
		}

		$storage = $file->getStorage();

		return [
			'id' => $file->getId(),
			'object' => [
				'id' => $file->getId(),
				'name' => $file->getName(),
				'sizeInt' => $file->getSize(),
				'size' => \CFile::formatSize($file->getSize()),
				'extension' => $file->getExtension(),
				'nameWithoutExtension' => getFileNameWithoutExtension($file->getName()),
			],
			'folderName' => $storage->getProxyType()->getTitleForCurrentUser() . ' / ' . $folder->getName(),
		];
	}

	protected function get(Disk\BaseObject $file)
	{
		if (!($file instanceof Disk\File))
		{
			throw new ArgumentTypeException('file', Disk\File::class);
		}

		$data = parent::get($file);
		$data['file'] = $data['object'];
		unset($data['object']);

		$downloadUri = $this->getActionUri('download', ['fileId' => $file->getId(),]);
		$showObjectInGridUri = $this->getUriToShowObjectInGrid($file);

		$data['file'] = array_merge($data['file'], [
			'extra' => [
				'downloadUri' => $downloadUri,
				'showInGridUri' => $showObjectInGridUri,
			],
		]);

		if ($file->getPreviewId())
		{
			$data['file']['extra']['previewUri'] = $this->getActionUri('showPreview', ['fileId' => $file->getId(),]);
		}

		if (TypeFile::isImage($file))
		{
			$data['file']['extra']['imagePreviewUri'] = $this->getActionUri('showImage', [
				'fileId' => $file->getId(),
				'signature' => ParameterSigner::getImageSignature($file->getId(), 400, 400),
				'width' => 400,
				'height' => 400,
			]);
		}

		return $data;
	}

	protected function getUriToShowObjectInGrid(Disk\File $file)
	{
		$urlManager = Driver::getInstance()->getUrlManager();
		$urlShowObjectInGrid = $urlManager->getUrlFocusController('showObjectInGrid', array(
			'objectId' => $file->getId(),
		));

		return new Main\Web\Uri($urlShowObjectInGrid);
	}

	public function renameAction(Disk\File $file, $newName, $autoCorrect = false)
	{
		return $this->rename($file, $newName, $autoCorrect);
	}

	public function createByContentAction(Disk\Folder $folder, $filename, Disk\Bitrix24Disk\TmpFile $content, $generateUniqueName = false)
	{
		$content->registerDelayedDeleteOnShutdown();
		$currentUserId = $this->getCurrentUser()->getId();
		$securityContext = $folder->getStorage()->getSecurityContext($currentUserId);
		$contentType = $this->request->getHeader('X-Upload-Content-Type')?: $content->getContentType();

		if (!$folder->canAdd($securityContext))
		{
			$this->addError(new Error(Loc::getMessage('DISK_ERROR_MESSAGE_DENIED')));

			return null;
		}

		if ($content->isCloud() && $content->getContentType())
		{
			$fileId = \CFile::saveFile([
				'name' => $content->getFilename(),
				'tmp_name' => $content->getAbsolutePath(),
				'type' => $contentType,
				'width' => $content->getWidth(),
				'height' => $content->getHeight(),
				'MODULE_ID' => Driver::INTERNAL_MODULE_ID,
				], Driver::INTERNAL_MODULE_ID, true, true);

			if (!$fileId)
			{
				$this->addError(new Error('Could not save file data by \CFile::saveFile'));

				return null;
			}

			//it's crutch to be similar @see \Bitrix\Disk\Folder::uploadFile()
			$filename = Disk\Ui\Text::correctFilename($filename);
			$file = $folder->addFile(
				[
					'NAME' => $filename,
					'FILE_ID' => $fileId,
					'SIZE' => $content->getSize(),
					'CREATED_BY' => $currentUserId,
				],
				[],
				$generateUniqueName
			);
		}
		else
		{
			$fileArray = \CFile::makeFileArray($content->getAbsolutePath());
			$fileArray['type'] = $contentType;
			$fileArray['name'] = $filename;
			$file = $folder->uploadFile(
				$fileArray,
				[
					'NAME' => $filename,
					'CREATED_BY' => $currentUserId,
				],
				[],
				$generateUniqueName
			);
		}

		if (!$file)
		{
			$this->addErrors($folder->getErrors());

			return null;
		}

		$previewFileData = $this->request->getFile('previewFile');
		if ($previewFileData && \CFile::isImage($previewFileData['name'], $previewFileData['type']))
		{
			$previewFileData['MODULE_ID'] = 'main';
			$previewId = \CFile::saveFile($previewFileData, 'main_preview', true, true);
			if ($previewId)
			{
				(new Main\UI\Viewer\PreviewManager())->setPreviewImageId($file->getFileId(), $previewId);
			}
		}

		return $this->getAction($file);
	}

	public function showImageAction(Disk\File $file, $width = 0, $height = 0, $exact = null)
	{
		$fileName = $file->getName();
		$fileData = $file->getFile();

		if (empty($fileName) || empty($fileData) || !is_array($fileData))
		{
			return null;
		}

		$isImage = TypeFile::isImage($fileName);
		if (!$isImage)
		{
			return $this->downloadAction($file);
		}

		$response = Response\ResizedImage::createByImageData(
			$fileData,
			$width,
			$height
		);

		$response
			->setResizeType($exact === 'Y' ? BX_RESIZE_IMAGE_EXACT : BX_RESIZE_IMAGE_PROPORTIONAL)
			->setName($fileName)
			->setCacheTime(86400)
		;

		return $response;
	}

	public function showPreviewAction(Disk\File $file, $width = 0, $height = 0, $exact = null)
	{
		if (!$file->getView()->getPreviewData())
		{
			return null;
		}

		$fileName = $file->getView()->getPreviewName();
		$fileData = $file->getView()->getPreviewData();

		if (empty($fileName) || empty($fileData) || !is_array($fileData))
		{
			return null;
		}

		$response = Response\ResizedImage::createByImageData(
			$fileData,
			$width,
			$height
		);

		$response
			->setResizeType($exact === 'Y' ? BX_RESIZE_IMAGE_EXACT : BX_RESIZE_IMAGE_PROPORTIONAL)
			->setName($file->getName())
			->setCacheTime(86400)
		;

		return $response;
	}

	public function downloadAction(Disk\File $file)
	{
		$response = Response\BFile::createByFileId($file->getFileId(), $file->getName());
		$response->setCacheTime(Disk\Configuration::DEFAULT_CACHE_TIME);

		return $response;
	}

	public function markDeletedAction(Disk\File $file)
	{
		return $this->markDeleted($file);
	}

	public function deleteAction(Disk\File $file)
	{
		return $this->deleteFile($file);
	}

	public function restoreAction(Disk\File $file)
	{
		return $this->restore($file);
	}

	public function restoreFromVersionAction(Disk\File $file, Disk\Version $version)
	{
		$securityContext = $file->getStorage()->getCurrentUserSecurityContext();
		if (!$file->canRestore($securityContext))
		{
			$this->addError(new Error(Loc::getMessage('DISK_ERROR_MESSAGE_DENIED')));

			return null;
		}

		if (!$file->restoreFromVersion($version, $this->getCurrentUser()->getId()))
		{
			$this->addErrors($file->getErrors());

			return null;
		}

		return $this->getAction($file);
	}

	public function generateExternalLinkAction(Disk\File $file)
	{
		return $this->generateExternalLink($file);
	}

	public function disableExternalLinkAction(Disk\File $file)
	{
		return $this->disableExternalLink($file);
	}

	public function getExternalLinkAction(Disk\File $file)
	{
		return $this->getExternalLink($file);
	}

	public function getAllowedOperationsRightsAction(Disk\File $file)
	{
		return $this->getAllowedOperationsRights($file);
	}

	public function addSharingAction(Disk\File $file, $entity, $taskName)
	{
		if (!Disk\Integration\Bitrix24Manager::isFeatureEnabled('disk_file_sharing'))
		{
			$this->addError(new Error('Not allowed'));

			return null;
		}

		$currentUserId = $this->getCurrentUser()->getId();
		$securityContext = $file->getStorage()->getSecurityContext($currentUserId);
		if (!$file->canShare($securityContext))
		{
			$this->addError(new Error(Loc::getMessage('DISK_ERROR_MESSAGE_DENIED')));

			return null;
		}

		$rightsManager = Driver::getInstance()->getRightsManager();
		if (!$rightsManager->isValidTaskName($taskName))
		{
			$this->addError(new Error('Invalid task name'));

			return null;
		}

		$maxTaskName = $rightsManager->getPseudoMaxTaskByObjectForUser($file, $currentUserId);
		if ($rightsManager->pseudoCompareTaskName($taskName, $maxTaskName) > 0)
		{
			$this->addError(new Error(Loc::getMessage('DISK_ERROR_MESSAGE_DENIED')));

			return null;
		}

		if (!Disk\Sharing::hasRightToKnowAboutEntity($currentUserId, $entity))
		{
			$this->addError(new Error("Could not share to entity {$entity}."));

			return null;
		}

		$sharing = Disk\Sharing::add(
			[
				'FROM_ENTITY' => Disk\Sharing::CODE_USER . $currentUserId,
				'REAL_OBJECT' => $file,
				'CREATED_BY' => $currentUserId,
				'CAN_FORWARD' => false,
				'TO_ENTITY' => $entity,
				'TASK_NAME' => $taskName,
			],
			$this->errorCollection
		);

		if(!$sharing)
		{
			return null;
		}

		return [
			'sharing' => [
				'id' => $sharing->getId(),
			],
		];
	}

	public function showSharingEntitiesAction(Disk\File $file)
	{
		$currentUserId = $this->getCurrentUser()->getId();
		$securityContext = $file->getStorage()->getSecurityContext($currentUserId);
		$rightsManager = Driver::getInstance()->getRightsManager();

		$entityList = [];
		//user has only read right. And he can't see on another sharing
		if(!$file->canShare($securityContext) && !$file->canChangeRights($securityContext))
		{
			/** @var Disk\User $user */
			$user = Disk\User::getById($currentUserId);

			$pseudoMaxTaskByObjectForUser = $rightsManager->getPseudoMaxTaskByObjectForUser($file, $currentUserId);
			$entityList = [
				[
					'entity' => [
						'id' => Disk\Sharing::CODE_USER . $currentUserId,
						'name' => $user->getFormattedName(),
						'avatar' => $user->getAvatarSrc(),
						'type' => 'users',
					],
					'sharing' => [
						'right' => $pseudoMaxTaskByObjectForUser,
						'name' => $rightsManager->getTaskTitleByName($pseudoMaxTaskByObjectForUser),
					],
				]
			];
		}
		else
		{
			foreach ($file->getMembersOfSharing() as $entity)
			{
				$entityList[] = [
					'entity' => [
						'id' => $entity['entityId'],
						'name' => $entity['name'],
						'avatar' => $entity['avatar'],
						'type' => $entity['type'],
					],
					'sharing' => [
						'id' => $entity['sharingId'],
						'taskName' => $entity['right'],
						'name' => $rightsManager->getTaskTitleByName($entity['right']),
						'canDelete' => true,
						'canChange' => true,
					],
				];
			}
		}

		return $entityList;
	}

	public function copyToMeAction(Disk\File $file)
	{
		$currentUserId = $this->getCurrentUser()->getId();
		$userStorage = Driver::getInstance()->getStorageByUserId($currentUserId);
		if (!$userStorage)
		{
			$this->addError(new Error('Could not find storage for current user'));

			return null;
		}
		$folder = $userStorage->getFolderForSavedFiles();
		if (!$folder)
		{
			$this->addError(new Error('Could not find folder for created files'));

			return null;
		}

		//so, now we don't copy links in the method copyTo. But here we have to copy content.
		//And after we set name to new object as it was on link.
		$newFile = $file->getRealObject()->copyTo($folder, $currentUserId, true);
		if ($file->getRealObject()->getName() != $file->getName())
		{
			$newFile->renameInternal($file->getName(), true);
		}

		if (!$newFile)
		{
			$this->addError(new Error('Could not copy file to storage for current user'));

			return null;
		}

		return $this->get($newFile);
	}

	public function showPropertiesAction(Disk\File $file)
	{
		$params = [
			'STORAGE' => $file->getStorage(),
			'FILE' => $file,
			'FILE_ID' => $file->getId(),
		];

		return new Response\Component('bitrix:disk.file.view', 'properties', $params);
	}

	public function runPreviewGenerationAction(Disk\File $file)
	{
		return [
			'previewGeneration' => $file->getView()->transformOnOpen($file),
		];
	}

	public function unlockAction(Disk\File $file)
	{
		if (!Configuration::isEnabledObjectLock())
		{
			$this->addError(new Error('Could not unlock. Feature is disabled in modules settings.'));

			return null;
		}

		$securityContext = $file->getStorage()->getCurrentUserSecurityContext();
		if (!$file->canUnlock($securityContext))
		{
			$this->addError(new Error('Could not unlock due to lack of rights.'));

			return null;
		}

		if (!$file->unlock($this->getCurrentUser()->getId()))
		{
			$this->addErrors($file->getErrors());

			return null;
		}

		return [
			'unlock' => null,
		];
	}
}