<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Disk\Internals\BaseComponent;
use Bitrix\Main\Localization\Loc;
use Bitrix\Transformer;
use Bitrix\Main\Engine\UrlManager;

if(!\Bitrix\Main\Loader::includeModule('transformer'))
{
	return false;
}

class CDiskFileTransformVideoComponent extends BaseComponent
{
	/** @var int */
	protected $bfileId;
	/** @var \Bitrix\Disk\File */
	protected $file;
	/** @var \Bitrix\Disk\AttachedObject */
	protected $attachedObject;

	protected function prepareParams()
	{
		parent::prepareParams();

		$this->bfileId = (int)$this->arParams['BFILE_ID'];
		$this->file = $this->arParams['FILE'];
		$this->attachedObject = $this->arParams['ATTACHED_OBJECT'];

		return $this;
	}

	protected function getUrlToDownload()
	{
		if ($this->attachedObject)
		{
			return UrlManager::getInstance()->create('disk.attachedObject.download', [
				'attachedObjectId' => $this->attachedObject->getId(),
			]);
		}

		return UrlManager::getInstance()->create('disk.file.download', [
			'fileId' => $this->file->getId(),
		]);
	}

	protected function processActionDefault()
	{
		if (empty($this->bfileId))
		{
			return;
		}

		$transformerManager = new \Bitrix\Main\UI\Viewer\Transformation\TransformerManager();
		$info = \Bitrix\Transformer\FileTransformer::getTransformationInfoByFile($this->bfileId);
		if ($info === false)
		{
			$this->arResult['STATUS'] = 'NOT_STARTED';
			$this->arResult['TITLE'] = Loc::getMessage('DISK_FILE_TRANSFORM_VIDEO_NOT_STARTED_TITLE');
			$this->arResult['DESC'] = Loc::getMessage('DISK_FILE_TRANSFORM_VIDEO_NOT_STARTED_DESC');
			$this->arResult['TRANSFORM_URL_TEXT'] = Loc::getMessage('DISK_FILE_TRANSFORM_VIDEO_NOT_STARTED_TRANSFORM');
		}
		else
		{
			$status = $info['status'];
			/** @var \Bitrix\Main\Type\DateTime $time */
			$time = $info['time'];

			if (
				$status >= Transformer\Command::STATUS_SUCCESS ||
				(time() - $time->getTimestamp()) > Transformer\FileTransformer::MAX_EXECUTION_TIME)
			{
				$this->arResult['STATUS'] = 'ERROR';
				$this->arResult['TITLE'] = Loc::getMessage('DISK_FILE_TRANSFORM_VIDEO_ERROR_TITLE');
				$this->arResult['DESC'] = Loc::getMessage('DISK_FILE_TRANSFORM_VIDEO_ERROR_DESC');

				if ((time() - $time->getTimestamp()) > Transformer\FileTransformer::MAX_EXECUTION_TIME)
				{
					$this->arResult['TRANSFORM_URL_TEXT'] = Loc::getMessage('DISK_FILE_TRANSFORM_VIDEO_ERROR_TRANSFORM');
				}
			}
			else
			{
				$this->arResult['STATUS'] = 'PROCESS';
				$this->arResult['TITLE'] = Loc::getMessage('DISK_FILE_TRANSFORM_VIDEO_IN_PROCESS_TITLE');
				$this->arResult['DESC'] = Loc::getMessage('DISK_FILE_TRANSFORM_VIDEO_IN_PROCESS_DESC');
			}
		}

		$this->arResult['DOWNLOAD_LINK'] = $this->getUrlToDownload();
		$this->arResult['RUN_GENERATION_PREVIEW'] = [
			'ACTION' => $this->attachedObject? 'disk.attachedObject.runPreviewGeneration' : 'disk.file.runPreviewGeneration',
			'FILE_ID' => $this->file->getId(),
			'ATTACHED_OBJECT_ID' => $this->attachedObject->getId(),
		];

		$transformerManager->subscribeCurrentUserForTransformation($this->bfileId);
		$this->arResult['PULL_TAG'] = $transformerManager::getPullTag($this->bfileId);

		$this->arResult['MESSAGES'] = Loc::loadLanguageFile(__FILE__);

		$this->includeComponentTemplate();
	}
}