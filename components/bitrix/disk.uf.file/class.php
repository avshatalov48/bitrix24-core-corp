<?php
use Bitrix\Disk\Configuration;
use Bitrix\Disk\Controller\Integration\Flipchart;
use Bitrix\Disk\Document\GoogleHandler;
use Bitrix\Disk\Document\LocalDocumentController;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Internals\BaseComponent;
use Bitrix\Disk\Document\DocumentHandler;
use Bitrix\Disk\TypeFile;
use Bitrix\Disk\Uf\FileUserType;
use Bitrix\Disk\Ui\FileAttributes;
use Bitrix\Disk\UrlManager;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

\Bitrix\Main\Loader::includeModule('disk');

class CDiskUfFileComponent extends BaseComponent implements \Bitrix\Main\Engine\Contract\Controllerable
{
	const VIEW_TYPE_WEB			= '';
	const VIEW_TYPE_WEB_GRID	= 'grid';
	const VIEW_TYPE_MOBILE		= 'mobile';
	const VIEW_TYPE_MOBILE_GRID	= 'mobile_grid';

	protected $editMode = false;

	public function configureActions()
	{
		return [];
	}

	protected function getViewTypesList()
	{
		return [
			self::VIEW_TYPE_WEB,
			self::VIEW_TYPE_WEB_GRID,
			self::VIEW_TYPE_MOBILE,
			self::VIEW_TYPE_MOBILE_GRID,
		];
	}

	protected function getGridViewTypesList()
	{
		return [
			self::VIEW_TYPE_WEB_GRID,
			self::VIEW_TYPE_MOBILE_GRID,
		];
	}

	protected function prepareParams()
	{
		if (!isset($this->arParams['INLINE']))
		{
			$this->arParams['INLINE'] = 'N';
		}

		if(($this->arParams['EDIT'] ?? null) === 'Y')
		{
			$this->editMode = true;
		}

		if(!empty($this->arParams['DISABLE_CREATING_FILE_BY_CLOUD']))
		{
			$this->arParams['DISABLE_CREATING_FILE_BY_CLOUD'] = true;
		}
		else
		{
			$this->arParams['DISABLE_CREATING_FILE_BY_CLOUD'] = null;
		}

		if(!empty($this->arParams['DISABLE_LOCAL_EDIT']))
		{
			$this->arParams['DISABLE_LOCAL_EDIT'] = true;
		}
		else
		{
			$this->arParams['DISABLE_LOCAL_EDIT'] = null;
		}

		if(isset($this->arParams['ENABLE_AUTO_BINDING_VIEWER']))
		{
			$this->arParams['ENABLE_AUTO_BINDING_VIEWER'] = (bool)$this->arParams['ENABLE_AUTO_BINDING_VIEWER'];
		}
		else
		{
			$this->arParams['ENABLE_AUTO_BINDING_VIEWER'] = null;
		}

		$this->arParams['USE_TOGGLE_VIEW'] = (isset($this->arParams['USE_TOGGLE_VIEW']) && ($this->arParams['USE_TOGGLE_VIEW'] == 'Y' || $this->arParams['USE_TOGGLE_VIEW'] === true));
		if (isset($this->arParams['PARAMS']['USE_TOGGLE_VIEW']))
		{
			$this->arParams['PARAMS']['USE_TOGGLE_VIEW'] = $this->arParams['USE_TOGGLE_VIEW'];
		}

		return $this;
	}

	protected function getComponentSignedParameters()
	{
		$result = [];

		if ($this->arParams['USE_TOGGLE_VIEW'])
		{
			$result = [
				'MOBILE' => $this->arParams['MOBILE'] ?? null,
				'PARAMS' => [],
				'EXTENDED_PREVIEW' => $this->arParams['EXTENDED_PREVIEW'] ?? null,
				'INLINE' => $this->arParams['INLINE'],
				'USE_TOGGLE_VIEW' => $this->arParams['USE_TOGGLE_VIEW'],
			];

			foreach($this->arParams['PARAMS'] as $key => $value)
			{
				$allowedKeysList = [
					'arUserField',
					'INLINE',
					'DISABLE_MOD_ZIP',
					'DISABLE_LOCAL_EDIT',
					'MAX_SIZE',
					'THUMB_SIZE',
					'HTML_SIZE',
					'SIZE'
				];

				if (!in_array($key, $allowedKeysList))
				{
					continue;
				}

				if (
					$key == 'arUserField'
					&& is_array($value)
				)
				{
					$defaults = [
						'ENTITY_ID',
						'FIELD_NAME',
						'USER_TYPE_ID',
						'ENTITY_VALUE_ID',
						'VALUE',
						'VALUE_INLINE'
					];

					$value = array_intersect_key(
						$value, array_flip($defaults)
					);
				}

				$result['PARAMS'][$key] = $value;
			}
		}

		return $result;
	}

	protected function processActionDefault()
	{
		$this->arResult = [
			'DISABLE_LOCAL_EDIT' => $this->arParams['DISABLE_LOCAL_EDIT'],
			'FILES' => $this->loadFilesData(),
			'UID' => $this->getComponentId(),
		];

		$this->arResult['SIGNED_PARAMS'] = $this->getComponentSignedParameters();;

		$driver = Driver::getInstance();

		$this->arResult['CLOUD_DOCUMENT'] = array();
		if($this->arParams['DISABLE_CREATING_FILE_BY_CLOUD'])
		{
			$this->arResult['CAN_CREATE_FILE_BY_CLOUD'] = false;
		}
		else
		{
			$this->arResult['CAN_CREATE_FILE_BY_CLOUD'] = Configuration::canCreateFileByCloud();
		}

		[$documentHandlerName, $documentHandlerCode, $isLocal] = $this->getConfigurationOfCloudDocument();
		if($documentHandlerCode)
		{
			$this->arResult['CLOUD_DOCUMENT'] = array(
				'DEFAULT_SERVICE' => $documentHandlerCode,
				'DEFAULT_SERVICE_LABEL' => $documentHandlerName,
				'IS_LOCAL' => $isLocal,
			);
			$this->arResult['DEFAULT_DOCUMENT_SERVICE_EDIT_NAME'] = $documentHandlerName;
			$this->arResult['DEFAULT_DOCUMENT_SERVICE_EDIT_CODE'] = $documentHandlerCode;
		}
		else
		{
			$documentHandlerCode = 'l';
		}

		$urlManager = $driver->getUrlManager();
		if($this->editMode)
		{
			$this->arResult['controlName'] = $this->arParams['PARAMS']['arUserField']['FIELD_NAME'];
			$this->arResult['SHARE_EDIT_ON_OBJECT_UF'] = Configuration::isEnabledDefaultEditInUf();

			$this->arResult['CREATE_BLANK_URL'] = $urlManager::getUrlToStartCreateUfFileByService('docx', $documentHandlerCode);
			$this->arResult['RENAME_FILE_URL'] = $urlManager::getUrlDocumentController('rename', array('document_action' => 'rename'));
			$this->arResult['UPLOAD_FILE_URL'] = $urlManager::getUrlToUploadUfFile();

			//now we show checkbox only if it's create post, etc.
			$this->arResult['DISK_ATTACHED_OBJECT_ALLOW_EDIT'] = empty($this->arResult['FILES']);
			$userFieldManager = Driver::getInstance()->getUserFieldManager();
			$this->arResult['INPUT_NAME_OBJECT_ALLOW_EDIT'] = $userFieldManager->getInputNameForAllowEditByEntityType($this->arParams['PARAMS']['arUserField']['ENTITY_ID']);
			$this->arResult['INPUT_NAME_TEMPLATE_VIEW'] = $userFieldManager->getInputNameForTemplateView($this->arParams['PARAMS']['arUserField']['ENTITY_ID']);


			$this->arResult['DOCUMENT_HANDLERS'] = [];
			if ($this->arResult['CAN_CREATE_FILE_BY_CLOUD'])
			{
				foreach ($this->getDocumentHandlersForEditingFile() as $handlerData)
				{
					$this->arResult['DOCUMENT_HANDLERS'][] = [
						'name' => $handlerData['name'],
						'code' => $handlerData['code'],
					];
				}
			}
		}

		foreach (GetModuleEvents("main", $this->arParams['PARAMS']['arUserField']["USER_TYPE_ID"], true) as $arEvent)
		{
			if (!ExecuteModuleEventEx($arEvent, array($this->arResult, $this->arParams)))
				return;
		}
		if(is_array($this->arParams['PARAMS']))
		{
			$this->arParams = array_merge($this->arParams, $this->arParams['PARAMS']);
		}

		if ($this->arParams['INLINE'] === 'Y' && !$this->editMode)
		{
			//we have to regenerate id, because it will be inline in the post and it's another group.
			foreach ($this->arResult['FILES'] as $file)
			{
				/** @var \Bitrix\Main\UI\Viewer\ItemAttributes $attr */
				$attr = $file['ATTRIBUTES_FOR_VIEWER'];
				$attr->setGroupBy(
					$attr->getGroupBy() . 'inline'
				);
			}
		}

		$this->arResult['ENABLED_MOD_ZIP'] = \Bitrix\Disk\ZipNginx\Configuration::isEnabled();
		if (!empty($this->arParams['DISABLE_MOD_ZIP']) && $this->arParams['DISABLE_MOD_ZIP'] === 'Y')
		{
			$this->arResult['ENABLED_MOD_ZIP'] = false;
		}
		if($this->arResult['ENABLED_MOD_ZIP'] && !$this->editMode)
		{
			$this->arResult['ATTACHED_IDS'] = array();
			$this->arResult['COMMON_SIZE'] = 0;
			foreach($this->arResult['FILES'] as $fileData)
			{
				if ($fileData['IS_MARK_DELETED'])
				{
					continue;
				}

				$this->arResult['ATTACHED_IDS'][] = $fileData['ID'];
				$this->arResult['COMMON_SIZE'] += $fileData['SIZE_BYTES'];
			}
			$this->arResult['DOWNLOAD_ARCHIVE_URL'] = $urlManager->getUrlUfController('downloadArchiveByEntity', array(
				'entity' => $this->arParams['PARAMS']['arUserField']['ENTITY_ID'],
				'entityId' => $this->arParams['PARAMS']['arUserField']['ENTITY_VALUE_ID'],
				'fieldName' => $this->arParams['PARAMS']['arUserField']['FIELD_NAME'],
				'signature' => \Bitrix\Disk\Security\ParameterSigner::getEntityArchiveSignature(
					$this->arParams['PARAMS']['arUserField']['ENTITY_ID'],
					$this->arParams['PARAMS']['arUserField']['ENTITY_VALUE_ID'],
					$this->arParams['PARAMS']['arUserField']['FIELD_NAME']
				),
			));
		}

		$this->includeComponentTemplate($this->editMode ? 'edit' : 'show'.($this->arParams['INLINE'] === 'Y' ? '_inline' : ''));
	}

	private function loadFilesData()
	{
		if(empty($this->arParams['PARAMS']['arUserField']))
		{
			return array();
		}

		$userId = $this->getUser() instanceof \CUser? $this->getUser()->getId() : \Bitrix\Disk\Security\SecurityContext::GUEST_USER;

		$values = $this->arParams['PARAMS']['arUserField']['VALUE'];
		if(!is_array($this->arParams['PARAMS']['arUserField']['VALUE']))
		{
			$values = array($values);
		}
		$files = array();
		$driver = Driver::getInstance();
		$urlManager = $driver->getUrlManager();
		$userFieldManager = $driver->getUserFieldManager();
		$isEnabledObjectLock = Configuration::isEnabledObjectLock();

		$userFieldManager->loadBatchAttachedObject($values);
		foreach($values as $id)
		{
			$attachedModel = null;
			[$type, $realValue] = FileUserType::detectType($id);
			if (empty($realValue) || $realValue <= 0)
			{
				continue;
			}

			if ($type == FileUserType::TYPE_NEW_OBJECT)
			{
				/** @var File $fileModel */
				$fileModel = File::loadById($realValue);
				if(!$fileModel || !$fileModel->canRead($fileModel->getStorage()->getCurrentUserSecurityContext()))
				{
					continue;
				}
			}
			else
			{
				/** @var \Bitrix\Disk\AttachedObject $attachedModel */
				$attachedModel = $userFieldManager->getAttachedObjectById($realValue);
				if(!$attachedModel)
				{
					continue;
				}
				if(!$this->editMode)
				{
					$attachedModel->setOperableEntity(array(
						'ENTITY_ID' => $this->arParams['PARAMS']['arUserField']['ENTITY_ID'],
						'ENTITY_VALUE_ID' => $this->arParams['PARAMS']['arUserField']['ENTITY_VALUE_ID'],
					));
				}
				/** @var File $fileModel */
				$fileModel = $attachedModel->getFile();
			}
			$securityContext = $fileModel->getStorage()->getCurrentUserSecurityContext();

			$name = $fileModel->getName();
			$data = array(
				'ID' => $id,
				'NAME' => $name,
				'CONVERT_EXTENSION' => DocumentHandler::isNeedConvertExtension($fileModel->getExtension()),
				'EDITABLE' => DocumentHandler::isEditable($fileModel->getExtension()),
				'CAN_UPDATE' => ($attachedModel ? $attachedModel->canUpdate($userId) : $fileModel->canUpdate($securityContext)),
				'IS_LOCKED' => false,
				'IS_MARK_DELETED' => $fileModel->isDeleted(),
				'CAN_RESTORE' => $fileModel->canRestore($securityContext),

				'FROM_EXTERNAL_SYSTEM' => $fileModel->getContentProvider() && $fileModel->getCreatedBy() == $userId,

				'EXTENSION' => $fileModel->getExtension(),
				'SIZE' => \CFile::formatSize($fileModel->getSize()),
				'SIZE_BYTES' => $fileModel->getSize(),
				'XML_ID' => $fileModel->getXmlId(),
				'FILE_ID' => $fileModel->getId(),

				'VIEW_URL' => $urlManager->getUrlToShowAttachedFileByService($id, 'gvdrive'),
				'EDIT_URL' => $urlManager->getUrlToStartEditUfFileByService($id, 'gdrive'),
				'DOWNLOAD_URL' => $urlManager->getUrlUfController('download', array('attachedId' => $id)),
				'COPY_TO_ME_URL' => $urlManager->getUrlUfController('copyToMe', array('attachedId' => $id)),

				'DELETE_URL' => ""
			);
			if(\Bitrix\Disk\TypeFile::isImage($fileModel))
			{
				$data["PREVIEW_URL"] = ($attachedModel === null ? $urlManager->getUrlForShowFile($fileModel) : $urlManager->getUrlUfController('show', array('attachedId' => $id)));
				$data["IMAGE"] = $fileModel->getFile();
			}

			if(!$this->editMode && \Bitrix\Disk\TypeFile::isVideo($fileModel))
			{
				if($fileModel->getView()->isHtmlAvailable())
				{
					$maxWidth = 560;
					$maxHeight = 480;
					if($fileModel->getView()->getId() > 0)
					{
						$previewPath = '';
						if($attachedModel === null)
						{
							$viewPath = array(
								$urlManager->getUrlForShowView($fileModel),
								$urlManager->getUrlForShowFile($fileModel)
							);
							if($fileModel->getPreviewId())
							{
								$previewPath = $urlManager->getUrlForShowPreview($fileModel);
							}
						}
						else
						{
							$viewPath = array(
								$urlManager->getUrlUfController('showView', array('attachedId' => $id, 'filename' => $fileModel->getName(), 'viewId' => $fileModel->getView()->getId())),
								$urlManager->getUrlUfController('show', array('attachedId' => $id, 'filename' => $fileModel->getName(), 'viewId' => $fileModel->getView()->getId())),
							);
							if($fileModel->getPreviewId())
							{
								$previewPath = $urlManager->getUrlUfController('showPreview', array('attachedId' => $id, 'viewId' => $fileModel->getView()->getId()));
							}
						}
						$data["VIDEO"] = $fileModel->getView()->render(array(
							'IS_MOBILE_APP' => ($this->getTemplateName() == 'mobile'),
							'PATH' => $viewPath,
							'AUTOSTART' => 'N',
							'AUTOSTART_ON_SCROLL' => 'Y',
							'IFRAME' => 'N',
							'LAZYLOAD' => 'Y',
							'PREVIEW' => $previewPath,
							'WIDTH' => $maxWidth,
							'HEIGHT' => $maxHeight,
						));
						$data["VIDEO"] = str_replace("\n", "", $data["VIDEO"]);
					}
					elseif($fileModel->getView()->isShowTransformationInfo())
					{
						$data['VIDEO'] = $fileModel
							->getView()
							->renderTransformationInProcessMessage([
								'ATTACHED_OBJECT' => $attachedModel,
								'FILE' => $fileModel,
							])
						;
						$data['VIDEO'] = str_replace("\n", '', $data['VIDEO']);
					}
				}
			}

			if(TypeFile::isVideo($fileModel) && $fileModel->getView()->getEditorTypeFile())
			{
				$data['TYPE_FILE'] = $fileModel->getView()->getEditorTypeFile();
			}

			if ($data['IS_MARK_DELETED'] && $data['CAN_RESTORE'])
			{
				$data['TRASHCAN_URL'] = $urlManager->getUrlFocusController('showObjectInTrashCanGrid', array(
					'objectId' => $fileModel->getId(),
				));
			}

			if($this->editMode)
			{
				$data['STORAGE'] = $fileModel->getStorage()->getProxyType()->getTitleForCurrentUser() . ' / ' . $fileModel->getParent()->getName();
			}
			elseif(!$this->editMode && $attachedModel)
			{
				$data['CURRENT_USER_IS_OWNER'] = $attachedModel->getCreatedBy() == $userId;
				$data['ALLOW_AUTO_COMMENT'] = $attachedModel->getAllowAutoComment();

				if($isEnabledObjectLock && $fileModel->getLock())
				{
					$data['CREATED_BY'] = $fileModel->getLock()->getCreatedBy();
					$data['IS_LOCKED'] = true;
					$data['IS_LOCKED_BY_SELF'] = $userId == $fileModel->getLock()->getCreatedBy();

				}

				$sourceUri = new \Bitrix\Main\Web\Uri($urlManager->getUrlUfController('download', array('attachedId' => $attachedModel->getId())));

				$groupId = $this->componentId;
				if (!$this->editMode)
				{
					$groupId = $this->arParams['PARAMS']['arUserField']['ENTITY_ID'] . $this->arParams['PARAMS']['arUserField']['ENTITY_VALUE_ID'];
				}

				$attr = $this->buildItemAttributes($attachedModel, $sourceUri)
					->setTitle($attachedModel->getName())
					->setGroupBy($groupId)
					->addAction([
						'type' => 'download',
					])
				;

				if (!$this->arParams['DISABLE_LOCAL_EDIT'])
				{
					$attr->addAction([
						'type' => 'copyToMe',
						'text' => Loc::getMessage('DISK_UF_ACTION_SAVE_TO_OWN_FILES'),
						'action' => 'BX.Disk.Viewer.Actions.runActionCopyToMe',
						'params' => [
							'attachedObjectId' => $attachedModel->getId(),
						],
						'extension' => 'disk.viewer.actions',
						'buttonIconClass' => 'ui-btn-icon-cloud',
					])
					;
				}

				if ($attachedModel->getObject()->getTypeFile() == TypeFile::FLIPCHART && $attachedModel->canRead($userId))
				{
					$openUrl = $this->getUrlManager()->getUrlForViewBoard($attachedModel->getObjectId());
					$attr->addAction([
						'type' => 'open',
						'buttonIconClass' => ' ',
						'action' => 'BX.Disk.Viewer.Actions.openInNewTab',
						'params' => [
							'attachedObjectId' => $attachedModel->getId(),
							'url' => $openUrl,
						],
					]);
					$attr->addAction([
						'type' => 'edit',
						'buttonIconClass' => ' ',
						'action' => 'BX.Disk.Viewer.Actions.openInNewTab',
						'params' => [
							'attachedObjectId' => $attachedModel->getId(),
							'url' => $openUrl,
						],
					]);
				}
				elseif ($data['CAN_UPDATE'] && !$this->arParams['DISABLE_LOCAL_EDIT'])
				{
					$documentName = \CUtil::JSEscape($attachedModel->getName());
					$forcedService = null;
					$items = [];
					if ($data['EDITABLE'])
					{
						foreach ($this->getDocumentHandlersForEditingFile() as $handlerData)
						{
							$items[] = [
								'text' => $handlerData['name'],
								'onclick' => "BX.Disk.Viewer.Actions.runActionEdit({name: '{$documentName}', attachedObjectId: {$attachedModel->getId()}, serviceCode: '{$handlerData['code']}'})",
							];
						}
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

				$data['ATTRIBUTES_FOR_VIEWER'] = $attr;
			}
			$files[] = $data;
		}
		unset($id);

		return $files;
	}

	private function buildItemAttributes(\Bitrix\Disk\AttachedObject $attachedObject, $sourceUri)
	{
		if ($attachedObject->getExtra()->get('FILE_CONTENT_TYPE'))
		{
			$attributes = FileAttributes::buildByFileData(
				[
					'ID' => $attachedObject->getFileId(),
					'CONTENT_TYPE' => $attachedObject->getExtra()->get('FILE_CONTENT_TYPE'),
					'WIDTH' => (int)$attachedObject->getExtra()->get('FILE_WIDTH'),
					'HEIGHT' => (int)$attachedObject->getExtra()->get('FILE_HEIGHT'),
					'ORIGINAL_NAME' => $attachedObject->getName(),
					'FILE_SIZE' => (int)$attachedObject->getExtra()->get('FILE_SIZE'),
				],
				$sourceUri
			);

			$attributes
				->setObjectId($attachedObject->getObjectId())
				->setAttachedObjectId($attachedObject->getId())
			;

			return $attributes;
		}

		try
		{
			return FileAttributes::buildByFileId($attachedObject->getFileId(), $sourceUri)
				->setObjectId($attachedObject->getObjectId())
				->setAttachedObjectId($attachedObject->getId())
				;
		}
		catch (ArgumentException $exception)
		{
			return FileAttributes::buildAsUnknownType($sourceUri);
		}
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

	/**
	 * @return array
	 * @throws \Bitrix\Main\NotImplementedException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function getConfigurationOfCloudDocument()
	{
		static $documentHandlerName = null;
		static $documentHandlerCode = null;
		static $isLocal = null;

		if ($documentHandlerName === null && Configuration::canCreateFileByCloud())
		{
			$documentServiceCode = \Bitrix\Disk\UserConfiguration::getDocumentServiceCode();
			if (!$documentServiceCode)
			{
				$documentServiceCode = LocalDocumentController::getCode();
			}
			if ($this->arParams['DISABLE_LOCAL_EDIT'] && LocalDocumentController::isLocalService($documentServiceCode))
			{
				$documentServiceCode = GoogleHandler::getCode();
			}
			if (LocalDocumentController::isLocalService($documentServiceCode))
			{
				$documentHandlerName = LocalDocumentController::getName();
				$documentHandlerCode = LocalDocumentController::getCode();
				$isLocal = true;
			}
			else
			{
				$defaultDocumentHandler = Driver::getInstance()->getDocumentHandlersManager()->getDefaultServiceForCurrentUser();
				if ($defaultDocumentHandler)
				{
					$documentHandlerName = $defaultDocumentHandler::getName();
					$documentHandlerCode = $defaultDocumentHandler::getCode();
					$isLocal = false;
				}
			}
		}

		return [$documentHandlerName, $documentHandlerCode, $isLocal];
	}

	public function toggleViewTypeAction(array $params = [])
	{

		$viewType = (isset($params['viewType']) ? $params['viewType'] : '');
		if (!in_array($viewType, $this->getViewTypesList()))
		{
			$viewType = self::VIEW_TYPE_WEB;
		}

		$componentParams = $this->arParams;
		$componentParams['CONTROLLER_HIT'] = 'Y';
		$componentParams['PARAMS']['LAZYLOAD'] = 'N';

		if (
			isset($componentParams['PARAMS'])
			&& isset($componentParams['PARAMS']['MOBILE'])
			&& $componentParams['PARAMS']['MOBILE'] == 'Y'
			&& !defined('BX_MOBILE')
		)
		{
			define('BX_MOBILE', true);
		}

		if (
			isset($componentParams['PARAMS'])
			&& isset($componentParams['PARAMS']['arUserField'])
			&& !empty($componentParams['PARAMS']['arUserField']['ENTITY_ID'])
			&& !empty($componentParams['PARAMS']['arUserField']['ENTITY_VALUE_ID'])
		)
		{
			\Bitrix\Disk\Uf\FileUserType::setTemplateType([
				'ENTITY_ID' => $componentParams['PARAMS']['arUserField']['ENTITY_ID'],
				'ENTITY_VALUE_ID' => $componentParams['PARAMS']['arUserField']['ENTITY_VALUE_ID'],
				'VALUE' =>  (in_array($viewType, $this->getGridViewTypesList()) ? $viewType : 'gallery')
			]);
		}

		return new \Bitrix\Main\Engine\Response\Component('bitrix:disk.uf.file', $viewType, $componentParams);
	}
}
