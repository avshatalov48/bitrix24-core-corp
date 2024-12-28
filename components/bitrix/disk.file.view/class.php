<?php
use Bitrix\Disk\Configuration;
use Bitrix\Disk\Document\LocalDocumentController;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Integration\Bitrix24Manager;
use Bitrix\Disk\Internals\DiskComponent;
use Bitrix\Disk\Internals\Engine\Contract\SidePanelWrappable;
use Bitrix\Disk\Internals\ExternalLinkTable;
use Bitrix\Disk\TypeFile;
use Bitrix\Disk\Ui\FileAttributes;
use Bitrix\Disk\Ui\Icon;
use Bitrix\Disk\Uf;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Disk\ProxyType;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

Loc::loadMessages(__FILE__);

class CDiskFileViewComponent extends DiskComponent implements Controllerable, SidePanelWrappable
{
	const ERROR_COULD_NOT_FIND_OBJECT  = 'DISK_FV_22001';
	const ERROR_COULD_NOT_SAVE_FILE    = 'DISK_FV_22002';
	const ERROR_COULD_NOT_FIND_VERSION = 'DISK_FV_22003';

	/** @var \Bitrix\Disk\File */
	protected $file;
	/** @var  array */
	protected $breadcrumbs;
	/** @var  array */
	protected $imageSize = array('width' => 600, 'height' => 800);

	protected $componentId = 'file_view_with_version';

	protected function listActions()
	{
		return array(
			'showBp' => array(
				'method' => array('GET', 'POST'),
				'name' => 'showBp',
				'check_csrf_token' => false,
			),
			'editUserField' => array(
				'method' => array('GET', 'POST'),
				'check_csrf_token' => false,
			),
			'showUserField' => array(
				'method' => array('GET', 'POST'),
				'check_csrf_token' => false,
			),
		);
	}

	protected function processBeforeAction($actionName)
	{
		parent::processBeforeAction($actionName);
		$this->findFile();

		if (!$this->file)
		{
			global $APPLICATION;
			$APPLICATION->includeComponent(
				'bitrix:disk.error.page',
				'',
				[]
			);

			return false;
		}

		$securityContext = $this->storage->getCurrentUserSecurityContext();
		if(!$this->file->canRead($securityContext))
		{
			$this->showAccessDenied();
			return false;
		}
		if($actionName === 'editUserField' && !$this->file->canUpdate($securityContext))
		{
			$this->showAccessDenied();
			return false;
		}

		return true;
	}

	protected function prepareParams()
	{
		parent::prepareParams();

		if (isset($this->arParams['FILE']) && $this->arParams['FILE'] instanceof \Bitrix\Disk\File)
		{
			return $this;
		}

		if(!isset($this->arParams['FILE_ID']))
		{
			throw new \Bitrix\Main\ArgumentException('FILE_ID required');
		}
		$this->arParams['FILE_ID'] = (int)$this->arParams['FILE_ID'];
		if($this->arParams['FILE_ID'] <= 0)
		{
			throw new \Bitrix\Main\ArgumentException('FILE_ID < 0');
		}

		return $this;
	}

	private function getBackUrl(array $breadcrumbs = array())
	{
		$back = $this->request->getQuery('back');
		if($back)
		{
			$back = urldecode($back);
		}
		else
		{
			$back = $this->getUrlManager()->encodeUrn(end($breadcrumbs));
		}

		return $back;
	}

	protected function processActionDefault()
	{
		if ($this->request->getQuery('show') === 'properties')
		{
			$this->setTemplateName('properties');
		}

		$securityContext = $this->storage->getCurrentUserSecurityContext();
		$urlManager = Driver::getInstance()->getUrlManager();

		$this->application->setTitle($this->file->getName());

		$breadcrumbs = $this->getBreadcrumbs();
		$externalLinkData = array(
			'ENABLED' => Configuration::isEnabledExternalLink()
		);
		$externalLink = $this->getExternalLink();
		if($externalLink)
		{
			$externalLinkData['ID'] = $externalLink->getId();
			$externalLinkData['OBJECT_ID'] = $externalLink->getObjectId();
			$externalLinkData['DOWNLOAD_COUNT'] = $externalLink->getDownloadCount();
			$externalLinkData['HAS_PASSWORD'] = $externalLink->hasPassword();
			$externalLinkData['HAS_DEATH_TIME'] = $externalLink->hasDeathTime();
			$externalLinkData['DEATH_TIME_TIMESTAMP'] = $externalLink->hasDeathTime()? $externalLink->getDeathTime()->getTimestamp() : null;
			$externalLinkData['DEATH_TIME'] = $externalLink->hasDeathTime()? $externalLink->getDeathTime()->toString() : null;
			$externalLinkData['LINK'] = Driver::getInstance()->getUrlManager()->getShortUrlExternalLink(array(
				'hash' => $externalLink->getHash(),
				'action' => 'default',
			), true);
		}

		$createdByLink = \CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_USER'], array("user_id" => $this->file->getCreatedBy()));
		$canUpdate = $this->file->canUpdate($securityContext);

		if ($this->arParams['PATH_TO_FILE_VIEW'])
		{
			$viewFile = CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_FILE_VIEW'], array(
				'FILE_ID' => $this->file->getId(),
				'FILE_PATH' => $this->arParams['RELATIVE_PATH'],
			));
		}
		else
		{
			$viewFile = $urlManager->getPathFileDetail($this->file);
		}

		$isEnabledObjectLock = Configuration::isEnabledObjectLock();
		$additionalParams = array('canUpdate' => $canUpdate);

		if($isEnabledObjectLock && $this->file->getLock())
		{
			$additionalParams['lockedBy'] = $this->file->getLock()->getCreatedBy();
		}

		$previewImage = null;
		if($this->file->getPreviewId())
		{
			$previewImage = $urlManager->getUrlForShowPreview($this->file, $this->imageSize);
		}

		$attr = FileAttributes::buildByFileId($this->file->getFileId(), new Uri($urlManager->getUrlForDownloadFile($this->file)))
			->setObjectId($this->file->getId())
			->setTitle($this->file->getName())
			->addAction([
				'type' => 'download',
			])
		;

		if ($canUpdate && \Bitrix\Disk\Document\DocumentHandler::isEditable($this->file->getExtension()))
		{
			$documentName = \CUtil::JSEscape($this->file->getName());
			$items = [];
			foreach ($this->getDocumentHandlersForEditingFile() as $handlerData)
			{
				$items[] = [
					'text' => $handlerData['name'],
					'onclick' => "BX.Disk.Viewer.Actions.runActionEdit({name: '{$documentName}', objectId: {$this->file->getId()}, serviceCode: '{$handlerData['code']}'})",
				];
			}
			$attr->addAction([
				'type' => 'edit',
				'action' => 'BX.Disk.Viewer.Actions.runActionDefaultEdit',
				'params' => [
					'objectId' => $this->file->getId(),
					'name' => $documentName,
				],
				'items' => $items,
			]);
		}

		$this->arResult = array(
			'STORAGE' => $this->storage,
			'USE_IN_ENTITIES' => false,
			'ENTITIES' => array(),
			'SHOW_USER_FIELDS' => false,
			'HISTORY_BLOCKED_BY_FEATURE' => !Bitrix24Manager::isFeatureEnabled('disk_file_history'),
			'USER_FIELDS' => array(),
			'EXTERNAL_LINK' => $externalLinkData,
			'FILE' => array(
				'ID' => $this->file->getId(),
				'IS_DELETED' => $this->file->isDeleted(),
				'IS_IMAGE' => TypeFile::isImage($this->file),
				'IS_VIDEO' => TypeFile::isVideo($this->file),
				'IS_EDITABLE' => \Bitrix\Disk\Document\DocumentHandler::isEditable($this->file->getExtension()),
				'VIEWER' => $this->getViewerHtml(),
				'CREATE_USER' => array(
					'LINK' => $createdByLink,
					'NAME' => $this->file->getCreateUser()->getFormattedName(),
					'WORK_POSITION' => $this->file->getCreateUser()->getWorkPosition(),
					'AVA_HTML' => $this->file->getCreateUser()->renderAvatar(),
				),
				'VIEWER_ATTRIBUTES' => $attr,
				'UPDATE_TIME' => $this->file->getUpdateTime(),
				'DELETE_TIME' => $this->file->getDeleteTime(),
				'ICON_CLASS' => Icon::getIconClassByObject($this->file),
				'NAME' => $this->file->getName(),
				'SIZE' => $this->file->getSize(),
				'IS_LINK' => $this->file->isLink(),
				'LOCK' => array(
					'IS_LOCKED' => false,
					'IS_LOCKED_BY_SELF' => false,
				),
				'FOLDER_LIST_WEBDAV' => rtrim(end($breadcrumbs), '/') . '/' . $this->file->getName(),
				'DOWNLOAD_URL' => $urlManager->getUrlForDownloadFile($this->file),

				'SHOW_PREVIEW_URL' => \Bitrix\Disk\Driver::getInstance()->getUrlManager()->getUrlForShowFile($this->file, array('width' => $this->imageSize['width'], 'height' => $this->imageSize['height'],)),
				'SHOW_FILE_URL' => \Bitrix\Disk\Driver::getInstance()->getUrlManager()->getUrlForShowFile($this->file),
				'SHOW_FILE_ABSOLUTE_URL' => \Bitrix\Disk\Driver::getInstance()->getUrlManager()->getUrlForShowFile($this->file, array(), true),
				'SHOW_PREVIEW_IMAGE_URL' => $previewImage,
			),
			'CAN_UPDATE' => $canUpdate,
			'CAN_DELETE' => $this->file->canDelete($securityContext),
			'CAN_RESTORE' => $this->file->canRestore($securityContext),
			'CAN_SHARE' => $this->file->canShare($securityContext) && Bitrix24Manager::isFeatureEnabled('disk_file_sharing'),
			'CAN_CHANGE_RIGHTS' => $this->file->canChangeRights($securityContext),
			'PATH_TO_FILE_VIEW' => $viewFile,
			'PATH_TO_TRASHCAN_LIST' => $this->file->getStorage()->getProxyType()->getBaseUrlTashcanList(),
			'PATH_TO_FILE_HISTORY' => CComponentEngine::makePathFromTemplate(
				$this->arParams['PATH_TO_FILE_HISTORY'],
				[
					'FILE_ID' => $this->file->getId(),
				]
			)
			//'BREADCRUMBS' => $breadcrumbs,
		);

		if($isEnabledObjectLock && $this->file->getLock())
		{
			$this->arResult['FILE']['LOCK']['CREATED_BY'] = $this->file->getLock()->getCreatedBy();
			$this->arResult['FILE']['LOCK']['IS_LOCKED'] = true;
			$this->arResult['FILE']['LOCK']['IS_LOCKED_BY_SELF'] = $this->getUser()->getId() == $this->file->getLock()->getCreatedBy();
		}

		$attachedObjects = $this->file->getAttachedObjects();
		if($attachedObjects)
		{
			$userId = $this->getUser()->getId();
			$this->arResult['USE_IN_ENTITIES'] = true;
			Uf\Connector::setPathToUser($this->arParams['PATH_TO_USER']);
			Uf\Connector::setPathToGroup($this->arParams['PATH_TO_GROUP']);
			foreach($attachedObjects as $attachedObject)
			{
				try
				{
					$connector = $attachedObject->getConnector();
					if (!$connector->canRead($userId))
					{
						continue;
					}
					$dataToShow = $connector->tryToGetDataToShowForUser($userId);
					if ($dataToShow)
					{
						$this->arResult['ENTITIES'][] = $dataToShow;
					}
				}
				catch(\Bitrix\Main\SystemException $exception)
				{
				}
			}
			unset($attachedObject);
		}

		$this->fillUserFieldForFile();

		$this->arParams['STATUS_BIZPROC'] = $this->storage->isEnabledBizProc() && Loader::includeModule("bizproc");
		if($this->arParams['STATUS_BIZPROC'])
		{
			$documentData = array(
				'DISK' => array(
					'DOCUMENT_TYPE' => \Bitrix\Disk\BizProcDocument::generateDocumentComplexType($this->storage->getId()),
					'DOCUMENT_ID' => \Bitrix\Disk\BizProcDocument::getDocumentComplexId($this->file->getId()),
				),
				'WEBDAV' => array(
					'DOCUMENT_TYPE' => \Bitrix\Disk\BizProcDocumentCompatible::generateDocumentComplexType($this->storage->getId()),
					'DOCUMENT_ID' => \Bitrix\Disk\BizProcDocumentCompatible::getDocumentComplexId($this->file->getId()),
				),
			);
			$webdavFileId = $this->file->getXmlId();
			if(!empty($webdavFileId))
			{
				if (Loader::includeModule("iblock"))
				{
					if($this->storage->getProxyType() instanceof ProxyType\Group)
					{
						$iblock = CIBlockElement::getList(array(), array("ID" => $webdavFileId, 'SHOW_NEW' => 'Y'), false, false, array('ID', 'IBLOCK_ID'))->fetch();
						$entity = 'CIBlockDocumentWebdavSocnet';
					}
					else
					{
						$iblock = CIBlockElement::getList(array(), array("ID" => $webdavFileId, 'SHOW_NEW' => 'Y'), false, false, array('ID', 'IBLOCK_ID'))->fetch();
						$entity = 'CIBlockDocumentWebdav';
					}
					if(!empty($iblock))
					{
						$documentData['OLD_FILE'] = array(
							'DOCUMENT_TYPE' => array('webdav', $entity, "iblock_".$iblock['IBLOCK_ID']),
							'DOCUMENT_ID' => array('webdav', $entity, $iblock['ID']),
						);
					}
				}
			}
			$this->getAutoloadTemplateBizProc($documentData);
			if ($this->request->isPost() && intval($this->request->getPost('bizproc_index')) > 0)
			{
				$this->showBizProc($documentData);
			}
		}

		$this->includeComponentTemplate($this->arParams['SUBTEMPLATE_NAME'] ?? '');
	}

	protected function getViewerHtml()
	{
		$urlManager = $this->getUrlManager();
		$viewerHtml = $previewPath = $transformInfoUrl = null;
		if (TypeFile::isVideo($this->file) && $this->file->getView()->isHtmlAvailable())
		{
			$viewPath = [
				$urlManager->getUrlForShowView($this->file),
				$urlManager->getUrlForShowFile($this->file)
			];
			if ($this->file->getPreviewId())
			{
				$previewPath = $urlManager->getUrlForShowPreview($this->file);
			}
			if (!$this->file->getViewId() && $this->file->getView()->isTransformationAllowed())
			{
				$transformInfoUrl = $urlManager->getUrlForShowTransformInfo($this->file, array('noError' => 'y'));
			}

			//hack, cratch! we have to include c_disk.js before other scripts which uses BX.Disk.*
			\Bitrix\Main\UI\Extension::load('disk.video');
			$viewerHtml = $this->file->getView()->render(
				[
					'PATH' => $viewPath,
					'AUTOSTART' => 'N',
					'AUTOSTART_ON_SCROLL' => 'N',
					'IFRAME' => 'N',
					'LAZYLOAD' => 'Y',
					'PREVIEW' => $previewPath,
					'WIDTH' => 560,
					'HEIGHT' => 480,
				]
			);
		}

		return $viewerHtml;
	}

	protected function processActionEditUserField()
	{
		global $APPLICATION;

		$APPLICATION->IncludeComponent(
			'bitrix:disk.sidepanel.wrapper',
			"",
			array(
				'POPUP_COMPONENT_NAME' => 'bitrix:disk.file.view',
				'POPUP_COMPONENT_TEMPLATE_NAME' => '',
				'POPUP_COMPONENT_PARAMS' => $this->arParams + ['action' => 'default', 'SUBTEMPLATE_NAME' => 'uf_edit'],
			)
		);
	}

	protected function processActionShowUserField()
	{
		global $APPLICATION;

		$APPLICATION->IncludeComponent(
			'bitrix:disk.sidepanel.wrapper',
			"",
			array(
				'POPUP_COMPONENT_NAME' => 'bitrix:disk.file.view',
				'POPUP_COMPONENT_TEMPLATE_NAME' => '',
				'POPUP_COMPONENT_PARAMS' => $this->arParams + ['action' => 'default', 'SUBTEMPLATE_NAME' => 'uf_show'],
			)
		);
	}

	protected function processActionShowBp()
	{
		$this->application->setTitle(htmlspecialcharsbx($this->storage->getProxyType()->getTitleForCurrentUser()));

		$viewFile = CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_FILE_VIEW'], array(
			'FILE_ID' => $this->file->getId(),
			'FILE_PATH' => $this->arParams['RELATIVE_PATH'],
		));

		$urlStartBizproc = \CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_DISK_START_BIZPROC'],array("ELEMENT_ID" => $this->file->getId()));
		$urlStartBizproc .= "?back_url=".urlencode($this->application->getCurPage());
		$urlStartBizproc .= (mb_strpos($urlStartBizproc, "?") === false ? "?" : "&").'workflow_template_id=0&'.bitrix_sessid_get();

		$this->arResult = array(
			'STORAGE' => $this->storage,
			'FILE' => array(
				'ID' => $this->file->getId(),
				'NAME' => $this->file->getName(),
			),
			'BP_ITEMS_FOR_START' => $this->getTemplateBizProcItemsForStart(),
			'PATH_TO_FILE_VIEW' => $viewFile,
			'PATH_TO_START_BIZPROC' => $urlStartBizproc,
			'STORAGE_ID' => 'STORAGE_'.$this->storage->getId(),
		);

		$this->arParams['STATUS_BIZPROC'] = $this->storage->isEnabledBizProc() && Loader::includeModule("bizproc");

		if($this->arParams['STATUS_BIZPROC'])
		{
			$documentData = array(
				'DISK' => array(
					'DOCUMENT_TYPE' => \Bitrix\Disk\BizProcDocument::generateDocumentComplexType($this->storage->getId()),
					'DOCUMENT_ID' => \Bitrix\Disk\BizProcDocument::getDocumentComplexId($this->file->getId()),
				),
				'WEBDAV' => array(
					'DOCUMENT_TYPE' => \Bitrix\Disk\BizProcDocumentCompatible::generateDocumentComplexType($this->storage->getId()),
					'DOCUMENT_ID' => \Bitrix\Disk\BizProcDocumentCompatible::getDocumentComplexId($this->file->getId()),
				),
			);
			$webdavFileId = $this->file->getXmlId();
			if(!empty($webdavFileId))
			{
				if (Loader::includeModule("iblock"))
				{
					if($this->storage->getProxyType() instanceof ProxyType\Group)
					{
						$iblock = CIBlockElement::getList(array(), array("ID" => $webdavFileId, 'SHOW_NEW' => 'Y'), false, false, array('ID', 'IBLOCK_ID'))->fetch();
						$entity = 'CIBlockDocumentWebdavSocnet';
					}
					else
					{
						$iblock = CIBlockElement::getList(array(), array("ID" => $webdavFileId, 'SHOW_NEW' => 'Y'), false, false, array('ID', 'IBLOCK_ID'))->fetch();
						$entity = 'CIBlockDocumentWebdav';
					}
					if(!empty($iblock))
					{
						$documentData['OLD_FILE'] = array(
							'DOCUMENT_TYPE' => array('webdav', $entity, "iblock_".$iblock['IBLOCK_ID']),
							'DOCUMENT_ID' => array('webdav', $entity, $iblock['ID']),
						);
					}
				}
			}
			$this->showBizProc($documentData);
		}


		$this->includeComponentTemplate('bp');
	}

	protected function findFile()
	{
		if (isset($this->arParams['FILE']) && $this->arParams['FILE'] instanceof \Bitrix\Disk\File)
		{
			$this->file = $this->arParams['FILE'];
		}
		else
		{
			$this->file = \Bitrix\Disk\File::loadById($this->arParams['FILE_ID'], array('REAL_OBJECT', 'CREATE_USER'));
		}

		return $this;
	}

	/**
	 * @return \Bitrix\Disk\ExternalLink|null
	 */
	protected function getExternalLink()
	{
		$extLinks = $this->file->getExternalLinks(array(
			'filter' => array(
				'OBJECT_ID' => $this->file->getId(),
				'CREATED_BY' => $this->getUser()->getId(),
				'TYPE' => ExternalLinkTable::TYPE_MANUAL,
				'IS_EXPIRED' => false,
			),
			'limit' => 1,
		));

		return array_pop($extLinks);
	}

	protected function getBreadcrumbs()
	{
		$crumbs = array();

		$parts = explode('/', trim($this->arParams['RELATIVE_PATH'], '/'));
		array_pop($parts);//last element is file.
		if(empty($parts))
		{
			$parts[] = '';
		}
		foreach ($parts as $i => $part)
		{
			$crumbs[] = CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_FOLDER_LIST'], array(
					'PATH' => implode('/', (array_slice($parts, 0, $i + 1))),
				));
		}
		unset($i, $part);

		return $crumbs;
	}

	protected function showBizProc($documentData)
	{
		$this->arResult['BIZPROC_PERMISSION'] = array();
		$this->arResult['BIZPROC_PERMISSION']['START'] = CBPDocument::canUserOperateDocument(
			CBPCanUserOperateOperation::StartWorkflow,
			$this->getUser()->getId(),
			$documentData['DISK']['DOCUMENT_ID']
		);
		$this->arResult['BIZPROC_PERMISSION']['VIEW'] = CBPDocument::canUserOperateDocument(
			CBPCanUserOperateOperation::ViewWorkflow,
			$this->getUser()->getId(),
			$documentData['DISK']['DOCUMENT_ID']
		);
		$this->arResult['BIZPROC_PERMISSION']['STOP'] = $this->arResult['BIZPROC_PERMISSION']['START'];
		$this->arResult['BIZPROC_PERMISSION']['DROP'] = CBPDocument::canUserOperateDocument(
			CBPCanUserOperateOperation::CreateWorkflow,
			$this->getUser()->getId(),
			$documentData['DISK']['DOCUMENT_ID']
		);

		foreach($documentData as $nameModuleId => $data)
		{
			$temporary[$nameModuleId] = CBPDocument::getDocumentStates($data['DOCUMENT_TYPE'], $data['DOCUMENT_ID']);
		}
		if(isset($temporary['OLD_FILE']))
		{
			$allBizProcArray = array_merge($temporary['DISK'], $temporary['WEBDAV'], $temporary['OLD_FILE']);
		}
		else
		{
			$allBizProcArray = array_merge($temporary['DISK'], $temporary['WEBDAV']);
		}
		if(!empty($allBizProcArray))
		{
			$userGroup = $this->getUser()->getUserGroupArray();
			$userGroup[]= 'author';
			if ($this->request->isPost() && intval($this->request->getPost('bizproc_index')) > 0)
			{
				$bizProcWorkflowId = array();
				$bizprocIndex = intval($this->request->getPost('bizproc_index'));
				for ($i = 1; $i <= $bizprocIndex; $i++)
				{
					$bpId = trim($this->request->getPost("bizproc_id_".$i));
					$bpTemplateId = intval($this->request->getPost("bizproc_template_id_".$i));
					$bpEvent = trim($this->request->getPost("bizproc_event_".$i));
					if ($bpId <> '')
					{
						if (!array_key_exists($bpId, $allBizProcArray))
							continue;
					}
					else
					{
						if (!array_key_exists($bpTemplateId, $allBizProcArray))
							continue;
						$bpId = $bizProcWorkflowId[$bpTemplateId];
					}
					if ($bpEvent <> '')
					{
						$errors = array();
						CBPDocument::sendExternalEvent(
							$bpId,
							$bpEvent,
							array("Groups" => $userGroup, "User" => $this->getUser()->getId()),
							$errors
						);
					}
					else
					{
						$errors = array();
						foreach($allBizProcArray as $idBizProc => $bizProcArray)
						{
							if($idBizProc == $bpId)
							{
								CBPDocument::TerminateWorkflow($bpId,$bizProcArray['DOCUMENT_ID'],$errors);
							}
						}
					}
					if (!empty($errors))
					{
						foreach ($errors as $error)
						{
							$this->arResult['ERROR_MESSAGE'] = $error['message'];
						}
					}
					else
					{
						LocalRedirect($this->arResult['PATH_TO_FILE_VIEW']."#tab-bp");
					}
				}
			}
			$this->arResult['BIZPROC_LIST'] = array();
			$count = 1;
			foreach($allBizProcArray as $idBizProc => $bizProcArray)
			{
				if(intval($bizProcArray["WORKFLOW_STATUS"]) < 0 || $idBizProc <= 0)
				{
					continue;
				}
				else if(!CBPDocument::canUserOperateDocument(
					CBPCanUserOperateOperation::ViewWorkflow,
					$this->getUser()->getId(),
					$documentData['DISK']['DOCUMENT_ID'],
					array(
						"DocumentStates" => $bizProcArray,
						"WorkflowId" => $bizProcArray["ID"] > 0 ? $bizProcArray["ID"] : $bizProcArray["TEMPLATE_ID"]
					)))
				{
					continue;
				}

				$groups = CBPDocument::getAllowableUserGroups($documentData['DISK']['DOCUMENT_TYPE']);
				foreach ($groups as $key => $val)
					$groups[mb_strtolower($key)] = $val;

				$users = array();
				$dmpWorkflow = CBPTrackingService::getList(
					array("ID" => "DESC"),
					array("WORKFLOW_ID" => $idBizProc, "TYPE" => array(
						CBPTrackingType::Report,
						CBPTrackingType::Custom,
						CBPTrackingType::FaultActivity,
						CBPTrackingType::Error
					)),
					false,
					array("nTopCount" => 5),
					array("ID", "TYPE", "MODIFIED", "ACTION_NOTE", "ACTION_TITLE", "ACTION_NAME", "EXECUTION_STATUS", "EXECUTION_RESULT")
				);

				while ($track = $dmpWorkflow->getNext())
				{
					$messageTemplate = "";
					switch ($track["TYPE"])
					{
						case 1:
							$messageTemplate = Loc::getMessage("DISK_FILE_VIEW_BPABL_TYPE_1");
							break;
						case 2:
							$messageTemplate = Loc::getMessage("DISK_FILE_VIEW_BPABL_TYPE_2");
							break;
						case 3:
							$messageTemplate = Loc::getMessage("DISK_FILE_VIEW_BPABL_TYPE_3");
							break;
						case 4:
							$messageTemplate = Loc::getMessage("DISK_FILE_VIEW_BPABL_TYPE_4");
							break;
						case 5:
							$messageTemplate = Loc::getMessage("DISK_FILE_VIEW_BPABL_TYPE_5");
							break;
						default:
							$messageTemplate = Loc::getMessage("DISK_FILE_VIEW_BPABL_TYPE_6");
					}

					$name = ($track["ACTION_TITLE"] <> '' ? $track["ACTION_TITLE"] : $track["ACTION_NAME"]);
					switch ($track["EXECUTION_STATUS"])
					{
						case CBPActivityExecutionStatus::Initialized:
							$status = Loc::getMessage("DISK_FILE_VIEW_BPABL_STATUS_1");
							break;
						case CBPActivityExecutionStatus::Executing:
							$status = Loc::getMessage("DISK_FILE_VIEW_BPABL_STATUS_2");
							break;
						case CBPActivityExecutionStatus::Canceling:
							$status = Loc::getMessage("DISK_FILE_VIEW_BPABL_STATUS_3");
							break;
						case CBPActivityExecutionStatus::Closed:
							$status = Loc::getMessage("DISK_FILE_VIEW_BPABL_STATUS_4");
							break;
						case CBPActivityExecutionStatus::Faulting:
							$status = Loc::getMessage("DISK_FILE_VIEW_BPABL_STATUS_5");
							break;
						default:
							$status = Loc::getMessage("DISK_FILE_VIEW_BPABL_STATUS_6");
					}
					switch ($track["EXECUTION_RESULT"])
					{
						case CBPActivityExecutionResult::None:
							$result = Loc::getMessage("DISK_FILE_VIEW_BPABL_RES_1");
							break;
						case CBPActivityExecutionResult::Succeeded:
							$result = Loc::getMessage("DISK_FILE_VIEW_BPABL_RES_2");
							break;
						case CBPActivityExecutionResult::Canceled:
							$result = Loc::getMessage("DISK_FILE_VIEW_BPABL_RES_3");
							break;
						case CBPActivityExecutionResult::Faulted:
							$result = Loc::getMessage("DISK_FILE_VIEW_BPABL_RES_4");
							break;
						case CBPActivityExecutionResult::Uninitialized:
							$result = Loc::getMessage("DISK_FILE_VIEW_BPABL_RES_5");
							break;
						default:
							$result = Loc::getMessage("DISK_FILE_VIEW_BPABL_RES_6");
					}

					$note = (($track["ACTION_NOTE"] <> '') ? ": ".$track["ACTION_NOTE"] : "");
					$pattern = array("#ACTIVITY#", "#STATUS#", "#RESULT#", "#NOTE#");
					$replaceArray = array($name, $status, $result, $note);
					if (!empty($track["ACTION_NAME"]) && !empty($track["ACTION_TITLE"]))
					{
						$pattern[] = $track["ACTION_NAME"];
						$replaceArray[] = $track["ACTION_TITLE"];
					}
					$messageTemplate = str_replace(
						$pattern,
						$replaceArray,
						$messageTemplate);

					if (preg_match_all("/(?<=\{\=user\:)([^\}]+)(?=\})/is", $messageTemplate, $arMatches))
					{
						$pattern = array(); $replacement = array();
						foreach ($arMatches[0] as $user)
						{
							$user = preg_quote($user);
							if (in_array("/\{\=user\:".$user."\}/is", $pattern))
								continue;
							$replace = "";
							if (array_key_exists(mb_strtolower($user), $groups))
								$replace = $groups[mb_strtolower($user)];
							elseif (array_key_exists(mb_strtoupper($user), $groups))
								$replace = $groups[mb_strtoupper($user)];
							else
							{
								$id = intval(str_replace("user_", "", $user));
								if (!array_key_exists($id, $users)):
									$dbRes = \CUser::getByID($id);
									$users[$id] = false;
									if ($dbRes && $arUser = $dbRes->getNext()):
										$name = CUser::formatName(str_replace(",","", COption::getOptionString("bizproc", "name_template", CSite::getNameFormat(false), SITE_ID)), $arUser, true, false);
										$arUser["FULL_NAME"] = (empty($name) ? $arUser["LOGIN"] : $name);
										$users[$id] = $arUser;
									endif;
								endif;
								if (!empty($users[$id]))
									$replace = "<a href=\"".
										\CComponentEngine::makePathFromTemplate('/company/personal/user/#USER_ID#/', array("USER_ID" => $id))."\">".
										$users[$id]["FULL_NAME"]."</a>";
							}

							if (!empty($replace))
							{
								$pattern[] = "/\{\=user\:".$user."\}/is";
								$pattern[] = "/\{\=user\:user\_".$user."\}/is";
								$replacement[] = $replace;
								$replacement[] = $replace;
							}
						}
						$messageTemplate = preg_replace($pattern, $replacement, $messageTemplate);
					}

					$this->arResult['BIZPROC_LIST'][$count]['DUMP_WORKFLOW'][] = $messageTemplate;
				}

				$tasks = CBPDocument::getUserTasksForWorkflow($this->getUser()->getId(), $idBizProc);
				$events = CBPDocument::getAllowableEvents($this->getUser()->getId(), $userGroup, $bizProcArray);
				if(!empty($tasks))
				{
					foreach($tasks as $task)
					{
						$urlTaskBizproc = \CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_DISK_TASK'],array("ID" => $task['ID']));
						$urlTaskBizproc .= "?back_url=".urlencode($this->application->getCurPage())."&file=".$this->file->getName();
						$this->arResult['BIZPROC_LIST'][$count]['TASK']['URL'] = $urlTaskBizproc;
						$this->arResult['BIZPROC_LIST'][$count]['TASK']['TASK_ID'] = $task['ID'];
						$this->arResult['BIZPROC_LIST'][$count]['TASK']['TASK_NAME'] = $task['NAME'];
					}

				}
				$this->arResult['BIZPROC_LIST'][$count]['ID'] = $bizProcArray['ID'];
				$this->arResult['BIZPROC_LIST'][$count]['WORKFLOW_STATUS'] = $bizProcArray["WORKFLOW_STATUS"];
				$this->arResult['BIZPROC_LIST'][$count]['TEMPLATE_ID'] = $bizProcArray['TEMPLATE_ID'];
				$this->arResult['BIZPROC_LIST'][$count]['TEMPLATE_NAME'] = $bizProcArray['TEMPLATE_NAME'];
				$this->arResult['BIZPROC_LIST'][$count]['STATE_MODIFIED'] = $bizProcArray['STATE_MODIFIED'];
				$this->arResult['BIZPROC_LIST'][$count]['STATE_TITLE'] = $bizProcArray['STATE_TITLE'];
				$this->arResult['BIZPROC_LIST'][$count]['STATE_NAME'] = $bizProcArray['STATE_NAME'];
				$this->arResult['BIZPROC_LIST'][$count]['EVENTS'] = $events;
				$count++;
			}
		}
	}

	protected function getAutoloadTemplateBizProc($documentData)
	{
		$this->arResult['WORKFLOW_TEMPLATES'] = array();
		$this->arResult['BIZPROC_PARAMETERS'] = false;
		foreach($documentData as $nameModule => $data)
		{
			$workflowTemplateObject = CBPWorkflowTemplateLoader::getList(
				array(),
				array(
					"DOCUMENT_TYPE" => $data["DOCUMENT_TYPE"],
					"AUTO_EXECUTE" => CBPDocumentEventType::Edit,
					"ACTIVE" => "Y",
					"!PARAMETERS" => null
				),
				false,
				false,
				array("ID", "NAME", "DESCRIPTION", "PARAMETERS")
			);
			while ($workflowTemplate = $workflowTemplateObject->getNext())
			{
				if(!empty($workflowTemplate['PARAMETERS']))
				{
					$this->arResult['BIZPROC_PARAMETERS'] = true;
				}
				$this->arResult['WORKFLOW_TEMPLATES'][$workflowTemplate['ID']]['ID'] = $workflowTemplate['ID'];
				$this->arResult['WORKFLOW_TEMPLATES'][$workflowTemplate['ID']]['NAME'] = $workflowTemplate['NAME'];
				$this->arResult['WORKFLOW_TEMPLATES'][$workflowTemplate['ID']]['PARAMETERS'] = $workflowTemplate['PARAMETERS'];
			}
		}
	}

	private function getTemplateBizProcItemsForStart()
	{
		$documentData = [
			'DISK'   => [
				'DOCUMENT_TYPE' => \Bitrix\Disk\BizProcDocument::generateDocumentComplexType($this->storage->getId()),
				'DOCUMENT_ID'   => \Bitrix\Disk\BizProcDocument::getDocumentComplexId($this->file->getId()),
			],
			'WEBDAV' => [
				'DOCUMENT_TYPE' => \Bitrix\Disk\BizProcDocumentCompatible::generateDocumentComplexType($this->storage->getId()),
				'DOCUMENT_ID'   => \Bitrix\Disk\BizProcDocumentCompatible::getDocumentComplexId($this->file->getId()),
			],
		];

		$templates = [];
		foreach($documentData as $nameModule => $data)
		{
			$res = CBPWorkflowTemplateLoader::getList(
				array(),
				array('DOCUMENT_TYPE' => $data['DOCUMENT_TYPE']),
				false,
				false,
				array("ID", "NAME", 'DOCUMENT_TYPE', 'ENTITY', 'PARAMETERS')
			);
			while ($workflowTemplate = $res->getNext())
			{
				if($nameModule == 'DISK')
				{
					$templateName = $workflowTemplate["NAME"];
				}
				else
				{
					$templateName = $workflowTemplate["NAME"]." ".Loc::getMessage('DISK_FOLDER_LIST_ACT_BIZPROC_OLD_TEMPLATE');
				}
				$templates[$workflowTemplate["ID"]] = $workflowTemplate;
				$templates[$workflowTemplate["ID"]]['NAME'] = $templateName;
			}
		}

		$listBpTemplates = [];
		foreach ($templates as $id => $template)
		{
			$params = \Bitrix\Main\Web\Json::encode(
				array(
					'moduleId' => $template['DOCUMENT_TYPE'][0],
					'entity' => $template['DOCUMENT_TYPE'][1],
					'documentType' => $template['DOCUMENT_TYPE'][2],
					'documentId' => $this->file->getId(),
					'templateId' => $id,
					'templateName' => $template['NAME'],
					'hasParameters' => !empty($template['PARAMETERS']),
				)
			);

			$listBpTemplates[] = array(
				"text" => $template['NAME'],
				"onclick" => "
					BX.PopupMenu.destroy('BizprocList-run');
					BX.Bizproc.Starter.singleStart({$params}, function(){ 
						 document.location.reload();
					});
				",
			);
		}

		return $listBpTemplates;
	}


	protected function fillUserFieldForFile()
	{
		$userFieldsObject = \Bitrix\Disk\Driver::getInstance()->getUserFieldManager()->getFieldsForObject($this->file);
		if($userFieldsObject)
		{
			$this->arResult['SHOW_USER_FIELDS'] = true;
			$this->arResult['USER_FIELDS'] = $userFieldsObject;
		}
	}

	public function configureActions()
	{
		return [];
	}

	public function showUfSidebarAction(\Bitrix\Disk\File $file)
	{
		$storage = $file->getStorage();
		if (!$storage)
		{
			$this->errorCollection[] = new \Bitrix\Disk\Internals\Error\Error('There is no storage');
			return;
		}

		$securityContext = $storage->getProxyType()->getSecurityContextByCurrentUser();
		if (!$file->canUpdate($securityContext))
		{
			$this->errorCollection[] = new \Bitrix\Disk\Internals\Error\Error('Could not update file');
			return;
		}
		$this->file = $file;

		$this->arResult = array(
			'FILE' => array(
				'ID' => $file->getId(),
			),
			'CAN_UPDATE' => true,
		);
		$this->fillUserFieldForFile();

		ob_start();
		$this->includeComponentTemplate('uf_sidebar');
		$html = ob_get_clean();

		return [
			'html' => $html,
		];
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
}
