<?php

namespace Bitrix\Disk\Document;

use Bitrix\Disk\Driver;
use Bitrix\Disk\ExternalLink;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\ExternalLinkTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

class GoogleViewerHandler extends DocumentHandler implements IViewer
{
	const ERROR_METHOD_IS_NOT_SUPPORTED = 'DISK_GV_HANDLER_22001';
	const ERROR_COULD_NOT_FIND_EXT_LINK = 'DISK_GV_HANDLER_22002';

	/**
	 * @inheritdoc
	 */
	public static function getCode()
	{
		return 'gvdrive';
	}

	/**
	 * @inheritdoc
	 */
	public static function getName()
	{
		return Loc::getMessage('DISK_GOOGLE_VIEWER_HANDLER_NAME');
	}

	/**
	 * Execute this method for check potential possibility get access token.
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function checkAccessibleTokenService()
	{
		return true;
	}


	/**
	 * Return link for authorize user in external service.
	 * @param string $mode
	 * @return string
	 */
	public function getUrlForAuthorizeInTokenService($mode = 'modal')
	{
		return '';
	}

	/**
	 * Request and store access token (self::accessToken) for self::userId
	 * @return $this
	 */
	public function queryAccessToken()
	{
		return $this;
	}

	/**
	 * @return bool
	 */
	public function hasAccessToken()
	{
		return true;
	}

	/**
	 * Create new blank file in cloud service.
	 * It is not necessary set shared rights on file.
	 * @param FileData $fileData
	 * @return FileData|null
	 */
	public function createBlankFile(FileData $fileData)
	{
		$this->errorCollection->add(array(
			new Error(Loc::getMessage('DISK_GOOGLE_VIEWER_HANDLER_ERROR_METHOD_IS_NOT_SUPPORTED'), self::ERROR_METHOD_IS_NOT_SUPPORTED)
		));
		return null;
	}

	/**
	 * Create file in cloud service by upload from us server.
	 * Necessary set shared rights on file for common work.
	 *
	 * @param FileData $fileData
	 * @return FileData|null
	 */
	public function createFile(FileData $fileData)
	{
		$this->errorCollection->add(array(
			new Error(Loc::getMessage('DISK_GOOGLE_VIEWER_HANDLER_ERROR_METHOD_IS_NOT_SUPPORTED'), self::ERROR_METHOD_IS_NOT_SUPPORTED)
		));
		return null;
	}

	/**
	 * Download file from cloud service by FileData::id, put contents in FileData::src
	 * @param FileData $fileData
	 * @return FileData|null
	 */
	public function downloadFile(FileData $fileData)
	{
		$this->errorCollection->add(array(
			new Error(Loc::getMessage('DISK_GOOGLE_VIEWER_HANDLER_ERROR_METHOD_IS_NOT_SUPPORTED'), self::ERROR_METHOD_IS_NOT_SUPPORTED)
		));
		return null;
	}

	/**
	 * Gets a file's metadata by ID.
	 *
	 * @param FileData $fileData
	 * @return array|null Describes file (id, title, size)
	 */
	public function getFileMetadata(FileData $fileData)
	{
		$this->errorCollection->add(array(
			new Error(Loc::getMessage('DISK_GOOGLE_VIEWER_HANDLER_ERROR_METHOD_IS_NOT_SUPPORTED'), self::ERROR_METHOD_IS_NOT_SUPPORTED)
		));
		return null;
	}

	/**
	 * Download part of file from cloud service by FileData::id, put contents in FileData::src
	 * @param FileData $fileData
	 * @param          $startRange
	 * @param          $chunkSize
	 * @return FileData|null
	 */
	public function downloadPartFile(FileData $fileData, $startRange, $chunkSize)
	{
		$this->errorCollection->add(array(
			new Error(Loc::getMessage('DISK_GOOGLE_VIEWER_HANDLER_ERROR_METHOD_IS_NOT_SUPPORTED'), self::ERROR_METHOD_IS_NOT_SUPPORTED)
		));
		return null;
	}

	/**
	 * Delete file from cloud service by FileData::id
	 * @param FileData $fileData
	 * @return bool
	 */
	public function deleteFile(FileData $fileData)
	{
		$this->errorCollection->add(array(
			new Error(Loc::getMessage('DISK_GOOGLE_VIEWER_HANDLER_ERROR_METHOD_IS_NOT_SUPPORTED'), self::ERROR_METHOD_IS_NOT_SUPPORTED)
		));
		return null;
	}


	/**
	 * Get url for showing preview file.
	 * @param FileData $fileData
	 * @return string|null
	 */
	public function getDataForViewFile(FileData $fileData)
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'file',
		)))
		{
			return null;
		}

		$seconds = (int)300;
		$deathTime = new DateTime;
		$deathTime->add("+ {$seconds} seconds");

		$data = array(
			'TYPE' => ExternalLinkTable::TYPE_AUTO,
			'DEATH_TIME' => $deathTime,
		);
		$specificVersionModel = $fileData->getVersion();
		if($specificVersionModel)
		{
			$data['VERSION_ID'] = $specificVersionModel->getId();
		}
		$extLinkModel = $fileData->getFile()->addExternalLink($data);

		if(!$extLinkModel)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_GOOGLE_VIEWER_HANDLER_ERROR_COULD_NOT_FIND_EXT_LINK'), self::ERROR_COULD_NOT_FIND_EXT_LINK)));
			$this->errorCollection->add($fileData->getFile()->getErrors());
		}

		$extLink = Driver::getInstance()->getUrlManager()->getUrlExternalLink(array(
			'hash' => $extLinkModel->getHash(),
			'action' => 'download',
		), true);

		return array(
			'id' => $extLinkModel->getHash(),
			'viewUrl' => Driver::getInstance()->getUrlManager()->generateUrlForGoogleViewer($extLink),
			'neededDelete' => false,
			'neededCheckView' => true,
		);
	}

	/**
	 * Lists folder contents
	 * @param $path
	 * @param $folderId
	 * @return mixed
	 */
	public function listFolder($path, $folderId)
	{
		$this->errorCollection->add(array(
			new Error(Loc::getMessage('DISK_GOOGLE_VIEWER_HANDLER_ERROR_METHOD_IS_NOT_SUPPORTED'), self::ERROR_METHOD_IS_NOT_SUPPORTED)
		));
		return null;
	}

	/**
	 * Check success view file in service.
	 * @param FileData $fileData
	 * @return bool
	 */
	public function checkViewFile(FileData $fileData)
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'id',
		)))
		{
			return null;
		}

		if(!ExternalLink::isValidValueForField('HASH', $fileData->getId(), $this->errorCollection))
		{
			return false;
		}

		/** @var ExternalLink $extLinkModel */
		$extLinkModel = ExternalLink::load(array('=HASH' => $fileData->getId()));
		if(!$extLinkModel)
		{
			$this->errorCollection->add(array(
				new Error(Loc::getMessage('DISK_GOOGLE_VIEWER_HANDLER_ERROR_COULD_NOT_FIND_EXT_LINK'), self::ERROR_COULD_NOT_FIND_EXT_LINK)
			));
			return null;
		}

		return (bool)$extLinkModel->getDownloadCount();
	}
}