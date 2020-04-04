<?php

namespace Bitrix\Disk\Uf;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\Document;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\User;
use Bitrix\Main\Localization\Loc;

Loc::loadLanguageFile(__FILE__);

final class LocalDocumentController extends Document\LocalDocumentController
{
	const ERROR_COULD_NOT_FIND_ATTACHED_OBJECT = 'DISK_UF_LOC_DOC_CON_22001';

	/** @var AttachedObject */
	protected $attachedModel;
	/** @var int */
	protected $attachedId;

	protected function prepareParams()
	{
		if($this->isActionWithExistsFile())
		{
			if(!$this->checkRequiredGetParams(array('attachedId')))
			{
				return false;
			}
			$this->attachedId = (int)$this->request->getQuery('attachedId');
		}

		return true;
	}

	protected function initializeData()
	{
		$this->attachedModel = AttachedObject::loadById($this->attachedId, array('OBJECT.STORAGE', 'VERSION'));
		if(!$this->attachedModel)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_UF_LOCAL_DOC_CONTROLLER_ERROR_COULD_NOT_FIND_ATTACHED_OBJECT'), self::ERROR_COULD_NOT_FIND_ATTACHED_OBJECT)));
			$this->sendJsonErrorResponse();
		}
		$this->fileId = $this->attachedModel->getObjectId();
		$this->file = $this->attachedModel->getFile();
		if($this->attachedModel->getVersionId())
		{
			$this->versionId = $this->attachedModel->getVersionId();
			$this->version = $this->attachedModel->getVersion();
		}
	}

	protected function initializeFile($fileId)
	{
		if($fileId == $this->attachedModel->getObjectId())
		{
			$this->file = $this->attachedModel->getFile();
		}
		else
		{
			parent::initializeFile($fileId);
		}
	}

	protected function checkReadPermissions()
	{
		if(!$this->attachedModel->canRead($this->getUser()->getId()))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_UF_LOCAL_DOC_CONTROLLER_ERROR_BAD_RIGHTS'), self::ERROR_BAD_RIGHTS)));
			$this->sendJsonErrorResponse();
		}
	}

	protected function checkUpdatePermissions()
	{
		if(!$this->attachedModel->canUpdate($this->getUser()->getId()))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_UF_LOCAL_DOC_CONTROLLER_ERROR_BAD_RIGHTS'), self::ERROR_BAD_RIGHTS)));
			$this->sendJsonErrorResponse();
		}
	}

	protected function processActionDownload()
	{
		LocalRedirect(Driver::getInstance()->getUrlManager()->getUrlUfController('download', array('attachedId' => $this->attachedId)));
	}

	/**
	 * For UF and work with local editors we have special scenario:
	 * If user don't have permission for update file,
	 * we have to attach new file to entity by posting comment with
	 * alternative version.
	 */
	protected function processActionCommit()
	{
		$userId = $this->getUser()->getId();
		if($this->attachedModel->canUpdate($userId))
		{
			parent::processActionCommit();
			return;
		}

		$this->checkRequiredFilesParams(array('file'));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		$userStorage = Driver::getInstance()->getStorageByUserId($userId);
		if(!$userStorage)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_LOCAL_DOC_CONTROLLER_ERROR_COULD_NOT_FIND_STORAGE'), self::ERROR_COULD_NOT_FIND_STORAGE)));
			$this->sendJsonErrorResponse();
		}
		$folder = $userStorage->getFolderForCreatedFiles();
		if(!$folder)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_LOCAL_DOC_CONTROLLER_ERROR_COULD_NOT_FIND_FOLDER_FOR_CREATED_FILES'), self::ERROR_COULD_NOT_FIND_FOLDER_FOR_CREATED_FILES)));
			$this->sendJsonErrorResponse();
		}
		if(!$folder->canAdd($folder->getStorage()->getCurrentUserSecurityContext()))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_LOCAL_DOC_CONTROLLER_ERROR_BAD_RIGHTS'), self::ERROR_BAD_RIGHTS)));
			$this->sendJsonErrorResponse();
		}

		//todo fix Cherezov. Ban encoding 1251
		$fileArray = $this->request->getFile('file');
		$fileArray['name'] = $this->file->getName();
		$newFile = $folder->uploadFile(
			$fileArray,
			array(
				'NAME' => $this->file->getName(),
				'CREATED_BY' => $userId
			),
			array(),
			true
		);
		if(!$newFile)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_LOCAL_DOC_CONTROLLER_ERROR_COULD_NOT_CREATE_FILE'), self::ERROR_COULD_NOT_CREATE_FILE)));
			$this->errorCollection->add($folder->getErrors());
			$this->sendJsonErrorResponse();
		}


		$valueFileUf = FileUserType::NEW_FILE_PREFIX . $newFile->getId();
		/** @var User $createUser */
		$createUser = User::loadById($userId);
		if(!$createUser)
		{
			$this->sendJsonErrorResponse();
		}
		$text = Loc::getMessage('DISK_UF_LOCAL_DOC_CONTROLLER_UPLOAD_NEW_VERSION_IN_COMMENT_M');
		if($createUser->getPersonalGender() == 'F')
		{
			$text = Loc::getMessage('DISK_UF_LOCAL_DOC_CONTROLLER_UPLOAD_NEW_VERSION_IN_COMMENT_F');
		}

		if($this->attachedModel->getAllowAutoComment())
		{
			$this->attachedModel->getConnector()->addComment($userId, array(
				'text' => $text,
				'fileId' => $valueFileUf,
				'authorGender' => $createUser->getPersonalGender()
			));
		}

		$this->sendJsonSuccessResponse();
	}


}