<?php

namespace Bitrix\Disk\Document;

use Bitrix\Disk\Configuration;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class BitrixHandler extends DocumentHandler implements IViewer
{
	const ERROR_METHOD_IS_NOT_SUPPORTED = 'DISK_BITRIX_HANDLER_22001';
	const ERROR_NO_VIEW = 'DISK_BITRIX_HANDLER_22002';
	const ERROR_NO_VIEW_SEND_TO_TRANSFORM = 'DISK_BITRIX_HANDLER_22003';

	/**
	 * @inheritdoc
	 */
	public static function getCode()
	{
		return 'bitrix';
	}

	/**
	 * @inheritdoc
	 */
	public static function getName()
	{
		return Loc::getMessage('DISK_BITRIX_HANDLER_NAME_2');
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
			new Error(Loc::getMessage('DISK_BITRIX_HANDLER_ERROR_METHOD_IS_NOT_SUPPORTED', array('#NAME#' => $this::getName())), self::ERROR_METHOD_IS_NOT_SUPPORTED)
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
			new Error(Loc::getMessage('DISK_BITRIX_HANDLER_ERROR_METHOD_IS_NOT_SUPPORTED', array('#NAME#' => $this::getName())), self::ERROR_METHOD_IS_NOT_SUPPORTED)
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
			new Error(Loc::getMessage('DISK_BITRIX_HANDLER_ERROR_METHOD_IS_NOT_SUPPORTED', array('#NAME#' => $this::getName())), self::ERROR_METHOD_IS_NOT_SUPPORTED)
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
			new Error(Loc::getMessage('DISK_BITRIX_HANDLER_ERROR_METHOD_IS_NOT_SUPPORTED', array('#NAME#' => $this::getName())), self::ERROR_METHOD_IS_NOT_SUPPORTED)
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
			new Error(Loc::getMessage('DISK_BITRIX_HANDLER_ERROR_METHOD_IS_NOT_SUPPORTED', array('#NAME#' => $this::getName())), self::ERROR_METHOD_IS_NOT_SUPPORTED)
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
			new Error(Loc::getMessage('DISK_BITRIX_HANDLER_ERROR_METHOD_IS_NOT_SUPPORTED', array('#NAME#' => $this::getName())), self::ERROR_METHOD_IS_NOT_SUPPORTED)
		));
		return null;
	}

	/**
	 * Get url for showing preview file.
	 * @param FileData $fileData
	 * @return array|null
	 */
	public function getDataForViewFile(FileData $fileData)
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'file',
		)))
		{
			return null;
		}

		$specificVersionModel = $fileData->getVersion();
		$urlManager = Driver::getInstance()->getUrlManager();
		$attachedId = false;
		if($fileData->getAttachedObject())
		{
			$attachedId = $fileData->getAttachedObject()->getId();
		}
		if($specificVersionModel)
		{
			$view = $specificVersionModel->getView();
			if($fileData->getAttachedObject())
			{
				$viewUrl = $urlManager->getUrlForShowAttachedVersionViewHtml($attachedId);
				$fallbackUrl = $urlManager->getUrlForShowAttachedVersionViewHtml($attachedId, array('mode' => 'iframe'));
			}
			else
			{
				$viewUrl = $urlManager->getUrlForShowVersionViewHtml($specificVersionModel);
				$fallbackUrl = $urlManager->getUrlForShowVersionViewHtml($specificVersionModel, array('mode' => 'iframe'));
			}
			$id = $specificVersionModel->getId();
		}
		else
		{
			$view = $fileData->getFile()->getView();
			if($attachedId > 0)
			{
				$viewUrl = $urlManager->getUrlForShowAttachedFileViewHtml($attachedId, array(), $fileData->getFile()->getUpdateTime()->getTimestamp());
				$fallbackUrl = $urlManager->getUrlForShowAttachedFileViewHtml($attachedId, array('mode' => 'iframe'), $fileData->getFile()->getUpdateTime()->getTimestamp());
			}
			else
			{
				$viewUrl = $urlManager->getUrlForShowViewHtml($fileData->getFile());
				$fallbackUrl = $urlManager->getUrlForShowViewHtml($fileData->getFile(), array('mode' => 'iframe'));
			}
			$id = $fileData->getFile()->getId();
		}

		if($view->isHtmlAvailable())
		{
			$viewerParams = array(
				'width' => $view->getJsViewerWidth(),
				'height' => $view->getJsViewerHeight(),
				'hideEdit' => $view->isJsViewerHideEditButton()? 1: 0,
				'src' => $viewUrl,
			);
			if($view->getJsViewerFallbackHtmlAttributeName())
			{
				$viewerParams[$view->getJsViewerFallbackHtmlAttributeName()] = $fallbackUrl;
			}
			if($view->isTransformationAllowed($fileData->getSize()))
			{
				$viewerParams['transformTimeout'] = $view->getTransformTime();
			}
			return array(
				'id' => $id,
				'viewUrl' => $viewUrl,
				'neededDelete' => false,
				'neededCheckView' => false,
				'viewerType' => $view->getJsViewerType(),
				'viewerParams' => $viewerParams,
			);
		}
		else
		{
			$view->transformOnOpen($fileData->getFile());
		}

		return null;
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
			new Error(Loc::getMessage('DISK_BITRIX_HANDLER_ERROR_METHOD_IS_NOT_SUPPORTED', array('#NAME#' => $this::getName())), self::ERROR_METHOD_IS_NOT_SUPPORTED)
		));
		return null;
	}
}