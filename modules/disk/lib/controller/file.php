<?php

namespace Bitrix\Disk\Controller;

use Bitrix\Disk;
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
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_ERROR_MESSAGE_DENIED'));

			return;
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
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			if (!$fileId)
			{
				$this->errorCollection[] = new Error('Could not save file data by \CFile::saveFile');

				return;
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
			$this->errorCollection->add($folder->getErrors());

			return;
		}

		$previewFileData = $this->request->getFile('previewFile');
		if ($previewFileData && \CFile::isImage($previewFileData['name'], $previewFileData['type']))
		{
			$previewFileData['MODULE_ID'] = 'main';
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			$previewId = \CFile::saveFile($previewFileData, 'main_preview', true);
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
			Application::getInstance()->terminate();

			return;
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
			Application::getInstance()->terminate();
		}

		$fileName = $file->getView()->getPreviewName();
		$fileData = $file->getView()->getPreviewData();

		if (empty($fileName) || empty($fileData) || !is_array($fileData))
		{
			Application::getInstance()->terminate();

			return;
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
		return Response\BFile::createByFileId($file->getFileId(), $file->getName());
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
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_ERROR_MESSAGE_DENIED'));

			return;
		}

		if (!$file->restoreFromVersion($version, $this->getCurrentUser()->getId()))
		{
			$this->errorCollection->add($file->getErrors());

			return;
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

	public function getAllowedOperationsRightsAction(Disk\File $file)
	{
		return $this->getAllowedOperationsRights($file);
	}

	public function addSharingAction(Disk\File $file, $entity, $taskName)
	{
		if (!Disk\Integration\Bitrix24Manager::isFeatureEnabled('disk_file_sharing'))
		{
			$this->errorCollection[] = new Error('Not allowed');

			return;
		}

		$currentUserId = $this->getCurrentUser()->getId();
		$securityContext = $file->getStorage()->getSecurityContext($currentUserId);
		if (!$file->canShare($securityContext))
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_ERROR_MESSAGE_DENIED'));

			return;
		}

		$rightsManager = Driver::getInstance()->getRightsManager();
		if (!$rightsManager->isValidTaskName($taskName))
		{
			$this->errorCollection[] = new Error('Invalid task name');

			return;
		}

		$maxTaskName = $rightsManager->getPseudoMaxTaskByObjectForUser($file, $currentUserId);
		if ($rightsManager->pseudoCompareTaskName($taskName, $maxTaskName) > 0)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_ERROR_MESSAGE_DENIED'));

			return;
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
			return;
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

			return;
		}
		$folder = $userStorage->getFolderForSavedFiles();
		if (!$folder)
		{
			$this->addError(new Error('Could not find folder for created files'));

			return;
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

			return;
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
}