<?php
use Bitrix\Disk\Configuration;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\BaseComponent;
use Bitrix\Disk\Document\DocumentHandler;
use Bitrix\Disk\Uf\LocalDocumentController;
use Bitrix\Disk\Ui\FileAttributes;
use Bitrix\Main\Localization\Loc;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CDiskUfVersionComponent extends BaseComponent
{
	protected function prepareParams()
	{
		if(!empty($this->arParams['DISABLE_LOCAL_EDIT']))
		{
			$this->arParams['DISABLE_LOCAL_EDIT'] = true;
		}
		else
		{
			$this->arParams['DISABLE_LOCAL_EDIT'] = null;
		}

		return $this;
	}

	protected function processActionDefault()
	{
		$this->arResult = array(
			'ONLY_HEAD_VERSION' => !Configuration::isEnabledKeepVersion(),
			'VERSIONS' => $this->loadData(),
			'UID' => randString(5),
		);

		$this->includeComponentTemplate();
	}

	private function loadData()
	{
		if(empty($this->arParams['PARAMS']['arUserField']))
		{
			return array();
		}
		if (!$this->getUser())
		{
			return [];
		}

		$userId = $this->getUser()->getId();
		$values = $this->arParams['PARAMS']['arUserField']['VALUE'];
		if(!is_array($this->arParams['PARAMS']['arUserField']['VALUE']))
		{
			$values = array($values);
		}
		$urlManager = \Bitrix\Disk\Driver::getInstance()->getUrlManager();
		$isEnabledObjectLock = Configuration::isEnabledObjectLock();

		$versions = array();
		foreach($values as $value)
		{
			$attachedObjectId = (int)$value;
			if($attachedObjectId <= 0)
			{
				continue;
			}
			/** @var \Bitrix\Disk\AttachedObject $attachedModel */
			$attachedModel = \Bitrix\Disk\AttachedObject::loadById($attachedObjectId, array('VERSION.OBJECT'));
			if(!$attachedModel)
			{
				continue;
			}
			$version = $attachedModel->getVersion();
			if(!$version || !$version->getObject())
			{
				continue;
			}
			$extension = $version->getExtension();

			$versionData = array(
				'ID' => $attachedModel->getId(),
				'NAME' => $version->getName(),
				'CONVERT_EXTENSION' => DocumentHandler::isNeedConvertExtension($extension),
				'EDITABLE' => DocumentHandler::isEditable($extension),
				'CAN_UPDATE' => $attachedModel->canUpdate($userId),
				'FROM_EXTERNAL_SYSTEM' => $version->getObject()->getContentProvider() && $version->getObject()->getCreatedBy() == $userId,
				'EXTENSION' => $extension,
				'SIZE' => \CFile::formatSize($version->getSize()),
				'HISTORY_URL' => $urlManager->getUrlUfController('history', array('attachedId' => $attachedModel->getId())),
				'DOWNLOAD_URL' => $urlManager->getUrlUfController('download', array('attachedId' => $attachedModel->getId())),
				'COPY_TO_ME_URL' => $urlManager->getUrlUfController('copyTome', array('attachedId' => $attachedModel->getId())),
				'VIEW_URL' => $urlManager->getUrlToShowAttachedFileByService($attachedModel->getId(), 'gvdrive'),
				'EDIT_URL' => $urlManager->getUrlToStartEditUfFileByService($attachedModel->getId(), 'gdrive'),
				'GLOBAL_CONTENT_VERSION' => $version->getGlobalContentVersion(),
				'CREATED_BY' => null,
				'IS_LOCKED' => null,
				'IS_LOCKED_BY_SELF' => null,
			);

			if($isEnabledObjectLock && $version->getObject()->getLock())
			{
				$objectLock = $version->getObject()->getLock();
				$versionData['CREATED_BY'] = $objectLock->getCreatedBy();
				$versionData['IS_LOCKED'] = true;
				$versionData['IS_LOCKED_BY_SELF'] = $this->getUser()->getId() == $objectLock->getCreatedBy();
			}

			$sourceUri = new \Bitrix\Main\Web\Uri($urlManager->getUrlUfController('download', array('attachedId' => $attachedModel->getId())));
			$attr = FileAttributes::buildByFileId($attachedModel->getFileId(), $sourceUri)
				->setObjectId($attachedModel->getObjectId())
				->setAttachedObjectId($attachedModel->getId())
				->setVersionId($attachedModel->getVersionId())
				->setTitle($version->getName())
				->addAction([
					'type' => 'download',
				])
				->addAction([
					'type' => 'copyToMe',
					'text' => Loc::getMessage('DISK_UF_VERSION_ACTION_SAVE_TO_OWN_FILES'),
					'action' => 'BX.Disk.Viewer.Actions.runActionCopyToMe',
					'params' => [
						'attachedObjectId' => $attachedModel->getId(),
					],
					'extension' => 'disk.viewer.actions',
					'buttonIconClass' => 'ui-btn-icon-cloud',
				])
			;

			if ($versionData['CAN_UPDATE'] && $versionData['EDITABLE'])
			{
				$documentName = \CUtil::JSEscape($version->getName());
				$items = [];
				foreach ($this->getDocumentHandlersForEditingFile() as $handlerData)
				{
					$items[] = [
						'text' => $handlerData['name'],
						'onclick' => "BX.Disk.Viewer.Actions.runActionEdit({name: '{$documentName}', attachedObjectId: {$attachedModel->getId()}, serviceCode: '{$handlerData['code']}'})",
					];
				}
				$attr->addAction([
					'type' => 'edit',
					'buttonIconClass' => ' ',
					'action' => 'BX.Disk.Viewer.Actions.runActionDefaultEdit',
					'params' => [
						'attachedObjectId' => $attachedModel->getId(),
						'name' => $documentName,
						'dependsOnService' => $items? null : LocalDocumentController::getCode(),
					],
					'items' => $items,
				]);

			}
			$versionData['ATTRIBUTES_FOR_VIEWER'] = $attr;


			$versions[] = $versionData;
		}
		unset($value);

		return $versions;
	}

	/**
	 * @return \Bitrix\Disk\Document\DocumentHandler[]
	 */
	private function listCloudHandlersForCreatingFile()
	{
		if (!\Bitrix\Disk\Configuration::canCreateFileByCloud())
		{
			return array();
		}

		$list = array();
		$documentHandlersManager = Driver::getInstance()->getDocumentHandlersManager();
		foreach ($documentHandlersManager->getHandlers() as $handler)
		{
			if ($handler instanceof \Bitrix\Disk\Document\Contract\FileCreatable)
			{
				$list[] = $handler;
			}
		}

		return $list;
	}

	private function getDocumentHandlersForEditingFile()
	{
		$handlers = [];
		foreach ($this->listCloudHandlersForCreatingFile() as $handler)
		{
			$handlers[] = [
				'code' => $handler::getCode(),
				'name' => $handler::getName(),
			];
		}

		return array_merge($handlers, [[
			'code' => LocalDocumentController::getCode(),
			'name' => LocalDocumentController::getName(),
		]]);
	}
}