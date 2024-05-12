<?php

namespace Bitrix\Disk\Uf;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Configuration;
use Bitrix\Disk\CrumbStorage;
use Bitrix\Disk\Document\CloudImport;
use Bitrix\Disk\Document\Contract\CloudImportInterface;
use Bitrix\Disk\Document\DocumentHandler;
use Bitrix\Disk\Document\GoogleHandler;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Security\DiskSecurityContext;
use Bitrix\Disk\Security\FakeSecurityContext;
use Bitrix\Disk\Security\ParameterSigner;
use Bitrix\Disk\Storage;
use Bitrix\Disk\TypeFile;
use Bitrix\Disk\ProxyType;
use Bitrix\Disk\Uf\Integration\DiskUploaderController;
use Bitrix\Disk\Ui\Text;
use Bitrix\Disk\User;
use Bitrix\Disk\ZipNginx;
use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class Controller extends Internals\Controller
{
	const ERROR_COULD_NOT_FIND_USER_STORAGE       = 'DISK_UF_CON_22001';
	const ERROR_COULD_NOT_FIND_FOLDER             = 'DISK_UF_CON_22002';
	const ERROR_COULD_NOT_FIND_CLOUD_IMPORT       = 'DISK_UF_CON_22003';
	const ERROR_COULD_NOT_WORK_WITH_TOKEN_SERVICE = 'DISK_UF_CON_22004';
	const ERROR_COULD_NOT_FIND_STORAGE            = 'DISK_UF_CON_22005';

	public static $previewParams = array("width" => 69, "height" => 69);

	protected function listActions()
	{
		return array(
			'openDialog',
			'selectFile' => 'openDialog',
			'renameFile' => array(
				'method' => array('POST'),
			),
			'searchFile' => array(
				'method' => array('POST')
			),
			'moveUploadedFile' => array(
				'method' => array('POST'),
			),
			'loadItems' => array(
				'method' => array('POST'),
			),
			'getUploadIniSettings',
			'listStorages',
			'listStorages',
			'listFolders',
			'getFolderForSavedFiles',
			'getGoogleAppData',
			'uploadFileMobileImport' => array(
				'method' => array('POST'),
				'check_csrf_token' => false,
			),
			'downloadFile' => array(
				'method' => array('GET'),
				'close_session' => true,
			),
			'download' => array(
				'method' => array('GET'),
				'close_session' => true,
			),
			'downloadArchive' => array(
				'method' => array('GET'),
				'close_session' => true,
			),
			'downloadArchiveByEntity' => array(
				'method' => array('GET'),
				'close_session' => true,
			),
			'show' => array(
				'method' => array('GET'),
				'close_session' => true,
			),
			'showView' => array(
				'method' => array('GET'),
				'close_session' => true,
			),
			'showVersionView' => array(
				'method' => array('GET'),
				'close_session' => true,
			),
			'showViewHtml' => array(
				'method' => array('GET'),
				'close_session' => true,
			),
			'showPreview' => array(
				'method' => array('GET'),
				'close_session' => true,
			),
			'transformOnOpen' => array(
				'method' => array('GET'),
				'close_session' => true,
			),
			'showTransformationInfo' => array(
				'method' => array('GET'),
				'close_session' => true,
			),
			'copyToMe' => array(
				'method' => array('POST', 'GET'),
				'check_csrf_token' => true,
			),
			'uploadFile' => array(
				'method' => array('POST', 'GET')
			),
			'deleteFile' => array(
				'method' => array('POST'),
			),
			'startUpload' => array(
				'method' => array('POST'),
			),
			'reloadAttachedObject' => array(
				'method' => array('POST'),
			),
			'uploadChunk' => array(
				'method' => array('POST'),
				'close_session' => true,
			),
			'saveAsNewFile' => array(
				'method' => array('POST'),
			),
			'updateAttachedObject' => array(
				'method' => array('POST'),
			),
			'disableAutoCommentToAttachedObject' => array(
				'method' => array('POST'),
			),
			'enableAutoCommentToAttachedObject' => array(
				'method' => array('POST'),
			),
			'getBreadcrumbs' => array(
				'method' => array('POST'),
			),
		);
	}

	protected function processBeforeAction($actionName)
	{
		parent::processBeforeAction($actionName);

		return true;
	}

	protected function getUserGroupWithStorage()
	{
		if(!\CBXFeatures::isFeatureEnabled('Workgroups'))
		{
			return array();
		}

		if(!Loader::includeModule('socialnetwork'))
		{
			return array();
		}

		$userId = $this->getUser()->getId();
		$currentUserGroups = array();

		$cache = Cache::createInstance();
		$cacheTtl = defined('BX_COMP_MANAGED_CACHE') ? 3153600 : 3600*4;
		$cachePath = "/disk/uf/{$userId}";
		if($cache->initCache($cacheTtl, 'group_storage_list_' . SITE_ID . '_' . $userId, $cachePath))
		{
			[$currentUserGroups] = $cache->getVars();
		}
		else
		{
			$cache->startDataCache();

			$taggedCache = Application::getInstance()->getTaggedCache();
			$taggedCache->startTagCache($cachePath);

			$conditionTree = \Bitrix\Main\ORM\Query\Query::filter();
			$conditionTree
				->where('STORAGE.ENTITY_TYPE', ProxyType\Group::class)
				->where('UG.USER_ID', $userId)
				->where('UG.GROUP.ACTIVE', 'Y')
				->where('UG.GROUP.CLOSED', 'N')
			;

			$connection = Application::getConnection();
			$sqlHelper = $connection->getSqlHelper();

			$diskSecurityContext = new DiskSecurityContext($userId);
			$storages = Storage::getReadableList(
				$diskSecurityContext,
				array(
					'filter' => $conditionTree,
					'runtime' => [
						(new ReferenceField('UG',
							'Bitrix\Socialnetwork\UserToGroupTable',
							[
								'=this.STORAGE.ENTITY_ID' => (
									$connection instanceof \Bitrix\Main\DB\PgsqlConnection
									? new SqlExpression($sqlHelper->castToChar('?#'), 'GROUP_ID')
									: 'ref.GROUP_ID'
								)
							],
							array('join_type' => 'INNER')
						))

					],
					'extra' => array('UG_GROUP_NAME' => 'UG.GROUP.NAME'),
				)
			);
			foreach($storages as $storage)
			{
				$currentUserGroups[$storage->getEntityId()] = array(
					'STORAGE' => $storage,
					'NAME' => $storage->getRootObject()->getExtra()->get('UG_GROUP_NAME'),
				);
			}
			unset($storage);

			$taggedCache->registerTag("sonet_user2group_U{$userId}");
			$taggedCache->endTagCache();

			$cache->endDataCache(array($currentUserGroups));
		}

		return $currentUserGroups;
	}

	protected function getCommonStorages()
	{
		$conditionTree = \Bitrix\Main\ORM\Query\Query::filter();
		$conditionTree
			->where('STORAGE.ENTITY_TYPE', ProxyType\Common::class)
			->where('STORAGE.SITE_ID', SITE_ID)
		;

		return Storage::getReadableList($this->getSecurityContextByUser($this->getUser()), ['filter' => $conditionTree]);
	}

	private function getSecurityContextByUser($user)
	{
		$diskSecurityContext = new DiskSecurityContext($user);
		if(Loader::includeModule('socialnetwork'))
		{

			if(\CSocnetUser::isCurrentUserModuleAdmin())
			{
				$diskSecurityContext = new FakeSecurityContext($user);
			}
		}
		if(User::isCurrentUserAdmin())
		{
			$diskSecurityContext = new FakeSecurityContext($user);
		}
		return $diskSecurityContext;
	}

	protected function processActionReloadAttachedObject()
	{
		$this->checkRequiredPostParams(array('attachedId',));

		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}
		/** @var AttachedObject $attachedModel */
		$attachedModel = AttachedObject::loadById((int)$this->request->getPost('attachedId'), array('OBJECT'));
		if(!$attachedModel)
		{
			$this->errorCollection->add(array(new Error('Could not find attached object')));
			$this->sendJsonErrorResponse();
		}

		if(!$attachedModel->canUpdate($this->getUser()->getId()))
		{
			$this->errorCollection->add(array(new Error("Bad permission. Could not update this file")));
			$this->sendJsonErrorResponse();
		}

		$file = $attachedModel->getFile();
		if(!$file)
		{
			$this->sendJsonErrorResponse();
		}
		if(!$file->canUpdateByCloudImport($file->getStorage()->getCurrentUserSecurityContext()))
		{
			$this->sendJsonAccessDeniedResponse();
		}
		$importManager = CloudImport\ImportManager::buildByAttachedObject($attachedModel);
		if(!$importManager)
		{
			return null;
		}
		$documentHandler = $importManager->getDocumentHandler();
		if(!$documentHandler->checkAccessibleTokenService())
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_UF_CONTROLLER_ERROR_COULD_NOT_WORK_WITH_TOKEN_SERVICE_B24', array('#NAME#' => $documentHandler::getName())), self::ERROR_COULD_NOT_WORK_WITH_TOKEN_SERVICE)));
			$this->errorCollection->add($documentHandler->getErrors());
			$this->sendJsonErrorResponse();
		}

		if(!$documentHandler->queryAccessToken()->hasAccessToken() || $documentHandler->isRequiredAuthorization())
		{
			$this->sendNeedAuth($documentHandler->getUrlForAuthorizeInTokenService('opener'));
		}

		$lastCloudImport = $attachedModel
			->getFile()
			->getLastCloudImportEntry()
		;
		if(!$importManager->hasNewVersion($lastCloudImport))
		{
			$this->sendJsonSuccessResponse(array(
				'hasNewVersion' => false,
			));
		}

		$cloudImportEntry = $importManager->forkImport($lastCloudImport);
		if(!$cloudImportEntry)
		{
			$this->errorCollection->add($importManager->getErrors());
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse(array(
			'hasNewVersion' => true,
			'cloudImport' => array(
				'id' => $cloudImportEntry->getId(),
			),
		));
	}

	protected function processActionStartUpload()
	{
		$this->checkRequiredPostParams(array('fileId', 'service'));

		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		$documentHandlersManager = Driver::getInstance()->getDocumentHandlersManager();
		$documentHandler = $documentHandlersManager->getHandlerByCode($this->request->getPost('service'));
		if(!$documentHandler)
		{
			$this->errorCollection->add($documentHandlersManager->getErrors());
			$this->sendJsonErrorResponse();
		}
		if(!$documentHandler->checkAccessibleTokenService())
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_UF_CONTROLLER_ERROR_COULD_NOT_WORK_WITH_TOKEN_SERVICE_B24', array('#NAME#' => $documentHandler::getName())), self::ERROR_COULD_NOT_WORK_WITH_TOKEN_SERVICE)));
			$this->errorCollection->add($documentHandler->getErrors());
			$this->sendJsonErrorResponse();
		}

		if(!$documentHandler->queryAccessToken()->hasAccessToken() || $documentHandler->isRequiredAuthorization())
		{
			$this->sendNeedAuth($documentHandler->getUrlForAuthorizeInTokenService('opener'));
		}

		$importManager = new CloudImport\ImportManager($documentHandler);
		$cloudImportEntry = $importManager->startImport($this->request->getPost('fileId'));
		if(!$cloudImportEntry)
		{
			$this->errorCollection->add($importManager->getErrors());
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse(array(
			'cloudImport' => array(
				'id' => $cloudImportEntry->getId(),
			),
		));
	}

	protected function processActionUploadChunk()
	{
		$this->checkRequiredPostParams(array('cloudImportId',));

		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		$cloudImport = CloudImport\Entry::load(array(
			'ID' => $this->request->getPost('cloudImportId'),
			'USER_ID' => $this->getUser()->getId(),
		));
		if(!$cloudImport)
		{
			$this->errorCollection->addOne(new Error('Could not find cloud import', self::ERROR_COULD_NOT_FIND_CLOUD_IMPORT));
			$this->sendJsonErrorResponse();
		}

		$documentHandlersManager = Driver::getInstance()->getDocumentHandlersManager();
		$documentHandler = $documentHandlersManager->getHandlerByCode($cloudImport->getService());
		if(!$documentHandler)
		{
			$this->errorCollection->add($documentHandlersManager->getErrors());
			$this->sendJsonErrorResponse();
		}
		if(!$documentHandler->checkAccessibleTokenService())
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_UF_CONTROLLER_ERROR_COULD_NOT_WORK_WITH_TOKEN_SERVICE_B24', array('#NAME#' => $documentHandler::getName())), self::ERROR_COULD_NOT_WORK_WITH_TOKEN_SERVICE)));
			$this->errorCollection->add($documentHandler->getErrors());
			$this->sendJsonErrorResponse();
		}

		if(!$documentHandler->queryAccessToken()->hasAccessToken() || $documentHandler->isRequiredAuthorization())
		{
			$this->sendNeedAuth($documentHandler->getUrlForAuthorizeInTokenService('opener'));
		}

		$importManager = new CloudImport\ImportManager($documentHandler);
		if(!$importManager->uploadChunk($cloudImport))
		{
			$this->errorCollection->add($importManager->getErrors());
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse(array(
			'step' => $cloudImport->isDownloaded()? 'finish' : 'download',
			'contentSize' => (int)$cloudImport->getContentSize(),
			'downloadedContentSize' => (int)$cloudImport->getDownloadedContentSize(),
		));
	}

	protected function processActionSaveAsNewFile()
	{
		$this->checkRequiredPostParams(array('cloudImportId',));

		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}
		$cloudImport = CloudImport\Entry::load(array(
			'ID' => $this->request->getPost('cloudImportId'),
			'USER_ID' => $this->getUser()->getId(),
		), array('TMP_FILE'));
		if(!$cloudImport)
		{
			$this->errorCollection->addOne(new Error('Could not find cloud import', self::ERROR_COULD_NOT_FIND_CLOUD_IMPORT));
			$this->sendJsonErrorResponse();
		}

		$documentHandlersManager = Driver::getInstance()->getDocumentHandlersManager();
		$documentHandler = $documentHandlersManager->getHandlerByCode($cloudImport->getService());
		if(!$documentHandler)
		{
			$this->errorCollection->add($documentHandlersManager->getErrors());
			$this->sendJsonErrorResponse();
		}
		if(!$documentHandler->checkAccessibleTokenService())
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_UF_CONTROLLER_ERROR_COULD_NOT_WORK_WITH_TOKEN_SERVICE_B24', array('#NAME#' => $documentHandler::getName())), self::ERROR_COULD_NOT_WORK_WITH_TOKEN_SERVICE)));
			$this->errorCollection->add($documentHandler->getErrors());
			$this->sendJsonErrorResponse();
		}

		if(!$documentHandler->queryAccessToken()->hasAccessToken() || $documentHandler->isRequiredAuthorization())
		{
			$this->sendNeedAuth($documentHandler->getUrlForAuthorizeInTokenService('opener'));
		}

		$storage = Driver::getInstance()->getStorageByUserId($this->getUser()->getId());
		$folder = $storage->getSpecificFolderByCode($documentHandler::SPECIFIC_FOLDER_CODE);

		$importManager = new CloudImport\ImportManager($documentHandler);
		$file = $importManager->saveFile($cloudImport, $folder);
		if(!$file)
		{
			$this->errorCollection->add($importManager->getErrors());
			$this->sendJsonErrorResponse();
		}

		$fileInfos = DiskUploaderController::getFileInfo([FileUserType::NEW_FILE_PREFIX . $file->getId()]);

		$this->sendJsonSuccessResponse(array(
			'file' => array(
				'id' => $file->getId(),
				'fileInfo' => $fileInfos[0] ?? [],
				'ufId' => FileUserType::NEW_FILE_PREFIX . $file->getId(),
				'name' => $file->getName(),
				'size' => $file->getSize(),
				'sizeFormatted' => \CFile::formatSize($file->getSize()),
				'folder' => $file->getParent()->getName(),
				'storage' => $file->getParent()->getName(),
				'previewUrl' => TypeFile::isImage($file)?
					Driver::getInstance()->getUrlManager()->getUrlForShowFile($file, array("width" => self::$previewParams["width"], "height" => self::$previewParams["height"])) : ''
			),
		));
	}

	protected function processActionUpdateAttachedObject()
	{
		$this->checkRequiredPostParams(array('cloudImportId', 'attachedId', ));

		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}
		$cloudImport = CloudImport\Entry::load(array(
			'ID' => $this->request->getPost('cloudImportId'),
			'USER_ID' => $this->getUser()->getId(),
		), array('TMP_FILE'));
		if(!$cloudImport)
		{
			$this->errorCollection->addOne(new Error('Could not find cloud import', self::ERROR_COULD_NOT_FIND_CLOUD_IMPORT));
			$this->sendJsonErrorResponse();
		}

		$documentHandlersManager = Driver::getInstance()->getDocumentHandlersManager();
		$documentHandler = $documentHandlersManager->getHandlerByCode($cloudImport->getService());
		if(!$documentHandler)
		{
			$this->errorCollection->add($documentHandlersManager->getErrors());
			$this->sendJsonErrorResponse();
		}
		if(!$documentHandler->checkAccessibleTokenService())
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_UF_CONTROLLER_ERROR_COULD_NOT_WORK_WITH_TOKEN_SERVICE_B24', array('#NAME#' => $documentHandler::getName())), self::ERROR_COULD_NOT_WORK_WITH_TOKEN_SERVICE)));
			$this->errorCollection->add($documentHandler->getErrors());
			$this->sendJsonErrorResponse();
		}

		if(!$documentHandler->queryAccessToken()->hasAccessToken() || $documentHandler->isRequiredAuthorization())
		{
			$this->sendNeedAuth($documentHandler->getUrlForAuthorizeInTokenService('opener'));
		}


		/** @var AttachedObject $attachedModel */
		$attachedModel = AttachedObject::loadById((int)$this->request->getPost('attachedId'), array('OBJECT'));
		if(!$attachedModel)
		{
			$this->errorCollection->add(array(new Error('Could not find attached object')));
			$this->sendJsonErrorResponse();
		}

		if(!$attachedModel->canUpdate($this->getUser()->getId()))
		{
			$this->errorCollection->add(array(new Error("Bad permission. Could not update this file")));
			$this->sendJsonErrorResponse();
		}

		$importManager = new CloudImport\ImportManager($documentHandler);
		$version = $importManager->uploadVersion($cloudImport);
		if(!$version)
		{
			$this->errorCollection->add($importManager->getErrors());
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse();
	}

	protected function listStorages()
	{
		$userStorage = Driver::getInstance()->getStorageByUserId($this->getUser()->getId());
		if(!$userStorage)
		{
			$this->errorCollection->add(array(new Error('Could not find storage for current user')));
			return null;
		}

		$urlUfController = Driver::getInstance()->getUrlManager()->getUrlUfController('loadItems');
		$list = array(
			'recently_used' => array(
				'id' => 'recently_used',
				'name' => Loc::getMessage('DISK_UF_CONTROLLER_RECENTLY_USED'),
				'type' => 'recently_used',
				'link' => $urlUfController,
			),
			$userStorage->getId() => array(
				'id' => $userStorage->getId(),
				'rootObjectId' => $userStorage->getRootObjectId(),
				'name' => $userStorage->getProxyType()->getTitleForCurrentUser(),
				'type' => 'user',
				'link' => $urlUfController,
			),
		);

		foreach($this->getUserGroupWithStorage() as $group)
		{
			if(empty($group['STORAGE']))
			{
				continue;
			}
			/** @var Storage $storage */
			$storage = $group['STORAGE'];
			$list[$storage->getId()] = array(
				'id' => $storage->getId(),
				'rootObjectId' => $storage->getRootObjectId(),
				'name' => $group['NAME'],
				'type' => 'group',
				'link' => $urlUfController,
			);
		}
		unset($group, $storage);

		foreach($this->getCommonStorages() as $common)
		{
			$list[$common->getId()] = array(
				'id' => $common->getId(),
				'rootObjectId' => $common->getRootObjectId(),
				'name' => $common->getName(),
				'type' => 'common',
				'link' => $urlUfController,
			);
		}
		unset($common);

		return $list;
	}

	protected function listCloudStorages()
	{
		$list = array();
		$documentHandlersManager = Driver::getInstance()->getDocumentHandlersManager();
		foreach($documentHandlersManager->getHandlersForImport() as $handler)
		{
			$list[$handler::getCode()] = array(
				'id' => $handler::getCode(),
				'name' => $handler::getStorageName(),
				'type' => 'cloud',
				'link' => Driver::getInstance()->getUrlManager()->getUrlUfController(
					'loadItems',
					array(
						'cloudImport' => 1,
						'service' => $handler::getCode(),
					)
				),
			);
		}
		unset($handler);

		return $list;
	}

	protected function processActionOpenDialog()
	{
		$selectedService = '';
		$fakeMove = $this->request->getQuery('wish') === 'fakemove';
		$enabledMultiSelect = $this->request->getQuery('multiselect') !== 'N';
		if($this->request->getQuery('cloudImport'))
		{
			$list = $this->listCloudStorages();
			$types = array(
				'cloud' => array(
					'id' => 'cloud',
					'order' => 4,
				),
			);
			$selectedService = $this->request->getQuery('service');
		}
		else
		{
			$list = $this->listStorages();
			if(!$list)
			{
				$this->sendJsonErrorResponse();
			}
			$types = array(
				'recently_used' => array(
					'id' => 'recently_used',
					'order' => 1,
					'searchable' => false,
				),
				'user' => array(
					'id' => 'user',
					'order' => 2,
					'searchable' => false,
				),
				'common' => array(
					'id' => 'common',
					'name' => Loc::getMessage('DISK_UF_CONTROLLER_SHARED_DOCUMENTS'),
					'order' => 3,
					'searchable' => false,
				),
				'group' => array(
					'id' => 'group',
					'name' => Loc::getMessage('DISK_UF_CONTROLLER_MY_GROUPS'),
					'order' => 4,
					'searchable' => false,
				),
			);
			if($fakeMove)
			{
				unset($types['recently_used']);
			}
		}

		$dialogName = $this->request->getQuery('dialogName');
		if ($dialogName == '')
		{
			$dialogName = 'DiskFileDialog';
		}

		$dialogTitle = Loc::getMessage($fakeMove? 'DISK_UF_CONTROLLER_SAVE_DOCUMENT_TITLE' : 'DISK_UF_CONTROLLER_SELECT_DOCUMENT_TITLE');
		if (!$fakeMove && !$enabledMultiSelect)
			$dialogTitle = Loc::getMessage('DISK_UF_CONTROLLER_SELECT_ONE_DOCUMENT_TITLE');

		$js = "
			<script>
				BX.DiskFileDialog.init({
					'currentTabId': '" . \CUtil::jSEscape($selectedService) . "',
					'name' : '".\CUtil::jSEscape($dialogName)."',

					'bindPopup' : { 'node' : null, 'offsetTop' : 0, 'offsetLeft': 0},

					'localize' : {
						'title' : '" . \CUtil::jSEscape($dialogTitle) . "',
						'saveButton' : '" . \CUtil::jSEscape(Loc::getMessage($fakeMove? 'DISK_UF_CONTROLLER_SELECT_FOLDER' : 'DISK_UF_CONTROLLER_SELECT_DOCUMENT')) . "',
						'cancelButton' : '" . \CUtil::jSEscape(Loc::getMessage('DISK_UF_CONTROLLER_CANCEL')) . "'
					},

					'callback' : {
						'saveButton' : function(tab, path, selected) {},
						'cancelButton' : function(tab, path, selected) {}
					},

					'type' : " . \CUtil::phpToJSObject($types) . ",
					'typeItems' : " . \CUtil::phpToJSObject($list) . ",
					'items' : {},

					'itemsDisabled' : {},
					'itemsSelected' : {},
					'itemsSelectEnabled' : " . ($fakeMove? '{folder: true}' : "{'onlyFiles' : true}") . ", // all, onlyFiles, folder, archive, image, file, video, txt, word, excel, ppt
					'itemsSelectMulti' : " . ($fakeMove || !$enabledMultiSelect ? 'false' : 'true') . ",

					'gridColumn' : {
						'name' : {'id' : 'name', 'name' : '" . \CUtil::jSEscape(Loc::getMessage('DISK_UF_CONTROLLER_TITLE_NAME')) . "', 'sort' : 'name', 'style': 'width: 310px', 'order': 1},
						'size' : {'id' : 'size', 'name' : '" . \CUtil::jSEscape(Loc::getMessage('DISK_UF_CONTROLLER_FILE_SIZE')) . "', 'sort' : 'sizeInt', 'style': 'width: 79px', 'order': 2},
						'modifyBy' : {'id' : 'modifyBy', 'name' : '" . \CUtil::jSEscape(Loc::getMessage('DISK_UF_CONTROLLER_TITLE_MODIFIED_BY')) . "', 'sort' : 'modifyBy', 'style': 'width: 122px', 'order': 3},
						'modifyDate' : {'id' : 'modifyDate', 'name' : '" . \CUtil::jSEscape(Loc::getMessage('DISK_UF_CONTROLLER_TITLE_TIMESTAMP')) . "', 'sort' : 'modifyDateInt', 'style': 'width: 90px', 'order': 4}
					},
					'gridOrder' : {'column': 'modifyDateInt', 'order':'desc'}
				});
			</script>
				";

		$this->sendResponse($js);
	}

	protected function processActionListStorages()
	{
		$list = $this->listStorages();
		if(!$list)
		{
			$this->sendJsonErrorResponse();
		}
		unset($list['recently_used']);

		$this->sendJsonResponse($list);
	}

	protected function processActionGetUploadIniSettings()
	{
		$this->sendJsonSuccessResponse(array(
			'upload_max_filesize' => \CUtil::unformat(ini_get('upload_max_filesize')),
			'post_max_size' => \CUtil::unformat(ini_get('post_max_size')),
		));
	}

	protected function processActionUploadFileMobileImport($storageId, $folderId = null)
	{
		$storage = Storage::loadById($storageId);
		if(!$storage)
		{
			$this->errorCollection[] = new Error(
				"Could not find storage by id {$storageId}",
				self::ERROR_COULD_NOT_FIND_STORAGE
			);
			$this->sendJsonErrorResponse();
		}

		if(!$folderId)
		{
			$folder = $storage->getRootObject();
		}
		else
		{
			$folder = Folder::loadById($folderId);
			if(!$folder)
			{
				$this->errorCollection[] = new Error(
					Loc::getMessage('DISK_UF_CONTROLLER_ERROR_COULD_NOT_FIND_FIND_FOLDER'),
					self::ERROR_COULD_NOT_FIND_FOLDER
				);
				$this->sendJsonErrorResponse();
			}
		}

		$securityContext = $folder->getStorage()->getCurrentUserSecurityContext();
		if(!$folder->canAdd($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		if(!$this->checkRequiredFilesParams(array('file')))
		{
			$this->sendJsonErrorResponse();
		}

		$fileArray = $this->request->getFile('file');
		$newFile = $folder->uploadFile(
			$fileArray,
			array(
				'NAME' => Text::correctFilename($fileArray['name']),
				'CREATED_BY' => $this->getUser()->getId(),
			),
			array(),
			true
		);

		if(!$newFile)
		{
			$this->errorCollection->add($folder->getErrors());
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse();
	}

	protected function processActionListFolders($storageId, $folderId = null)
	{
		$storage = Storage::loadById($storageId);
		if(!$storage)
		{
			$this->errorCollection[] = new Error(
				"Could not find storage by id {$storageId}",
				self::ERROR_COULD_NOT_FIND_STORAGE
			);
			$this->sendJsonErrorResponse();
		}

		$securityContext = $storage->getCurrentUserSecurityContext();
		if(!$folderId)
		{
			$folder = $storage->getRootObject();
		}
		else
		{
			$folder = Folder::loadById($folderId);
			if(!$folder)
			{
				$this->errorCollection[] = new Error(
					Loc::getMessage('DISK_UF_CONTROLLER_ERROR_COULD_NOT_FIND_FIND_FOLDER'),
					self::ERROR_COULD_NOT_FIND_FOLDER
				);
				$this->sendJsonErrorResponse();
			}
		}

		$response = array();
		foreach($folder->getChildren($securityContext) as $baseObject)
		{
			/** @var BaseObject $baseObject */

			$isFolder = $baseObject instanceof Folder;
			if($isFolder && !$baseObject->canAdd($securityContext))
			{
				continue;
			}

			$response[] = array(
				'id' => $baseObject->getId(),
				'type' => $isFolder? 'folder' : 'file',
				'name' => $baseObject->getName(),
				'size' => $isFolder? null : $baseObject->getSize(),
			);
		}

		$this->sendJsonResponse($response);
	}

	protected function processActionGetFolderForSavedFiles()
	{
		$storage = Driver::getInstance()->getStorageByUserId($this->getUser()->getId());
		if(!$storage)
		{
			$this->errorCollection[] = new Error(
				"Could not find storage for user id {$this->getUser()->getId()}",
				self::ERROR_COULD_NOT_FIND_STORAGE
			);
			$this->sendJsonErrorResponse();
		}

		$securityContext = $storage->getCurrentUserSecurityContext();

		$folder = $storage->getFolderForSavedFiles();
		if(!$folder)
		{
			$this->errorCollection[] = new Error(
				Loc::getMessage('DISK_UF_CONTROLLER_ERROR_COULD_NOT_FIND_FIND_FOLDER'),
				self::ERROR_COULD_NOT_FIND_FOLDER
			);
			$this->sendJsonErrorResponse();
		}

		if(!$folder->canAdd($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		$this->sendJsonResponse(array(
			'id' => $folder->getId(),
			'storageId' => $folder->getStorageId(),
			'type' => 'folder',
			'name' => $folder->getName(),
		));
	}

	protected function processActionGetGoogleAppData()
	{
		$service = GoogleHandler::getCode();
		$documentHandlersManager = Driver::getInstance()->getDocumentHandlersManager();
		$documentHandler = $documentHandlersManager->getHandlerByCode($service);
		if (!$documentHandler)
		{
			$this->errorCollection->add($documentHandlersManager->getErrors());
			$this->sendJsonErrorResponse();
		}
		if (!($documentHandler instanceof CloudImportInterface))
		{
			$this->errorCollection[] = new Error("Document handler {{$documentHandler::getCode()}} does not implement " . CloudImportInterface::class);
			$this->sendJsonErrorResponse();
		}
		if (!$documentHandler->checkAccessibleTokenService())
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_UF_CONTROLLER_ERROR_COULD_NOT_WORK_WITH_TOKEN_SERVICE_B24', array('#NAME#' => $documentHandler::getName())), self::ERROR_COULD_NOT_WORK_WITH_TOKEN_SERVICE)));
			$this->errorCollection->add($documentHandler->getErrors());
			$this->sendJsonErrorResponse();
		}

		if (!$documentHandler->queryAccessToken()->hasAccessToken() || $documentHandler->isRequiredAuthorization())
		{
			$this->sendNeedAuth($documentHandler->getUrlForAuthorizeInTokenService('opener'));
		}

		if (!$documentHandler->listFolder('/', '') && $documentHandler->isRequiredAuthorization())
		{
			$this->sendNeedAuth($documentHandler->getUrlForAuthorizeInTokenService('opener'));
		}

		$this->sendJsonSuccessResponse([
		   'clientId' => $documentHandler->getClientId(),
		   'apiKey' => $documentHandler->getApiKey(),
		   'appId' => $documentHandler->getAppId(),
		   'accessToken' => $documentHandler->queryAccessToken()->getAccessToken(),
	   ]);
	}

	protected function processActionLoadItems()
	{
		$this->checkRequiredPostParams(array(
			'FORM_TAB_TYPE', 'FORM_TAB_ID', 'FORM_PATH',
		));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}
		$dialogName = $this->request->getPost('FORM_NAME') ?: 'DiskFileDialog';
		$typeStorage = mb_strtolower($this->request->getPost('FORM_TAB_TYPE'));
		if(!in_array($typeStorage, array('user', 'common', 'group', 'cloud', 'recently_used'), true))
		{
			$this->errorCollection->add(array(new Error("Invalid storage type {$typeStorage}")));
			$this->sendJsonErrorResponse();
		}
		$storageId = (int)$this->request->getPost('FORM_TAB_ID');
		$path = $this->request->getPost('FORM_PATH');

		$storage = null;
		if($typeStorage === 'recently_used')
		{
			$this->sendJsonSuccessResponse(array(
				'FORM_NAME' => $dialogName,
				'FORM_ITEMS' => Driver::getInstance()->getRecentlyUsedManager()->getFileListByUser($this->getUser()->getId()),
				'FORM_ITEMS_DISABLED' => array(),
				'FORM_PATH' => $path,
				'FORM_IBLOCK_ID' => 0,
			));
		}
		elseif($typeStorage === 'cloud')
		{
			$documentHandlersManager = Driver::getInstance()->getDocumentHandlersManager();
			$documentHandler = $documentHandlersManager->getHandlerByCode($this->request->getQuery('service'));
			if(!$documentHandler)
			{
				$this->errorCollection->add($documentHandlersManager->getErrors());
				$this->sendJsonErrorResponse();
			}
			if (!($documentHandler instanceof CloudImportInterface))
			{
				$this->errorCollection[] = new Error("Document handler {{$documentHandler::getCode()}} does not implement " . CloudImportInterface::class);
				$this->sendJsonErrorResponse();
			}
			if(!$documentHandler->checkAccessibleTokenService())
			{
				$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_UF_CONTROLLER_ERROR_COULD_NOT_WORK_WITH_TOKEN_SERVICE_B24', array('#NAME#' => $documentHandler::getName())), self::ERROR_COULD_NOT_WORK_WITH_TOKEN_SERVICE)));
				$this->errorCollection->add($documentHandler->getErrors());
				$this->sendJsonErrorResponse();
			}

			if(!$documentHandler->queryAccessToken()->hasAccessToken() || $documentHandler->isRequiredAuthorization())
			{
				$this->sendNeedAuth($documentHandler->getUrlForAuthorizeInTokenService('opener'));
			}

			$itemsCloud = $this->listItemsCloud($documentHandler, $path);
			if($itemsCloud === null && $documentHandler->isRequiredAuthorization())
			{
				$this->sendNeedAuth($documentHandler->getUrlForAuthorizeInTokenService('opener'));
			}
			$this->sendJsonSuccessResponse(array(
				'sortMode' => Internals\Grid\FolderListOptions::SORT_MODE_ORDINARY,
				'FORM_NAME' => $dialogName,
				'FORM_ITEMS' => $itemsCloud,
				'FORM_ITEMS_DISABLED' => array(),
				'FORM_PATH' => $path,
				'FORM_IBLOCK_ID' => 0,
			));
		}
		else
		{
			$storage = $this->getStorageByType($typeStorage, $storageId);
		}

		if(!$storage)
		{
			$this->errorCollection->add(array(new Error('Could not find storage for current user')));
			$this->sendJsonErrorResponse();
		}
		$options = new Internals\Grid\FolderListOptions($storage);
		$this->sendJsonSuccessResponse(array(
			'sortMode' => $options->getSortMode(),
			'FORM_NAME' => $dialogName,
			'FORM_ITEMS' => $this->listItems($storage, $path),
			'FORM_ITEMS_DISABLED' => array(),
			'FORM_PATH' => $path,
			'FORM_IBLOCK_ID' => 0,
		));

	}

	private function getStorageByType($type, $storageId)
	{
		$storage = null;
		if($type === 'user')
		{
			return Driver::getInstance()->getStorageByUserId($this->getUser()->getId());
		}
		elseif($type === 'group')
		{
			$storage = Storage::loadById($storageId);
			if(!$storage || !$storage->getProxyType() instanceof ProxyType\Group)
			{
				$this->errorCollection->add(array(new Error("Invalid storage type {$type}. Is not a group.")));
				return null;
			}
		}
		elseif($type === 'common')
		{
			$storage = Storage::loadById($storageId);
			if(!$storage || !$storage->getProxyType() instanceof ProxyType\Common)
			{
				$this->errorCollection->add(array(new Error("Invalid storage type {$type}. Is not a common storage.")));
				return null;
			}
		}

		return $storage;
	}

	/**
	 * @param $storage
	 * @param $path
	 * @return array
	 */
	protected function listItems(Storage $storage, $path = '/')
	{
		$currentFolderId = Driver::getInstance()->getUrlManager()->resolveFolderIdFromPath($storage, $path);
		/** @var Folder $folder */
		$folder = Folder::loadById($currentFolderId);
		if(!$folder)
		{
			$this->errorCollection->add(array(new Error('Could not find folder by path')));
			$this->sendJsonErrorResponse();
		}

		$securityContext = $storage->getCurrentUserSecurityContext();
		$urlManager = Driver::getInstance()->getUrlManager();
		$urlForLoadItems = $urlManager->getUrlUfController('loadItems');

		$response = array();
		$path = rtrim($path, '/') . '/';
		foreach($folder->getChildren($securityContext, array('with' => array('UPDATE_USER'))) as $item)
		{
			/** @var File|Folder $item */
			$isFolder = $item instanceof Folder;
			$id = $item->getId();
			$res = array(
				'id' => $item->getId(),
				'type' => $isFolder ? 'folder' : 'file',
				'link' => $urlForLoadItems,
				'name' => $item->getName(),
				'path' => $path . $item->getName(),
				'size' => $isFolder ? '' : \CFile::formatSize($item->getSize()),
				'sizeInt' => $isFolder ? '' : $item->getSize(),
				'modifyBy' => $item->getUpdateUser()->getFormattedName(),
				'modifyDate' => $item->getUpdateTime()->format('d.m.Y'),
				'modifyDateInt' => $item->getUpdateTime()->getTimestamp(),
			);
			if (!$isFolder)
			{
				$extension = $item->getExtension();
				$id = FileUserType::NEW_FILE_PREFIX.$item->getId();
				$res = array_merge(
					$res,
					array(
						'id' => $id,
						'ext' => $extension,
						'storage' => $folder->getName()
					)
				);
				if (TypeFile::isImage($item))
				{
					$res['previewUrl'] = $urlManager->getUrlForShowFile($item);
				}
				$fileType = $item->getView()->getEditorTypeFile();
				if(!empty($fileType))
				{
					$res['fileType'] = $fileType;
				}
			}
			$response[$id] = $res;
		}
		unset($item);

		return $response;
	}

	/**
	 * @param DocumentHandler|CloudImportInterface $documentHandler
	 * @param string          $path
	 * @return array|null
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	protected function listItemsCloud(DocumentHandler $documentHandler, $path = '/')
	{
		$urlManager = Driver::getInstance()->getUrlManager();
		$items = $documentHandler->listFolder($path, $this->request->getQuery('folderId'));
		if($items === null)
		{
			$this->errorCollection->add($documentHandler->getErrors());
			return null;
		}
		$response = array();
		foreach($items as $item)
		{
			$item['link'] = $urlManager->getUrlUfController(
				'loadItems',
				array(
					'folderId' => $item['id'],
					'service' => $documentHandler::getCode(),
				)
			);
			$response[$item['id']] = $item;

		}
		unset($item);

		return $response;
	}

	protected function processActionDownloadArchiveByEntity($signature, $entity, $entityId, $fieldName)
	{
		if(!ParameterSigner::validateEntityArchiveSignature($signature, $entity, $entityId, $fieldName))
		{
			$this->sendJsonInvalidSignResponse('Invalid signature');
		}

		if(!ZipNginx\Configuration::isEnabled())
		{
			$this->errorCollection[] = new Error('Work with mod_zip is disabled in module settings.');
			$this->sendJsonErrorResponse();
		}

		$zipArchive = new ZipNginx\Archive('archive' . date('y-m-d') . '.zip');

		$userFieldManager = Driver::getInstance()->getUserFieldManager();
		foreach ($userFieldManager->getAttachedObjectByEntity($entity, $entityId, $fieldName) as $attachedObject)
		{
			if (!$attachedObject->canRead($this->getUser()->getId()))
			{
				continue;
			}

			$zipArchive->addEntry(
				ZipNginx\ArchiveEntry::createFromAttachedObject($attachedObject)
			);
		}

		if ($zipArchive->isEmpty())
		{
			$this->errorCollection[] = new Error('Archive is empty');
			$this->sendJsonErrorResponse();
		}

		$zipArchive->send();
		$this->end();
	}

	protected function processActionDownloadArchive($signature, array $attachedIds = array())
	{
		if(!ParameterSigner::validateArchiveSignature($signature, $attachedIds))
		{
			$this->sendJsonInvalidSignResponse('Invalid signature');
		}

		if(!ZipNginx\Configuration::isEnabled())
		{
			$this->errorCollection[] = new Error('Work with mod_zip is disabled in module settings.');
			$this->sendJsonErrorResponse();
		}

		$zipArchive = new ZipNginx\Archive('archive' . date('y-m-d') . '.zip');

		$userFieldManager = Driver::getInstance()->getUserFieldManager();
		$userFieldManager->loadBatchAttachedObject($attachedIds);
		foreach($attachedIds as $id)
		{
			if(!$userFieldManager->isLoadedAttachedObject($id))
			{
				continue;
			}

			$attachedModel = $userFieldManager->getAttachedObjectById($id);
			if(!$attachedModel->canRead($this->getUser()->getId()))
			{
				continue;
			}

			$zipArchive->addEntry(
				ZipNginx\ArchiveEntry::createFromAttachedObject($attachedModel)
			);
		}

		if($zipArchive->isEmpty())
		{
			$this->errorCollection[] = new Error('Archive is empty');
			$this->sendJsonErrorResponse();
		}

		$zipArchive->send();
		$this->end();
	}

	protected function processActionDownload($showFile = false)
	{
		$attachedModel = $this->getAttachedModel();

		$file = $attachedModel->getFile();
		if (!$file)
		{
			$this->sendJsonErrorResponse();
		}

		$fileName = $file->getName();
		$fileData = $file->getFile();

		$version = $attachedModel->getVersion();
		if ($version)
		{
			$fileName = $version->getName();
			$fileData = $version->getFile();
		}

		$isImage = TypeFile::isImage($fileData['ORIGINAL_NAME']) || TypeFile::isImage($fileName);
		$isImage = $isImage && !TypeFile::shouldTreatImageAsFile($fileData);
		$cacheTime = $isImage ? 86400 : Configuration::DEFAULT_CACHE_TIME;

		if ($isImage)
		{
			$fileData = $this->resizeImage($fileData, $attachedModel->getId());
		}
		else
		{
			$trackedObjectManager = Driver::getInstance()->getTrackedObjectManager();
			$trackedObjectManager->pushAttachedObject($this->getUser()->getId(), $attachedModel, true);
		}

		if ($isImage && $showFile && $attachedModel->getConnector()->isAnonymousAllowed())
		{
			$response = \Bitrix\Main\Context::getCurrent()->getResponse();
			$response->addHeader("X-Bitrix-Public-Link", "img");
		}

		\CFile::viewByUser($fileData, array('force_download' => !$showFile, 'cache_time' => $cacheTime, 'attachment_name' => $fileName));
	}

	protected function processActionShow()
	{
		$this->processActionDownload(true);
	}

	/**
	 * Show view of the file.
	 */
	protected function processActionShowView()
	{
		$attachedModel = $this->getAttachedModel();

		$file = $attachedModel->getFile();
		if(!$file->getView()->getId())
		{
			$this->end();
		}
		$fileName = $file->getView()->getName();
		$fileData = $file->getView()->getData();

		$cacheTime = Configuration::DEFAULT_CACHE_TIME;

		\CFile::viewByUser($fileData, array('force_download' => false, 'cache_time' => $cacheTime, 'attachment_name' => $fileName));
	}

	/**
	 * Show preview of the file.
	 */
	protected function processActionShowPreview()
	{
		$attachedModel = $this->getAttachedModel();

		$file = $attachedModel->getFile();
		if(!$file->getPreviewId())
		{
			$this->end();
		}
		$fileName = $file->getView()->getPreviewName();
		$fileData = $file->getView()->getPreviewData();

		$cacheTime = 86400;

		\CFile::viewByUser($fileData, array('force_download' => false, 'cache_time' => $cacheTime, 'attachment_name' => $fileName));
	}

	/**
	 * Show view of the version.
	 */
	protected function processActionShowVersionView()
	{
		$attachedModel = $this->getAttachedModel();

		$version = $attachedModel->getVersion();
		if(!$version)
		{
			$this->end();
		}

		$fileName = $version->getView()->getName();
		$fileData = $version->getView()->getData();

		$cacheTime = Configuration::DEFAULT_CACHE_TIME;

		\CFile::viewByUser($fileData, array('force_download' => false, 'cache_time' => $cacheTime, 'attachment_name' => $fileName));
	}

	/**
	 * Returns html-code to show view.
	 *
	 * @param string $pathToView
	 * @param string $mode
	 * @param string $print
	 * @param string $preview
	 * @param string $sizeType
	 * @param string $printUrl
	 * @param string $autostart
	 * @param string $width
	 * @param string $height
	 */
	protected function processActionShowViewHtml($pathToView, $mode = '', $print = '', $preview = '', $sizeType = '', $printUrl = '', $autostart = 'Y', $width = null, $height = null)
	{
		$attachedModel = $this->getAttachedModel();

		$file = $attachedModel->getFile();
		if(!$file->getView()->getId())
		{
			$this->end();
		}

		$printParam = $iframe = 'N';
		if($mode === 'iframe')
		{
			$iframe = 'Y';
			if($print === 'Y')
			{
				$printParam = 'Y';
			}
		}
		$elementId = 'bx_ajaxelement_' . $file->getId() . '_' . randString(4);
		$version = $attachedModel->getVersion();
		if($version)
		{
			$view = $version->getView();
		}
		else
		{
			$view = $file->getView();
		}
		$html = $view->render(array(
			'PATH' => $pathToView,
			'IFRAME' => $iframe,
			'ID' => $elementId,
			'PRINT' => $printParam,
			'PREVIEW' => $preview,
			'SIZE_TYPE' => $sizeType,
			'PRINT_URL' => $printUrl,
			'AUTOSTART' => ($autostart !== 'Y' ? 'N' : 'Y'),
			'WIDTH' => $width,
			'HEIGHT' => $height,
		));
		if($iframe == 'Y')
		{
			echo $html;
		}
		else
		{
			$result = array('html' => $html, 'innerElementId' => $elementId);
			$result = array_merge($result, $view->getJsViewerAdditionalJsonParams());
			$this->sendJsonResponse($result);
		}
		$this->end();
	}

	protected function processActionTransformOnOpen()
	{
		$attachedModel = $this->getAttachedModel();

		$file = $attachedModel->getFile();

		$result = $file->getView()->transformOnOpen($file);

		$this->sendJsonResponse($result);
	}

	protected function processActionShowTransformationInfo($transformOnOpenUrl = '', $refreshUrl = '', $noError = '')
	{
		$attachedModel = $this->getAttachedModel();
		if(!$attachedModel->getFile()->getView()->isShowTransformationInfo())
		{
			$this->sendJsonErrorResponse();
		}
		if($noError == 'y' && $attachedModel->getFile()->getView()->isLastTransformationFailed())
		{
			$this->sendJsonErrorResponse();
		}
		$params = array(
			'TRANSFORM_URL' => $transformOnOpenUrl,
			'REFRESH_URL' => $refreshUrl,
		);
		$result = array(
			'html' => $attachedModel->getFile()->getView()->renderTransformationInProcessMessage($params)
		);
		$this->sendJsonSuccessResponse($result);
	}

	/**
	 * Common method to retrieve attached model from rhe request.
	 *
	 * @return AttachedObject
	 */
	protected function getAttachedModel()
	{
		$this->checkRequiredGetParams(array(
			'attachedId',
		));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}
		/** @var AttachedObject $attachedModel */
		$attachedModel = AttachedObject::loadById((int)$this->request->getQuery('attachedId'), array('OBJECT', 'VERSION'));
		if(!$attachedModel)
		{
			$this->errorCollection->add(array(new Error('Could not find attached object')));
			$this->sendJsonErrorResponse();
		}

		if(!$attachedModel->canRead($this->getUser()->getId()))
		{
			$this->errorCollection->add(array(new Error("Bad permission. Could not read this file")));
			$this->sendJsonErrorResponse();
		}

		return $attachedModel;
	}

	protected function processActionCopyToMe()
	{
		$this->checkRequiredGetParams(array(
			'attachedId',
		));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}
		$attachedModel = AttachedObject::loadById((int)$this->request->getQuery('attachedId'), array('OBJECT', 'VERSION'));
		if(!$attachedModel)
		{
			$this->errorCollection->add(array(new Error('Could not find attached object')));
			$this->sendJsonErrorResponse();
		}

		if(!$attachedModel->canRead($this->getUser()->getId()))
		{
			$this->errorCollection->add(array(new Error("Bad permission. Could not read this file")));
			$this->sendJsonErrorResponse();
		}

		$userStorage = Driver::getInstance()->getStorageByUserId($this->getUser()->getId());
		if(!$userStorage)
		{
			$this->errorCollection->add(array(new Error("Could not find storage for current user")));
			$this->sendJsonErrorResponse();
		}
		$folder = $userStorage->getFolderForSavedFiles();
		if(!$folder)
		{
			$this->errorCollection->add(array(new Error("Could not find folder for created files")));
			$this->sendJsonErrorResponse();
		}
		$file = $attachedModel->getObject();
		$newFile = $file->copyTo($folder, $this->getUser()->getId(), true);

		if(!$newFile)
		{
			$this->errorCollection->add(array(new Error("Could not copy file to storage for current user")));
			$this->sendJsonErrorResponse();
		}

		$urlManager = Driver::getInstance()->getUrlManager();
		$viewUrl = $urlManager->encodeUrn(
			$urlManager->getUrlFocusController('showObjectInGrid', array(
				'objectId' => $newFile->getId(),
			))
		);
		$runViewerUrl = $urlManager->encodeUrn(
			$urlManager->getUrlFocusController('showObjectInGrid', array(
				'objectId' => $newFile->getId(),
				'cmd' => 'show',
			))
		);


		$this->sendJsonSuccessResponse(array(
			'newId' => $newFile->getId(),
			'viewUrl' => $viewUrl,
			'runViewUrl' => $runViewerUrl,
		));
	}

	function processActionHandleFile($hash, &$file, &$package, &$upload, &$error)
	{
		$errorCollection = new ErrorCollection();
		$storage = Driver::getInstance()->getStorageByUserId($this->getUser()->getId());
		if(!$storage)
		{
			$errorCollection->add(array(new Error(Loc::getMessage('DISK_UF_CONTROLLER_ERROR_COULD_NOT_FIND_USER_STORAGE'), self::ERROR_COULD_NOT_FIND_USER_STORAGE)));
			$error = implode(" ", $errorCollection->toArray());
			return false;
		}
		$data = array(
			'NAME' => $file['name'],
			'CREATED_BY' => $this->getUser()->getId(),
		);
		if(mb_strpos($file['name'], 'videomessage') === 0)
		{
			$folder = $storage->getFolderForRecordedFiles();
			$data['CODE'] = File::CODE_RECORDED_FILE;
		}
		else
		{
			$folder = $storage->getFolderForUploadedFiles();
		}
		if(!$folder)
		{
			$errorCollection->add(array(new Error(Loc::getMessage('DISK_UF_CONTROLLER_ERROR_COULD_NOT_FIND_FIND_FOLDER'), self::ERROR_COULD_NOT_FIND_FOLDER)));
			$error = implode(" ", $errorCollection->toArray());
			return false;
		}

		$urlManager = Driver::getInstance()->getUrlManager();
		if($folder->canAdd($storage->getCurrentUserSecurityContext()))
		{
			$fileModel = $folder->uploadFile($file["files"]["default"], $data, array(), true);

			if($fileModel)
			{
				if($fileModel->getView()->isShowTransformationUpgradeMessage())
				{
					$notification = Loc::getMessage('DISK_UF_CONTROLLER_FILE_IS_TOO_BIG_FOR_TRANSFORMATION');
				}
				$name = $fileModel->getName();
				$id = FileUserType::NEW_FILE_PREFIX.$fileModel->getId();
				$fileType = $fileModel->getView()->getEditorTypeFile();

				$file = array_merge(
					$file,
					array(
						'id' => $id, // TODO delete this after main 17.0.0 release
						'attachId' => $id,
						'fileId' => $fileModel->getId(),
						'originalId' => $fileModel->getId(),
						'name' => $name,
						'label' => getFileNameWithoutExtension($name),
						'ext' => $fileModel->getExtension(),
						'size' => \CFile::formatSize($fileModel->getSize()),
						'sizeInt' => $fileModel->getSize(),
						'storage' => $storage->getProxyType()->getTitleForCurrentUser() . ' / ' . $folder->getName(),
						'canChangeName' => true,
					),
					(TypeFile::isImage($fileModel) ? [
						'previewUrl' => $urlManager->getUrlForShowFile(
							$fileModel,
							array_merge([
								'width' => self::$previewParams['width'],
								'height' => self::$previewParams['height'],
								'exact' => 'Y',
							], ($this->request->getPost('previewParams') ?? []))
						)
					] : array()),
					(!empty($fileType) ? array (
						'fileType' => $fileType,
					): array()),
					(!empty($notification) ? array (
						'notification' => $notification,
					): array())
				);
			}
			else
			{
				$error = (is_array($folder->getErrors()) ? implode(" ", $folder->getErrors()) : 'The file has not been saved');
			}
		}
		return (empty($error));
	}

	protected function processActionUploadFile()
	{
		static $uploader = null;
		if ($uploader === null)
			$uploader = new \Bitrix\Main\UI\Uploader\Uploader(array(
				"events" => array(
					"onFileIsUploaded" => array($this, "processActionHandleFile")
				),
				"storage" => array(
					"cloud" => true,
					"moduleId" => Driver::INTERNAL_MODULE_ID
				)
			), "get");
		if (!$uploader->checkPost() &&
			check_bitrix_sessid() &&
			$this->request->getFile("disk_file"))
		{
			$file = $this->request->getFile("disk_file") +
				array("files" =>
					array("default" =>
						$this->request->getFile("disk_file")));
			if ($this ->processActionHandleFile(
				$hash = "",
				$file,
				$package = array(),
				$upload = array(),
				$error = array()
				)
			)
			{
				unset($file["files"]);
				unset($file["tmp_name"]);
				$this->sendJsonResponse(array(
					'status' => self::STATUS_SUCCESS,
					'data' => $file
				));
			}
			else
			{
				$this->sendJsonResponse(array(
					'status' => self::STATUS_ERROR,
					'message' => $error
				));

			}
		}
	}

	protected function processActionDownloadFile(): void
	{
		$this->checkRequiredGetParams(['attachedId']);

		if ($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		[$type, $realValue] = FileUserType::detectType($this->request->getQuery('attachedId'));
		if ($type === FileUserType::TYPE_NEW_OBJECT)
		{
			$fileModel = File::loadById((int)$realValue, ['STORAGE']);
			if (!$fileModel)
			{
				$this->addError(new Error('Could not find file'));
				$this->sendJsonErrorResponse();
			}
			if (!$fileModel->canRead($fileModel->getStorage()->getCurrentUserSecurityContext()))
			{
				$this->addError(new Error("Bad permission. Could not read this file"));
				$this->sendJsonErrorResponse();
			}

			$fileName = $fileModel->getName();
			$fileData = $fileModel->getFile();

			if (!$fileData)
			{
				$this->end();
			}

			$cacheTime = 0;

			$width = (int)$this->request->getQuery('width');
			$height = (int)$this->request->getQuery('height');
			$isImage = TypeFile::isImage($fileData['ORIGINAL_NAME']) || TypeFile::isImage($fileName);
			$isImage = $isImage && !TypeFile::shouldTreatImageAsFile($fileData);
			if ($isImage && ($width > 0 || $height > 0))
			{
				$signature = $this->request->getQuery('signature');
				if (!$signature)
				{
					$this->sendJsonInvalidSignResponse('Empty signature');
				}
				if (!ParameterSigner::validateImageSignature($signature, $fileModel->getId(), $width, $height))
				{
					$this->sendJsonInvalidSignResponse('Invalid signature');
				}

				$resizeType = $this->request->getQuery('exact') === 'Y' ? BX_RESIZE_IMAGE_EXACT : BX_RESIZE_IMAGE_PROPORTIONAL;
				$tmpFile = \CFile::resizeImageGet($fileData, ['width' => $width, 'height' => $height], $resizeType, true, false, true);
				$fileData['FILE_SIZE'] = $tmpFile['size'];
				$fileData['SRC'] = $tmpFile['src'];
				$cacheTime = 86400;
			}
			\CFile::viewByUser($fileData, ['force_download' => false, 'cache_time' => $cacheTime, 'attachment_name' => $fileName]);
		}
		else
		{
			$this->addError(new Error('Could not find attached object'));
			$this->sendJsonErrorResponse();
		}
	}

	protected function processActionDeleteFile($attachedId)
	{
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		[$type, $realValue] = FileUserType::detectType($attachedId);

		if ($type == FileUserType::TYPE_NEW_OBJECT)
		{
			$file = File::loadById((int)$realValue, array('STORAGE'));
			if(!$file)
			{
				$this->errorCollection->add(array(new Error("Could not find file")));
				$this->sendJsonErrorResponse();
			}

			if(!$file->canDelete($file->getStorage()->getCurrentUserSecurityContext()))
			{
				$this->errorCollection->add(array(new Error("Bad permission. Could not read this file")));
				$this->sendJsonErrorResponse();
			}

			if($file->countAttachedObjects() != 0)
			{
				$this->errorCollection->add(array(new Error('Could not delete file which attached to entities')));
				$this->sendJsonErrorResponse();
			}

			if($file->getGlobalContentVersion() != 1)
			{
				$this->errorCollection->add(array(new Error('Could not delete file which has a few versions')));
				$this->sendJsonErrorResponse();
			}

			if(!$file->getParent() ||
				$file->getParent()->getCode() != Folder::CODE_FOR_UPLOADED_FILES
			)
			{
				$this->errorCollection->add(array(new Error('Could not delete file which is not located in folder for uploaded files.')));
				$this->sendJsonErrorResponse();
			}

			if(!$file->delete($this->getUser()->getId()))
			{
				$this->errorCollection->add($file->getErrors());
				$this->sendJsonErrorResponse();
			}

			$this->sendJsonSuccessResponse(array(
				'id' => $attachedId,
			));
		}
		else
		{
			$this->errorCollection->add(array(new Error('Could not delete attached object')));
			$this->sendJsonErrorResponse();
		}
	}

	protected function processActionSearchFile($entityType, $entityId, $searchQuery)
	{
		$models = $this->searchObjects($entityType, $entityId, $searchQuery);
		if($models === null)
		{
			$this->sendJsonErrorResponse();
		}

		$urlManager = Driver::getInstance()->getUrlManager();
		$urlForLoadItems = $urlManager->getUrlUfController('loadItems');

		$response = array();
		foreach($models as $item)
		{
			/** @var File|Folder $item */
			$isFolder = $item instanceof Folder;
			if($isFolder)
			{
				continue;
			}
			$id = FileUserType::NEW_FILE_PREFIX.$item->getId();
			$response[$id] = array(
				'id' => $id,
				'type' => 'file',
				'link' => $urlForLoadItems,
				'name' => $item->getName(),
				'size' => $isFolder ? '' : \CFile::formatSize($item->getSize()),
				'sizeInt' => $isFolder ? '' : $item->getSize(),
				'modifyBy' => $item->getUpdateUser()->getFormattedName(),
				'modifyDate' => $item->getUpdateTime()->format('d.m.Y'),
				'modifyDateInt' => $item->getUpdateTime()->getTimestamp(),
				'ext' => $item->getExtension(),
			);
		}
		unset($item);

		$this->sendJsonSuccessResponse(array(
			'items' => $response,
		));
	}

	private function searchObjects($entityType, $entityId, $searchQuery, $limit = 40)
	{
		if($entityType === 'recently_used')
		{
			$recentlyUsedManager = Driver::getInstance()->getRecentlyUsedManager();
			return $recentlyUsedManager->getFileModelListByUser($this->getUser(), array('%NAME' => $searchQuery));
		}
		if($entityType === 'storage')
		{
			$storage = $this->getStorageByType(
				$this->request->getPost('storageType'),
				$this->request->getPost('storageId')
			);

			if(!$storage)
			{
				$this->errorCollection[] = new Error('Could not find storage');
				return null;
			}

			$currentFolderId = Driver::getInstance()->getUrlManager()->resolveFolderIdFromPath($storage, $entityId);
			/** @var Folder $folder */
			$folder = Folder::loadById($currentFolderId);
			if(!$folder)
			{
				$this->errorCollection->add(array(new Error('Could not find folder by path')));
				return null;
			}
			$securityContext = $folder->getStorage()->getCurrentUserSecurityContext();

			return $folder->getDescendants($securityContext, array(
				'with' => array('UPDATE_USER'),
				'filter' => array('%NAME' => $searchQuery),
				'limit' => $limit
			));
		}
		if($entityType === 'all')
		{
			return array();
		}
		$this->errorCollection->add(array(new Error("Could not parse entity type {$entityType}")));

		return null;
	}

	protected function processActionMoveUploadedFile()
	{
		$this->checkRequiredPostParams(array('attachedId', 'targetFolderId'));

		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}
		[$type, $objectId] = FileUserType::detectType($this->request->getPost('attachedId'));
		if($type != FileUserType::TYPE_NEW_OBJECT || !$objectId)
		{
			$this->errorCollection->add(array(new Error('Could not move attached file')));
			$this->sendJsonErrorResponse();
		}

		$targetFolderId = (int)$this->request->getPost('targetFolderId');
		/** @var File $file */
		$file = File::loadById($objectId, array('STORAGE'));
		if(!$file)
		{
			$this->errorCollection->add(array(new Error('Could not find file')));
			$this->sendJsonErrorResponse();
		}
		if($file->getCreatedBy() != $this->getUser()->getId())
		{
			$this->errorCollection->add(array(new Error('Could not move alien file')));
			$this->sendJsonErrorResponse();
		}
		/** @var Folder $targetFolder */
		$targetFolder = Folder::loadById($targetFolderId, array('STORAGE'));
		if(!$targetFolder)
		{
			$this->errorCollection->add(array(new Error('Could not find target folder')));
			$this->sendJsonErrorResponse();
		}
		if(!$file->canMove($file->getStorage()->getCurrentUserSecurityContext(), $targetFolder))
		{
			$this->errorCollection->add(array(new Error('Bad permission. Could not move this file')));
			$this->sendJsonErrorResponse();
		}
		if(!$file->moveToAnotherFolder($targetFolder, $this->getUser()->getId(), true))
		{
			$this->errorCollection->add(array(new Error('Could not move the file')));
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse();
	}

	protected function processActionRenameFile()
	{
		$this->checkRequiredPostParams(array('newName', 'attachedId'));

		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		[$type, $realValue] = FileUserType::detectType($this->request->getPost('attachedId'));

		if ($type == FileUserType::TYPE_NEW_OBJECT)
		{
			/** @var File $model */
			$model = File::loadById((int)$realValue, array('STORAGE'));
			if(!$model)
			{
				$this->errorCollection->add(array(new Error("Could not find file")));
				$this->sendJsonErrorResponse();
			}
			if(!$model->canRename($model->getStorage()->getCurrentUserSecurityContext()))
			{
				$this->errorCollection->add(array(new Error("Bad permission. Could not read this file")));
				$this->sendJsonErrorResponse();
			}
			$newName = Text::correctFilename(($this->request->getPost('newName')));
			if(!$model->renameInternal($newName, true))
			{
				$this->errorCollection->add($model->getErrors());
				$this->sendJsonErrorResponse();
			}

			$this->sendJsonSuccessResponse(array(
				'id' => $this->request->getPost('attachedId'),
				'name' => $model->getName(),
			));
		}
		else
		{
			$this->sendJsonErrorResponse();
		}
	}

	protected function processActionDisableAutoCommentToAttachedObject()
	{
		$this->checkRequiredPostParams(array('attachedId',));
		if($this->setAutoCommentToAttachedObject($this->request->getPost('attachedId'), false))
		{
			$this->sendJsonSuccessResponse();
		}
		$this->sendJsonErrorResponse();
	}

	protected function processActionEnableAutoCommentToAttachedObject()
	{
		$this->checkRequiredPostParams(array('attachedId',));
		if($this->setAutoCommentToAttachedObject($this->request->getPost('attachedId'), true))
		{
			$this->sendJsonSuccessResponse();
		}
		$this->sendJsonErrorResponse();
	}

	protected function processActionGetBreadcrumbs($attachedId)
	{
		/** @var AttachedObject $attachedModel */
		$attachedModel = AttachedObject::loadById((int)$attachedId);
		if (!$attachedModel)
		{
			$this->errorCollection[] = new Error('Could not find attached object');
			$this->sendJsonErrorResponse();
		}

		if (!$attachedModel->canRead($this->getUser()->getId()))
		{
			$this->errorCollection[] = new Error('Bad permission. Could not read this file');
			$this->sendJsonErrorResponse();
		}

		$file = $attachedModel->getObject();
		$crumbs = CrumbStorage::getInstance()->getByObject($file);
		$proxyType = $file->getStorage()->getProxyType();
		if ($proxyType instanceof ProxyType\User)
		{
			$title = $proxyType->getTitleForCurrentUser();
		}
		else
		{
			$title = $proxyType->getEntityTitle();
		}
		array_unshift($crumbs, $title);

		$this->sendJsonSuccessResponse(array(
			'crumbs' => $crumbs,
		));
	}

	private function setAutoCommentToAttachedObject($attachedId, $enable)
	{
		if($this->errorCollection->hasErrors())
		{
			return false;
		}
		/** @var AttachedObject $attachedModel */
		$attachedModel = AttachedObject::loadById((int)$attachedId);
		if(!$attachedModel)
		{
			$this->errorCollection->add(array(new Error('Could not find attached object')));
			return false;
		}
		if($attachedModel->getCreatedBy() != $this->getUser()->getId())
		{
			$this->errorCollection->add(array(new Error('Could not disable comments to another attached object')));
			return false;
		}

		return $enable? $attachedModel->enableAutoComment() : $attachedModel->disableAutoComment();
	}

	protected function sendNeedAuth($authUrl)
	{
		$this->sendJsonResponse(array(
			'status' => self::STATUS_NEED_AUTH,
			'authUrl' => $authUrl,
			'isBitrix24' => ModuleManager::isModuleInstalled('bitrix24'),
		));
	}

	protected function runProcessingIfUserNotAuthorized(): void
	{
		$action = $this->getAction();
		if (\in_array($action, ['download', 'show']))
		{
			return;
		}

		parent::runProcessingIfUserNotAuthorized();
	}
}
