<?php
use Bitrix\Disk\Document\LocalDocumentController;
use Bitrix\Disk\Internals\DiskComponent;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Main\Localization\Loc;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

Loc::loadMessages(__FILE__);

class CDiskFolderToolbarComponent extends DiskComponent
{
	const ERROR_COULD_NOT_FIND_OBJECT = 'DISK_FT_22001';

	protected $mode = 'folder_list';
	protected $extendedAddFolder;
	protected $isArchivedGroupStorage = null;

	protected function prepareParams()
	{
		parent::prepareParams();

		if(isset($this->arParams['FOLDER']))
		{
			if(!$this->arParams['FOLDER'] instanceof Folder)
			{
				throw new \Bitrix\Main\ArgumentException('FOLDER not instance of \\Bitrix\\Disk\\Folder');
			}
			$this->arParams['FOLDER_ID'] = $this->arParams['FOLDER']->getId();
		}
		if(!isset($this->arParams['FOLDER_ID']))
		{
			throw new \Bitrix\Main\ArgumentException('FOLDER_ID required');
		}
		$this->arParams['FOLDER_ID'] = (int)$this->arParams['FOLDER_ID'];
		if($this->arParams['FOLDER_ID'] <= 0)
		{
			throw new \Bitrix\Main\ArgumentException('FOLDER_ID < 0');
		}
		if(!empty($this->arParams['MODE']))
		{
			$this->mode = $this->arParams['MODE'];
		}
		$this->extendedAddFolder = $this->arParams['EXTENDED_ADD_FOLDER'] = !empty($this->arParams['EXTENDED_ADD_FOLDER']);

		if(empty($this->arParams['RELATIVE_PATH']))
		{
			$this->arParams['RELATIVE_PATH'] = null;
		}

		return $this;
	}

	/**
	 * @return Folder
	 */
	protected function getFolder()
	{
		if(isset($this->arParams['FOLDER']))
		{
			return $this->arParams['FOLDER'];
		}
		$this->arParams['FOLDER'] = Folder::loadById($this->arParams['FOLDER_ID']);

		return $this->arParams['FOLDER'];
	}

	protected function isTrashCan()
	{
		return $this->mode === 'trashcan';
	}

	protected function isFolderList()
	{
		return $this->mode === 'folder_list';
	}

	protected function isExternalLinkList()
	{
		return $this->mode === 'external_link_list';
	}

	protected function processActionDefault()
	{
		$driver = \Bitrix\Disk\Driver::getInstance();
		$this->arResult['BUTTONS'] = array();

		$securityContext = $this->storage->getCurrentUserSecurityContext();
		/** @var Folder $folder */
		$folder = $this->getFolder();
		if(!$folder)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_TOOLBAR_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT)));
			$this->includeComponentTemplate();
		}
		$this->arResult['CLOUD_DOCUMENT'] = array();
		if($this->isFolderList() && $folder->canAdd($securityContext) && !$this->isArchivedGroupStorage())
		{
			$this->arResult['BUTTONS'][] = array(
				'TEXT' => Loc::getMessage('DISK_FOLDER_TOOLBAR_UPLOAD_FILE_TEXT'),
				'TITLE' => Loc::getMessage('DISK_FOLDER_TOOLBAR_UPLOAD_FILE_TITLE'),
				'LINK' => 'javascript:void(0);',
				'ICON' => 'element-upload',
			);
			if(\Bitrix\Disk\Configuration::canCreateFileByCloud())
			{
				$documentHandlerName = $documentHandlerCode = null;

				$documentServiceCode = \Bitrix\Disk\UserConfiguration::getDocumentServiceCode();
				if(!$documentServiceCode)
				{
					$documentServiceCode = 'l';
				}
				if(LocalDocumentController::isLocalService($documentServiceCode))
				{
					$documentHandlerName = LocalDocumentController::getName();
					$documentHandlerCode = LocalDocumentController::getCode();
				}
				else
				{
					$defaultDocumentHandler = \Bitrix\Disk\Driver::getInstance()->getDocumentHandlersManager()->getDefaultServiceForCurrentUser();
					if($defaultDocumentHandler)
					{
						$documentHandlerName = $defaultDocumentHandler->getName();
						$documentHandlerCode = $defaultDocumentHandler->getCode();
					}
				}

				if($documentHandlerCode)
				{
					$urlManager = $driver->getUrlManager();
					$this->arResult['CLOUD_DOCUMENT'] = array(
						'DEFAULT_SERVICE' => $documentHandlerCode,
						'DEFAULT_SERVICE_LABEL' => $documentHandlerName,
						'CREATE_BLANK_FILE_URL' => $urlManager->getUrlToStartCreateUfFileByService('docx', $documentHandlerCode),
						'RENAME_BLANK_FILE_URL' => $urlManager->getUrlDocumentController('rename', array('document_action' => 'rename')),
					);

					$this->arResult['BUTTONS'][] = array(
						'TEXT' => Loc::getMessage('DISK_FOLDER_TOOLBAR_CREATE_DOC_TEXT'),
						'TITLE' => Loc::getMessage('DISK_FOLDER_TOOLBAR_CREATE_DOC_TITLE'),
						'LINK' => "javascript:BX.Disk['FolderToolbarClass_{$this->getComponentId()}'].createFile();",
						'ICON' => 'docs-add',
					);
				}
			}
			$this->arResult['BUTTONS'][] = array(
				'TEXT' => Loc::getMessage('DISK_FOLDER_TOOLBAR_CREATE_FOLDER_TEXT'),
				'TITLE' => Loc::getMessage('DISK_FOLDER_TOOLBAR_CREATE_FOLDER_TITLE'),
				'LINK' => $this->extendedAddFolder?
						"javascript:BX.Disk['FolderToolbarClass_{$this->getComponentId()}'].createExtendedFolder();" :
						"javascript:BX.Disk['FolderToolbarClass_{$this->getComponentId()}'].createFolder();",
				'ICON' => 'folder-add',
			);
		}
		if(!empty($this->arParams['URL_TO_EMPTY_TRASHCAN']) && $this->isTrashCan() && $folder->canRestore($securityContext))
		{
			$this->arResult['BUTTONS'][] = array(
				'TEXT' => Loc::getMessage('DISK_FOLDER_TOOLBAR_EMPTY_TRASHCAN_TEXT'),
				'TITLE' => Loc::getMessage('DISK_FOLDER_TOOLBAR_EMPTY_TRASHCAN_TITLE'),
				'LINK' => "javascript:BX.Disk['FolderToolbarClass_{$this->getComponentId()}'].emptyTrashCan();",
				'ICON' => 'empty-trashcan',
			);
		}
		$this->arResult['DROPDOWN_FILTER'] = array();
		if(!empty($this->arParams['URL_TO_FOLDER_LIST']))
		{
			$this->arResult['DROPDOWN_FILTER'][] = array(
				'TEXT' => Loc::getMessage('DISK_FOLDER_TOOLBAR_FOLDER_LIST_TEXT'),
				'TITLE' => Loc::getMessage('DISK_FOLDER_TOOLBAR_FOLDER_LIST_TITLE'),
				'HREF' => $this->arParams['URL_TO_FOLDER_LIST'],
			);
			if($this->isFolderList())
			{
				$this->arResult['DROPDOWN_FILTER_CURRENT_LABEL'] = Loc::getMessage('DISK_FOLDER_TOOLBAR_FOLDER_LIST_TEXT');

				if($this->arParams['RELATIVE_PATH'] && $folder->getId() != $this->storage->getRootObjectId())
				{
					$relativePath = explode('/', trim($this->arParams['RELATIVE_PATH'], '/'));
					array_pop($relativePath);
					if($relativePath)
					{
						$prevPageListing = rtrim(CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_FOLDER_LIST'], array(
							'PATH' => implode('/', $relativePath),
						)), '/');
					}
					else
					{
						$prevPageListing = $this->arParams['URL_TO_FOLDER_LIST'];
					}

					array_unshift($this->arResult['BUTTONS'], array(
						'TEXT' => Loc::getMessage('DISK_FOLDER_TOOLBAR_EXTERNAL_LINK_LIST_GO_BACK_TEXT'),
						'TITLE' => Loc::getMessage('DISK_FOLDER_TOOLBAR_EXTERNAL_LINK_LIST_GO_BACK_TITLE'),
						'LINK' => $prevPageListing,
						'ICON' => 'back',
					));
				}
			}
		}
		if(!empty($this->arParams['PATH_TO_EXTERNAL_LINK_LIST']))
		{
			$this->arResult['DROPDOWN_FILTER'][] = array(
				'TEXT' => Loc::getMessage('DISK_FOLDER_TOOLBAR_EXTERNAL_LINK_LIST_TEXT_2'),
				'TITLE' => Loc::getMessage('DISK_FOLDER_TOOLBAR_EXTERNAL_LINK_LIST_TITLE'),
				'HREF' => $this->arParams['PATH_TO_EXTERNAL_LINK_LIST'],
			);
			if($this->isExternalLinkList())
			{
				$this->arResult['DROPDOWN_FILTER_CURRENT_LABEL'] = Loc::getMessage('DISK_FOLDER_TOOLBAR_EXTERNAL_LINK_LIST_TEXT_2');

				$this->arResult['BUTTONS'][] = array(
					'TEXT' => Loc::getMessage('DISK_FOLDER_TOOLBAR_EXTERNAL_LINK_LIST_GO_BACK_TEXT'),
					'TITLE' => Loc::getMessage('DISK_FOLDER_TOOLBAR_EXTERNAL_LINK_LIST_GO_BACK_TITLE'),
					'LINK' => $this->arParams['URL_TO_FOLDER_LIST'],
					'ICON' => 'back',
				);
			}
		}
		if(!empty($this->arParams['URL_TO_TRASHCAN_LIST']))
		{
			$this->arResult['DROPDOWN_FILTER'][] = array(
				'TEXT' => Loc::getMessage('DISK_FOLDER_TOOLBAR_TRASHCAN_TEXT_2'),
				'TITLE' => Loc::getMessage('DISK_FOLDER_TOOLBAR_TRASHCAN_TITLE'),
				'HREF' => $this->arParams['URL_TO_TRASHCAN_LIST'],
			);
			if($this->isTrashCan())
			{
				$this->arResult['DROPDOWN_FILTER_CURRENT_LABEL'] = Loc::getMessage('DISK_FOLDER_TOOLBAR_TRASHCAN_TEXT');

				if($this->arParams['RELATIVE_PATH'] && $folder->getId() != $this->storage->getRootObjectId())
				{
					$relativePath = explode('/', trim($this->arParams['RELATIVE_PATH'], '/'));
					array_pop($relativePath);
					if($relativePath)
					{
						$prevPageListing = rtrim(CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_TRASHCAN_LIST'], array(
							'TRASH_PATH' => implode('/', $relativePath),
						)), '/');
					}
					else
					{
						$prevPageListing = $this->arParams['URL_TO_TRASHCAN_LIST'];
					}

					array_unshift($this->arResult['BUTTONS'], array(
						'TEXT' => Loc::getMessage('DISK_FOLDER_TOOLBAR_EXTERNAL_LINK_LIST_GO_BACK_TEXT'),
						'TITLE' => Loc::getMessage('DISK_FOLDER_TOOLBAR_EXTERNAL_LINK_LIST_GO_BACK_TITLE'),
						'LINK' => $prevPageListing,
						'ICON' => 'back',
					));
				}
				else
				{
					array_unshift($this->arResult['BUTTONS'], array(
						'TEXT' => Loc::getMessage('DISK_FOLDER_TOOLBAR_EXTERNAL_LINK_LIST_GO_BACK_TEXT'),
						'TITLE' => Loc::getMessage('DISK_FOLDER_TOOLBAR_EXTERNAL_LINK_LIST_GO_BACK_TITLE'),
						'LINK' => $this->arParams['URL_TO_FOLDER_LIST'],
						'ICON' => 'back',
					));
				}
			}
		}

		$this->includeComponentTemplate();
	}

	protected function isArchivedGroupStorage()
	{
		if($this->isArchivedGroupStorage !== null)
		{
			return $this->isArchivedGroupStorage;
		}
		$this->isArchivedGroupStorage = false;
		if(!$this->storage->getProxyType() instanceof Bitrix\Disk\ProxyType\Group)
		{
			return $this->isArchivedGroupStorage;
		}
		if(!\Bitrix\Main\Loader::includeModule('socialnetwork'))
		{
			return $this->isArchivedGroupStorage;
		}
		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		$group = CSocNetGroup::getByID($this->storage->getEntityId());
		if(
			!empty($group['CLOSED']) && $group['CLOSED'] === 'Y' && 
			\Bitrix\Main\Config\Option::get('socialnetwork', 'work_with_closed_groups', 'N') === 'N'
		)
		{
			$this->isArchivedGroupStorage = true;
		}
		return $this->isArchivedGroupStorage;
	}
}