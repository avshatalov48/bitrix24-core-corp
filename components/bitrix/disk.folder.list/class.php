<?php

use Bitrix\Disk\Configuration;
use Bitrix\Disk\Document\DocumentHandler;
use Bitrix\Disk\Integration\Bitrix24Manager;
use Bitrix\Disk\Search\Reindex\BaseObjectIndex;
use Bitrix\Disk\Search\Reindex\ExtendedIndex;
use Bitrix\Disk\Search\Reindex\HeadIndex;
use Bitrix\Disk\Storage;
use Bitrix\Disk\Ui\FileAttributes;
use Bitrix\Disk\ZipNginx;
use Bitrix\Disk\Document\Contract;
use Bitrix\Disk\Document\LocalDocumentController;
use Bitrix\Disk\Driver;
use Bitrix\Disk\ExternalLink;
use Bitrix\Disk\Integration\BizProcManager;
use Bitrix\Disk\Internals\ExternalLinkTable;
use Bitrix\Disk\Internals\Grid\FolderListOptions;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\ProxyType;
use Bitrix\Disk\Internals\DiskComponent;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\SharingTable;
use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Sharing;
use Bitrix\Disk\Ui;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Query\Filter;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Grid;
use Bitrix\Main\Loader;
use Bitrix\Disk\Security\SecurityContext;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Search\Content;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\Viewer\ItemAttributes;
use Bitrix\Main\Web\PostDecodeFilter;
use Bitrix\Main\Web\Uri;
use Bitrix\Socialnetwork;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

Loc::loadMessages(__FILE__);

class CDiskFolderListComponent extends DiskComponent implements \Bitrix\Main\Engine\Contract\Controllerable
{
	const ERROR_COULD_NOT_FIND_OBJECT  = 'DISK_FL_22001';
	const ERROR_COULD_NOT_FIND_SHARING = 'DISK_FL_22002';
	const ERROR_INVALID_DATA_TYPE      = 'DISK_FL_22003';

	const FILTER_WITH_EXTERNAL_LINK = 1;
	const FILTER_SHARED_FROM_ME     = 2;
	const FILTER_SHARED_TO_ME       = 3;

	protected $componentId = 'folder_list';

	protected $trashMode = false;
	/** @var \Bitrix\Disk\Folder */
	protected $folder;
	/** @var  array */
	protected $breadcrumbs;
	/** @var  Bitrix\Disk\Internals\Grid\FolderListOptions */
	protected $gridOptions;
	/** @var string */
	private $information = '';
	/** @var  array */
	private $imageSize = array('width' => 64, 'height' => 64, 'exact' => 'Y');
	private $templateBizProc;

	protected function processBeforeAction($actionName)
	{
		Loader::includeModule('ui');
		parent::processBeforeAction($actionName);

		$this->findFolder();
		$this->buildGridOptions();

		if ($this->request->getQuery('viewMode'))
		{
			$this->gridOptions->storeViewMode($this->request->getQuery('viewMode'));
		}
		if ($this->request->getQuery('viewSize'))
		{
			$this->gridOptions->storeViewSize($this->request->getQuery('viewSize'));
		}
		if ($this->request->getQuery('sortMode') || $this->request->getPost('sortMode'))
		{
			$this->gridOptions->storeSortMode($this->request->getQuery('sortMode')?: $this->request->getPost('sortMode'));
		}

		return true;
	}

	private function findFolder()
	{
		if (Bitrix\Main\Grid\Context::isInternalRequest() && $this->request->getPost('folderId'))
		{
			$this->arParams['FOLDER_ID'] = (int)$this->request->getPost('folderId');
		}

		$this->folder = \Bitrix\Disk\Folder::loadById($this->arParams['FOLDER_ID']);

		if (!$this->folder)
		{
			throw new \Bitrix\Main\SystemException("Invalid file.");
		}

		return $this;
	}

	private function buildGridOptions()
	{
		$viewStorage = null;
		if (
			Bitrix\Main\Grid\Context::isInternalRequest() &&
			($this->request->getPost('viewGridStorageId') || $this->request->get('grid_id'))
		)
		{
			$viewStorageId = (int)$this->request->getPost('viewGridStorageId');
			if (!$viewStorageId && $this->request->get('grid_id'))
			{
				$viewStorageId = (int)FolderListOptions::extractStorageId($this->request->get('grid_id'));
			}

			$viewStorage = \Bitrix\Disk\Storage::loadById($viewStorageId);
		}

		if (!$viewStorage)
		{
			$viewStorage = $this->storage;
		}

		if ($this->isTrashMode())
		{
			$this->gridOptions = new Bitrix\Disk\Internals\Grid\TrashCanOptions($viewStorage);
		}
		else
		{
			$this->gridOptions = new Bitrix\Disk\Internals\Grid\FolderListOptions($viewStorage);
		}
	}

	protected function prepareParams()
	{
		parent::prepareParams();

		if (isset($this->arParams['FOLDER']))
		{
			if (!$this->arParams['FOLDER'] instanceof Folder)
			{
				throw new \Bitrix\Main\ArgumentException('FOLDER not instance of \\Bitrix\\Disk\\Folder');
			}
			$this->arParams['FOLDER_ID'] = $this->arParams['FOLDER']->getId();
		}
		if (!isset($this->arParams['FOLDER_ID']))
		{
			throw new \Bitrix\Main\ArgumentException('FOLDER_ID required');
		}
		$this->arParams['FOLDER_ID'] = (int)$this->arParams['FOLDER_ID'];
		if ($this->arParams['FOLDER_ID'] <= 0)
		{
			throw new \Bitrix\Main\ArgumentException('FOLDER_ID < 0');
		}

		if (empty($this->arParams['PATH_TO_GROUP']))
		{
			$this->arParams['PATH_TO_GROUP'] = '/';
		}

		if (!empty($this->arParams['TRASH_MODE']))
		{
			$this->trashMode = true;
		}

		return $this;
	}

	private function existActionButton($buttonName)
	{
		return $this->getActionButtonValue($buttonName) !== null;
	}

	private function getActionButtonValue($buttonName)
	{
		if (!$this->request->isPost())
		{
			return null;
		}

		$controls = $this->request->getPost('controls');
		if (empty($controls[$buttonName]))
		{
			return null;
		}

		return $controls[$buttonName];
	}

	private function shouldBeBlockAddButtons(Storage $storage): bool
	{
		$proxyType = $storage->getProxyType();
		if (!($proxyType instanceof ProxyType\Common))
		{
			return false;
		}

		return !Bitrix24Manager::isFeatureEnabled('disk_common_storage');
	}

	private function setPageTitle(): void
	{
		$proxyType = $this->storage->getProxyType();
		$this->application->setTitle(htmlspecialcharsbx($proxyType->getTitleForCurrentUser()));

		if ($proxyType instanceof ProxyType\Group)
		{
			$this->application->SetPageProperty('title', Socialnetwork\ComponentHelper::getWorkgroupPageTitle([
				'WORKGROUP_ID' => (int)$this->storage->getEntityId(),
				'TITLE' => $proxyType->getTitle(),
			]));
		}
	}

	protected function processActionDefault()
	{
		$errorsInGridActions = $this->processGridActions();
		$this->setPageTitle();

		$securityContext = $this->storage->getCurrentUserSecurityContext();
		$proxyType = $this->storage->getProxyType();

		$connectedGroupObject = $this->getConnectedGroupObject();
		$filterOptions = new \Bitrix\Main\UI\Filter\Options($this->gridOptions->getGridId());

		$this->arResult = array(
			'GRID_INFORMATION' => $this->information,
			'ERRORS_IN_GRID_ACTIONS' => $errorsInGridActions->toArray(),
			'FILTER' => $this->isTrashMode()? $this->getFilterForTrashMode() : $this->getFilter(),
			'IS_RUNNING_FILTER' => !empty($filterOptions->getSearchString()),
			'GRID' => $this->getGridData(),
			'IS_BITRIX24' => ModuleManager::isModuleInstalled('bitrix24'),
			'IS_TRASH_MODE' => $this->isTrashMode(),
			'FOLDER' => array(
				'ID' => $this->folder->getId(),
				'MODEL' => $this->folder,
				'NAME' => $this->folder->getName(),
				'IS_DELETED' => $this->folder->isDeleted(),
				'CAN_ADD' => $this->folder->canAdd($securityContext),
				'CREATE_TIME' => $this->folder->getCreateTime(),
				'UPDATE_TIME' => $this->folder->getUpdateTime(),
			),
			'STORAGE' => array(
				'ID' => $this->storage->getId(),
				'NAME' => $proxyType->getEntityTitle(),
				'BLOCK_ADD_BUTTONS' => $this->shouldBeBlockAddButtons($this->storage),
				'LINK' => $proxyType->getBaseUrlFolderList(),
				'TRASH_LINK' => $proxyType->getBaseUrlTashcanList(),
				'FILE_LINK_PREFIX' => $proxyType->getBaseUrlFileDetail(),
				'TRASH_FILE_LINK_PREFIX' => $proxyType->getBaseUrlTrashcanFileDetail(),
				'ROOT_OBJECT_ID' => $this->storage->getRootObjectId(),
				'CAN_ADD' => $this->storage->canAdd($securityContext),
				'CAN_CHANGE_RIGHTS_ON_STORAGE' => $this->storage->canChangeRights($securityContext),
				'CAN_CHANGE_SETTINGS_ON_STORAGE' => $this->storage->canChangeSettings($securityContext),
				'CAN_CHANGE_SETTINGS_ON_BIZPROC' => $this->storage->canCreateWorkflow($securityContext),
				'CAN_CHANGE_SETTINGS_ON_BIZPROC_EXCEPT_USER' => $proxyType instanceof ProxyType\User ? false : true,
				'SHOW_BIZPROC' => $this->isItTimeToShowBizProc(),
				'FOR_SOCNET_GROUP' => $proxyType instanceof ProxyType\Group,
				'CONNECTED_SOCNET_GROUP_OBJECT_ID' => isset($connectedGroupObject['LINK_OBJECT_ID'])? $connectedGroupObject['LINK_OBJECT_ID'] : null,
				'NETWORK_DRIVE_LINK' => Driver::getInstance()->getUrlManager()->getHostUrl() . $proxyType->getBaseUrlFolderList(),
			),
			'RELATIVE_PATH' => $this->arParams['RELATIVE_PATH'],
			'RELATIVE_PATH_ENCODED' => $this->getUrlManager()->encodeUrn($this->arParams['RELATIVE_PATH']),
			'BREADCRUMBS' => $this->getBreadcrumbs(),
			'BREADCRUMBS_ROOT' => $this->getRootBreadcrumb(),
			'URL_TO_DETACH_OBJECT' => '?action=detachConnectedObject',
			'ENABLED_MOD_ZIP' => ZipNginx\Configuration::isEnabled(),
			'ENABLED_EXTERNAL_LINK' => Configuration::isEnabledExternalLink(),
			'ENABLED_OBJECT_LOCK' => Configuration::isEnabledObjectLock(),
			'ENABLED_TRASHCAN_TTL' => Configuration::getTrashCanTtl() !== -1,
			'TRASHCAN_TTL' => Configuration::getTrashCanTtl(),
			'CLOUD_DOCUMENT' => $this->getConfigurationOfCloudDocument(),
			'PATH_TO_DISK_VOLUME' => $this->buildPathToDiskVolume(),
			'PATH_TO_USER_TRASHCAN_LIST' => $this->storage->getProxyType()->getBaseUrlTashcanList(),
			'DOCUMENT_HANDLERS' => $this->getDocumentHandlersForCreatingFile(),
			'STATUS_BIZPROC' => $this->storage->isEnabledBizProc() && Loader::includeModule("bizproc"),
			'SHOW_SEARCH_NOTICE' => \Bitrix\Main\Config\Option::get('disk', 'needBaseObjectIndex', 'x') !== 'N',
		);

		if ($this->gridOptions->getViewMode() === FolderListOptions::VIEW_MODE_TILE)
		{
			$this->arResult['TILE_ITEMS'] = [];
			foreach ($this->arResult['GRID']['ROWS'] as $row)
			{
				/** @var BaseObject $object */
				$object = $row['object'];

				$dataSet = null;
				$viewerAttributes = $row['data']['VIEWER_ATTRS'] ?? null;
				if ($viewerAttributes instanceof ItemAttributes)
				{
					$dataSet = $viewerAttributes->toDataSet();
				}

				$info = [
					'id' => $row['id'],
					'name' => $object->getName(),
					'isFolder' => $object instanceof Folder,
					'isFile' => $object instanceof File,
					'canAdd' => $object instanceof Folder && $object->canAdd($securityContext),
					'link' => $row['data']['OPEN_URL'],
					'isSymlink' => !empty($row['data']['SHARED']) || $object->isLink(),
					'isLocked' => $object instanceof File && $object->getLock(),
					'isDraggable' => !$this->isTrashMode(),
					'isDroppable' => !$this->isTrashMode() && ($object instanceof Folder),
					'formattedSize' => $row['columns']['FORMATTED_SIZE'],
					'actions' => $row['actions'],
					'attributes' => $dataSet,
				];

				if ($object instanceof File && \Bitrix\Disk\TypeFile::isImage($object))
				{
					$info['image'] = \Bitrix\Main\Engine\UrlManager::getInstance()->create('disk.api.file.showImage', [
						'fileId' => $object->getId(),
						'signature' => \Bitrix\Disk\Security\ParameterSigner::getImageSignature($object->getId(), 400, 400),
						'width' => 400,
						'height' => 400,
					]);
				}
				elseif ($object instanceof File && $object->getPreviewId())
				{
					$info['image'] = \Bitrix\Main\Engine\UrlManager::getInstance()->create('disk.api.file.showPreview', [
						'fileId' => $object->getId(),
						'signature' => \Bitrix\Disk\Security\ParameterSigner::getImageSignature($object->getId(), 400, 400),
						'width' => 400,
						'height' => 400,
					]);
				}

				$this->arResult['TILE_ITEMS'][] = $info;
			}

			if (Bitrix\Main\Grid\Context::isInternalRequest())
			{
				$this->includeComponentTemplate('only_grid');

				return;
			}
		}

		if ($this->arResult['STORAGE']['SHOW_BIZPROC'])
		{
			$this->appendToResultAutoloadTemplateBizProc();
		}

		$this->includeComponentTemplate();
	}

	private function isItTimeToShowBizProc()
	{
		if ($this->isTrashMode())
		{
			return false;
		}

		return $this->storage->isEnabledBizProc() && BizProcManager::isAvailable();
	}

	private function getGridData()
	{
		$grid = array(
			'ID' => $this->gridOptions->getGridId(),
			'MODE' => $this->gridOptions->getViewMode(),
			'SORT_MODE' => $this->gridOptions->getSortMode(),
			'VIEW_SIZE' => $this->gridOptions->getViewSize(),
		);
		[$grid['SORT'], $grid['SORT_VARS']] = $this->gridOptions->getGridOptionsSorting();

		$possibleColumnForSorting = $this->gridOptions->getPossibleColumnForSorting();
		$visibleColumns = array_combine(
			$this->gridOptions->getVisibleColumns(),
			$this->gridOptions->getVisibleColumns()
		);

		$isEnabledObjectLock = Configuration::isEnabledObjectLock();
		$isEnabledShowExtendedRights = $this->storage->isEnabledShowExtendedRights();
		$isItTimeToShowBizProc = $this->isItTimeToShowBizProc();
		$securityContext = $this->storage->getCurrentUserSecurityContext();
		$proxyType = $this->storage->getProxyType();
		$isStorageCurrentUser = $proxyType instanceof ProxyType\User && $proxyType->getTitleForCurrentUser() != $proxyType->getTitle();

		$pageSize = $this->gridOptions->getPageSize();
		$nav = $this->gridOptions->getNavigation();
		$nav->initFromUri();

		$parameters = Driver::getInstance()->getRightsManager()->addRightsCheck(
			$securityContext,
			[],
			array('ID', 'CREATED_BY')
		);

		$parameters['order'] = $this->gridOptions->getOrderForOrm();
		$parameters = $this->modifyByFilter($parameters);

		$parameters['select'] = ['ID'];
		$parameters['limit'] = $pageSize + 1; // +1 because we want to know about existence next page
		$parameters['offset'] = $nav->getOffset();

		$objectIds = [];
		foreach (ObjectTable::getList($parameters) as $row)
		{
			$objectIds[] = $row['ID'];
		}

		$this->folder->preloadOperationsForSpecifiedObjects($objectIds, $securityContext);
		$isShowFromDifferentLevels = $this->isShowFromDifferentLevels($parameters['filter']);
		$sharedObjectIds = [];
		if (!$this->isTrashMode() && $objectIds)
		{
			$sharedObjectIds = $this->getUserShareObjectIds();
			$crumbStorage = \Bitrix\Disk\CrumbStorage::getInstance();
		}
		else
		{
			$crumbStorage = \Bitrix\Disk\TrashCrumbStorage::getInstance();
		}

		$possibleToDownloadArchive = ZipNginx\Configuration::isEnabled();
		$nowTime = time() + CTimeZone::getOffset();
		$fullFormatWithoutSec = preg_replace('/:s$/', '', CAllDatabase::dateFormatToPHP(CSite::GetDateFormat("FULL")));

		$onlyRead = true;

		$urlManager = Driver::getInstance()->getUrlManager();
		$storageTitle = $proxyType->getTitle();

		$countObjectsOnPage = 0;
		if($this->arParams['RELATIVE_PATH'] !== '/')
		{
			$relativePath = trim($this->arParams['RELATIVE_PATH'], '/');
		}
		else
		{
			$relativePath = '/';
		}

		$parameters = [
			'select' => ['*', 'FILE_CONTENT_TYPE' => 'FILE_CONTENT.CONTENT_TYPE'],
			'with' => $this->buildWithByVisibleColumns($visibleColumns),
			'filter' => [
				'@ID' => $objectIds?: [0],
			],
			'order' => $parameters['order'],
		];
		if ($isEnabledObjectLock && !$this->isTrashMode())
		{
			$parameters['with'][] = 'LOCK';
		}

		$rows = array();
		foreach (Folder::getList($parameters) as $row)
		{
			$countObjectsOnPage++;

			if($countObjectsOnPage > $pageSize)
			{
				break;
			}

			$object = BaseObject::buildFromRow($row, $parameters['with']);
			/** @var File|Folder $object */
			$name = $object->getName();
			$objectId = $object->getId();
			$exportData = array(
				'TYPE' => $object->getType(),
				'NAME' => $name,
				'SHARED' => !empty($sharedObjectIds[$row['ID']]),
				'ID' => $objectId,
			);

			$isFolder = $object instanceof Folder;
			$isFile = !$isFolder;
			$actions = $columns = array();

			if ($isShowFromDifferentLevels)
			{
				$distance = $crumbStorage->calculateDistance($this->folder, $object)?: array();
				$pathFromFolder = trim(implode('/', $distance), '/');

				if (!$isFolder)
				{
					$detailPageFile = $this->getDetailFilePage($object, trim($relativePath . '/' . $pathFromFolder, '/'));
				}
				$listingPage = $this->getListingPage($object, trim($relativePath . '/' . $pathFromFolder, '/'));
			}
			else
			{
				if (!$isFolder)
				{
					$detailPageFile = $this->getDetailFilePage($object, $relativePath);
				}
				$listingPage = $this->getListingPage($object, $relativePath);
			}

			if (
				$onlyRead &&
				($object->canUpdate($securityContext) || $object->canMarkDeleted($securityContext))
			)
			{
				$onlyRead = false;
			}

			if($object->canRead($securityContext))
			{
				if ($isFolder)
				{
					$exportData['OPEN_URL'] = $urlManager->encodeUrn(rtrim($listingPage, '/') . '/' . $object->getOriginalName() . '/');

					$openAction = array(
						'id' => 'open',
						'text' => Loc::getMessage('DISK_FOLDER_LIST_ACT_OPEN'),
						'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_open.svg',
						'className' => 'disk-folder-list-context-menu-item',
						'href' => $exportData['OPEN_URL'],
						'onclick' => "BX.Disk['FolderListClass_{$this->componentId}'].openFolderContextMenu(this, event, {$objectId}, {
							id: {$objectId},
							name: '" . CUtil::JSEscape($name) . "'
						});",
					);
				}
				else
				{
					$exportData['OPEN_URL'] = $urlManager->encodeUrn($detailPageFile);
					$openAction = array(
						'id' => 'open',
						"text" => Loc::getMessage('DISK_FOLDER_LIST_ACT_DETAILS'),
						'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_open.svg',
						'className' => 'disk-folder-list-context-menu-item',
						"href" => $exportData['OPEN_URL'],
					);
				}

				$actions[] = $openAction;

				if ($isFile)
				{
					$actions[] = array(
						'className' => 'disk-folder-list-context-menu-item',
						'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_download.svg',
						"text" => Loc::getMessage('DISK_FOLDER_LIST_ACT_DOWNLOAD'),
						"href" => $urlManager->getUrlForDownloadFile($object),
					);
				}
				elseif ($isFolder && $possibleToDownloadArchive && !$object->isDeleted())
				{
					$uriToDownloadArchive = \Bitrix\Main\Engine\UrlManager::getInstance()->create('disk.api.folder.downloadArchive', [
						'folderId' => $objectId,
					]);

					$actions[] = array(
						'className' => 'disk-folder-list-context-menu-item',
						'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_download.svg',
						'text' => Loc::getMessage('DISK_FOLDER_LIST_ACT_DOWNLOAD'),
						'href' => $uriToDownloadArchive,
					);
				}

				if ($object->isDeleted() && $object->canRestore($securityContext))
				{
					$actions[] = array(
						"text" => Loc::getMessage('DISK_TRASHCAN_ACT_RESTORE'),
						'className' => 'disk-folder-list-context-menu-item',
						"onclick" =>
							"BX.Disk['FolderListClass_{$this->getComponentId()}'].openConfirmRestore({
							object: {
								id: {$object->getId()},
								name: '" . CUtil::JSEscape($name) . "',
								isFolder: " . ($isFolder? 'true' : 'false') . "
							 }
						})",
					);
				}

				if ($isFile && $object->canUpdate($securityContext) && DocumentHandler::isEditable($object->getExtension()))
				{
					$actions[] = array(
						'id' => 'edit',
						'text' => Loc::getMessage('DISK_FOLDER_LIST_ACT_EDIT'),
						'className' => \Bitrix\Disk\UserConfiguration::isSetLocalDocumentService()? 'disk-folder-list-context-menu-item' : 'disk-popup-menu-hidden-item disk-folder-list-context-menu-item',
						'hide' => \Bitrix\Disk\UserConfiguration::isSetLocalDocumentService()? false : true,
						'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_edit.svg',
						'onclick' => "BX.UI.Viewer.Instance.runActionByNode(BX('disk_obj_{$objectId}'), 'edit', {
							modalWindow: BX.Disk.openBlankDocumentPopup()
						});",
					);
				}

				if ($isFolder)
				{
					$internalLink = $urlManager->getUrlFocusController('openFolderList', array('folderId' => $object->getId()), true);
				}
				else
				{
					$internalLink = $urlManager->getUrlFocusController('showObjectInGrid', [
							'objectId' => $object->getId(),
							'cmd' => 'show',
					], true);
				}

				$actionToShare = [];
				if(!$object->isDeleted() && Configuration::isPossibleToShowExternalLinkControl())
				{
					$actionToShare[] = array(
						"text" => Loc::getMessage('DISK_FOLDER_LIST_ACT_GET_EXT_LINK'),
						'className' => 'disk-folder-list-context-menu-item',
						"onclick" =>
							$this->filterB24Feature(
								$isFolder? 'disk_manual_external_folder' : 'disk_manual_external_link',
								"BX.Disk['FolderListClass_{$this->componentId}'].openExternalLinkDetailSettingsWithEditing({$objectId});"
							),
					);
				}

				if (!$object->isDeleted())
				{
					$actionToShare[] = array(
						"id" => "copy-buffer",
						'className' => 'disk-folder-list-context-menu-item',
						'dataset' => [
							'preventCloseContextMenu' => true,
						],
						"text" => Loc::getMessage('DISK_FOLDER_LIST_ACT_COPY_INTERNAL_LINK'),
						"onclick" => "BX.Disk['FolderListClass_{$this->componentId}'].copyLinkInternalLink('{$internalLink}', this);",
					);
				}

				if(!$object->isDeleted() && !$isFolder && $isEnabledObjectLock && $object->canLock($securityContext))
				{
					$actions[] = array(
						"id" => "lock",
						"className" => $object->getLock()? "disk-popup-menu-hidden-item disk-folder-list-context-menu-item" : 'disk-folder-list-context-menu-item',
						"text" => Loc::getMessage('DISK_FOLDER_LIST_ACT_LOCK'),
						'hide' => $object->getLock()? true : false,
						'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_lock.svg',
						"onclick" => "BX.Disk['FolderListClass_{$this->componentId}'].lockFile({
							object: {
								id: {$objectId},
								name: '" . CUtil::JSEscape($name) . "'
							}
						}); BX.fireEvent(document.body, 'click');",
					);
				}
				if (!$object->isDeleted() && !$isFolder && $isEnabledObjectLock && $object->canUnlock($securityContext))
				{
					$actions[] = array(
						"id" => "unlock",
						"className" => !$object->getLock()? "disk-popup-menu-hidden-item disk-folder-list-context-menu-item" : 'disk-folder-list-context-menu-item',
						"text" => Loc::getMessage('DISK_FOLDER_LIST_ACT_UNLOCK'),
						'hide' => !$object->getLock()? true : false,
						'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_unlock.svg',
						"onclick" => "BX.Disk['FolderListClass_{$this->componentId}'].unlockFile({
							object: {
								id: {$objectId},
								name: '" . CUtil::JSEscape($name) . "'
							}
						}); BX.fireEvent(document.body, 'click');",
					);
				}

				if (!$object->isDeleted() && !$object->canChangeRights($securityContext) && !$object->canShare($securityContext))
				{
					$actionToShare[] = array(
						"id" => "share",
						'className' => 'disk-folder-list-context-menu-item',
						"text" => Loc::getMessage('DISK_FOLDER_LIST_ACT_SHOW_SHARING_DETAIL_3'),
						"onclick" =>
							$this->filterB24Feature(
								$isFolder? 'disk_folder_sharing' : 'disk_file_sharing',
								"BX.Disk.showSharingDetailWithoutEdit({
									ajaxUrl: '/bitrix/components/bitrix/disk.folder.list/ajax.php',
									object: {
										id: {$objectId},
										name: '" . CUtil::JSEscape($name) . "',
										isFolder: " . ($isFolder? 'true' : 'false') . "
									 }
								})"
							),
					);
				}
				elseif (!$object->isDeleted() && $object->canChangeRights($securityContext))
				{
					$actionToShare[] = array(
						"id" => "share",
						'className' => 'disk-folder-list-context-menu-item',
						"text" => Loc::getMessage('DISK_FOLDER_LIST_ACT_SHOW_SHARING_DETAIL_3'),
						"onclick" =>
							$this->filterB24Feature(
								$isFolder? 'disk_folder_sharing' : 'disk_file_sharing',
								"BX.Disk['FolderListClass_{$this->componentId}'].showSharingDetailWithChangeRights({
									object: {
										id: {$objectId},
										name: '" . CUtil::JSEscape($name) . "',
										isFolder: " . ($isFolder? 'true' : 'false') . "
									 }
								})"
							),
					);
				}
				elseif (!$object->isDeleted() && $object->canShare($securityContext))
				{
					$actionToShare[] = array(
						"id" => "share",
						'className' => 'disk-folder-list-context-menu-item',
						"text" => Loc::getMessage('DISK_FOLDER_LIST_ACT_SHOW_SHARING_DETAIL_3'),
						"onclick" =>
							$this->filterB24Feature(
								$isFolder? 'disk_folder_sharing' : 'disk_file_sharing',
								"BX.Disk['FolderListClass_{$this->componentId}'].showSharingDetailWithSharing({
									object: {
										id: {$objectId},
										name: '" . CUtil::JSEscape($name) . "',
										isFolder: " . ($isFolder? 'true' : 'false') . "
									 }
								})"
							),
					);
				}

				if ($actionToShare)
				{
					$actions[] = array(
						'id' => 'share-section',
						'text' => Loc::getMessage('DISK_FOLDER_LIST_ACT_SHARE_COMPLEX'),
						'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_share.svg',
						'className' => 'disk-folder-list-context-menu-item',
						'dataset' => [
							'preventCloseContextMenu' => true,
						],
						'items' => $actionToShare,
					);
				}

				if(!$object->isDeleted() && $isEnabledShowExtendedRights && !$object->isLink() && $object->canChangeRights($securityContext))
				{
					$actions[] = array(
						"text" => Loc::getMessage('DISK_FOLDER_LIST_ACT_RIGHTS_SETTINGS'),
						'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_general_access.svg',
						'className' => 'disk-folder-list-context-menu-item',
						"onclick" =>
							$this->filterB24Feature(
								$isFolder? 'disk_folder_rights' : 'disk_file_rights',
								"BX.Disk['FolderListClass_{$this->componentId}'].showRightsOnObjectDetail({
									object: {
										id: {$objectId},
										name: '" . CUtil::JSEscape($name) . "',
										isFolder: " . ($isFolder? 'true' : 'false') . "
									 }
								})"
							),
					);
				}

				if (!$object->isDeleted())
				{
					if($object->canRename($securityContext))
					{
						$actions[] = array(
							"text" => Loc::getMessage('DISK_FOLDER_LIST_ACT_RENAME'),
							'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_rename.svg',
							'className' => 'disk-folder-list-context-menu-item',
							"onclick" => "BX.Disk['FolderListClass_{$this->componentId}'].renameInline({$objectId})",
						);
					}

					$actions[] = array(
						"text" => Loc::getMessage('DISK_FOLDER_LIST_ACT_COPY'),
						'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_copy.svg',
						'className' => 'disk-folder-list-context-menu-item',
						"onclick" => "BX.Disk['FolderListClass_{$this->componentId}'].openCopyModalWindow({
							id: {$this->storage->getRootObjectId()},
							canAdd: " . (int)$this->storage->canAdd($securityContext) . ",
							name: '" . CUtil::JSEscape($storageTitle) . "'
						}, {
							id: {$objectId},
							name: '" . CUtil::JSEscape($name) . "'
						});",
					);

					if($object->canMarkDeleted($securityContext))
					{
						$actions[] = array(
							"text" => Loc::getMessage('DISK_FOLDER_LIST_ACT_MOVE'),
							'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_move.svg',
							'className' => 'disk-folder-list-context-menu-item',
							"onclick" => "BX.Disk['FolderListClass_{$this->componentId}'].openMoveModalWindow({
								id: {$this->storage->getRootObjectId()},
								canAdd: " . (int)$this->storage->canAdd($securityContext) . ",
								name: '" . CUtil::JSEscape($storageTitle) . "'
							}, {
								id: {$objectId},
								name: '" . CUtil::JSEscape($name) . "'
							});",
						);
					}

					if(
						!$isStorageCurrentUser &&
						(!isset($sharedObjectIds[$object->getRealObjectId()]) ||
						$sharedObjectIds[$object->getRealObjectId()]['TO_ENTITY'] != Sharing::CODE_USER . $this->getUser()->getId())
					)
					{
						$actions[] = array(
							'id' => 'connect',
							"text" => Loc::getMessage('DISK_FOLDER_LIST_ACT_CONNECT'),
							'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_connect_to_disk.svg',
							'className' => 'disk-folder-list-context-menu-item',
							"onclick" => "BX.Disk['FolderListClass_{$this->componentId}'].connectObjectToDisk({
								object: {
									id: {$objectId},
									name: '" . CUtil::JSEscape($name) . "',
									isFolder: " . ($isFolder? 'true' : 'false') . "
								}
							});",
						);
					}
				}

				if(!$isFolder)
				{
					$linkOnHistory = CComponentEngine::makePathFromTemplate(
						$this->arParams['PATH_TO_FILE_HISTORY'],
						[
							'FILE_ID' => $object->getId(),
						]
					);

					$actions[] = array(
						'text' => Loc::getMessage('DISK_FOLDER_LIST_ACT_SHOW_HISTORY'),
						'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_history.svg',
						'className' => 'disk-folder-list-context-menu-item',
						'onclick' =>  Bitrix24Manager::isFeatureEnabled('disk_file_history')? "BX.SidePanel.Instance.open('{$linkOnHistory}')" : "BX.UI.InfoHelper.show('limit_office_version_storage');",
					);
				}
			}

			$columnsBizProc = array(
				'BIZPROC' => ''
			);
			$bizprocIcon = array(
				'BIZPROC' => ''
			);
			if($isItTimeToShowBizProc && !$isFolder)
			{
				[$actions, $columnsBizProc, $bizprocIcon] = $this->getBizProcData($object, $securityContext, $actions, $columnsBizProc, $bizprocIcon, $exportData);
			}

			if($object->canMarkDeleted($securityContext))
			{
				if($object->isLink())
				{
					$actions[] = array(
						"id" => "detach",
						'className' => 'disk-folder-list-context-menu-item',
						"text" => Loc::getMessage('DISK_FOLDER_LIST_DETACH_BUTTON'),
						'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_disconnect.svg',
						"onclick" =>
							"BX.Disk['FolderListClass_{$this->componentId}'].openConfirmDetach({
								object: {
									id: {$objectId},
									name: '" . CUtil::JSEscape($name) . "',
									isFolder: " . ($isFolder? 'true' : 'false') . "
								 }
							})",
					);
				}
				elseif($object->getCode() !== Folder::CODE_FOR_UPLOADED_FILES)
				{
					$actions[] = array(
						"text" => Loc::getMessage('DISK_FOLDER_LIST_ACT_MARK_DELETED'),
						'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_remove.svg',
						'className' => 'disk-folder-list-context-menu-item',
						"onclick" =>
							"BX.Disk['FolderListClass_{$this->componentId}'].openConfirmDelete({
								object: {
									id: {$objectId},
									name: '" . CUtil::JSEscape($name) . "',
									isDeleted: " . ($object->isDeleted()? 'true' : 'false') . ",
									isFolder: " . ($isFolder? 'true' : 'false') . "
								 },
								canDelete: " . ($object->canDelete($securityContext)? 'true' : 'false') . "
							})",
					);
				}
			}
			$iconClass = Ui\Icon::getIconClassByObject($object, !empty($sharedObjectIds[$objectId]));
			if($isFolder)
			{
				$nameSpecialChars = htmlspecialcharsbx($name);
				$columnName = "
					<table class=\"bx-disk-object-name\"><tr>
							<td style=\"width: 45px;\">
								<div data-object-id=\"{$objectId}\" class=\"js-disk-grid-open-folder bx-file-icon-container-small {$iconClass}\"></div>
							</td>
							<td><a class=\"bx-disk-folder-title js-disk-grid-folder\" id=\"disk_obj_{$objectId}\" href=\"{$exportData['OPEN_URL']}\" data-object-id=\"{$objectId}\" data-can-add=\"{$object->canAdd($securityContext)}\">{$nameSpecialChars}</a></td>
					</tr></table>
				";
			}
			else
			{
				$sourceUri = new Uri($urlManager->getUrlForDownloadFile($object));
				$fileData = [
					'ID' => $object->getFileId(),
					'CONTENT_TYPE' => $row['FILE_CONTENT_TYPE'],
					'ORIGINAL_NAME' => $object->getName(),
					'FILE_SIZE' => $object->getSize(),
				];
				$attr = $this->buildItemAttributes($object, $sourceUri, $fileData)
					->setTitle($object->getName())
					->setGroupBy($this->componentId)
					->addAction([
						'type' => 'download',
					])
					->addAction([
						'type' => 'copyToMe',
						'text' => Loc::getMessage('DISK_ACTION_SAVE_TO_OWN_FILES'),
						'action' => "BX.Disk.Viewer.Actions.runActionCopyToMe",
						'params' => [
							'objectId' => $objectId,
						],
						'extension' => 'disk.viewer.actions',
						'buttonIconClass' => 'ui-btn-icon-cloud',
					])
				;

				if ($isFile && $object->canUpdate($securityContext))
				{
					$documentName = \CUtil::JSEscape($name);
					$items = [];
					if (DocumentHandler::isEditable($object->getExtension()))
					{
						foreach ($this->getDocumentHandlersForEditingFile() as $handlerData)
						{
							$items[] = [
								'text' => $handlerData['name'],
								'onclick' => "BX.Disk.Viewer.Actions.runActionEdit({name: '" . CUtil::JSEscape($documentName) . "', objectId: {$objectId}, serviceCode: '{$handlerData['code']}'})",
							];
						}
					}

					$attr->addAction([
						'type' => 'edit',
						'buttonIconClass' => ' ',
						'action' => 'BX.Disk.Viewer.Actions.runActionDefaultEdit',
						'params' => [
							'objectId' => $objectId,
							'name' => $documentName,
							'dependsOnService' => $items? null : LocalDocumentController::getCode(),
						],
						'items' => $items,
					]);
				}

				$attr->addAction([
					'type' => 'info',
					'action' => 'BX.Disk.Viewer.Actions.runActionInfo',
					'params' => [
						'objectId' => $objectId,
					],
					'extension' => 'disk.viewer.actions',
				]);


				if($grid['MODE'] === FolderListOptions::VIEW_MODE_TILE)
				{
					$exportData['VIEWER_ATTRS'] = $attr;
				}

				$lockedBy = null;
				$inlineStyleLockIcon = 'style="display:none;"';
				if($isEnabledObjectLock && $object->getLock())
				{
					$lockedBy = $object->getLock()->getCreatedBy();
					$inlineStyleLockIcon = '';
				}

				$nameSpecialChars = htmlspecialcharsbx($name);
				$columnName = "
					<table class=\"bx-disk-object-name\"><tr>
						<td style=\"width: 45px;\">
							<div data-object-id=\"{$objectId}\" class=\"bx-file-icon-container-small {$iconClass}\">
								<div id=\"lock-anchor-created-{$objectId}-{$this->componentId}\" {$inlineStyleLockIcon} class=\"js-lock-icon js-disk-locked-document-tooltip disk-locked-document-block-icon-small-list disk-locked-document-block-icon-small-folder\" data-lock-created-by=\"{$lockedBy}\"></div>
							</div>
						</td>
						<td><span class=\"bx-disk-folder-title\" style='cursor: pointer;' id=\"disk_obj_{$objectId}\" href=\"{$exportData['OPEN_URL']}\" {$attr}>{$nameSpecialChars}</span></td>
						<td>{$bizprocIcon['BIZPROC']}</td>
					</tr></table>
				";
			}

			$timestampCreate = $object->getCreateTime()->toUserTime()->getTimestamp();
			$timestampUpdate = $object->getUpdateTime()->toUserTime()->getTimestamp();
			$timestampDelete = $object->isDeleted()? $object->getDeleteTime()->toUserTime()->getTimestamp() : 0;

			$columns = array(
				'ID' => $objectId,
				'CREATE_TIME' => ($nowTime - $timestampCreate > 158400)? formatDate($fullFormatWithoutSec, $timestampCreate, $nowTime) : formatDate('x', $timestampCreate, $nowTime),
				'UPDATE_TIME' => ($nowTime - $timestampUpdate > 158400)? formatDate($fullFormatWithoutSec, $timestampUpdate, $nowTime) : formatDate('x', $timestampUpdate, $nowTime),
				'DELETE_TIME' => ($nowTime - $timestampDelete > 158400)? formatDate($fullFormatWithoutSec, $timestampDelete, $nowTime) : formatDate('x', $timestampDelete, $nowTime),
				'NAME' => $columnName,
				'FORMATTED_SIZE' => $isFolder? '' : CFile::formatSize($object->getSize()),
			);

			if (isset($visibleColumns['CREATE_USER']))
			{
				$createUser = $object->getCreateUser();
				$createdByLink = \CComponentEngine::makePathFromTemplate(
					$this->arParams['PATH_TO_USER'],
					array('user_id' => $object->getCreatedBy())
				);

				$columns['CREATE_USER'] = "
					<div class=\"bx-disk-user-link\"><span class=\"bx-disk-fileinfo-owner-avatar\" style=\"background-image: url('" . Uri::urnEncode($createUser->getAvatarSrc()) . "');\"></span><a target='_blank' href=\"{$createdByLink}\" id=\"\">" . htmlspecialcharsbx(
						$createUser->getFormattedName()) . "</a></div>
				";
			}

			if (isset($visibleColumns['UPDATE_USER']))
			{
				$updateUser = $object->getUpdateUser()?: $object->getCreateUser();
				$updatedByLink = \CComponentEngine::makePathFromTemplate(
					$this->arParams['PATH_TO_USER'],
					array('user_id' => $updateUser->getId())
				);

				$columns['UPDATE_USER'] = "
					<div class=\"bx-disk-user-link\"><span class=\"bx-disk-fileinfo-owner-avatar\" style=\"background-image: url('" . Uri::urnEncode($updateUser->getAvatarSrc()) . "');\"></span><a target='_blank' href=\"{$updatedByLink}\" id=\"\">" . htmlspecialcharsbx(
						$updateUser->getFormattedName()) . "</a></div>
				";
			}

			if (isset($visibleColumns['DELETE_USER']))
			{
				$deleteUser = $object->getDeleteUser()?: $object->getCreateUser();
				$deletedByLink = \CComponentEngine::makePathFromTemplate(
					$this->arParams['PATH_TO_USER'],
					array('user_id' => $deleteUser->getId())
				);

				$columns['DELETE_USER'] = "
					<div class=\"bx-disk-user-link\"><span class=\"bx-disk-fileinfo-owner-avatar\" style=\"background-image: url('" . Uri::urnEncode($deleteUser->getAvatarSrc()) . "');\"></span><a target='_blank' href=\"{$deletedByLink}\" id=\"\">" . htmlspecialcharsbx(
						$deleteUser->getFormattedName()) . "</a></div>
				";
			}

			if($isItTimeToShowBizProc)
			{
				$columns['BIZPROC'] = $columnsBizProc["BIZPROC"];
			}

			$rows[] = array(
				'id' => $objectId,
				'object' => $object,
				'data' => $exportData,
				'columns' => $columns,
				'attrs' => array(
					'data-can-destroy' => $object->canDelete($securityContext),
					'data-is-folder' => $isFolder,
					'data-is-file' => !$isFolder,
				),
				'actions' => $actions,
			);
		}

		$nav->setRecordCount($nav->getOffset() + $countObjectsOnPage);

		$grid['HEADERS'] = $this->getGridHeaders();
		$grid['NAV_OBJECT'] = $nav;

		$grid['COLUMN_FOR_SORTING'] = $possibleColumnForSorting;
		$grid['ROWS'] = $rows;
		$grid['ACTION_PANEL'] = $this->getGroupActions(array(
			'edit' => !$onlyRead,
			'delete' => !$onlyRead,
		));

		$grid['ONLY_READ_ACTIONS'] = $onlyRead;

		return $grid;
	}

	private function filterB24Feature($feature, $js, $skip = false)
	{
		return Bitrix24Manager::filterJsAction($feature, $js, $skip);
	}

	protected function getGroupActions(array $configuration = array())
	{
		$snippet = new \Bitrix\Main\Grid\Panel\Snippet();

		$deleteButton = array(
			'ICON' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_remove.svg',
			'TYPE' => Grid\Panel\Types::BUTTON,
			'NAME' => Loc::getMessage('DISK_FOLDER_LIST_ACT_MARK_DELETED'),
			'TEXT' => Loc::getMessage('DISK_FOLDER_LIST_ACT_MARK_DELETED'),
			'VALUE' => 'delete',
			'ONCHANGE' => array(
				array(
					'ACTION' => Grid\Panel\Actions::CALLBACK,
					'DATA' => array(
						array(
							'JS' => "BX.Disk['FolderListClass_{$this->componentId}'].openConfirmDeleteGroup()",
						),
					),
				),
			),
		);
		$destroyLink = array(
			'ICON' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_remove.svg',
			'NAME' => Loc::getMessage('DISK_TRASHCAN_ACT_DESTROY'),
			'TEXT' => Loc::getMessage('DISK_TRASHCAN_ACT_DESTROY'),
			'TYPE' => Grid\Panel\Types::BUTTON,
			'VALUE' => 'destroy',
			'ONCHANGE' => array(
				array(
					'ACTION' => Grid\Panel\Actions::CALLBACK,
					'DATA' => array(
						array(
							'JS' => "BX.Disk['FolderListClass_{$this->componentId}'].openConfirmDestroyGroup()",
						),
					),
				),
			),
		);
		$restoreLink = array(
			"TYPE" => Grid\Panel\Types::BUTTON,
			'NAME' => Loc::getMessage('DISK_TRASHCAN_ACT_RESTORE'),
			'TEXT' => Loc::getMessage('DISK_TRASHCAN_ACT_RESTORE'),
			'VALUE' => 'restore',
			'ONCHANGE' => array(
				array(
					'ACTION' => Grid\Panel\Actions::CALLBACK,
					'CONFIRM' => true,
					'CONFIRM_APPLY_BUTTON' => Loc::getMessage('DISK_TRASHCAN_ACT_RESTORE'),
					'DATA' => array(
						array(
							'JS' => "BX.Disk['FolderListClass_{$this->componentId}'].processGridGroupActionRestore()",
						),
					),
				),
			),
		);

		$copyButton = array(
			'ICON' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_copy.svg',
			'TYPE' => Grid\Panel\Types::BUTTON,
			'NAME' => Loc::getMessage('DISK_FOLDER_LIST_ACT_COPY'),
			'TEXT' => Loc::getMessage('DISK_FOLDER_LIST_ACT_COPY'),
			'VALUE' => 'copy',
			'ONCHANGE' => array(
				array(
					'ACTION' => Grid\Panel\Actions::CALLBACK,
					'DATA' => array(
						array(
							'JS' => "BX.Disk['FolderListClass_{$this->componentId}'].openConfirmCopyGroup()",
						),
					),
				),
			),
		);
		$moveButton = array(
			'ICON' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_move.svg',
			'TYPE' => Grid\Panel\Types::BUTTON,
			'NAME' => Loc::getMessage('DISK_FOLDER_LIST_ACT_MOVE'),
			'TEXT' => Loc::getMessage('DISK_FOLDER_LIST_ACT_MOVE'),
			'VALUE' => 'move',
			'ONCHANGE' => array(
				array(
					'ACTION' => Grid\Panel\Actions::CALLBACK,
					'DATA' => array(
						array(
							'JS' => "BX.Disk['FolderListClass_{$this->componentId}'].openConfirmMoveGroup()",
						),
					),
				),
			),
		);
		$downloadButton = array(
			'ICON' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_download.svg',
			'TYPE' => Grid\Panel\Types::BUTTON,
			'NAME' => Loc::getMessage('DISK_FOLDER_LIST_ACT_DOWNLOAD'),
			'TEXT' => Loc::getMessage('DISK_FOLDER_LIST_ACT_DOWNLOAD'),
			'VALUE' => 'download',
			'ONCHANGE' => array(
				array(
					'ACTION' => Grid\Panel\Actions::CALLBACK,
					'DATA' => array(
						array(
							'JS' => "BX.Disk['FolderListClass_{$this->componentId}'].downloadGroup()",
						),
					),
				),
			),
		);

		if ($this->isTrashMode())
		{
			$buttons = array(
				$restoreLink,
				$destroyLink,
			);
		}
		else
		{
			$buttons = array(
				ZipNginx\Configuration::isEnabled()? $downloadButton : null,
				$copyButton,
				$moveButton,
				$configuration['edit']? $snippet->getEditAction() : null,
				$configuration['delete']? $deleteButton : null,
			);
		}

		return [
			'GROUPS' => [
				[
					'ITEMS' => array_values(array_filter($buttons)),
				],
			]
		];
	}

	private function buildItemAttributes(File $file, Uri $sourceUri, array $possibleFileData = [])
	{
		if (isset($possibleFileData['ID']) && isset($possibleFileData['CONTENT_TYPE']))
		{
			return FileAttributes::buildByFileData($possibleFileData, $sourceUri)->setObjectId($file->getId());
		}

		return FileAttributes::tryBuildByFileId($file->getFileId(), $sourceUri)
			->setObjectId($file->getId())
		;
	}

	private function getListingPage(BaseObject $object, $relativePath = null)
	{
		if ($this->isTrashMode())
		{
			if ($relativePath)
			{
				return rtrim(CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_TRASHCAN_LIST'], array(
					'TRASH_PATH' => $relativePath,
				)), '/');
			}

			return $this->getUrlManager()->getPathInTrashcanListing($object);
		}

		if ($relativePath)
		{
			return rtrim(CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_FOLDER_LIST'], array(
				'PATH' => $relativePath,
			)), '/');
		}

		return $this->getUrlManager()->getPathFolderList($object->getParent());
	}

	private function getDetailFilePage(File $file, $relativePath = null)
	{
		if ($this->isTrashMode())
		{
			if ($relativePath)
			{
				return CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_TRASHCAN_FILE_VIEW'], array(
					'FILE_ID' => $file->getId(),
					'TRASH_FILE_PATH' => ltrim($relativePath . '/' . $file->getOriginalName(), '/'),
				));

			}

			return $this->getUrlManager()->getPathTrashcanFileDetail($file);
		}

		if ($relativePath)
		{
			return CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_FILE_VIEW'], array(
				'FILE_ID' => $file->getId(),
				'FILE_PATH' => ltrim($relativePath . '/' . $file->getName(), '/'),
			));

		}

		return $this->getUrlManager()->getPathFileDetail($file);
	}

	private function isShowFromDifferentLevels(array $filter = array())
	{
		return !empty($filter['PATH_CHILD.PARENT_ID']);
	}

	private function buildWithByVisibleColumns(array $visibleColumns)
	{
		return array_intersect_key(
			$visibleColumns,
			array(
				'CREATE_USER' => true,
				'UPDATE_USER' => true,
				'DELETE_USER' => true,
			)
		);
	}

	private function modifyByFilter(array $parameters)
	{
		$filterOptions = new \Bitrix\Main\UI\Filter\Options($this->gridOptions->getGridId());
		if ($this->request->getPost('resetFilter') || $this->request->get('resetFilter'))
		{
			$filterOptions->reset();
		}

		$filterData = $filterOptions->getFilter();

		$filter = [];
		if ($this->isTrashMode())
		{
			//shown trash can root
			if ($this->arParams['RELATIVE_PATH'] == '/')
			{
				$filter['DELETED_TYPE'] = ObjectTable::DELETED_TYPE_ROOT;
				$filter['PATH_CHILD.PARENT_ID'] = $this->folder->getRealObjectId();
				$filter['!PATH_CHILD.OBJECT_ID'] = $this->folder->getRealObjectId();
			}
			else
			{
				$filter['PARENT_ID'] = $this->folder->getId();
				$filter['DELETED_TYPE'] = ObjectTable::DELETED_TYPE_CHILD;
			}

			$ttl = Configuration::getTrashCanTtl();
			if ($ttl !== -1)
			{
				$filter['>DELETE_TIME'] = DateTime::createFromTimestamp(time() - $ttl * 86400);
			}
		}
		else
		{
			$filter = array(
				'PARENT_ID' => $this->folder->getRealObjectId(),
				'DELETED_TYPE' => ObjectTable::DELETED_TYPE_NONE,
			);
		}

		$fulltextContent = null;
		if (array_key_exists('FIND', $filterData) && !empty($filterData['FIND']))
		{
			$fulltextContent = \Bitrix\Disk\Search\FullTextBuilder::create()
				->addText(trim($filterData['FIND']))
				->getSearchValue()
			;
		}

		if (
			!array_key_exists('FILTER_APPLIED', $filterData) ||
			$filterData['FILTER_APPLIED'] != true
		)
		{
			$parameters['filter'] = array_merge($parameters['filter'], $filter);

			return $parameters;
		}

		if ($this->isTrashMode())
		{
			unset($filter['DELETED_TYPE'], $filter['PARENT_ID']);
			$filter['!=DELETED_TYPE'] = ObjectTable::DELETED_TYPE_NONE;
		}

		//when we are searching we use only this way to sort data
		$parameters['order'] = $this->gridOptions->getDefaultSorting();

		if ($fulltextContent && Content::canUseFulltextSearch($fulltextContent))
		{
			if (HeadIndex::isReady() && empty($filterData['SEARCH_BY_CONTENT']))
			{
				$filter["*HEAD_INDEX.SEARCH_INDEX"] = $fulltextContent;
			}
			elseif
			(
				!empty($filterData['SEARCH_BY_CONTENT']) &&
				Configuration::allowUseExtendedFullText() &&
				ExtendedIndex::isReady()
			)
			{
				$filter["*EXTENDED_INDEX.SEARCH_INDEX"] = $fulltextContent;
			}
			elseif
			(
				(!empty($filterData['SEARCH_BY_CONTENT']) || !HeadIndex::isReady()) &&
				BaseObjectIndex::isReady()
			)
			{
				$filter["*SEARCH_INDEX"] = $fulltextContent;
			}
		}

		if (!empty($filterData['SEARCH_IN_CURRENT_FOLDER']))
		{
			$filter['PARENT_ID'] = $this->folder->getRealObjectId();
		}
		else
		{
			$filter['PATH_CHILD.PARENT_ID'] = $this->folder->getRealObjectId();
			$filter['!PATH_CHILD.OBJECT_ID'] = $this->folder->getRealObjectId();

			unset($filter['PARENT_ID']);
		}

		if (!empty($filterData['NAME']))
		{
			$filter['%=NAME'] = str_replace('%', '', $filterData['NAME']) . '%';
		}

		if (!empty($filterData['CREATED_BY']))
		{
			$filter['CREATED_BY'] = (int)$filterData['CREATED_BY'];
		}

		if (!empty($filterData['ID_from']))
		{
			$filter['>=ID'] = (int)$filterData['ID_from'];
		}
		if (!empty($filterData['ID_to']))
		{
			$filter['<=ID'] = (int)$filterData['ID_to'];
		}

		if (!empty($filterData['CREATE_TIME_from']))
		{
			try
			{
				$filter['>=CREATE_TIME'] = new DateTime($filterData['CREATE_TIME_from']);
			}
			catch (Exception $e)
			{}
		}

		if (!empty($filterData['CREATE_TIME_to']))
		{
			try
			{
				$filter['<=CREATE_TIME'] = new DateTime($filterData['CREATE_TIME_to']);
			}
			catch (Exception $e)
			{}
		}

		if (!empty($filterData['UPDATE_TIME_from']))
		{
			try
			{
				$filter['>=UPDATE_TIME'] = new DateTime($filterData['UPDATE_TIME_from']);
			}
			catch (Exception $e)
			{}
		}

		if (!empty($filterData['UPDATE_TIME_to']))
		{
			try
			{
				$filter['<=UPDATE_TIME'] = new DateTime($filterData['UPDATE_TIME_to']);
			}
			catch (Exception $e)
			{}
		}

		if (!empty($filterData['DELETE_TIME_from']))
		{
			try
			{
				$filter['>=DELETE_TIME'] = new DateTime($filterData['DELETE_TIME_from']);
			}
			catch (Exception $e)
			{}
		}

		if (!empty($filterData['DELETE_TIME_to']))
		{
			try
			{
				$filter['<=DELETE_TIME'] = new DateTime($filterData['DELETE_TIME_to']);
			}
			catch (Exception $e)
			{}
		}

		if (!empty($filterData['WITH_EXTERNAL_LINK']))
		{
			$filter[ExternalLinkTable::className() . ':OBJECT.CREATED_BY'] = $this->getUser()->getId();
			$filter[ExternalLinkTable::className() . ':OBJECT.IS_EXPIRED'] = 0;
			$filter[ExternalLinkTable::className() . ':OBJECT.TYPE'] = ExternalLink::TYPE_MANUAL;
		}

		if (!empty($filterData['SHARED']))
		{
			if ($filterData['SHARED'] == self::FILTER_SHARED_TO_ME)
			{
				$filter[SharingTable::className() . ':LINK_OBJECT.LINK_STORAGE_ID'] = $this->storage->getId();
				$filter['=' . SharingTable::className() . ':LINK_OBJECT.TO_ENTITY'] = Sharing::CODE_USER . $this->getUser()->getId();
				$filter['!='. SharingTable::className() . ':LINK_OBJECT.FROM_ENTITY'] = Sharing::CODE_USER . $this->getUser()->getId();
			}
			if ($filterData['SHARED'] == self::FILTER_SHARED_FROM_ME)
			{
				$fromEntity = Application::getConnection()->getSqlHelper()->forSql(Sharing::CODE_USER . $this->getUser()->getId());
				$parameters['runtime'][] = new ExpressionField(
					'HAS_SHARING',
					'CASE WHEN EXISTS (
							SELECT 1 FROM b_disk_sharing s
							WHERE s.REAL_OBJECT_ID = %1$s AND s.REAL_STORAGE_ID = %2$s AND
							s.STATUS = ' . SharingTable::STATUS_IS_APPROVED . ' AND s.FROM_ENTITY = "' . $fromEntity . '"
						)
					THEN 1 ELSE 0 END',
					array('REAL_OBJECT_ID', 'STORAGE_ID'),
					array('data_type' => 'boolean',)
				);

				$filter['HAS_SHARING'] = 1;
			}
		}

		$parameters['filter'] = array_merge($parameters['filter'], $filter);

		return $parameters;
	}

	private function getUserShareObjectIds()
	{
		$sharedObjectIds = array();
		foreach(SharingTable::getList(array(
			'select' => array('REAL_OBJECT_ID', 'TO_ENTITY', 'FROM_ENTITY'),
			'filter' => array(
				array(
					'LOGIC' => 'OR',
					'=TO_ENTITY' => Sharing::CODE_USER . $this->getUser()->getId(),
					'=FROM_ENTITY' => Sharing::CODE_USER . $this->getUser()->getId(),
				),
				'!=STATUS' => SharingTable::STATUS_IS_DECLINED,
				'REAL_STORAGE_ID' => $this->folder->getStorageId(),
			),
		))->fetchAll() as $row)
		{
			$sharedObjectIds[$row['REAL_OBJECT_ID']] = $row;
		}
		unset($row);

		return $sharedObjectIds;
	}

	private function appendToResultAutoloadTemplateBizProc()
	{
		$this->arResult['WORKFLOW_TEMPLATES'] = array();
		$this->arResult['BIZPROC_PARAMETERS'] = false;

		$documentData = array(
			'DISK' => array(
				'DOCUMENT_TYPE' => \Bitrix\Disk\BizProcDocument::generateDocumentComplexType($this->storage->getId()),
			),
			'WEBDAV' => array(
				'DOCUMENT_TYPE' => \Bitrix\Disk\BizProcDocumentCompatible::generateDocumentComplexType($this->storage->getId()),
			),
		);

		foreach ($documentData as $nameModule => $data)
		{
			$workflowTemplateObject = CBPWorkflowTemplateLoader::getList(
				array(),
				array(
					"DOCUMENT_TYPE" => $data["DOCUMENT_TYPE"],
					"AUTO_EXECUTE" => CBPDocumentEventType::Create,
					"ACTIVE" => "Y",
					"!PARAMETERS" => null
				),
				false,
				false,
				array("ID", "NAME", "DESCRIPTION", "PARAMETERS")
			);
			while ($workflowTemplate = $workflowTemplateObject->getNext())
			{
				if (!empty($workflowTemplate['PARAMETERS']))
				{
					$this->arResult['BIZPROC_PARAMETERS'] = true;
					$this->arResult['WORKFLOW_TEMPLATES'][$workflowTemplate['ID']]['PARAMETERS'] = $workflowTemplate['PARAMETERS'];
				}
				$this->arResult['WORKFLOW_TEMPLATES'][$workflowTemplate['ID']]['ID'] = $workflowTemplate['ID'];
				$this->arResult['WORKFLOW_TEMPLATES'][$workflowTemplate['ID']]['NAME'] = $workflowTemplate['NAME'];
			}
		}
	}

	private function getTemplateBizProc($documentData)
	{
		$temporary = array();
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
					$old = '';
					$templateName = $workflowTemplate["NAME"];
				}
				else
				{
					$old = '&old=1';
					$templateName = $workflowTemplate["NAME"]." ".Loc::getMessage('DISK_FOLDER_LIST_ACT_BIZPROC_OLD_TEMPLATE');
				}
				$url = $this->arParams['PATH_TO_DISK_START_BIZPROC'];
				$url .= "?back_url=".urlencode($this->application->getCurPageParam());
				$url .= (mb_strpos($url, "?") === false ? "?" : "&")."workflow_template_id=".$workflowTemplate["ID"].$old.'&'.bitrix_sessid_get();
				$temporary[$workflowTemplate["ID"]] = $workflowTemplate;
				$temporary[$workflowTemplate["ID"]]['NAME'] = $templateName;
				$temporary[$workflowTemplate["ID"]]['URL'] = $url;
			}
		}
		return $temporary;
	}

	private function getBizProcData(File $object, SecurityContext $securityContext, array $actions, array $columnsBizProc, array $bizprocIcon, array $exportData)
	{
		$documentData = array(
			'DISK'   => array(
				'DOCUMENT_TYPE' => \Bitrix\Disk\BizProcDocument::generateDocumentComplexType($this->storage->getId()),
				'DOCUMENT_ID'   => \Bitrix\Disk\BizProcDocument::getDocumentComplexId($object->getId()),
			),
			'WEBDAV' => array(
				'DOCUMENT_TYPE' => \Bitrix\Disk\BizProcDocumentCompatible::generateDocumentComplexType($this->storage->getId()),
				'DOCUMENT_ID'   => \Bitrix\Disk\BizProcDocumentCompatible::getDocumentComplexId($object->getId()),
			),
		);

		if ($this->templateBizProc === null)
		{
			$this->templateBizProc = $this->getTemplateBizProc($documentData);
		}

		$listBpTemplates = array();
		foreach ($this->templateBizProc as $idTemplate => $valueTemplate)
		{
			$params = \Bitrix\Main\Web\Json::encode(
				array(
					'moduleId' => $valueTemplate['DOCUMENT_TYPE'][0],
					'entity' => $valueTemplate['DOCUMENT_TYPE'][1],
					'documentType' => $valueTemplate['DOCUMENT_TYPE'][2],
					'documentId' => $object->getId(),
					'templateId' => $idTemplate,
					'templateName' => $valueTemplate['NAME'],
					'hasParameters' => !empty($valueTemplate['PARAMETERS']),
				)
			);

			$listBpTemplates[] = array(
				"text" => $valueTemplate['NAME'],
				"onclick" => "BX.Bizproc.Starter.singleStart({$params}, function(){ BX.Disk.showModalWithStatusAction({status: 'success'}); });",
			);
		}

		if ($object->canStartBizProc($securityContext) && !empty($listBpTemplates))
		{
			$actions[] = array(
				"className" => 'disk-folder-list-context-menu-item',
				"text" => Loc::getMessage("DISK_FOLDER_LIST_ACT_START_BIZPROC"),
				'dataset' => [
					'preventCloseContextMenu' => true,
				],
				"items" => $listBpTemplates
			);
		}

		$webdavFileId = $object->getXmlId();
		if (!empty($webdavFileId))
		{
			if (Loader::includeModule("iblock"))
			{
				if ($this->storage->getProxyType() instanceof ProxyType\Group)
				{
					$iblock = CIBlockElement::getList(array(), array("ID" => $webdavFileId, 'SHOW_NEW' => 'Y'), false, false, array('ID', 'IBLOCK_ID'))->fetch();
					$entity = 'CIBlockDocumentWebdavSocnet';
				}
				else
				{
					$iblock = CIBlockElement::getList(array(), array("ID" => $webdavFileId, 'SHOW_NEW' => 'Y'), false, false, array('ID', 'IBLOCK_ID'))->fetch();
					$entity = 'CIBlockDocumentWebdav';
				}
				if (!empty($iblock))
				{
					$documentData['OLD_FILE'] = array(
						'DOCUMENT_TYPE' => array('webdav', $entity, "iblock_".$iblock['IBLOCK_ID']),
						'DOCUMENT_ID'   => array('webdav', $entity, $iblock['ID']),
					);
				}
			}
		}

		foreach ($documentData as $nameModuleId => $data)
		{
			$temporary[$nameModuleId] = CBPDocument::getDocumentStates($data['DOCUMENT_TYPE'], $data['DOCUMENT_ID']);
		}
		if (isset($temporary['OLD_FILE']))
		{
			$documentStates = array_merge($temporary['DISK'], $temporary['WEBDAV'], $temporary['OLD_FILE']);
		}
		else
		{
			$documentStates = array_merge($temporary['DISK'], $temporary['WEBDAV']);
		}
		foreach ($documentStates as $key => $documentState)
		{
			if (empty($documentState['ID']))
			{
				unset($documentStates[$key]);
			}
		}
		$columnsBizProc['BIZPROC'] = "";
		$bizprocIcon['BIZPROC'] = "";
		if (!empty($documentStates))
		{
			if (count($documentStates) == 1)
			{
				$documentState = reset($documentStates);
				if ($documentState['WORKFLOW_STATUS'] > 0 || empty($documentState['WORKFLOW_STATUS']))
				{
					$tasksWorkflow = CBPDocument::getUserTasksForWorkflow($this->getUser()->GetID(), $documentState["ID"]);
					$columnsBizProc["BIZPROC"] =
						'<div class="bizproc-item-title">'.htmlspecialcharsbx($documentState["TEMPLATE_NAME"]).': '.
						'<span class="bizproc-item-title bizproc-state-title" style="">'.
						'<a href="'.$exportData["OPEN_URL"].'?action=showBp">'.
						($documentState["STATE_TITLE"] <> '' ? htmlspecialcharsbx($documentState["STATE_TITLE"]) : htmlspecialcharsbx($documentState["STATE_NAME"])).
						'</a>'.
						'</span>'.
						'</div>';
					$columnsBizProc['BIZPROC'] = str_replace("'", "\"", $columnsBizProc['BIZPROC']);

					$bizprocIcon["BIZPROC"] = "<div class=\"element-bizproc-status bizproc-statuses ".
						(!($documentState["ID"] == '' || $documentState["WORKFLOW_STATUS"] == '') ?
							'bizproc-status-'.(empty($tasksWorkflow) ? "inprogress" : "attention") : '').
						"\" onmouseover='BX.hint(this, \"".addslashes($columnsBizProc["BIZPROC"])."\")'></div>";

					if (!empty($tasksWorkflow))
					{
						$tmp = array();
						foreach ($tasksWorkflow as $val)
						{
							$url = CComponentEngine::makePathFromTemplate($this->arParams["PATH_TO_DISK_TASK"], array("ID" => $val["ID"]));
							$url .= "?back_url=".urlencode($this->application->getCurPageParam());
							$tmp[] = '<a href="'.$url.'">'.$val["NAME"].'</a>';
						}
						$columnsBizProc["BIZPROC"] .= '<div class="bizproc-tasks">'.implode(", ", $tmp).'</div>';

						return array($actions, $columnsBizProc, $bizprocIcon);
					}

					return array($actions, $columnsBizProc, $bizprocIcon);
				}

				return array($actions, $columnsBizProc, $bizprocIcon);
			}
			else
			{
				$tasks = array();
				$inprogress = false;
				foreach ($documentStates as $key => $documentState)
				{
					if ($documentState['WORKFLOW_STATUS'] > 0 || empty($documentState['WORKFLOW_STATUS']))
					{
						$tasksWorkflow = CBPDocument::getUserTasksForWorkflow($this->getUser()->GetID(), $documentState["ID"]);
						if (!$inprogress)
							$inprogress = ($documentState['ID'] <> '' && $documentState['WORKFLOW_STATUS'] <> '');
						if (!empty($tasksWorkflow))
						{
							foreach ($tasksWorkflow as $val)
							{
								$tasks[] = $val;
							}
						}
					}
				}

				$columnsBizProc["BIZPROC"] =
					'<span class="bizproc-item-title">'.
					Loc::getMessage("DISK_FOLDER_LIST_GRID_BIZPROC").': <a href="'.$exportData["OPEN_URL"].'?action=showBp" title="'.
					Loc::getMessage("DISK_FOLDER_LIST_GRID_BIZPROC_TITLE").'">'.count($documentStates).'</a></span>'.
					(!empty($tasks) ?
						'<br /><span class="bizproc-item-title">'.
						Loc::getMessage("DISK_FOLDER_LIST_GRID_BIZPROC_TASKS").': <a href="'.$this->arParams["PATH_TO_DISK_TASK_LIST"].'" title="'.
						Loc::getMessage("DISK_FOLDER_LIST_GRID_BIZPROC_TASKS_TITLE").'">'.count($tasks).'</a></span>' : '');
				$bizprocIcon["BIZPROC"] = "<div class=\"element-bizproc-status bizproc-statuses ".
					($inprogress ? ' bizproc-status-'.(empty($tasks) ? "inprogress" : "attention") : '').
					"\" onmouseover='BX.hint(this, \"".addslashes($columnsBizProc['BIZPROC'])."\")'></div>";

				return array($actions, $columnsBizProc, $bizprocIcon);
			}
		}

		return array($actions, $columnsBizProc, $bizprocIcon);
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

	private function getDocumentHandlersForCreatingFile()
	{
		$handlers = array();
		foreach ($this->listCloudHandlersForCreatingFile() as $handler)
		{
			$handlers[] = array(
				'code' => $handler::getCode(),
				'name' => $handler::getName(),
			);
		}

		return array_merge($handlers, array(array(
			'code' => LocalDocumentController::getCode(),
			'name' => LocalDocumentController::getName(),
		)));
	}

	/**
	 * @return DocumentHandler[]
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
			if ($handler instanceof Contract\FileCreatable)
			{
				$list[] = $handler;
			}
		}

		return $list;
	}

	private function buildPathToDiskVolume()
	{
		$path = null;
		if (!empty($this->arParams["PATH_TO_USER_DISK_VOLUME"]))
		{
			$path = $this->arParams["PATH_TO_USER_DISK_VOLUME"];
		}
		elseif (!empty($this->arParams["PATH_TO_DISK_VOLUME"]))
		{
			$path = $this->arParams["PATH_TO_DISK_VOLUME"];
		}

		$proxyType = $this->storage->getProxyType();
		$isUserStorage = $proxyType instanceof ProxyType\User;
		if ($path && $isUserStorage)
		{
			return CComponentEngine::MakePathFromTemplate(
				$path,
				array(
					'ACTION' => '',
					'user_id' => CurrentUser::get()->getId()
				)
			);
		}

		return '';
	}

	private function getConnectedGroupObject()
	{
		$proxyType = $this->storage->getProxyType();
		$isGroupStorage = $proxyType instanceof ProxyType\Group;
		$groupSharingData = array();
		if (!$isGroupStorage)
		{
			return null;
		}

		$isConnectedGroupStorage = Sharing::isConnectedToUserStorage($this->getUser()->getId(), $this->storage->getRootObject(), $groupSharingData);
		if (!$isConnectedGroupStorage)
		{
			return null;
		}

		return $groupSharingData;
	}

	private function processGridActions()
	{
		$buttonName = "action_button_{$this->gridOptions->getGridId()}";

		if (
			!Bitrix\Main\Grid\Context::isInternalRequest() ||
			!$this->existActionButton($buttonName) ||
			!check_bitrix_sessid()
		)
		{
			return new \Bitrix\Disk\Internals\Error\ErrorCollection();
		}

		$userId = CurrentUser::get()->getId();
		$controlValues = $this->request->getPost('controls');
		$buttonValue = $this->getActionButtonValue($buttonName);

		$backupErrorCollection = $this->errorCollection;

		$this->errorCollection = new \Bitrix\Disk\Internals\Error\ErrorCollection();

		if ($this->request->isAjaxRequest())
		{
			\CUtil::jSPostUnescape();
			$this->request->addFilter(new PostDecodeFilter);
		}

		foreach ($this->request->getPost('rows') as $rowId => $sourceData)
		{
			if ($buttonValue === 'delete')
			{
				if ($this->deleteObject($rowId, $userId))
				{
					$this->information = Loc::getMessage('DISK_FOLDER_LIST_INF_AFTER_ACTION_DELETE');
				}
			}
			elseif ($buttonValue === 'destroy')
			{
				if ($this->destroyObject($rowId, $userId))
				{
					$this->information = Loc::getMessage('DISK_FOLDER_LIST_INF_AFTER_ACTION_DELETE');
				}
			}
			elseif ($buttonValue === 'restore')
			{
				$this->restoreObject($rowId, $userId);
			}
			elseif ($buttonValue === 'edit')
			{
				if ($this->updateObject($rowId, $sourceData))
				{
					$this->information = Loc::getMessage('DISK_FOLDER_LIST_INF_AFTER_ACTION_RENAME');
				}
			}
			elseif ($buttonValue === 'copy')
			{
				if ($this->copyObject($rowId, $controlValues['destinationFolderId']))
				{
					$this->information = Loc::getMessage('DISK_FOLDER_LIST_INF_AFTER_ACTION_RENAME');
				}
			}
			elseif ($buttonValue === 'move')
			{
				if ($this->moveObject($rowId, $controlValues['destinationFolderId']))
				{
					$this->information = Loc::getMessage('DISK_FOLDER_LIST_INF_AFTER_ACTION_RENAME');
				}
			}
		}

		$gridActionsErrorCollection = $this->errorCollection;
		$this->errorCollection = $backupErrorCollection;

		return $gridActionsErrorCollection;
	}


	private function copyObject($objectId, $targetFolderId)
	{
		static $targetList = array();
		if (!isset($targetList[$targetFolderId]))
		{
			$targetList[$targetFolderId] = Folder::loadById($targetFolderId, array('STORAGE'));
		}
		/** @var Folder $targetFolder */
		$targetFolder = $targetList[$targetFolderId];
		if (!$targetFolder)
		{
			return false;
		}

		/** @var Folder|File $object */
		$object = BaseObject::loadById((int)$objectId, array('STORAGE'));
		if(!$object)
		{
			return false;
		}

		if(!$object->canRead($object->getStorage()->getCurrentUserSecurityContext()))
		{
			return false;
		}

		if(!$targetFolder->canAdd($targetFolder->getStorage()->getCurrentUserSecurityContext()))
		{
			return false;
		}

		if($object->copyTo($targetFolder, $this->getUser()->getId(), true))
		{
			$this->errorCollection->add($object->getErrors());

			return false;
		}

		return true;
	}

	private function moveObject($objectId, $targetFolderId)
	{
		static $targetList = array();
		if (!isset($targetList[$targetFolderId]))
		{
			$targetList[$targetFolderId] = Folder::loadById($targetFolderId, array('STORAGE'));
		}
		/** @var Folder $targetFolder */
		$targetFolder = $targetList[$targetFolderId];
		if (!$targetFolder)
		{
			return false;
		}

		/** @var Folder|File $object */
		$object = BaseObject::loadById((int)$objectId, array('STORAGE'));
		if(!$object)
		{
			return false;
		}

		if(!$object->canMove($object->getStorage()->getCurrentUserSecurityContext(), $targetFolder))
		{
			return false;
		}

		if($object->moveTo($targetFolder, $this->getUser()->getId(), true))
		{
			$this->errorCollection->add($object->getErrors());

			return false;
		}

		return true;
	}

	private function updateObject($objectId, $sourceData)
	{
		if (empty($sourceData['NAME']))
		{
			return true;
		}
		/** @var Folder|File $object */
		$object = BaseObject::loadById($objectId);
		if (!$object)
		{
			return false;
		}

		if (!$object->canRename($object->getStorage()->getCurrentUserSecurityContext()))
		{
			return false;
		}

		if ($object instanceof Folder)
		{
			$sourceData['NAME'] = \Bitrix\Disk\Ui\Text::correctFolderName($sourceData['NAME']);
		}
		if ($object instanceof File)
		{
			$sourceData['NAME'] = \Bitrix\Disk\Ui\Text::correctFilename($sourceData['NAME']);
		}

		if (!$object->rename($sourceData['NAME']))
		{
			$this->errorCollection->add($object->getErrors());

			return false;
		}

		return true;
	}

	private function deleteObject($objectId, $userId)
	{
		/** @var Folder|File $object */
		$object = BaseObject::loadById($objectId);
		if (!$object || $object->isDeleted())
		{
			return false;
		}

		if (!$object->canMarkDeleted($object->getStorage()->getCurrentUserSecurityContext()))
		{
			return false;
		}

		return $object->markDeleted($userId);
	}

	private function restoreObject($objectId, $userId)
	{
		/** @var Folder|File $object */
		$object = BaseObject::loadById($objectId);
		if(!$object)
		{
			return false;
		}
		if(!$object->canRestore($object->getStorage()->getCurrentUserSecurityContext()))
		{
			return false;
		}

		return $object->restore($userId);
	}

	private function destroyObject($objectId, $userId)
	{
		/** @var Folder|File $object */
		$object = BaseObject::loadById($objectId);
		if (!$object)
		{
			return false;
		}

		if (!$object->canDelete($object->getStorage()->getCurrentUserSecurityContext()))
		{
			return false;
		}

		if ($object instanceof Folder)
		{
			return $object->deleteTree($userId);
		}

		if ($object instanceof File)
		{
			return $object->delete($userId);
		}

		return false;
	}

	private function getRootBreadcrumb()
	{
		$proxyType = $this->storage->getProxyType();

		return array(
			'NAME' => $proxyType->getTitleForCurrentUser(),
			'LINK' => $proxyType->getBaseUrlFolderList(),
			'ID' => $this->isTrashMode()? null : $this->storage->getRootObjectId(),
		);
	}

	private function getBreadcrumbs()
	{
		$template = $this->isTrashMode()? $this->arParams['PATH_TO_TRASHCAN_LIST'] : $this->arParams['PATH_TO_FOLDER_LIST'];

		$crumbs = array();
		$parts = explode('/', trim($this->arParams['RELATIVE_PATH'], '/'));
		foreach ($this->arParams['RELATIVE_ITEMS'] as $i => $item)
		{
			if (empty($item))
			{
				continue;
			}

			$path = implode('/', (array_slice($parts, 0, $i + 1)));
			$crumbs[] = array(
				'ID' => $item['ID'],
				'NAME' => Ui\Text::cleanTrashCanSuffix($item['NAME']),
				'LINK' => rtrim(
							  CComponentEngine::MakePathFromTemplate(
								  $template,
								  array(
									  'PATH' => $path,
									  'TRASH_PATH' => $path,
								  )
							  ),
							  '/'
						  ) . '/',
			);
		}

		if ($this->isTrashMode())
		{
			array_unshift(
				$crumbs,
				[
					'ID' => $this->storage->getRootObjectId(),
					'NAME' => Loc::getMessage('DISK_TRASHCAN_NAME'),
					'LINK' => CComponentEngine::MakePathFromTemplate(
						$this->arParams['PATH_TO_TRASHCAN_LIST'],
						[
							'TRASH_PATH' => '',
						]
					),
				]
			);
		}


		return $crumbs;
	}

	private function getFilter()
	{
		return array(
			'FILTER_ID' => $this->gridOptions->getGridId(),
			'FILTER' => array_filter(array(
				array(
					'id' => 'NAME',
					'name' => Loc::getMessage('DISK_FOLDER_FILTER_NAME'),
					'default' => true,
				),
				//				array(
				//					'id' => 'CREATED_BY',
				//					'name' => Loc::getMessage('DISK_FOLDER_FILTER_CREATED_BY'),
				//					'type' => 'number',
				//				),
				array(
					'id' => 'ID',
					'name' => Loc::getMessage('DISK_FOLDER_FILTER_ID'),
					'type' => 'number',
				),
				array(
					'id' => 'CREATE_TIME',
					'name' => Loc::getMessage('DISK_FOLDER_FILTER_CREATE_TIME'),
					'type' => 'date',
					'time' => true,
				),
				array(
					'id' => 'UPDATE_TIME',
					'name' => Loc::getMessage('DISK_FOLDER_FILTER_UPDATE_TIME'),
					'type' => 'date',
					'time' => true,
				),
				Configuration::allowUseExtendedFullText() && ExtendedIndex::isReady()? array(
					'id' => 'SEARCH_BY_CONTENT',
					'name' => Loc::getMessage('DISK_FOLDER_FILTER_SEARCH_BY_CONTENT'),
					'type' => 'checkbox',
					'default' => true,
					'valueType' => 'numeric',
				) : null,
				array(
					'id' => 'SEARCH_IN_CURRENT_FOLDER',
					'name' => Loc::getMessage('DISK_FOLDER_FILTER_SEARCH_IN_CURRENT_FOLDER'),
					'type' => 'checkbox',
					'default' => true,
					'valueType' => 'numeric',
				),
				array(
					'id' => 'WITH_EXTERNAL_LINK',
					'name' => Loc::getMessage('DISK_FOLDER_FILTER_WITH_EXTERNAL_LINK'),
					'type' => 'list',
					'default' => false,
					'items' => array(
						self::FILTER_WITH_EXTERNAL_LINK => Loc::getMessage('DISK_FOLDER_FILTER_WITH_EXTERNAL_LINK_YES')
					),
				),
				array(
					'id' => 'SHARED',
					'name' => Loc::getMessage('DISK_FOLDER_FILTER_SHARED'),
					'type' => 'list',
					'default' => false,
					'items' => array(
						self::FILTER_SHARED_FROM_ME => Loc::getMessage('DISK_FOLDER_FILTER_SHARED_FROM_ME'),
						self::FILTER_SHARED_TO_ME => Loc::getMessage('DISK_FOLDER_FILTER_SHARED_TO_ME'),
					),
				),
			)),
			'FILTER_PRESETS' => $this->getPresetFields(),
			'ENABLE_LIVE_SEARCH' => true,
			'ENABLE_LABEL' => true,
			'RESET_TO_DEFAULT_MODE' => true,
		);
	}

	private function getPresetFields()
	{
		\Bitrix\Main\UI\Filter\Options::calcDates(
			'UPDATE_TIME',
			array('UPDATE_TIME_datesel' => \Bitrix\Main\UI\Filter\DateType::CURRENT_WEEK),
			$sevenDayBefore
		);

		return array(
			'recently_updated' => array(
				'name' => Loc::getMessage('DISK_FOLDER_FILTER_PRESETS_RECENTLY_UPDATED'),
				'default' => false,
				'fields' => $sevenDayBefore
			),
			'with_external_link' => array(
				'name' => Loc::getMessage('DISK_FOLDER_FILTER_PRESETS_WITH_EXTERNAL_LINK'),
				'default' => false,
				'fields' => array(
					'WITH_EXTERNAL_LINK' => self::FILTER_WITH_EXTERNAL_LINK
				)
			),
			'shared_to_me' => array(
				'name' => Loc::getMessage('DISK_FOLDER_FILTER_PRESETS_SHARED_TO_ME'),
				'default' => false,
				'fields' => array(
					'SHARED' => self::FILTER_SHARED_TO_ME
				)
			),
			'shared_from_me' => array(
				'name' => Loc::getMessage('DISK_FOLDER_FILTER_PRESETS_SHARED_FROM_ME'),
				'default' => false,
				'fields' => array(
					'SHARED' => self::FILTER_SHARED_FROM_ME
				)
			),
		);
	}

	private function getFilterForTrashMode()
	{
		return array(
			'FILTER_ID' => $this->gridOptions->getGridId(),
			'FILTER' => array(
				array(
					'id' => 'NAME',
					'name' => Loc::getMessage('DISK_FOLDER_FILTER_NAME'),
					'default' => true,
				),
				array(
					'id' => 'ID',
					'name' => Loc::getMessage('DISK_FOLDER_FILTER_ID'),
					'type' => 'number',
				),
				array(
					'id' => 'CREATE_TIME',
					'name' => Loc::getMessage('DISK_FOLDER_FILTER_CREATE_TIME'),
					'type' => 'date',
					'time' => true,
				),
				array(
					'id' => 'UPDATE_TIME',
					'name' => Loc::getMessage('DISK_FOLDER_FILTER_UPDATE_TIME'),
					'type' => 'date',
					'time' => true,
				),
				array(
					'id' => 'DELETE_TIME',
					'name' => Loc::getMessage('DISK_TRASHCAN_FOLDER_FILTER_DELETE_TIME'),
					'type' => 'date',
					'time' => true,
				),
			),
			'FILTER_PRESETS' => $this->getPresetFieldsForTrashMode(),
			'ENABLE_LIVE_SEARCH' => true,
			'ENABLE_LABEL' => true,
			'RESET_TO_DEFAULT_MODE' => true,
		);
	}

	private function getPresetFieldsForTrashMode()
	{
		\Bitrix\Main\UI\Filter\Options::calcDates(
			'UPDATE_TIME',
			array('UPDATE_TIME_datesel' => \Bitrix\Main\UI\Filter\DateType::CURRENT_WEEK),
			$sevenDayBeforeUpdated
		);

		\Bitrix\Main\UI\Filter\Options::calcDates(
			'DELETE_TIME',
			array('DELETE_TIME_datesel' => \Bitrix\Main\UI\Filter\DateType::CURRENT_WEEK),
			$sevenDayBeforeDeleted
		);

		return array(
			'recently_deleted' => array(
				'name' => Loc::getMessage('DISK_TRASHCAN_FOLDER_FILTER_PRESETS_RECENTLY_DELETED'),
				'default' => false,
				'fields' => $sevenDayBeforeDeleted
			),
			'recently_updated' => array(
				'name' => Loc::getMessage('DISK_TRASHCAN_FOLDER_FILTER_PRESETS_RECENTLY_UPDATED'),
				'default' => false,
				'fields' => $sevenDayBeforeUpdated
			),
		);
	}

	private function getConfigurationOfCloudDocument()
	{
		static $conf = null;
		if ($conf !== null)
		{
			return $conf;
		}

		if (!\Bitrix\Disk\Configuration::canCreateFileByCloud())
		{
			return array();
		}

		$documentHandlerName = $documentHandlerCode = null;

		$documentServiceCode = \Bitrix\Disk\UserConfiguration::getDocumentServiceCode();
		if (!$documentServiceCode)
		{
			$documentServiceCode = 'l';
		}
		if (LocalDocumentController::isLocalService($documentServiceCode))
		{
			$documentHandlerName = LocalDocumentController::getName();
			$documentHandlerCode = LocalDocumentController::getCode();
		}
		else
		{
			$defaultDocumentHandler = Driver::getInstance()->getDocumentHandlersManager()->getDefaultServiceForCurrentUser();
			if ($defaultDocumentHandler)
			{
				$documentHandlerName = $defaultDocumentHandler::getName();
				$documentHandlerCode = $defaultDocumentHandler::getCode();
			}
		}

		if (!$documentHandlerCode)
		{
			return array();
		}

		$urlManager = Driver::getInstance()->getUrlManager();

		$conf = array(
			'DEFAULT_SERVICE' => $documentHandlerCode,
			'DEFAULT_SERVICE_LABEL' => $documentHandlerName,
			'CREATE_BLANK_FILE_URL' => $urlManager::getUrlForStartCreateFile('docx', $documentHandlerCode),
			'RENAME_BLANK_FILE_URL' => $urlManager::getUrlDocumentController(
				'rename',
				array('document_action' => 'rename')
			),
		);

		return $conf;
	}

	protected function getGridHeaders()
	{
		$possibleColumnForSorting = $this->gridOptions->getPossibleColumnForSorting();
		$defaultColumns = array_combine(
			$this->gridOptions->getDefaultColumns(),
			$this->gridOptions->getDefaultColumns()
		);

		$headers = array(
			array(
				'id' => 'ID',
				'name' => 'ID',
				'sort' => isset($possibleColumnForSorting['ID']) ? 'ID' : false,
				'default' => isset($defaultColumns['ID']),
			),
			array(
				'id' => 'NAME',
				'name' => Loc::getMessage('DISK_FOLDER_LIST_COLUMN_NAME'),
				'sort' => isset($possibleColumnForSorting['NAME']) ? 'NAME' : false,
				'default' => isset($defaultColumns['NAME']),
				'editable' => array(
					'size' => 45,
				),
			),
			array(
				'id' => 'CREATE_TIME',
				'name' => Loc::getMessage('DISK_FOLDER_LIST_COLUMN_CREATE_TIME'),
				'sort' => isset($possibleColumnForSorting['CREATE_TIME']) ? 'CREATE_TIME' : false,
				'default' => isset($defaultColumns['CREATE_TIME']),
			),
			array(
				'id' => 'UPDATE_TIME',
				'name' => Loc::getMessage('DISK_FOLDER_LIST_COLUMN_UPDATE_TIME'),
				'sort' => isset($possibleColumnForSorting['UPDATE_TIME']) ? 'UPDATE_TIME' : false,
				'first_order' => $this->isTrashMode()? null : 'desc',
				'default' => isset($defaultColumns['UPDATE_TIME']),
			),
			($this->isTrashMode() ? array(
				'id' => 'DELETE_TIME',
				'name' => Loc::getMessage('DISK_TRASHCAN_COLUMN_DELETE_TIME'),
				'sort' => isset($possibleColumnForSorting['DELETE_TIME']) ? 'DELETE_TIME' : false,
				'first_order' => $this->isTrashMode() ? 'desc' : null,
				'default' => isset($defaultColumns['DELETE_TIME']),
			) : null),
			array(
				'id' => 'CREATE_USER',
				'name' => Loc::getMessage('DISK_FOLDER_LIST_COLUMN_CREATE_USER'),
				'sort' => isset($possibleColumnForSorting['CREATE_USER']) ? 'CREATE_USER' : false,
				'default' => isset($defaultColumns['CREATE_USER']),
			),
			array(
				'id' => 'UPDATE_USER',
				'name' => Loc::getMessage('DISK_FOLDER_LIST_COLUMN_UPDATE_USER'),
				'sort' => isset($possibleColumnForSorting['UPDATE_USER']) ? 'UPDATE_USER' : false,
				'default' => isset($defaultColumns['UPDATE_USER']),
			),
			($this->isTrashMode() ? array(
				'id' => 'DELETE_USER',
				'name' => Loc::getMessage('DISK_TRASHCAN_COLUMN_DELETE_USER'),
				'default' => isset($defaultColumns['DELETE_USER']),
			) : null),
			array(
				'id' => 'FORMATTED_SIZE',
				'name' => Loc::getMessage('DISK_FOLDER_LIST_COLUMN_FORMATTED_SIZE'),
				'sort' => isset($possibleColumnForSorting['FORMATTED_SIZE']) ? 'FORMATTED_SIZE' : false,
				'first_order' => 'desc',
				'default' => isset($defaultColumns['FORMATTED_SIZE']),
			),
		);

		if($this->isItTimeToShowBizProc())
		{
			$headers[] = array(
				'id' => 'BIZPROC',
				'name' => Loc::getMessage('DISK_FOLDER_LIST_COLUMN_BIZPROC'),
				'default' => isset($defaultColumns['BIZPROC']),
			);
		}

		return array_filter($headers);
	}

	private function isTrashMode()
	{
		return (bool)$this->trashMode;
	}

	/**
	 * @return array
	 */
	public function configureActions()
	{
		return [];
	}

	public function getSteppersAction()
	{
		return [
			'html' => Ui\Stepper::getHtml(),
		];
	}

	public function saveViewOptionsAction(\Bitrix\Disk\Storage $storage, $viewMode = null, $viewSize = null)
	{
		if ($this->isTrashMode())
		{
			$gridOptions = new Bitrix\Disk\Internals\Grid\TrashCanOptions($storage);
		}
		else
		{
			$gridOptions = new Bitrix\Disk\Internals\Grid\FolderListOptions($storage);
		}

		if ($viewMode)
		{
			$gridOptions->storeViewMode($viewMode);
		}
		if ($viewSize)
		{
			$gridOptions->storeViewSize($viewSize);
		}
	}
}
