<?php

use Bitrix\Disk\Internals\DiskComponent;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Ui;
use Bitrix\Main\Grid;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

Loc::loadMessages(__FILE__);

class CDiskTrashCanComponent extends DiskComponent
{
	const ERROR_COULD_NOT_FIND_OBJECT = 'DISK_TC_22001';

	protected $componentId = 'trash_can_list';
	/** @var \Bitrix\Disk\Folder */
	protected $folder;
	/** @var  array */
	protected $breadcrumbs;
	/** @var  Bitrix\Disk\Internals\Grid\TrashCanOptions */
	protected $gridOptions;

	protected function processBeforeAction($actionName)
	{
		parent::processBeforeAction($actionName);

		$this->findFolder();

		$this->gridOptions = new Bitrix\Disk\Internals\Grid\TrashCanOptions($this->storage);

		return true;
	}

	protected function prepareParams()
	{
		parent::prepareParams();

		if(!isset($this->arParams['FOLDER_ID']))
		{
			throw new \Bitrix\Main\ArgumentException('FOLDER_ID required');
		}
		$this->arParams['FOLDER_ID'] = (int)$this->arParams['FOLDER_ID'];
		if($this->arParams['FOLDER_ID'] <= 0)
		{
			throw new \Bitrix\Main\ArgumentException('FOLDER_ID < 0');
		}

		return $this;
	}

	protected function existActionButton($buttonName)
	{
		return $this->getActionButtonValue($buttonName) !== null;
	}

	protected function getActionButtonValue($buttonName)
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

	private function processGridActions($gridId)
	{
		$buttonName = 'action_button_'.$gridId;

		if (!Bitrix\Main\Grid\Context::isInternalRequest() || !$this->existActionButton($buttonName) || !check_bitrix_sessid())
		{
			return;
		}

		$userId = $this->getUser()->getId();
		$buttonValue = $this->getActionButtonValue($buttonName);
		foreach ($this->request->getPost('rows') as $rowId)
		{
			if ($buttonValue === 'restore')
			{
				$this->restoreObject($rowId, $userId);
			}
			if ($buttonValue === 'delete' || $buttonValue === 'destroy')
			{
				$this->destroyObject($rowId, $userId);
			}
		}
	}

	protected function destroyObject($objectId, $userId)
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

		return $object->delete($userId);
	}

	protected function restoreObject($objectId, $userId)
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

	protected function processActionDefault()
	{
		if(
			!$this->folder->canRead($this->storage->getCurrentUserSecurityContext())
		)
		{
			$this->showAccessDenied();
			return false;
		}
		$gridId = $this->gridOptions->getGridId();

		$this->application->setTitle(htmlspecialcharsbx($this->storage->getProxyType()->getTitleForCurrentUser()));

		$this->processGridActions($gridId);
		$this->arResult = array(
			'FILTER' => $this->getFilter($gridId),
			'GRID' => $this->getGridData($gridId),

			'STORAGE' => array(
				'ID' => $this->storage->getId(),
			),
			'FOLDER' => array(
				'ID' => $this->folder->getId(),
				'NAME' => $this->folder->getName(),
				'IS_DELETED' => $this->folder->isDeleted(),
				'CREATE_TIME' => $this->folder->getCreateTime(),
				'UPDATE_TIME' => $this->folder->getUpdateTime(),
			),
			'BREADCRUMBS' => $this->getBreadcrumbs(),
			'BREADCRUMBS_ROOT' => array(
				'NAME' => Loc::getMessage('DISK_TRASHCAN_NAME'),
				'LINK' => CComponentEngine::MakePathFromTemplate($this->arParams['PATH_TO_TRASHCAN_LIST'], array(
					'TRASH_PATH' => '',
				)),
				'ID' => $this->storage->getRootObjectId(),
			),
		);

		$this->includeComponentTemplate();
	}

	protected function findFolder()
	{
		$this->folder = \Bitrix\Disk\Folder::loadById($this->arParams['FOLDER_ID']);

		if(!$this->folder)
		{
			throw new \Bitrix\Main\SystemException("Invalid folder.");
		}
		return $this;
	}

	private function getListingPage(BaseObject $object, $relativePath = null)
	{
		if ($relativePath)
		{
			return rtrim(CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_TRASHCAN_LIST'], array(
				'TRASH_PATH' => $relativePath,
			)), '/');
		}

		return $this->getUrlManager()->getPathInTrashcanListing($object);
	}

	private function getDetailFilePage(File $file, $relativePath = null)
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

	private function isShowFromDifferentLevels(array $filter = array())
	{
		return
			isset($filter['DELETED_TYPE']) && $filter['DELETED_TYPE'] == ObjectTable::DELETED_TYPE_ROOT ||
			isset($filter['!=DELETED_TYPE']) && $filter['!=DELETED_TYPE'] == ObjectTable::DELETED_TYPE_NONE
		;
	}

	private function getGridData($gridId)
	{
		$grid = array(
			'ID' => $gridId,
		);
		[$grid['SORT'], $grid['SORT_VARS']] = $this->gridOptions->getGridOptionsSorting();
		$visibleColumns = array_combine(
			$this->gridOptions->getVisibleColumns(),
			$this->gridOptions->getVisibleColumns()
		);

		$parameters = array(
			'with' => $this->buildWithByVisibleColumns($visibleColumns),
			'filter' => array(),
		);
		$securityContext = $this->storage->getCurrentUserSecurityContext();

		$pageSize = $this->gridOptions->getPageSize();
		$nav = $this->gridOptions->getNavigation();
		$nav->initFromUri();

		$parameters = $this->modifyByFilter($parameters, $gridId);

		$parameters['order'] = $this->gridOptions->getOrderForOrm();
		$parameters['limit'] = $pageSize + 1; // +1 because we want to know about existence next page
		$parameters['offset'] = $nav->getOffset();

		$isShowFromDifferentLevels = $this->isShowFromDifferentLevels($parameters['filter']);
		$items = $this->folder->getDescendants($securityContext, $parameters, null);
		if (count($items))
		{
			$this->folder->preloadOperationsForChildren($securityContext);
		}

		$urlManager = \Bitrix\Disk\Driver::getInstance()->getUrlManager();
		$rows = array();

		$countObjectsOnPage = 0;
		foreach ($items as $object)
		{
			$countObjectsOnPage++;

			if ($countObjectsOnPage > $pageSize)
			{
				break;
			}

			$isFolder = $object instanceof Folder;
			/** @var File|Folder $object */
			$exportData = $object->toArray();

			$relativePath = trim($this->arParams['RELATIVE_PATH'], '/');
			if ($isShowFromDifferentLevels)
			{
				if (!$isFolder)
				{
					$detailPageFile = $this->getDetailFilePage($object);
				}
				$listingPage = $this->getListingPage($object);
			}
			else
			{
				if (!$isFolder)
				{
					$detailPageFile = $this->getDetailFilePage($object, $relativePath);
				}
				$listingPage = $this->getListingPage($object, $relativePath);
			}

			$isFolder = $object instanceof Folder;
			$actions = array();

			$exportData['OPEN_URL'] = $urlManager->encodeUrn($isFolder? $listingPage . '/' . $object->getOriginalName() : $detailPageFile);
			if (!$isFolder)
			{
				$actions[] = array(
					"text" => Loc::getMessage('DISK_TRASHCAN_ACT_DOWNLOAD'),
					"href" => $urlManager->getUrlForDownloadFile($object),
				);
			}

			if ($object->isDeleted() && $object->canRestore($securityContext))
			{
				$actions[] = array(
					"text" => Loc::getMessage('DISK_TRASHCAN_ACT_RESTORE'),
					"onclick" =>
						"BX.Disk['TrashCanClass_{$this->getComponentId()}'].openConfirmRestore({
							object: {
								id: {$object->getId()},
								name: '{$object->getName()}',
								isFolder: " . ($isFolder? 'true' : 'false') . "
							 }
						})",
				);
			}
			if ($object->canDelete($securityContext))
			{
				$actions[] = array(
					"text" => Loc::getMessage('DISK_TRASHCAN_ACT_DESTROY'),
					"onclick" =>
						"BX.Disk['TrashCanClass_{$this->getComponentId()}'].openConfirmDelete({
							object: {
								id: {$object->getId()},
								name: '{$object->getName()}',
								isFolder: " . ($isFolder? 'true' : 'false') . "
							 }
						})",
				);
			}

			if ($isFolder)
			{
				$uri = $urlManager->encodeUrn($listingPage . '/' . $object->getOriginalName());
			}
			else
			{
				$uri = \Bitrix\Disk\Driver::getInstance()->getUrlManager()->encodeUrn($detailPageFile);
			}
			$iconClass = \Bitrix\Disk\Ui\Icon::getIconClassByObject($object);
			$name = htmlspecialcharsbx($object->getName());

			$updateDateTime = $object->getUpdateTime();
			$columnName = "
				<table class=\"bx-disk-object-name\"><tr>
						<td style=\"width: 45px;\"><div data-object-id=\"{$object->getId()}\" class=\"js-disk-grid-open-folder bx-file-icon-container-small {$iconClass}\"></div></td>
						<td><a data-object-id=\"{$object->getId()}\" class=\"bx-disk-folder-title js-disk-grid-folder\" id=\"disk_obj_{$object->getId()}\" href=\"{$uri}\" data-bx-dateModify=\"" . htmlspecialcharsbx($updateDateTime) . "\">{$name}</a></td>
				</tr></table>
			";

			$deletedTime = $object->getDeleteTime();
			$createdByLink = \CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_USER'], array("user_id" => $object->getCreatedBy()));
			$deletedByLink = \CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_USER'], array("user_id" => $object->getDeletedBy()));

			$columns = array(
				'CREATE_TIME' => formatDate('x', $object->getCreateTime()->toUserTime()->getTimestamp(), (time() + CTimeZone::getOffset())),
				'UPDATE_TIME' => formatDate('x', $updateDateTime->toUserTime()->getTimestamp(), (time() + CTimeZone::getOffset())),
				'DELETE_TIME' => formatDate('x', $deletedTime->toUserTime()->getTimestamp(), (time() + CTimeZone::getOffset())),
				'NAME' => $columnName,
				'FORMATTED_SIZE' => $isFolder? '' : CFile::formatSize($object->getSize()),
				'CREATE_USER' => "
					<div class=\"bx-disk-user-link\"><a target='_blank' href=\"{$createdByLink}\" id=\"\">" . htmlspecialcharsbx($object->getCreateUser()->getFormattedName()) . "</a></div>
				",
				'DELETE_USER' => "
					<div class=\"bx-disk-user-link\"><a target='_blank' href=\"{$deletedByLink}\" id=\"\">" . htmlspecialcharsbx($object->getDeleteUser()->getFormattedName()) . "</a></div>
				",
			);

			if ($visibleColumns['CREATE_USER'])
			{
				$createUser = $object->getCreateUser();
				$createdByLink = \CComponentEngine::makePathFromTemplate(
					$this->arParams['PATH_TO_USER'],
					array('user_id' => $object->getCreatedBy())
				);

				$columns['CREATE_USER'] = "
					<div class=\"bx-disk-user-link\">{$createUser->renderAvatar()}<a target='_blank' href=\"{$createdByLink}\" id=\"\">" . htmlspecialcharsbx(
						$createUser->getFormattedName()) . "</a></div>
				";
			}

			if ($visibleColumns['UPDATE_USER'])
			{
				$updateUser = $object->getUpdateUser()?: $object->getCreateUser();
				$updatedByLink = \CComponentEngine::makePathFromTemplate(
					$this->arParams['PATH_TO_USER'],
					array('user_id' => $updateUser->getId())
				);

				$columns['UPDATE_USER'] = "
					<div class=\"bx-disk-user-link\">{$updateUser->renderAvatar()}<a target='_blank' href=\"{$updatedByLink}\" id=\"\">" . htmlspecialcharsbx(
						$updateUser->getFormattedName()) . "</a></div>
				";
			}

			if ($visibleColumns['DELETE_USER'])
			{
				$deleteUser = $object->getCreateUser();
				$deletedByLink = \CComponentEngine::makePathFromTemplate(
					$this->arParams['PATH_TO_USER'],
					array('user_id' => $object->getDeletedBy())
				);

				$columns['DELETE_USER'] = "
					<div class=\"bx-disk-user-link\">{$deleteUser->renderAvatar()}<a target='_blank' href=\"{$deletedByLink}\" id=\"\">" . htmlspecialcharsbx(
						$deleteUser->getFormattedName()) . "</a></div>
				";
			}

			$exportData['ICON_CLASS'] = $iconClass;
			$exportData['IS_SHARED'] = false;
			$exportData['IS_LINK'] = false;
			$tildaExportData = array();
			foreach($exportData as $exportName => $exportValue)
			{
				$tildaExportData['~' . $exportName] = $exportValue;
			}
			unset($exportRow);
			$rows[] = array(
				'data' => array_merge($exportData, $tildaExportData),
				'columns' => $columns,
				'actions' => $actions,
			);
		}

		$nav->setRecordCount($nav->getOffset() + $countObjectsOnPage);

		$grid['HEADERS'] = $this->getGridHeaders();
		$grid['NAV_OBJECT'] = $nav;
		$grid['ROWS'] = $rows;
		$grid['ACTION_PANEL'] = $this->getGroupActions($gridId);

		return $grid;
	}

	protected function getBreadcrumbs()
	{
		$crumbs = array();

		$parts = explode('/', trim($this->arParams['RELATIVE_PATH'], '/'));
		foreach($this->arParams['RELATIVE_ITEMS'] as $i => $item)
		{
			if(empty($item))
			{
				continue;
			}
			$crumbs[] = array(
				'ID' => $item['ID'],
				'NAME' => Ui\Text::cleanTrashCanSuffix($item['NAME']),
				'LINK' => rtrim(CComponentEngine::MakePathFromTemplate($this->arParams['PATH_TO_TRASHCAN_LIST'], array(
					'TRASH_PATH' => implode('/', (array_slice($parts, 0, $i + 1)))?: '',
				)), '/') . '/',
			);
		}
		unset($i, $part);

		return $crumbs;
	}

	protected function buildWithByVisibleColumns(array $visibleColumns)
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

	private function getFilter($gridId)
	{
		return array(
			'FILTER_ID' => $gridId,
			'FILTER' => array(
				array(
					'id' => 'NAME',
					'name' => Loc::getMessage('DISK_TRASHCAN_FOLDER_FILTER_NAME'),
					'default' => true,
				),
				array(
					'id' => 'ID',
					'name' => Loc::getMessage('DISK_TRASHCAN_FOLDER_FILTER_ID'),
					'type' => 'number',
				),
				array(
					'id' => 'CREATE_TIME',
					'name' => Loc::getMessage('DISK_TRASHCAN_FOLDER_FILTER_CREATE_TIME'),
					'type' => 'date',
					'time' => true,
				),
				array(
					'id' => 'UPDATE_TIME',
					'name' => Loc::getMessage('DISK_TRASHCAN_FOLDER_FILTER_UPDATE_TIME'),
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

	private function modifyByFilter(array $parameters, $gridId)
	{
		$filterOptions = new \Bitrix\Main\UI\Filter\Options($gridId);
		if ($this->request->getPost('resetFilter'))
		{
			$filterOptions->reset();
		}

		$filterData = $filterOptions->getFilter();

		$filter = array();
		//shown trash can root
		if ($this->arParams['RELATIVE_PATH'] == '/')
		{
			$filter['DELETED_TYPE'] = ObjectTable::DELETED_TYPE_ROOT;
		}
		else
		{
			$filter['PARENT_ID'] = $this->folder->getId();
			$filter['DELETED_TYPE'] = ObjectTable::DELETED_TYPE_CHILD;
		}

		if (!array_key_exists('FILTER_APPLIED', $filterData) || $filterData['FILTER_APPLIED'] != true)
		{
			$parameters['filter'] = array_merge($parameters['filter'], $filter);

			return $parameters;
		}

		unset($filter['DELETED_TYPE'], $filter['PARENT_ID']);
		$filter['!=DELETED_TYPE'] = ObjectTable::DELETED_TYPE_NONE;

		if (array_key_exists('FIND', $filterData) && !empty($filterData['FIND']))
		{
			$fulltextContent = \Bitrix\Disk\Search\FullTextBuilder::create()
				->addText(trim($filterData['FIND']))
				->getSearchValue()
			;

			$operation = ObjectTable::getEntity()->fullTextIndexEnabled('SEARCH_INDEX') ? '*' : '*%';
			$filter["{$operation}SEARCH_INDEX"] = $fulltextContent;
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

		if (!empty($filterData['CREATE_TIME_to']) > 0)
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

		if (!empty($filterData['UPDATE_TIME_to']) > 0)
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

		if (!empty($filterData['DELETE_TIME_to']) > 0)
		{
			try
			{
				$filter['<=DELETE_TIME'] = new DateTime($filterData['DELETE_TIME_to']);
			}
			catch (Exception $e)
			{}
		}

		$parameters['filter'] = array_merge($parameters['filter'], $filter);

		return $parameters;
	}

	/**
	 * @return array
	 */
	protected function getGridHeaders()
	{
		$possibleColumnForSorting = $this->gridOptions->getPossibleColumnForSorting();

		return array(
			array(
				'id' => 'ID',
				'name' => 'ID',
				'sort' => isset($possibleColumnForSorting['ID']) ? 'ID' : false,
				'default' => false,
			),
			array(
				'id' => 'NAME',
				'name' => Loc::getMessage('DISK_TRASHCAN_COLUMN_NAME'),
				'sort' => isset($possibleColumnForSorting['NAME']) ? 'NAME' : false,
				'default' => true,
			),
			array(
				'id' => 'DELETE_TIME',
				'name' => Loc::getMessage('DISK_TRASHCAN_COLUMN_DELETE_TIME'),
				'sort' => isset($possibleColumnForSorting['DELETE_TIME']) ? 'DELETE_TIME' : false,
				'default' => true,
			),
			array(
				'id' => 'CREATE_TIME',
				'name' => Loc::getMessage('DISK_TRASHCAN_COLUMN_CREATE_TIME'),
				'sort' => isset($possibleColumnForSorting['CREATE_TIME']) ? 'CREATE_TIME' : false,
				'default' => false,
			),
			array(
				'id' => 'UPDATE_TIME',
				'name' => Loc::getMessage('DISK_TRASHCAN_COLUMN_UPDATE_TIME'),
				'sort' => isset($possibleColumnForSorting['UPDATE_TIME']) ? 'UPDATE_TIME' : false,
				'default' => false,
			),
			array(
				'id' => 'CREATE_USER',
				'name' => Loc::getMessage('DISK_TRASHCAN_COLUMN_CREATE_USER'),
				'default' => false,
			),
			array(
				'id' => 'DELETE_USER',
				'name' => Loc::getMessage('DISK_TRASHCAN_COLUMN_DELETE_USER'),
				'default' => false,
			),
			array(
				'id' => 'FORMATTED_SIZE',
				'name' => Loc::getMessage('DISK_TRASHCAN_COLUMN_FORMATTED_SIZE'),
				'sort' => isset($possibleColumnForSorting['FORMATTED_SIZE']) ? 'FORMATTED_SIZE' : false,
				'default' => true,
			),
		);
	}

	protected function getGroupActions($gridId)
	{
		$prefix = $gridId;

		$chooseAction = array('NAME' => Loc::getMessage('DISK_TRASHCAN_DEFAULT_ACTION'), 'VALUE' => 'none');
		$destroyLink = array(
			'NAME' => Loc::getMessage('DISK_TRASHCAN_ACT_DESTROY'),
			'VALUE' => 'destroy',
			'ONCHANGE' => array(
				array(
					'ACTION' => Grid\Panel\Actions::CALLBACK,
					'CONFIRM' => true,
					'CONFIRM_APPLY_BUTTON' => Loc::getMessage('DISK_TRASHCAN_ACT_DESTROY'),
					'DATA' => array(
						array(
							'JS' => 'Grid.sendSelected()',
						),
					),
				),
			),
		);
		$restoreLink = array(
			'NAME' => Loc::getMessage('DISK_TRASHCAN_ACT_RESTORE'),
			'VALUE' => 'restore',
			'ONCHANGE' => array(
				array(
					'ACTION' => Grid\Panel\Actions::CALLBACK,
					'CONFIRM' => true,
					'CONFIRM_APPLY_BUTTON' => Loc::getMessage('DISK_TRASHCAN_ACT_RESTORE'),
					'DATA' => array(
						array(
							'JS' => 'Grid.sendSelected()',
						),
					),
				),
			),
		);

		$dropDownList = array(
			$chooseAction,
			$restoreLink,
			$destroyLink,

		);

		return array(
			'GROUPS' => array(
				array(
					'ITEMS' => array(
						array(
							"TYPE" => Grid\Panel\Types::DROPDOWN,
							"ID" => "action_button_{$prefix}",
							"NAME" => "action_button_{$prefix}",
							"ITEMS" => $dropDownList
						),
					)
				)
			)
		);
	}
}