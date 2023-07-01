<?php

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Disk\Internals\VolumeTable;
use Bitrix\Disk\Internals\BaseComponent;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Volume;


if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

Loc::loadMessages(__FILE__);

Loader::includeModule('disk');

class CDiskVolumeComponent extends BaseComponent
{
	/** @var \Bitrix\Disk\Storage */
	private $storage;

	/** @var int */
	private $storageId;

	/** @var int */
	private $currentUserStorageId;

	/** @var \Bitrix\Disk\Folder */
	private $folder;

	/** @var int */
	private $folderId;

	/** @var bool */
	private $onlyFolder = false;

	/** @var bool */
	private $reload = false;

	/** @var int */
	private $filterId = -1;

	/** @var string */
	private $moduleId = '';

	/** @var string */
	private $indicatorId = '';

	/** @var boolean */
	private $useDiskSizeAsTotalVolume = true;// diff in totals is about ~25%. Look more in task No93464

	/* session setting var names */
	const SETTING_ADMIN_MODE = 'disk_volume_admin';
	const SETTING_EXPERT_MODE = 'disk_volume_expert';
	const SETTING_QUEUE_STEP = 'disk_volume_queue_step';

	/* must repeat action */
	const STATUS_TIMEOUT = 'timeout';

	/* component actions */
	const ACTION_DEFAULT = 'default';
	const ACTION_DISKS = 'disks';
	const ACTION_DISKS_USERS = 'users';
	const ACTION_DISKS_GROUPS = 'groups';
	const ACTION_DISKS_COMMON = 'common';
	const ACTION_DISKS_TRASHCAN = 'trashcan';
	const ACTION_FOLDER = 'folder';
	const ACTION_FOLDER_FILES = 'folderfiles';
	const ACTION_STORAGE = 'storage';
	const ACTION_FILES = 'files';
	const ACTION_TRASH_FILES = 'trashfiles';

	/* ajax component actions */
	const ACTION_MEASURE = 'measure';
	const ACTION_PURIFY = 'purify';
	const ACTION_MEASURE_STORAGE = 'measureStorage';
	const ACTION_MEASURE_STORAGE_STEPS = 9;// count steps within method \DiskVolumeController::processActionMeasureStorage
	const ACTION_MEASURE_FOLDER = 'measureFolder';
	const ACTION_DELETE_FILE = 'deleteFile';
	const ACTION_DELETE_FILE_UNNECESSARY_VERSION = 'deleteFileUnnecessaryVersion';
	const ACTION_DELETE_GROUP_FILE = 'deleteGroupFile';
	const ACTION_DELETE_GROUP_FILE_UNNECESSARY_VERSION = 'deleteGroupFileUnnecessaryVersion';
	const ACTION_DELETE_UNNECESSARY_VERSION = 'deleteUnnecessaryVersion';
	const ACTION_DELETE_FOLDER = 'deleteFolder';
	const ACTION_EMPTY_FOLDER = 'emptyFolder';
	const ACTION_SETUP_CLEANER_JOB = 'setupCleanerJob';
	const ACTION_SEND_NOTIFICATION = 'sendNotification';
	const ACTION_CANCEL_WORKERS = 'cancelWorkers';
	const ACTION_EMPTY_TRASHCAN = 'emptyTrashcan';


	/* component templates */
	const TEMPLATE_DISK_TOP = 'disks';
	const TEMPLATE_FOLDER_TOP = 'storage';
	const TEMPLATE_FILE_TOP = 'files';

	/* metrica marks */
	// 1. global scan
	const METRIC_MARK_GLOBAL_SCAN = 'globalScan';
	// 2. global unnecessary clean
	const METRIC_MARK_GLOBAL_UNNECESSARY_CLEAN = 'globalUnnecessaryClean';
	// 3. global trashcan clean
	const METRIC_MARK_GLOBAL_TRASHCAN_CLEAN = 'globalTrashcanClean';
	// 4. certain disk clean
	const METRIC_MARK_CERTAIN_DISK_CLEAN = 'certainDiskClean';
	// 5. certain folder clean
	const METRIC_MARK_CERTAIN_FOLDER_CLEAN = 'certainFolderClean';

	/* Preform direct action or setup agent task */
	const SETUP_CLEANER_FILE_THRESHOLD_COUNT = 20;

	/**
	 * Return component action command.
	 *
	 * @return string
	 */
	public function getAction()
	{
		return parent::getAction();
	}

	/**
	 * Set current storage id.
	 * @param int $storageId
	 * @return void
	 */
	public function setStorageId($storageId)
	{
		$this->storageId = $storageId;
	}

	/**
	 * Set current folder id.
	 * @param int $folderId
	 * @return void
	 */
	public function setFolderId($folderId)
	{
		$this->folderId = $folderId;
	}

	/**
	 * @return boolean
	 */
	public function isAdminModeAllowed()
	{
		static $isUserAdmin;
		if (is_null($isUserAdmin))
		{
			$isUserAdmin = \Bitrix\Disk\User::isCurrentUserAdmin();
		}

		return $isUserAdmin;
	}

	/**
	 * @return boolean
	 */
	public function useDiskSizeAsTotalVolume()
	{
		return $this->useDiskSizeAsTotalVolume;
	}

	/**
	 * @return boolean
	 */
	public function isAdminMode()
	{
		if ($this->isAdminModeAllowed())
		{
			if (!empty($this->arParams['STORAGE_ID']) && (int)$this->arParams['STORAGE_ID'] > 0)
			{
				return false;
			}
			if (isset($_SESSION[self::SETTING_ADMIN_MODE]))
			{
				return (bool)($_SESSION[self::SETTING_ADMIN_MODE] === 'on');
			}

			return true;
		}

		return false;
	}

	/**
	 * @return boolean
	 */
	public function isExpertMode()
	{
		if (isset($_SESSION[self::SETTING_EXPERT_MODE]))
		{
			return (bool)($_SESSION[self::SETTING_EXPERT_MODE] === 'on');
		}

		return false;
	}

	/**
	 * @return boolean
	 */
	public function isOnlySpecificStorage()
	{
		return (
			$this->isAdminModeAllowed() &&
			!empty($this->arParams['STORAGE_ID']) &&
			((int)$this->arParams['STORAGE_ID'] > 0)
		);
	}

	/**
	 * @return boolean
	 * @throws Main\ObjectException
	 */
	public function isDiskEmpty()
	{
		$isEmpty = true;

		if (!$this->isAdminMode() || $this->isOnlySpecificStorage())
		{
			$disk = $this->getIndicator(Volume\Storage\Storage::className());
			$disk->addFilter('STORAGE_ID', $this->storageId);
		}
		else
		{
			$disk = $this->getIndicator(Volume\Bfile::className());
		}

		$measureResult = $disk->getMeasurementResult();

		if (
			!($result = $measureResult->fetch()) ||
			(($result['FILE_COUNT'] == 0) && ($result['FILE_SIZE'] == 0)) ||
			(($result['DISK_COUNT'] == 0) && ($result['DISK_SIZE'] == 0))
		)
		{
			$measureResult = $disk->purify()->measure()->getMeasurementResult();
			$result = $measureResult->fetch();
		}

		if ($result)
		{
			if ($this->useDiskSizeAsTotalVolume())
			{
				$isEmpty = ($result['DISK_COUNT'] == 0) && ($result['DISK_SIZE'] == 0);
			}
			else
			{
				$isEmpty = ($result['FILE_COUNT'] == 0) && ($result['FILE_SIZE'] == 0);
			}
		}

		return $isEmpty;
	}

	/**
	 * @return boolean
	 */
	public function isNeedReload()
	{
		// there are no running task
		$filter = array(
			'=OWNER_ID' => $this->getUser()->getId(),
			'=AGENT_LOCK' => array(
				Volume\Task::TASK_STATUS_RUNNING,
				Volume\Task::TASK_STATUS_WAIT,
			),
		);
		if (!$this->isAdminMode())
		{
			$filter['=STORAGE_ID'] = $this->getCurrentUserStorageId();
		}
		$workerResult = \Bitrix\Disk\Internals\VolumeTable::getList(array(
			'select' => array('ID'),
			'filter' => $filter,
			'limit' => 1,
		));
		if ($workerResult)
		{
			if ($row = $workerResult->fetch())
			{
				return false;
			}
		}

		// are there finished tasks
		// last measure a day ago
		$filter = array(
			'=OWNER_ID' => $this->getUser()->getId(),
			array(
				'LOGIC' => 'OR',
				'=AGENT_LOCK' => array(
					Volume\Task::TASK_STATUS_DONE,
					Volume\Task::TASK_STATUS_CANCEL,
				),
				array(
					'LOGIC' => 'AND',
					'=AGENT_LOCK' => array(
						Volume\Task::TASK_STATUS_NONE,
					),
					'<CREATE_TIME' => new \Bitrix\Main\Type\DateTime(date('Y-m-d H:i:s', strtotime('-1 days')), 'Y-m-d H:i:s'),
				),
			),
		);
		if (!$this->isAdminMode())
		{
			$filter['=STORAGE_ID'] = $this->getCurrentUserStorageId();
		}
		$workerResult = \Bitrix\Disk\Internals\VolumeTable::getList(array(
			'select' => array('ID'),
			'filter' => $filter,
			'order' => array('CREATE_TIME' => 'DESC'),
			'limit' => 1,
		));
		if ($workerResult)
		{
			if ($row = $workerResult->fetch())
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @return array
	 */
	public function listActions()
	{
		$listAction = array();

		if ($this->isOnlySpecificStorage() || !$this->isAdminMode())
		{
			$listAction[self::ACTION_FOLDER] = array(
				'method' => array('GET', 'POST'),
				'check_csrf_token' => false,
				'sef_path' => 'storage/#folderId#/',
			);
			$listAction[self::ACTION_FOLDER_FILES] = array(
				'method' => array('GET', 'POST'),
				'check_csrf_token' => false,
				'sef_path' => 'files/#folderId#/',
			);
			$listAction[self::ACTION_STORAGE] = array(
				'method' => array('GET', 'POST'),
				'check_csrf_token' => false,
				'sef_path' => 'storage/',
			);
			$listAction[self::ACTION_FILES] = array(
				'method' => array('GET', 'POST'),
				'check_csrf_token' => false,
				'sef_path' => 'files/',
			);

			$listAction[self::ACTION_TRASH_FILES] = array(
				'method' => array('GET', 'POST'),
				'check_csrf_token' => false,
				'sef_path' => 'trashcan/',
			);
		}
		else
		{
			$listAction[self::ACTION_DISKS_USERS] = array(
				'method' => array('GET', 'POST'),
				'check_csrf_token' => false,
				'sef_path' => 'disks/users/',
			);
			$listAction[self::ACTION_DISKS_GROUPS] = array(
				'method' => array('GET', 'POST'),
				'check_csrf_token' => false,
				'sef_path' => 'disks/groups/',
			);
			$listAction[self::ACTION_DISKS_COMMON] = array(
				'method' => array('GET', 'POST'),
				'check_csrf_token' => false,
				'sef_path' => 'disks/common/',
			);
			$listAction[self::ACTION_DISKS_TRASHCAN] = array(
				'method' => array('GET', 'POST'),
				'check_csrf_token' => false,
				'sef_path' => 'disks/trashcan/',
			);
			$listAction[self::ACTION_DISKS] = array(
				'method' => array('GET', 'POST'),
				'check_csrf_token' => false,
				'sef_path' => 'disks/',
			);

			$listAction[self::ACTION_FOLDER] = array(
				'method' => array('GET', 'POST'),
				'check_csrf_token' => false,
				'sef_path' => 'storage/#storageId#/#folderId#/',
			);
			$listAction[self::ACTION_STORAGE] = array(
				'method' => array('GET', 'POST'),
				'check_csrf_token' => false,
				'sef_path' => 'storage/#storageId#/',
			);
			$listAction[self::ACTION_FOLDER_FILES] = array(
				'method' => array('GET', 'POST'),
				'check_csrf_token' => false,
				'sef_path' => 'files/#storageId#/#folderId#/',
			);
			$listAction[self::ACTION_FILES] = array(
				'method' => array('GET', 'POST'),
				'check_csrf_token' => false,
				'sef_path' => 'files/#storageId#/',
			);

			$listAction[self::ACTION_TRASH_FILES] = array(
				'method' => array('GET', 'POST'),
				'check_csrf_token' => false,
				'sef_path' => 'trashcan/#storageId#/',
			);
		}

		return $listAction;
	}


	/**
	 * Returns list of indicator's ids for full scanning.
	 *
	 * @return array
	 */
	private function listFullScanIndicator()
	{
		$indicatorList = array();

		// must be first
		$indicatorList[] = array(
			'indicatorId' => Volume\Bfile::getIndicatorId()
		);
		$indicatorList[] = array(
			'indicatorId' => Volume\Module\Disk::getIndicatorId()
		);
		$indicatorList[] = array(
			'indicatorId' => Volume\Module\DiskTrashcan::getIndicatorId()
		);


		// full list available indicators
		$indicatorIdList = Volume\Base::listIndicator();

		// skip accessory type indicators
		$accessoryIndicatorList = array(
			Volume\Bfile::getIndicatorId(),
			Volume\Module\Disk::getIndicatorId(),
			Volume\Module\DiskTrashcan::getIndicatorId(),
			Volume\Module\Webdav::getIndicatorId(),
			Volume\Module\Iblock::getIndicatorId(),
			Volume\File::getIndicatorId(),
			Volume\FileDeleted::getIndicatorId(),
			Volume\Folder::getIndicatorId(),
			Volume\FolderTree::getIndicatorId(),
			Volume\FolderDeleted::getIndicatorId(),
			'Duplicate',// indicator has been removed
			'Storage_Uploaded',// indicator has been removed
		);

		foreach ($indicatorIdList as $indicatorId => $indicatorIdClass)
		{
			if (in_array($indicatorId, $accessoryIndicatorList))
			{
				continue;
			}
			$indicatorList[] = array(
				'indicatorId' => $indicatorId,
			);
		}

		return $indicatorList;
	}


	/**
	 * Returns queue list of action for full scanning.
	 *
	 * @return array
	 */
	private function queueScanActionList()
	{
		$queueList = array();

		if ($this->isAdminMode() && !$this->isOnlySpecificStorage())
		{
			$queueList[] = array(
				'action' => self::ACTION_CANCEL_WORKERS,
				'metric' => self::METRIC_MARK_GLOBAL_SCAN,
			);
			$queueList[] = array(
				'action' => self::ACTION_PURIFY,
			);

			$listIndicator = $this->listFullScanIndicator();
			foreach ($listIndicator as $indicatorParameters)
			{
				/** @var \Bitrix\Disk\Volume\IVolumeIndicator $ind */
				$ind = $this->getIndicator($indicatorParameters['indicatorId']);

				$indicatorParameters['subTaskCount'] = count($ind->getMeasureStages());
				$indicatorParameters['action'] = self::ACTION_MEASURE;

				$queueList[] = $indicatorParameters;
			}
			$queueList[] = array(
				'action' => self::ACTION_MEASURE_STORAGE,
				'storageId' => $this->getCurrentUserStorageId(),
				'subTaskCount' => self::ACTION_MEASURE_STORAGE_STEPS,
			);
		}
		else
		{
			$queueList[] = array(
				'action' => self::ACTION_CANCEL_WORKERS,
			);
			$queueList[] = array(
				'action' => self::ACTION_PURIFY,
				'storageId' => $this->storageId,
			);
			$queueList[] = array(
				'action' => self::ACTION_MEASURE_STORAGE,
				'storageId' => $this->storageId,
				'subTaskCount' => self::ACTION_MEASURE_STORAGE_STEPS,
			);
		}

		return $queueList;
	}


	/**
	 * Searches action in request url.
	 *
	 * @return self
	 */
	protected function resolveAction()
	{
		parent::resolveAction();

		if (!empty($this->arParams['SEF_MODE']) && $this->arParams['SEF_MODE'] === 'Y' && !empty($this->arParams['SEF_FOLDER']))
		{
			$listAction = $this->listActions();

			$urlTemplates404 = array(
				self::ACTION_DEFAULT => '',
			);
			foreach ($listAction as $action => $description)
			{
				if (!empty($description['sef_path']))
				{
					$urlTemplates404[$action] = $description['sef_path'];
				}
			}

			$variables = array();
			$action = \CComponentEngine::ParseComponentPath($this->arParams['SEF_FOLDER'], $urlTemplates404, $variables);

			if (!is_string($action) || !isset($urlTemplates404[$action]))
			{
				$action = self::ACTION_DEFAULT;
			}
			if ($action && isset($listAction[$action]))
			{
				$this->realActionName = $action;
			}

			if (!$this->realActionName || $this->realActionName === self::ACTION_DEFAULT)
			{
				$this->realActionName = self::ACTION_DEFAULT;
				$this->setAction($this->realActionName, array(
					'method' => array('GET', 'POST'),
					'name' => self::ACTION_DEFAULT,
					'check_csrf_token' => false,
				));
			}
			else
			{
				$listAction = $this->normalizeListOfAction($listAction);
				$description = $listAction[$this->realActionName];
				$this->setAction($description['name'], $description);
			}

			if (!is_null($variables['storageId']) && (int)$variables['storageId'] > 0)
			{
				$this->storageId = (int)$variables['storageId'];
			}
			if (!is_null($variables['folderId']) && (int)$variables['folderId'] > 0)
			{
				$this->folderId = (int)$variables['folderId'];
			}
		}

		return $this;
	}

	/**
	 * @return $this
	 */
	protected function prepareParams()
	{
		if (empty($this->arParams['SEF_MODE']))
		{
			$this->arParams['SEF_MODE'] = 'N';
		}

		if ($this->arParams['SEF_MODE'] === 'Y' && !empty($this->arParams['SEF_FOLDER']))
		{
			$this->arParams['RELATIVE_PATH'] = $this->arParams['SEF_FOLDER'];
		}
		else
		{
			$context = Application::getInstance()->getContext();
			$this->arParams['RELATIVE_PATH'] = $context->getRequest()->getRequestedPage();
		}

		if ($this->arParams['SEF_MODE'] === 'Y')
		{
			$listAction = $this->listActions();
			foreach ($listAction as $action => $description)
			{
				if (
					empty($this->arParams['PATH_TO_DISK_VOLUME_'.mb_strtoupper($action)]) &&
					!empty($description['sef_path'])
				)
				{
					$this->arParams['PATH_TO_DISK_VOLUME_'.mb_strtoupper($action)] = $description['sef_path'];
				}
			}
		}


		if (empty($this->arParams['AJAX_PATH']))
		{
			$this->arParams['AJAX_PATH'] = $this->getPath().'/ajax.php';
		}

		// expert mode switch on/off
		if (!is_null($this->request->get('expert')))
		{
			$_SESSION[self::SETTING_EXPERT_MODE] = ($this->request->get('expert') === 'on' ? 'on' : 'off');
		}

		// administrator mode switch on/off
		if ($this->isAdminModeAllowed())
		{
			if (!is_null($this->request->get('admin')))
			{
				$_SESSION[self::SETTING_ADMIN_MODE] = ($this->request->get('admin') === 'on' ? 'on' : 'off');
			}
		}

		if (!is_null($this->request->get('indicatorId')))
		{
			$this->indicatorId = $this->request->get('indicatorId');
		}

		// current user storageId
		$this->loadCurrentUserStorage();

		// specific storage
		if ($this->isOnlySpecificStorage())
		{
			$this->storageId = (int)$this->arParams['STORAGE_ID'];
		}
		elseif (!$this->isAdminMode())
		{
			$this->storageId = $this->getCurrentUserStorageId();
		}
		elseif (!is_null($this->request->get('storageId')))
		{
			$this->storageId = (int)$this->request->get('storageId');
		}


		if (!is_null($this->request->get('moduleId')))
		{
			$this->moduleId = $this->request->get('moduleId');
		}

		if (!is_null($this->request->get('folderId')))
		{
			$this->folderId = (int)$this->request->get('folderId');
		}

		if (!is_null($this->request->get('reload')))
		{
			$this->reload = (bool)($this->request->get('reload') === 'Y');
		}

		if (!is_null($this->request->get('filterId')))
		{
			$this->filterId = (int)$this->request->get('filterId');
		}

		if (!is_null($this->request->get('filterFolder')))
		{
			$this->onlyFolder = ($this->request->get('filterFolder') === 'Y');
		}

		return $this;
	}

	/**
	 * @var string $actionName
	 * @return bool
	 */
	protected function processBeforeAction($actionName)
	{
		parent::processBeforeAction($actionName);

		$this->errorCollection->clear();

		$user = $this->getUser();
		if (!$user || !$user->isAuthorized() || !$user->getId())
		{
			$this->showAccessDenied();

			return false;
		}

		// continue queue
		if ($actionName !== self::ACTION_DEFAULT && !is_null($this->getQueueStep()))
		{
			// queue is running only on default
			LocalRedirect($this->getActionUrl());
		}

		// check correct action per mode
		if ($actionName !== self::ACTION_DEFAULT && !$this->isExpertMode())
		{
			// for non expert only default action
			LocalRedirect($this->getActionUrl(array('action' => self::ACTION_DEFAULT)));
		}
		if ($actionName === self::ACTION_DEFAULT && $this->isAdminMode() && $this->isExpertMode() && ($this->reload === false))
		{
			// admin+expert go to disks from default action
			LocalRedirect($this->getActionUrl(array('action' => self::ACTION_DISKS)));
		}
		if ($actionName === self::ACTION_DEFAULT && !$this->isAdminMode() && !$this->isOnlySpecificStorage() && $this->isExpertMode() && ($this->reload === false))
		{
			// non admin + expert go to storage from default action
			LocalRedirect($this->getActionUrl(array('action' => self::ACTION_STORAGE, 'storageId' => '')));
		}
		if (!$this->isAdminModeAllowed() || !$this->isAdminMode())
		{
			// non admin allow only default, storage and files action
			if (!in_array($actionName, array(self::ACTION_DEFAULT, self::ACTION_STORAGE, self::ACTION_FILES, self::ACTION_FOLDER_FILES, self::ACTION_TRASH_FILES)))
			{
				LocalRedirect($this->getActionUrl());
			}
		}


		// check if indicator exists
		if ($this->indicatorId !== '')
		{
			try
			{
				$this->getIndicator($this->indicatorId);
			}
			catch (\Bitrix\Main\ObjectException $ex)
			{
				\ShowError($ex->getMessage());
			}
		}

		$this->arResult['ACTION'] = $this->getAction();
		$this->arResult['ADMIN_MODE'] = $this->isAdminMode();
		$this->arResult['ADMIN_MODE_ALLOW'] = $this->isAdminModeAllowed();
		$this->arResult['SPECIFIC_STORAGE_MODE'] = $this->isOnlySpecificStorage();
		$this->arResult['EXPERT_MODE'] = $this->isExpertMode();
		$this->arResult['ONLY_DISK_MODE'] = $this->useDiskSizeAsTotalVolume();
		if (
			$actionName === self::ACTION_DEFAULT ||
			($actionName === self::ACTION_DISKS && $this->isExpertMode())
		)
		{
			$this->arResult['WORKER_COUNT'] = \Bitrix\Disk\Volume\Cleaner::checkRestoreWorkers((int)$this->getUser()->getId());
		}
		else
		{
			$this->arResult['WORKER_COUNT'] = \Bitrix\Disk\Volume\Cleaner::countWorker((int)$this->getUser()->getId());
		}
		$this->arResult["NEED_RELOAD"] = $this->isNeedReload();

		$this->arResult["HAS_WORKER_IN_PROCESS"] = $this->hasWorkerInProcess();
		$this->arResult["WORKER_USES_CRONTAB"] = $this->canAgentUseCrontab();

		$this->arResult['FULL_SCAN_INDICATOR_LIST'] = $this->listFullScanIndicator();
		$this->arResult['SCAN_ACTION_LIST'] = $this->queueScanActionList();

		$this->arResult['LINK_TO_DISKS'] = $this->getActionUrl(array('action' => self::ACTION_DISKS));

		return true;
	}

	/**
	 * @return void
	 */
	protected function processActionDefault()
	{
		try
		{
			$this->arResult['QUEUE_RUNNING'] = false;
			$this->arResult['DATA_COLLECTED'] = false;

			if (!is_null($this->getQueueStep()))
			{
				$this->arResult['RUN_QUEUE'] = 'continue';
				$this->arResult['QUEUE_STEP'] = $this->getQueueStep();
				$this->arResult['QUEUE_RUNNING'] = true;
			}
			elseif ($this->reload)
			{
				$this->arResult['RUN_QUEUE'] = 'full';
				$this->arResult['QUEUE_RUNNING'] = true;
			}

			$this->arResult['DISK_EMPTY'] = $this->isDiskEmpty();

			if ($this->isOnlySpecificStorage() || !$this->isAdminMode())
			{
				try
				{
					$this->loadStorage();
				}
				catch (\Bitrix\Main\SystemException $exception)
				{
					$this->errorCollection->add(array(new Error($exception->getMessage(), $exception->getCode())));
				}

				$this->loadMeasurementResult(
					'TrashCan',
					Volume\Storage\TrashCan::getIndicatorId(),
					array('=STORAGE_ID' => $this->storageId)
				);

				$this->loadMeasurementResult(
					'Storage',
					Volume\Storage\Storage::getIndicatorId(),
					array('=STORAGE_ID' => $this->storageId)
				);

				$this->loadMeasurementResult(
					'FileType',
					Volume\FileType::getIndicatorId(),
					array(
						'=STORAGE_ID' => $this->storageId,
						'=FOLDER_ID' => null,
					)
				);
			}
			elseif ($this->isAdminMode())
			{
				$this->loadModulesResult();

				$this->loadMeasurementResult(
					'TrashCan',
					Volume\Storage\TrashCan::getIndicatorId(),
					array('!STORAGE_ID' => null)
				);

				$this->loadMeasurementResult(
					'Storage',
					Volume\Storage\Storage::getIndicatorId(),
					array('!STORAGE_ID' => null)
				);
			}

			// totals
			$this->arResult = array_merge($this->arResult, $this->getTotals());

			if (
				(isset($this->arResult['TrashCan'], $this->arResult['TrashCan']['FILE_COUNT']) && $this->arResult['TrashCan']['FILE_COUNT'] > 0) ||
				(isset($this->arResult['Storage'], $this->arResult['Storage']['FILE_COUNT']) && $this->arResult['Storage']['FILE_COUNT'] > 0) ||
				(is_array($this->arResult['MODULES']['LIST']) && count($this->arResult['MODULES']['LIST']) > 0)
			)
			{
				$this->arResult['DATA_COLLECTED'] = true;
			}

			if ($this->useDiskSizeAsTotalVolume() && $this->arResult['MODULES']['DISK_SIZE'] <= 0)
			{
				$this->arResult['MODULES']['LIST'] = array();
			}
			elseif ($this->arResult['MODULES']['FILE_SIZE'] <= 0)
			{
				$this->arResult['MODULES']['LIST'] = array();
			}

			if (!empty($this->arResult['MODULES']['LIST']))
			{
				foreach ($this->arResult['MODULES']['LIST'] as &$row)
				{
					try
					{
						$this->decorateResult($row);
					}
					catch (\Bitrix\Main\SystemException $exception)
					{
						continue;
					}
				}
				unset($row);
			}

		}
		catch (\Bitrix\Main\SystemException $exception)
		{
			$this->errorCollection->add(array(new Error($exception->getMessage(), $exception->getCode())));
		}
		$this->includeComponentTemplate();
	}

	/**
	 * @param string $action Component action command.
	 * @param string $indicatorId Indicator type id.
	 *
	 * @return array
	 */
	private function getHeaderDefinition($action, $indicatorId = '')
	{
		$result = array();
		/*
			sort_state
			first_order
			order
			sort_url
			sort
			showname
			original_name
			name
			align
			is_shown
			class
			width
			editable
			prevent_default
		*/
		if ($action === self::ACTION_DISKS)
		{
			$result = array();

			$result[] = array(
				'id' => 'TITLE',
				'name' => Loc::getMessage('DISK_VOLUME_TITLE'),
				'default' => true,
				'sort' => 'TITLE',
				'first_order' => 'ASC',
				'prevent_default' => false,
			);

			if ($indicatorId === Volume\Storage\TrashCan::getIndicatorId())
			{
				$result[] = array(
					'id' => 'TRASHCAN_SIZE',
					'name' => Loc::getMessage('DISK_VOLUME_TRASHCAN_SIZE'),
					'default' => true,
					'align' => 'right',
					'sort' => 'FILE_SIZE',
					'first_order' => 'desc',
					//'class' => 'disk-volume-hint',
				);
			}
			else
			{
				$result[] = array(
					'id' => 'FILE_SIZE',
					'name' => Loc::getMessage('DISK_VOLUME_SIZE'),
					'default' => true,
					'align' => 'right',
					'sort' => 'FILE_SIZE',
					'first_order' => 'desc',
					//'class' => 'disk-volume-hint',
				);
			}


			$result[] = array(
				'id' => 'PERCENT',
				'name' => Loc::getMessage('DISK_VOLUME_PERCENT'),
				'default' => true,
				'sort' => 'PERCENT',
				'first_order' => 'desc',
				'align' => 'right',
				//'class' => 'disk-volume-hint',
			);

			$result[] = array(
				'id' => 'FILE_COUNT',
				'name' => Loc::getMessage('DISK_VOLUME_COUNT_FILES'),
				'default' => false,
				'align' => 'right',
				'sort' => 'FILE_COUNT',
				'first_order' => 'desc',
				//'class' => 'disk-volume-hint',
			);

			$result[] = array(
				'id' => 'VERSION_COUNT',
				'name' => Loc::getMessage('DISK_VOLUME_VERSION_COUNT'),
				'default' => true,
				'align' => 'right',
				'sort' => 'VERSION_COUNT',
				'first_order' => 'desc',
				//'class' => 'disk-volume-hint',
			);

			if ($indicatorId !== Volume\Storage\TrashCan::getIndicatorId())
			{
				$result[] = array(
					'id' => 'UNNECESSARY_VERSION_SIZE',
					'name' => Loc::getMessage('DISK_VOLUME_UNNECESSARY_VERSION_SIZE'),
					'default' => true,
					'align' => 'right',
					'sort' => 'UNNECESSARY_VERSION_SIZE',
					'first_order' => 'desc',
					//'class' => 'disk-volume-hint',
				);
				$result[] = array(
					'id' => 'UNNECESSARY_VERSION_COUNT',
					'name' => Loc::getMessage('DISK_VOLUME_UNNECESSARY_VERSION_COUNT'),
					'default' => false,
					'align' => 'right',
					'sort' => 'UNNECESSARY_VERSION_COUNT',
					'first_order' => 'desc',
					//'class' => 'disk-volume-hint',
				);

				$result[] = array(
					'id' => 'TRASHCAN_SIZE',
					'name' => Loc::getMessage('DISK_VOLUME_TRASHCAN_SIZE'),
					'default' => true,
					'align' => 'right',
					//'class' => 'disk-volume-hint',
					//'sort' => 'FILE_SIZE',
					//'first_order' => 'desc',
				);
			}

		}
		elseif ($action === self::ACTION_STORAGE)
		{
			$result = array(
				array(
					'id' => 'TITLE',
					'name' => Loc::getMessage('DISK_VOLUME_TITLE'),
					'default' => true,
					'sort' => 'TITLE',
					'first_order' => 'ASC',
				),

				array(
					'id' => 'FILE_SIZE',
					'name' => Loc::getMessage('DISK_VOLUME_FOLDER_SIZE'),
					'default' => true,
					'align' => 'right',
					'sort' => 'FILE_SIZE',
					'first_order' => 'desc',
					//'class' => 'disk-volume-hint',
				),

				array(
					'id' => 'PERCENT',
					'name' => Loc::getMessage('DISK_VOLUME_PERCENT'),
					'default' => false,
					'sort' => 'PERCENT',
					'first_order' => 'desc',
					'align' => 'right',
					//'class' => 'disk-volume-hint',
				),


				array(
					'id' => 'FILE_COUNT',
					'name' => Loc::getMessage('DISK_VOLUME_COUNT_FILES'),
					'default' => true,
					'align' => 'right',
					'sort' => 'FILE_COUNT',
					'first_order' => 'desc',
					//'class' => 'disk-volume-hint',
				),

				array(
					'id' => 'VERSION_COUNT',
					'name' => Loc::getMessage('DISK_VOLUME_VERSION_COUNT'),
					'default' => true,
					'align' => 'right',
					'sort' => 'VERSION_COUNT',
					'first_order' => 'desc',
					//'class' => 'disk-volume-hint',
				),

				/*
				array(
					'id' => 'USING_COUNT',
					'name' => Loc::getMessage('DISK_VOLUME_USING_COUNT'),
					'default' => true,
					'sort' => 'USING_COUNT',
					'first_order' => 'desc',
				),
				*/

				array(
					'id' => 'UNNECESSARY_VERSION_SIZE',
					'name' => Loc::getMessage('DISK_VOLUME_UNNECESSARY_VERSION_SIZE'),
					'default' => true,
					'align' => 'right',
					'sort' => 'UNNECESSARY_VERSION_SIZE',
					'first_order' => 'desc',
					//'class' => 'disk-volume-hint',
				),

				array(
					'id' => 'UNNECESSARY_VERSION_COUNT',
					'name' => Loc::getMessage('DISK_VOLUME_UNNECESSARY_VERSION_COUNT'),
					'default' => false,
					'align' => 'right',
					'sort' => 'UNNECESSARY_VERSION_COUNT',
					'first_order' => 'desc',
					//'class' => 'disk-volume-hint',
				),

				array(
					'id' => 'UPDATE_TIME',
					'name' => Loc::getMessage('DISK_VOLUME_UPDATE_TIME'),
					'default' => true,
					'sort' => 'UPDATE_TIME',
					'first_order' => 'desc',
					//'class' => 'disk-volume-hint',
				),
			);
		}
		elseif ($action === self::ACTION_FILES || $action === self::ACTION_FOLDER)
		{
			$result = array(
				array(
					'id' => 'TITLE',
					'name' => Loc::getMessage('DISK_VOLUME_TITLE'),
					'default' => true,
					'sort' => 'TITLE',
					'first_order' => 'ASC',
				),

				array(
					'id' => 'SIZE_FILE',
					'name' => Loc::getMessage('DISK_VOLUME_SIZE_FILE'),
					'default' => true,
					'align' => 'right',
					'sort' => 'SIZE_FILE',
					'first_order' => 'desc',
					//'class' => 'disk-volume-hint',
				),

				array(
					'id' => 'VERSION_SIZE',
					'name' => Loc::getMessage('DISK_VOLUME_VERSION_SIZE'),
					'default' => true,
					'align' => 'right',
					'sort' => 'VERSION_SIZE',
					'first_order' => 'desc',
					//'class' => 'disk-volume-hint',
				),

				array(
					'id' => 'VERSION_COUNT',
					'name' => Loc::getMessage('DISK_VOLUME_FILE_VERSION_COUNT'),
					'default' => true,
					'align' => 'right',
					'sort' => 'VERSION_COUNT',
					'first_order' => 'desc',
					//'class' => 'disk-volume-hint',
				),

				array(
					'id' => 'UNNECESSARY_VERSION_SIZE',
					'name' => Loc::getMessage('DISK_VOLUME_UNNECESSARY_VERSION_SIZE'),
					'default' => true,
					'align' => 'right',
					'sort' => 'UNNECESSARY_VERSION_SIZE',
					'first_order' => 'desc',
					//'class' => 'disk-volume-hint',
				),

				array(
					'id' => 'UNNECESSARY_VERSION_COUNT',
					'name' => Loc::getMessage('DISK_VOLUME_UNNECESSARY_VERSION_COUNT'),
					'default' => false,
					'align' => 'right',
					'sort' => 'UNNECESSARY_VERSION_COUNT',
					'first_order' => 'desc',
					//'class' => 'disk-volume-hint',
				),

				array(
					'id' => 'USING_COUNT',
					'name' => Loc::getMessage('DISK_VOLUME_USING_COUNT'),
					'default' => true,
					//'sort' => 'USING_COUNT',
					//'first_order' => 'desc',
					//'class' => 'disk-volume-hint',
				),

				array(
					'id' => 'UPDATE_TIME',
					'name' => Loc::getMessage('DISK_VOLUME_UPDATE_TIME'),
					'default' => true,
					'sort' => 'UPDATE_TIME',
					'first_order' => 'desc',
					//'class' => 'disk-volume-hint',
				),
			);
		}

		return $result;
	}

	/**
	 * @param string $action Component action command.
	 *
	 * @return array
	 */
	private function getFilterDefinition($action)
	{
		$result = array();
		if ($action === self::ACTION_DISKS)
		{
			$result['indicatorId'] = array(
				'id' => 'indicatorId',
				'name' => Loc::getMessage('DISK_VOLUME_DISK_TYPE'),
				'type' => 'list',
				'items' => array(
					//Volume\Storage\Storage::getIndicatorId() => Loc::getMessage('DISK_VOLUME_TOP_ALL'),
					Volume\Storage\User::getIndicatorId() => Loc::getMessage('DISK_VOLUME_TOP_USER'),
					Volume\Storage\Group::getIndicatorId() => Loc::getMessage('DISK_VOLUME_TOP_GROUP'),
					Volume\Storage\Common::getIndicatorId() => Loc::getMessage('DISK_VOLUME_TOP_COMMON'),
					Volume\Storage\TrashCan::getIndicatorId() => Loc::getMessage('DISK_VOLUME_TOP_TRASHCAN'),
				),
				'default' => true,
			);
			$result['TITLE'] =
				array(
					'id' => 'TITLE',
					'name' => Loc::getMessage('DISK_VOLUME_STORAGE_TITLE'),
					'type' => 'string',
					'default' => true,
				);

			/*
			$result['CREATED_BY_ID'] = array(
				'id' => 'CREATED_BY_ID',
				'name' => Loc::getMessage('DISK_VOLUME_STORAGE_OWNER'),
				'default' => true,
				'type' => 'custom_entity',
				'selector' =>
					array (
						'TYPE' => 'user',
						'DATA' =>
							array (
								'ID' => 'created_by',
								'FIELD_ID' => 'CREATED_BY_ID',
							),
					),
			);
			*/

		}
		elseif ($action === self::ACTION_STORAGE)
		{
			$result['TITLE'] =
				array(
					'id' => 'TITLE',
					'name' => Loc::getMessage('DISK_VOLUME_FOLDER_TITLE'),
					'type' => 'string',
					'default' => true,
				);
		}
		elseif ($action === self::ACTION_FILES)
		{
			$result['TITLE'] =
				array(
					'id' => 'TITLE',
					'name' => Loc::getMessage('DISK_VOLUME_FILE_TITLE'),
					'type' => 'string',
					'default' => true,
				);

			$fileTypes = \Bitrix\Disk\TypeFile::getListOfValues();
			$items = array();
			foreach ($fileTypes as $fileTypeId)
			{
				switch ($fileTypeId)
				{
					case \Bitrix\Disk\TypeFile::PDF:
					case \Bitrix\Disk\TypeFile::KNOWN:
					case \Bitrix\Disk\TypeFile::UNKNOWN:
						break;
					default:
						$items[$fileTypeId] = \Bitrix\Disk\TypeFile::getName($fileTypeId);
				}
			}
			$result['TYPE_FILE'] =
				array(
					'id' => 'TYPE',
					'name' => Loc::getMessage('DISK_VOLUME_TYPE_FILE'),
					'params' => array('multiple' => 'Y'),
					'type' => 'list',
					'items' => $items,
					'multiple' => true,
					'default' => true,
				);
		}

		return $result;
	}

	/**
	 * @param string $action Component action command.
	 *
	 * @return array
	 */
	private function getFilterPresetsDefinition($action)
	{
		$result = array();
		if ($action === self::ACTION_DISKS)
		{
			/*$result[self::ACTION_DISKS] = array(
				'name' => Loc::getMessage('DISK_VOLUME_TOP_ALL'),
				'default' => true,
				'fields' => array(
					'indicatorId' => Volume\Storage\Storage::getIndicatorId(),
				)
			);*/
			$result[self::ACTION_DISKS_USERS] = array(
				'name' => Loc::getMessage('DISK_VOLUME_TOP_USER'),
				'fields' => array(
					'indicatorId' => Volume\Storage\User::getIndicatorId(),
				)
			);
			$result[self::ACTION_DISKS_GROUPS] = array(
				'name' => Loc::getMessage('DISK_VOLUME_TOP_GROUP'),
				'fields' => array(
					'indicatorId' => Volume\Storage\Group::getIndicatorId(),
				)
			);
			$result[self::ACTION_DISKS_COMMON] = array(
				'name' => Loc::getMessage('DISK_VOLUME_TOP_COMMON'),
				'fields' => array(
					'indicatorId' => Volume\Storage\Common::getIndicatorId(),
				)
			);
			$result[self::ACTION_DISKS_TRASHCAN] = array(
				'name' => Loc::getMessage('DISK_VOLUME_TOP_TRASHCAN'),
				'fields' => array(
					'indicatorId' => Volume\Storage\TrashCan::getIndicatorId(),
				)
			);
		}

		return $result;
	}

	/**
	 * @param string $action Component action command.
	 *
	 * @return array
	 */
	private function getFilter($action)
	{
		$filterPresetList = $this->getFilterPresetsDefinition($action);
		$filterOptions = new \Bitrix\Main\UI\Filter\Options($this->arResult['FILTER_ID'], $filterPresetList);
		$filter = $filterOptions->getFilter($this->getFilterDefinition($action));

		// Predefined filter sets
		if (isset($filter['PRESET_ID']) && $filter['FILTER_APPLIED'] == '1')
		{
			if ($filter['PRESET_ID'] === 'tmp_filter')
			{
				$this->indicatorId = $filter['indicatorId'];
			}
			else
			{
				if (isset($filterPresetList[$filter['PRESET_ID']]))
				{
					$filterPreset = $filterPresetList[$filter['PRESET_ID']];
					$this->indicatorId = $filterPreset['fields']['indicatorId'];
				}
			}

			if (empty($this->indicatorId))
			{
				switch ($action)
				{
					case self::ACTION_DISKS:
						$this->indicatorId = Volume\Storage\Storage::getIndicatorId();
						break;
					case self::ACTION_STORAGE:
						$this->indicatorId = Volume\Folder::getIndicatorId();
						break;
					case self::ACTION_FILES:
						$this->indicatorId = Volume\File::getIndicatorId();
						break;
				}
			}

			$find = '';
			if (!empty($filter['FIND']) && trim($filter['FIND']) <> '')
			{
				$find = $filter['%TITLE'] = trim($filter['FIND']);
			}
			$title = '';
			if (!empty($filter['TITLE']) && trim($filter['TITLE']) <> '')
			{
				$title = $filter['%TITLE'] = trim($filter['TITLE']);
			}
			if (!empty($find) && !empty($title))
			{
				$filter['LOGIC'] = 'AND';
				$filter[] = array('%TITLE' => $find);
				$filter[] = array('%TITLE' => $title);
				unset($filter['%TITLE']);
			}

			unset(
				$filter['indicatorId'],
				$filter['FILTER_ID'],
				$filter['PRESET_ID'],
				$filter['FIND'],
				$filter['FILTER_APPLIED'],
				$filter['TITLE']
			);
		}

		return $filter;
	}


	/**
	 * @return void
	 */
	protected function processActionUsers()
	{
		$this->action = self::ACTION_DISKS;
		$this->arResult['ACTION'] = $this->getAction();
		$this->indicatorId = Volume\Storage\User::getIndicatorId();
		$this->processActionDisks();
	}

	/**
	 * @return void
	 */
	protected function processActionGroups()
	{
		$this->action = self::ACTION_DISKS;
		$this->arResult['ACTION'] = $this->getAction();
		$this->indicatorId = Volume\Storage\Group::getIndicatorId();
		$this->processActionDisks();
	}

	/**
	 * @return void
	 */
	protected function processActionCommon()
	{
		$this->action = self::ACTION_DISKS;
		$this->arResult['ACTION'] = $this->getAction();
		$this->indicatorId = Volume\Storage\Common::getIndicatorId();
		$this->processActionDisks();
	}

	/**
	 * @return void
	 */
	protected function processActionTrashcan()
	{
		$this->action = self::ACTION_DISKS;
		$this->arResult['ACTION'] = $this->getAction();
		$this->indicatorId = Volume\Storage\TrashCan::getIndicatorId();
		$this->processActionDisks();
	}

	/**
	 * @return void
	 */
	protected function processActionDisks()
	{
		$this->arResult['GRID_ID'] = 'diskVolumeDiskGrid';
		$this->arResult['FILTER_ID'] = 'diskVolumeDiskFilter';
		$this->arResult['DATA_COLLECTED'] = false;

		$this->arResult['FILTER_PRESETS'] = $this->getFilterPresetsDefinition($this->getAction());

		$gridOptions = new \Bitrix\Main\Grid\Options($this->arResult['GRID_ID'], $this->arResult['FILTER_PRESETS']);

		$filter = $this->getFilter($this->getAction());

		// Default indicator type
		if (empty($this->indicatorId))
		{
			$this->indicatorId = Volume\Storage\Storage::getIndicatorId();
		}

		$this->arResult['INDICATOR'] = $this->indicatorId;
		$this->arResult['HEADERS'] = $this->getHeaderDefinition($this->getAction(), $this->indicatorId);
		$this->arResult['FILTER'] = $this->getFilterDefinition($this->getAction());

		// Sorting order
		$sorting = $gridOptions->GetSorting(array('sort' => array('FILE_SIZE' => 'desc')));

		// Per page navigation
		$navParams = $gridOptions->GetNavParams();
		$pageSize = $navParams['nPageSize'];

		$nav = new \Bitrix\Main\UI\PageNavigation('page');
		$nav->allowAllRecords(false)
			->setPageSize($pageSize)
			->initFromUri();

		$pagination = array(
			'offset' => $nav->getOffset(),
			'limit' => $nav->getLimit(),
		);

		try
		{
			$cursor = $this->loadMeasurementResult(
				$this->indicatorId,
				$this->indicatorId,
				$filter,
				$sorting['sort'],
				$pagination
			);
			if ($cursor instanceof \Bitrix\Main\DB\Result && $cursor->getSelectedRowsCount() > 0)
			{
				$this->arResult['DATA_COLLECTED'] = true;
				$this->arResult['ROWS_COUNT'] = $cursor->getCount();
				$nav->setRecordCount($cursor->getCount());

				$this->arResult['SORT'] = $sorting['sort'];
				$this->arResult['SORT_VARS'] = $sorting['vars'];
				$this->arResult['NAV_OBJECT'] = $nav;
				$this->arResult['CURRENT_PAGE'] = $nav->getCurrentPage();
			}
		}
		catch (\Bitrix\Main\SystemException $exception)
		{
			$this->errorCollection->add(array(new Error($exception->getMessage(), $exception->getCode())));
		}

		try
		{
			$this->loadMeasurementResult(
				'Storage',
				Volume\Storage\Storage::getIndicatorId(),
				array('!STORAGE_ID' => null)
			);

			$this->loadMeasurementResult(
				'TrashCan',
				Volume\Storage\TrashCan::getIndicatorId(),
				array('!STORAGE_ID' => null)
			);

			$this->loadMeasurementResult(
				'FileType',
				Volume\FileType::getIndicatorId(),
				array(
					'!STORAGE_ID' => null,
					'=FOLDER_ID' => null,
				),
				array(),
				array(),
				true
			);

			// totals
			$this->arResult = array_merge($this->arResult, $this->getTotals());
			if ($this->arResult['TOTAL_FILE_COUNT'])
			{
				$this->arResult['DATA_COLLECTED'] = true;
			}
		}
		catch (\Bitrix\Main\SystemException $exception)
		{
			$this->errorCollection->add(array(new Error($exception->getMessage(), $exception->getCode())));
		}

		//$this->arResult['MENU_ITEMS'] = $this->getMenuItems();

		$this->includeComponentTemplate(self::TEMPLATE_DISK_TOP);
	}


	/**
	 * @return void
	 */
	protected function processActionFolder()
	{
		$this->action = self::ACTION_STORAGE;
		$this->arResult['ACTION'] = $this->getAction();
		$this->processActionStorage();
	}

	/**
	 * @return void
	 */
	protected function processActionStorage()
	{
		$this->arResult['GRID_ID'] = 'diskVolumeStorageGrid';
		$this->arResult['FILTER_ID'] = 'diskVolumeStorageFilter';
		$this->arResult['DATA_COLLECTED'] = false;
		$this->arResult['BREAD_CRUMB'] = [];

		// Default indicator type
		if (empty($this->indicatorId))
		{
			$this->indicatorId = Volume\Folder::getIndicatorId();
		}

		$this->arResult['FILTER_PRESETS'] = $this->getFilterPresetsDefinition($this->getAction());
		$this->arResult['INDICATOR'] = $this->indicatorId;

		$this->arResult['HEADERS'] = $this->getHeaderDefinition($this->getAction());
		$this->arResult['FILTER'] = $this->getFilterDefinition($this->getAction());

		try
		{
			$this->loadStorage();
			$this->loadFolder();
		}
		catch (\Bitrix\Main\SystemException $exception)
		{
			$this->errorCollection->add(array(new Error($exception->getMessage(), $exception->getCode())));
			$this->includeComponentTemplate(self::TEMPLATE_FOLDER_TOP);

			return;
		}

		// im storage
		$isImStorage = in_array($this->storage->getEntityType(), \Bitrix\Disk\Volume\Module\Im::getEntityType());
		if ($isImStorage === true)
		{
			$imIndicator = new \Bitrix\Disk\Volume\Module\Im();
			if ($imIndicator->isMeasureAvailable() === false)
			{
				$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_VOLUME_IM_STORAGE_FAIL'), 'IM_STORAGE_FAIL')));
				$this->includeComponentTemplate(self::TEMPLATE_FOLDER_TOP);
			}
		}


		$this->arResult['STORAGE_ID'] = $this->storageId;
		$this->arResult['STORAGE_ROOT_ID'] = $this->storage->getRootObjectId();
		$this->arResult['FOLDER_ID'] = $this->folderId;

		if ($this->reload)
		{
			try
			{
				$row = \Bitrix\Disk\Internals\VolumeTable::getByPrimary($this->filterId, array('select' => array('INDICATOR_TYPE')))->fetch();
				if ($row && !empty($row['INDICATOR_TYPE']))
				{
					/** @var Volume\IVolumeIndicator $storageIndicatorType */
					$storageIndicatorType = $row['INDICATOR_TYPE'];
				}
				else
				{
					$storageIndicatorType = Volume\Storage\Storage::className();
				}
				$storageIndicatorId = $storageIndicatorType::getIndicatorId();

				if ($storageIndicatorType == Volume\Storage\TrashCan::className())
				{
					$folderIndicatorId = Volume\FolderDeleted::getIndicatorId();
					$fileIndicatorId = Volume\FileDeleted::getIndicatorId();
				}
				else
				{
					$folderIndicatorId = Volume\FolderTree::getIndicatorId();
					$fileIndicatorId = Volume\File::getIndicatorId();
				}

				$this->reload(
					$storageIndicatorId,
					[
						'=STORAGE_ID' => $this->storageId,
					],
					$this->filterId,
					true
				);

				$this->reload($folderIndicatorId, [
					'=STORAGE_ID' => $this->storageId,
					'=FOLDER_ID' => $this->folderId,
				]);

				if ($storageIndicatorType != Volume\Storage\TrashCan::className())
				{
					$this->reload(
						Volume\Storage\TrashCan::getIndicatorId(),
						array(
							'=STORAGE_ID' => $this->storageId,
						),
						-1,
						true
					);
				}

				$this->reload(
					$fileIndicatorId,
					array(
						'=STORAGE_ID' => $this->storageId,
						'=PARENT_ID' => $this->folderId,
					),
					-1,
					true
				);

				$this->reload(
					$fileIndicatorId,
					array(
						'=STORAGE_ID' => $this->storageId,
						'=FOLDER_ID' => $this->folderId,
					),
					-1,
					true
				);

				$this->clearQueueStep();

				if ($this->filterId > 0)
				{
					VolumeTable::update($this->filterId, array('COLLECTED' => 1));
				}
			}
			catch (\Bitrix\Main\SystemException $exception)
			{
				$this->errorCollection->add(array(new Error($exception->getMessage(), $exception->getCode())));
			}
		}

		$gridOptions = new \Bitrix\Main\Grid\Options($this->arResult['GRID_ID'], $this->arResult['FILTER_PRESETS']);

		$filter = $this->getFilter($this->getAction());

		$filter['=STORAGE_ID'] = $this->storageId;// only one storage
		//$filter['!PARENT_ID'] = null;// exclude root folder

		// Sorting order
		$sorting = $gridOptions->GetSorting(array('sort' => array('FILE_SIZE' => 'desc')));
		if (isset($sorting['sort']['UPDATE_TIME']))
		{
			$sorting['sort']['FOLDER.UPDATE_TIME'] = $sorting['sort']['UPDATE_TIME'];
			unset($sorting['sort']['UPDATE_TIME']);
		}

		// Per page navigation
		$navParams = $gridOptions->GetNavParams();
		$pageSize = $navParams['nPageSize'];

		$nav = new \Bitrix\Main\UI\PageNavigation('page');
		$nav->allowAllRecords(false)
			->setPageSize($pageSize)
			->initFromUri();

		$pagination = array(
			'offset' => $nav->getOffset(),
			'limit' => $nav->getLimit(),
		);

		try
		{
			$cursor = $this->loadMeasurementResult(
				$this->indicatorId,
				$this->indicatorId,
				$filter,
				$sorting['sort'],
				$pagination
			);
			if ($cursor instanceof \Bitrix\Main\DB\Result && $cursor->getSelectedRowsCount() > 0)
			{
				$this->arResult['ROWS_COUNT'] = $cursor->getCount();
				$nav->setRecordCount($cursor->getCount());

				$this->arResult['SORT'] = $sorting['sort'];
				$this->arResult['SORT_VARS'] = $sorting['vars'];
				$this->arResult['NAV_OBJECT'] = $nav;
				$this->arResult['CURRENT_PAGE'] = $nav->getCurrentPage();
			}
		}
		catch (\Bitrix\Main\SystemException $exception)
		{
			$this->errorCollection->add(array(new Error($exception->getMessage(), $exception->getCode())));
		}

		try
		{
			$this->loadMeasurementResult(
				'Storage',
				Volume\Storage\Storage::getIndicatorId(),
				array(
					'=STORAGE_ID' => $this->storageId
				)
			);

			$this->loadMeasurementResult(
				'FileType',
				Volume\FileType::getIndicatorId(),
				array(
					'=STORAGE_ID' => $this->storageId,
					'=FOLDER_ID' => null,
				)
			);

			$this->loadMeasurementResult(
				'TrashCan',
				Volume\Storage\TrashCan::getIndicatorId(),
				array(
					'STORAGE_ID' => $this->storageId
				)
			);

			// totals
			$this->arResult = array_merge($this->arResult, $this->getTotals());

			if ($this->arResult['TOTAL_FILE_COUNT'])
			{
				$this->arResult['DATA_COLLECTED'] = true;
			}

			$this->arResult['BREAD_CRUMB'] = $this->getBreadcrumb();
		}
		catch (\Bitrix\Main\SystemException $exception)
		{
			$this->errorCollection->add(array(new Error($exception->getMessage(), $exception->getCode())));
		}

		//$this->arResult['MENU_ITEMS'] = $this->getMenuItems();

		$this->arResult['ACTION_MENU'] = $this->getStorageMenuItems();

		$this->includeComponentTemplate(self::TEMPLATE_FOLDER_TOP);
	}


	/**
	 * @return void
	 */
	protected function processActionFolderFiles()
	{
		$this->action = self::ACTION_FILES;
		$this->arResult['ACTION'] = $this->getAction();
		$this->processActionFiles();
	}

	/**
	 * @return void
	 */
	protected function processActionFiles()
	{
		$this->arResult['GRID_ID'] = 'diskVolumeFilesGrid';
		$this->arResult['FILTER_ID'] = 'diskVolumeFilesFilter';
		$this->arResult['DATA_COLLECTED'] = false;
		$this->arResult['BREAD_CRUMB'] = [];

		// Default indicator type
		$this->indicatorId = Volume\File::getIndicatorId();

		$this->arResult['FILTER_PRESETS'] = $this->getFilterPresetsDefinition($this->getAction());
		$this->arResult['INDICATOR'] = $this->indicatorId;

		$this->arResult['HEADERS'] = $this->getHeaderDefinition($this->getAction());
		$this->arResult['FILTER'] = $this->getFilterDefinition($this->getAction());

		try
		{
			$this->loadStorage();
			$this->loadFolder();
		}
		catch (\Bitrix\Main\SystemException $exception)
		{
			$this->errorCollection->add(array(new Error($exception->getMessage(), $exception->getCode())));
			$this->includeComponentTemplate(self::TEMPLATE_FILE_TOP);

			return;
		}

		// im storage
		$isImStorage = in_array($this->storage->getEntityType(), \Bitrix\Disk\Volume\Module\Im::getEntityType());
		if ($isImStorage === true)
		{
			$imIndicator = new \Bitrix\Disk\Volume\Module\Im();
			if ($imIndicator->isMeasureAvailable() === false)
			{
				$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_VOLUME_IM_STORAGE_FAIL'), 'IM_STORAGE_FAIL')));
				$this->includeComponentTemplate(self::TEMPLATE_FILE_TOP);
			}
		}

		$this->arResult['STORAGE_ID'] = $this->storageId;
		$this->arResult['STORAGE_ROOT_ID'] = $this->storage->getRootObjectId();
		$this->arResult['FOLDER_ID'] = $this->folderId;

		if ($this->reload)
		{
			try
			{
				$this->reload(
					Volume\File::getIndicatorId(),
					array(
						'=STORAGE_ID' => $this->storageId,
						'=PARENT_ID' => $this->folderId
					),
					-1,
					true
				);

				$this->reload(
					Volume\File::getIndicatorId(),
					array(
						'=STORAGE_ID' => $this->storageId,
						'=FOLDER_ID' => $this->folderId
					),
					-1,
					true
				);

				$this->clearQueueStep();

				if ($this->filterId > 0)
				{
					VolumeTable::update($this->filterId, array('COLLECTED' => 1));
				}
			}
			catch (\Bitrix\Main\SystemException $exception)
			{
				$this->errorCollection->add(array(new Error($exception->getMessage(), $exception->getCode())));
			}
		}

		$gridOptions = new \Bitrix\Main\Grid\Options($this->arResult['GRID_ID'], $this->arResult['FILTER_PRESETS']);

		$filter = $this->getFilter($this->getAction());

		$filter['=STORAGE_ID'] = $this->storageId;// only one storage
		if ($this->folderId > 0)
		{
			$filter['PARENT_ID'] = $this->folderId;
		}
		// only root folder
		if ($this->onlyFolder)
		{
			$filter['FOLDER_ID'] = $this->folderId;
		}

		// TypeFile::PDF and TypeFile::KNOWN file interprets as TypeFile::DOCUMENT type
		if (isset($filter['TYPE']) && in_array(\Bitrix\Disk\TypeFile::DOCUMENT, $filter['TYPE']))
		{
			$filter['TYPE'][] = \Bitrix\Disk\TypeFile::PDF;
			$filter['TYPE'][] = \Bitrix\Disk\TypeFile::KNOWN;
		}

		// Sorting order
		$sorting = $gridOptions->GetSorting(array('sort' => array('VERSION_SIZE' => 'desc')));

		// Per page navigation
		$navParams = $gridOptions->GetNavParams();
		$pageSize = $navParams['nPageSize'];

		$nav = new \Bitrix\Main\UI\PageNavigation('page');
		$nav->allowAllRecords(false)
			->setPageSize($pageSize)
			->initFromUri();

		$pagination = array(
			'offset' => $nav->getOffset(),
			'limit' => $nav->getLimit(),
		);

		try
		{
			$cursor = $this->loadMeasurementResult(
				$this->indicatorId,
				$this->indicatorId,
				$filter,
				$sorting['sort'],
				$pagination
			);
			if ($cursor instanceof \Bitrix\Main\DB\Result && $cursor->getSelectedRowsCount() > 0)
			{
				$this->arResult['ROWS_COUNT'] = $cursor->getCount();
				$nav->setRecordCount($cursor->getCount());

				$this->arResult['SORT'] = $sorting['sort'];
				$this->arResult['SORT_VARS'] = $sorting['vars'];
				$this->arResult['NAV_OBJECT'] = $nav;
				$this->arResult['CURRENT_PAGE'] = $nav->getCurrentPage();
			}
		}
		catch (\Bitrix\Main\SystemException $exception)
		{
			$this->errorCollection->add(array(new Error($exception->getMessage(), $exception->getCode())));
		}

		try
		{
			$this->loadMeasurementResult(
				'Storage',
				Volume\Storage\Storage::getIndicatorId(),
				array(
					'=STORAGE_ID' => $this->storageId
				)
			);

			$this->loadMeasurementResult(
				'FileType',
				Volume\FileType::getIndicatorId(),
				array(
					'=STORAGE_ID' => $this->storageId,
					'=FOLDER_ID' => null,
				)
			);

			$this->loadMeasurementResult(
				'TrashCan',
				Volume\Storage\TrashCan::getIndicatorId(),
				array(
					'=STORAGE_ID' => $this->storageId
				)
			);

			// totals
			$this->arResult = array_merge($this->arResult, $this->getTotals());

			if ($this->arResult['TOTAL_FILE_COUNT'])
			{
				$this->arResult['DATA_COLLECTED'] = true;
			}

			$this->arResult['BREAD_CRUMB'] = $this->getBreadcrumb();
		}
		catch (\Bitrix\Main\SystemException $exception)
		{
			$this->errorCollection->add(array(new Error($exception->getMessage(), $exception->getCode())));
		}

		//$this->arResult['MENU_ITEMS'] = $this->getMenuItems();

		$this->arResult['ACTION_MENU'] = $this->getStorageMenuItems();


		$this->includeComponentTemplate(self::TEMPLATE_FILE_TOP);
	}


	/**
	 * @return void
	 */
	protected function processActionTrashFiles()
	{
		$this->action = self::ACTION_FILES;
		$this->arResult['ACTION'] = $this->getAction();

		$this->arResult['GRID_ID'] = 'diskVolumeFilesGrid';
		$this->arResult['FILTER_ID'] = 'diskVolumeFilesFilter';
		$this->arResult['DATA_COLLECTED'] = false;

		// Default indicator type
		$this->indicatorId = Volume\FileDeleted::getIndicatorId();
		$this->folderId = null;


		$this->arResult['FILTER_PRESETS'] = $this->getFilterPresetsDefinition($this->getAction());
		$this->arResult['INDICATOR'] = $this->indicatorId;

		$this->arResult['HEADERS'] = $this->getHeaderDefinition($this->getAction());
		$this->arResult['FILTER'] = $this->getFilterDefinition($this->getAction());

		try
		{
			$this->loadStorage();
			//$this->loadFolder();
		}
		catch (\Bitrix\Main\SystemException $exception)
		{
			$this->errorCollection->add(array(new Error($exception->getMessage(), $exception->getCode())));
			$this->includeComponentTemplate(self::TEMPLATE_FILE_TOP);

			return;
		}

		$this->arResult['STORAGE_ID'] = $this->storageId;
		$this->arResult['STORAGE_ROOT_ID'] = $this->storage->getRootObjectId();
		//$this->arResult['FOLDER_ID'] = $this->folderId;

		if ($this->reload)
		{
			try
			{
				$this->reload(Volume\FileDeleted::getIndicatorId(), array(
					'=STORAGE_ID' => $this->storageId,
					//'=PARENT_ID' => $this->folderId
				));

				$this->reload(Volume\FileDeleted::getIndicatorId(), array(
					'=STORAGE_ID' => $this->storageId,
					//'=FOLDER_ID' => $this->folderId
				));

				if ($this->filterId > 0)
				{
					VolumeTable::update($this->filterId, array('COLLECTED' => 1));
				}
			}
			catch (\Bitrix\Main\SystemException $exception)
			{
				$this->errorCollection->add(array(new Error($exception->getMessage(), $exception->getCode())));
			}
		}

		$gridOptions = new \Bitrix\Main\Grid\Options($this->arResult['GRID_ID'], $this->arResult['FILTER_PRESETS']);

		$filter = $this->getFilter($this->getAction());

		$filter['=STORAGE_ID'] = $this->storageId;// only one storage

		// TypeFile::PDF and TypeFile::KNOWN file interprets as TypeFile::DOCUMENT type
		if (isset($filter['TYPE']) && in_array(\Bitrix\Disk\TypeFile::DOCUMENT, $filter['TYPE']))
		{
			$filter['TYPE'][] = \Bitrix\Disk\TypeFile::PDF;
			$filter['TYPE'][] = \Bitrix\Disk\TypeFile::KNOWN;
		}

		// Sorting order
		$sorting = $gridOptions->GetSorting(array('sort' => array('VERSION_SIZE' => 'desc')));


		// Per page navigation
		$navParams = $gridOptions->GetNavParams();
		$pageSize = $navParams['nPageSize'];

		$nav = new \Bitrix\Main\UI\PageNavigation('page');
		$nav->allowAllRecords(false)
			->setPageSize($pageSize)
			->initFromUri();

		$pagination = array(
			'offset' => $nav->getOffset(),
			'limit' => $nav->getLimit(),
		);

		try
		{
			$cursor = $this->loadMeasurementResult(
				$this->indicatorId,
				$this->indicatorId,
				$filter,
				$sorting['sort'],
				$pagination
			);
			if ($cursor instanceof \Bitrix\Main\DB\Result && $cursor->getSelectedRowsCount() > 0)
			{
				$this->arResult['ROWS_COUNT'] = $cursor->getCount();
				$nav->setRecordCount($cursor->getCount());

				$this->arResult['SORT'] = $sorting['sort'];
				$this->arResult['SORT_VARS'] = $sorting['vars'];
				$this->arResult['NAV_OBJECT'] = $nav;
				$this->arResult['CURRENT_PAGE'] = $nav->getCurrentPage();
			}
		}
		catch (\Bitrix\Main\SystemException $exception)
		{
			$this->errorCollection->add(array(new Error($exception->getMessage(), $exception->getCode())));
		}

		try
		{
			$this->loadMeasurementResult(
				'Storage',
				Volume\Storage\Storage::getIndicatorId(),
				array(
					'=STORAGE_ID' => $this->storageId
				)
			);

			$this->loadMeasurementResult(
				'TrashCan',
				Volume\Storage\TrashCan::getIndicatorId(),
				array(
					'=STORAGE_ID' => $this->storageId
				)
			);

			// totals
			$this->arResult = array_merge($this->arResult, $this->getTotals());

			if ($this->arResult['TOTAL_FILE_COUNT'])
			{
				$this->arResult['DATA_COLLECTED'] = true;
			}

		}
		catch (\Bitrix\Main\SystemException $exception)
		{
			$this->errorCollection->add(array(new Error($exception->getMessage(), $exception->getCode())));
		}

		$this->arResult['ACTION_MENU'] = $this->getStorageMenuItems();

		$this->includeComponentTemplate(self::TEMPLATE_FILE_TOP);
	}


	/**
	 * @param string $indicatorType - Indicator class name
	 * @param array $filter
	 * @param integer $filterId Saved filter row id.
	 * @param boolean $suppressTimeout Do not use break down operation by timeout.
	 *
	 * @return String Status code STATUS_SUCCESS | STATUS_ERROR.
	 * @throws \Bitrix\Main\SystemException
	 */
	public function purify($indicatorType, $filter = array(), $filterId = -1, $suppressTimeout = false)
	{
		$fullClearFilter = array(
			'=OWNER_ID' => $this->getUser()->getId(),
		);

		if (!$this->isAdminMode())
		{
			$filter['STORAGE_ID'] = $this->getCurrentUserStorageId();
			$fullClearFilter['STORAGE_ID'] = $this->getCurrentUserStorageId();
		}
		elseif ($this->isOnlySpecificStorage())
		{
			$filter['STORAGE_ID'] = $this->storageId;
			$fullClearFilter['STORAGE_ID'] = $this->storageId;
		}

		if ($indicatorType === '*')
		{
			VolumeTable::deleteByFilter($fullClearFilter);
			// clear statistic for progress bar
			Volume\Cleaner::clearProgressInfo((int)$this->getUser()->getId());
		}
		elseif ($indicatorType !== '')
		{
			/** @var \Bitrix\Disk\Volume\IVolumeIndicator $indicator */
			$indicator = $this->getIndicator($indicatorType);

			if ($filterId > 0)
			{
				$indicator->restoreFilter($filterId);
			}

			foreach ($filter as $key => $val)
			{
				$indicator->addFilter($key, $val);
			}

			$indicator->purify();
		}

		Volume\Cleaner::countWorker((int)$this->getUser()->getId());

		return self::STATUS_SUCCESS;
	}

	/**
	 * Preforms measurement operation.
	 *
	 * @param string $indicatorType Indicator class name.
	 * @param array $filter parameter for indicator.
	 * @param integer $filterId Saved filter row id.
	 * @param boolean $suppressTimeout Do not use break down operation by timeout.
	 *
	 * @return String Status code STATUS_SUCCESS | STATUS_ERROR | STATUS_TIMEOUT.
	 */
	public function measure($indicatorType, $filter = array(), $filterId = -1, $suppressTimeout = false)
	{
		$indicator = $this->getIndicator($indicatorType);
		if ($filterId > 0)
		{
			$indicator->restoreFilter($filterId);
		}

		if ($indicator instanceof \Bitrix\Disk\Volume\IVolumeIndicatorModule)
		{
			if (!$indicator->isMeasureAvailable())
			{
				return self::STATUS_SUCCESS;
			}
		}

		if (!$this->isAdminMode())
		{
			$filter['STORAGE_ID'] = $this->getCurrentUserStorageId();
		}
		elseif ($this->isOnlySpecificStorage())
		{
			$filter['STORAGE_ID'] = $this->storageId;
		}

		foreach ($filter as $key => $val)
		{
			$indicator->addFilter($key, $val);
		}

		if ($suppressTimeout !== true && $indicator instanceof \Bitrix\Disk\Volume\IVolumeTimeLimit)
		{
			$indicator->startTimer();

			if ($this->getQueueStepParam('subTask') !== null)
			{
				$indicator->setStage($this->getQueueStepParam('subTask'));
			}

			try
			{
				$indicator->measure();
			}
			catch (Main\SystemException $exception)
			{
				if ($exception->getCode() === $indicator::ERROR_LOCK_TIMEOUT)
				{
					return self::STATUS_TIMEOUT;
				}
				throw $exception;
			}

			// go next
			$this->setQueueStepParam('subTask', $indicator->getStage());

			if ($indicator->hasTimeLimitReached())
			{
				return self::STATUS_TIMEOUT;
			}
		}
		else
		{
			$indicator->measure();
		}

		return self::STATUS_SUCCESS;
	}

	/**
	 * Preforms data preparation.
	 *
	 * @param string $indicatorType Indicator class name.
	 * @param array $filter parameter for indicator.
	 *
	 * @return String Status code STATUS_SUCCESS | STATUS_ERROR | STATUS_TIMEOUT.
	 * @throws \Bitrix\Main\ObjectException
	 */
	public function prepareData($indicatorType, $filter = array())
	{
		$indicator = $this->getIndicator($indicatorType);

		if ($indicator instanceof \Bitrix\Disk\Volume\IVolumeIndicatorModule)
		{
			if (!$indicator->isMeasureAvailable())
			{
				return self::STATUS_SUCCESS;
			}
		}

		foreach ($filter as $key => $val)
		{
			$indicator->addFilter($key, $val);
		}

		if ($indicator instanceof \Bitrix\Disk\Volume\IVolumeTimeLimit)
		{
			$indicator->startTimer();
			$indicator->prepareData();
			if ($indicator->hasTimeLimitReached())
			{
				return self::STATUS_TIMEOUT;
			}
		}
		else
		{
			$indicator->prepareData();
		}

		return self::STATUS_SUCCESS;
	}

	/**
	 * Just continuously start methods clear and measure.
	 *
	 * @param string $indicatorType Indicator class name.
	 * @param array $filter Filter parameter for indicator.
	 * @param integer $filterId Saved filter row id.
	 * @param boolean $suppressTimeout Do not use break down operation by timeout.
	 *
	 * @return String Status code STATUS_SUCCESS | STATUS_ERROR | STATUS_TIMEOUT.
	 * @throws \Bitrix\Main\SystemException
	 */
	public function reload($indicatorType, $filter = array(), $filterId = -1, $suppressTimeout = false)
	{
		if ($filterId <= 0)
		{
			$this->purify($indicatorType, $filter, $filterId, $suppressTimeout);
		}

		return $this->measure($indicatorType, $filter, $filterId, $suppressTimeout);
	}



	/**
	 * Returns total volumes.
	 * @return array
	 */
	public function getTotals()
	{
		$totals = array();
		$storageInfo = array();
		$trashCanInfo = array();

		if (
			$this->isOnlySpecificStorage() ||
			!$this->isAdminMode() ||
			($this->isAdminMode() && $this->storageId > 0)
		)
		{
			/** @var \Bitrix\Disk\Volume\IVolumeIndicator $trashCanIndicator */
			$trashCanIndicator = $this->getIndicator(Volume\Storage\TrashCan::className());
			$trashCanIndicator->addFilter('STORAGE_ID', $this->storageId);
			$trashCanIndicator->loadTotals();
			if ($trashCanIndicator->getTotalCount() > 0)
			{
				$trashCanInfo['FILE_SIZE'] = $trashCanIndicator->getTotalSize();
				$trashCanInfo['FILE_SIZE'] += $trashCanIndicator->getPreviewSize();//Preview
				$trashCanInfo['FILE_SIZE_FORMAT'] = \CFile::formatSize($trashCanInfo['FILE_SIZE']);
				$trashCanInfo['FILE_COUNT'] = $trashCanIndicator->getTotalCount();
			}

			/** @var \Bitrix\Disk\Volume\IVolumeIndicator $trashCanIndicator */
			$storageIndicator = $this->getIndicator(Volume\Storage\Storage::className());
			$storageIndicator->addFilter('STORAGE_ID', $this->storageId);
			$storageIndicator->loadTotals();
			if ($storageIndicator->getTotalCount() > 0)
			{
				$storageInfo['FILE_SIZE'] = $storageIndicator->getTotalSize();
				$storageInfo['FILE_SIZE'] += $storageIndicator->getPreviewSize();//Preview
				$storageInfo['FILE_SIZE_FORMAT'] = \CFile::formatSize($storageInfo['FILE_SIZE']);
				$storageInfo['FILE_COUNT'] = $storageIndicator->getTotalCount();

				$storageInfo['UNNECESSARY_VERSION_SIZE'] = $storageIndicator->getUnnecessaryVersionSize();
				$storageInfo['UNNECESSARY_VERSION_COUNT'] = $storageIndicator->getUnnecessaryVersionCount();
				$storageInfo['UNNECESSARY_VERSION_SIZE_FORMAT'] = \CFile::formatSize($storageInfo['UNNECESSARY_VERSION_SIZE']);
			}

			// limit only for one storage
			$totals['DISK_SPACE'] = Volume\Storage\Storage::getAvailableSpace($this->storage);
		}
		elseif ($this->isAdminMode())
		{
			/** @var \Bitrix\Disk\Volume\IVolumeIndicator $trashCanIndicator */
			$trashCanIndicator = $this->getIndicator(Volume\Storage\TrashCan::className());
			$trashCanIndicator->addFilter('!STORAGE_ID', null);
			$trashCanIndicator->loadTotals();
			if ($trashCanIndicator->getTotalCount() > 0)
			{
				if ($this->useDiskSizeAsTotalVolume())
				{
					$trashCanInfo['FILE_SIZE'] = $trashCanIndicator->getDiskSize();
					$trashCanInfo['FILE_COUNT'] = $trashCanIndicator->getDiskCount();
				}
				else
				{
					$trashCanInfo['FILE_SIZE'] = $trashCanIndicator->getTotalSize();
					$trashCanInfo['FILE_COUNT'] = $trashCanIndicator->getTotalCount();
				}
				$trashCanInfo['FILE_SIZE'] += $trashCanIndicator->getPreviewSize();//Preview
				$trashCanInfo['FILE_SIZE_FORMAT'] = \CFile::formatSize($trashCanInfo['FILE_SIZE']);
			}

			/** @var \Bitrix\Disk\Volume\IVolumeIndicator $trashCanIndicator */
			$storageIndicator = $this->getIndicator(Volume\Storage\Storage::className());
			$storageIndicator->addFilter('!STORAGE_ID', null);
			$storageIndicator->loadTotals();
			if ($storageIndicator->getTotalCount() > 0)
			{
				if ($this->useDiskSizeAsTotalVolume())
				{
					$storageInfo['FILE_SIZE'] = $storageIndicator->getDiskSize();
					$storageInfo['FILE_COUNT'] = $storageIndicator->getDiskCount();
				}
				else
				{
					$storageInfo['FILE_SIZE'] = $storageIndicator->getTotalSize();
					$storageInfo['FILE_COUNT'] = $storageIndicator->getTotalCount();
				}
				$storageInfo['FILE_SIZE'] += $storageIndicator->getPreviewSize();//Preview
				$storageInfo['FILE_SIZE_FORMAT'] = \CFile::formatSize($storageInfo['FILE_SIZE']);
				$storageInfo['UNNECESSARY_VERSION_SIZE'] = $storageIndicator->getUnnecessaryVersionSize();
				$storageInfo['UNNECESSARY_VERSION_COUNT'] = $storageIndicator->getUnnecessaryVersionCount();
				$storageInfo['UNNECESSARY_VERSION_SIZE_FORMAT'] = \CFile::formatSize($storageInfo['UNNECESSARY_VERSION_SIZE']);
			}

			// full disk limit
			$totals['DISK_SPACE'] = Volume\Storage\Storage::getAvailableSpace();
		}

		if ($totals['DISK_SPACE'] > 0)
		{
			$totals['DISK_SPACE_FORMAT'] = \CFile::formatSize($totals['DISK_SPACE']);
		}

		$totals['DROP_TOTAL_SIZE'] = .0;
		$totals['DROP_TOTAL_COUNT'] = .0;
		$totals['TOTAL_FILE_SIZE'] = 0;
		$totals['TOTAL_FILE_SIZE_FORMAT'] = \CFile::formatSize(0);
		$totals['TOTAL_FILE_COUNT'] = 0;
		$totals['DROP_UNNECESSARY_VERSION'] = 0;
		$totals['DROP_UNNECESSARY_VERSION_COUNT'] = 0;
		$totals['DROP_UNNECESSARY_VERSION_FORMAT'] = \CFile::formatSize(0);
		$totals['DROP_TRASHCAN'] = 0;
		$totals['DROP_TRASHCAN_COUNT'] = 0;
		$totals['DROP_TRASHCAN_FORMAT'] = \CFile::formatSize(0);

		// add other to totals
		if (
			$this->isAdminMode() &&
			!$this->useDiskSizeAsTotalVolume() &&
			!$this->isOnlySpecificStorage() &&
			!($this->isAdminMode() && $this->storageId > 0)
		)
		{
			$totals = $this->getIndicator(Volume\Bfile::className());
			$totals->loadTotals();
			if ($totals->getTotalSize() > 0)
			{
				$totals['TOTAL_FILE_SIZE'] += ($totals->getTotalSize() - $totals->getDiskSize());
				$totals['TOTAL_FILE_COUNT'] += ($totals->getTotalCount() - $totals->getTotalVersion());
			}
		}

		if ($trashCanInfo['FILE_COUNT'] > 0)
		{
			$totals['DROP_TRASHCAN'] = $trashCanInfo['FILE_SIZE'];
			$totals['DROP_TRASHCAN_COUNT'] = $trashCanInfo['FILE_COUNT'];
			$totals['DROP_TRASHCAN_FORMAT'] = $trashCanInfo['FILE_SIZE_FORMAT'];

			$totals['TOTAL_FILE_COUNT'] += $trashCanInfo['FILE_COUNT'];
			$totals['TOTAL_FILE_SIZE'] += $trashCanInfo['FILE_SIZE'];

			$totals['DROP_TOTAL_COUNT'] += $trashCanInfo['FILE_COUNT'];
			$totals['DROP_TOTAL_SIZE'] += $trashCanInfo['FILE_SIZE'];
		}
		if ($storageInfo['FILE_COUNT'] > 0)
		{
			$totals['DROP_UNNECESSARY_VERSION'] = $storageInfo['UNNECESSARY_VERSION_SIZE'];
			$totals['DROP_UNNECESSARY_VERSION_COUNT'] = $storageInfo['UNNECESSARY_VERSION_COUNT'];
			$totals['DROP_UNNECESSARY_VERSION_FORMAT'] = $storageInfo['UNNECESSARY_VERSION_SIZE_FORMAT'];

			$totals['TOTAL_FILE_COUNT'] += $storageInfo['FILE_COUNT'];
			$totals['TOTAL_FILE_SIZE'] += $storageInfo['FILE_SIZE'];

			$totals['DROP_TOTAL_COUNT'] += $storageInfo['UNNECESSARY_VERSION_COUNT'];
			$totals['DROP_TOTAL_SIZE'] += $storageInfo['UNNECESSARY_VERSION_SIZE'];
		}


		$totals['TOTAL_FILE_SIZE_FORMAT'] = \CFile::formatSize($totals['TOTAL_FILE_SIZE']);
		$totals['DROP_TOTAL_SIZE_FORMAT'] = \CFile::formatSize($totals['DROP_TOTAL_SIZE']);

		list($dropTotalSizeDigit, $dropTotalSizeUnits) = explode(' ', $totals['DROP_TOTAL_SIZE_FORMAT']);
		$totals['DROP_TOTAL_SIZE_DIGIT'] = $dropTotalSizeDigit;
		$totals['DROP_TOTAL_SIZE_UNITS'] = $dropTotalSizeUnits;

		return $totals;
	}


	/**
	 * @param string $resId Result index to load data.
	 * @param string $indicatorId Indicator class name
	 * @param array $filter Filter parameter for indicator.
	 *
	 * @return boolean
	 * @throws \Bitrix\Main\ObjectException
	 */
	private function loadMeasurementTotals($resId, $indicatorId, $filter = array())
	{
		/** @var \Bitrix\Disk\Volume\IVolumeIndicator $indicator */
		$indicator = $this->getIndicator($indicatorId);

		$this->arResult['INDICATOR_COLLECTED'][$indicatorId] = false;

		foreach ($filter as $key => $val)
		{
			$indicator->addFilter($key, $val);
		}
		if (!isset($this->arResult[$resId]['LIST']))
		{
			$this->arResult[$resId]['FILE_SIZE'] = (double)0.0;
			$this->arResult[$resId]['FILE_SIZE_FORMAT'] = '';
			$this->arResult[$resId]['FILE_COUNT'] = (double)0.0;
			$this->arResult[$resId]['DISK_SIZE'] = (double)0.0;
			$this->arResult[$resId]['DISK_SIZE_FORMAT'] = '';
			$this->arResult[$resId]['DISK_COUNT'] = (double)0.0;
			$this->arResult[$resId]['ATTACHED_COUNT'] = (double)0.0;
			$this->arResult[$resId]['LINK_COUNT'] = (double)0.0;
			$this->arResult[$resId]['SHARING_COUNT'] = (double)0.0;
			$this->arResult[$resId]['UNNECESSARY_VERSION_SIZE'] = (double)0.0;
			$this->arResult[$resId]['UNNECESSARY_VERSION_SIZE_FORMAT'] = '';
			$this->arResult[$resId]['UNNECESSARY_VERSION_COUNT'] = (double)0.0;
			$this->arResult[$resId]['LIST'] = array();
		}

		$indicator->loadTotals();

		if ($indicator->getTotalCount() > 0)
		{
			$this->arResult['INDICATOR_COLLECTED'][$indicatorId] = true;

			$this->arResult[$resId]['FILE_SIZE'] += $indicator->getTotalSize();
			$this->arResult[$resId]['FILE_SIZE_FORMAT'] = \CFile::formatSize($this->arResult[$resId]['FILE_SIZE']);
			$this->arResult[$resId]['FILE_COUNT'] += $indicator->getTotalCount();
			$this->arResult[$resId]['DISK_SIZE'] += $indicator->getDiskSize();
			$this->arResult[$resId]['DISK_SIZE_FORMAT'] = \CFile::formatSize($this->arResult[$resId]['DISK_SIZE']);
			$this->arResult[$resId]['DISK_COUNT'] += $indicator->getDiskCount();
			$this->arResult[$resId]['VERSION_COUNT'] += $indicator->getTotalVersion();
			$this->arResult[$resId]['ATTACHED_COUNT'] += $indicator->getTotalAttached();
			$this->arResult[$resId]['LINK_COUNT'] += $indicator->getTotalLink();
			$this->arResult[$resId]['SHARING_COUNT'] += $indicator->getTotalSharing();
			$this->arResult[$resId]['UNNECESSARY_VERSION_SIZE'] += $indicator->getUnnecessaryVersionSize();
			$this->arResult[$resId]['UNNECESSARY_VERSION_SIZE_FORMAT'] = \CFile::formatSize($this->arResult[$resId]['UNNECESSARY_VERSION_SIZE']);
			$this->arResult[$resId]['UNNECESSARY_VERSION_COUNT'] += $indicator->getUnnecessaryVersionCount();

			$this->arResult[$resId]['LIST'][$indicatorId] = array(
				'INDICATOR' => $indicatorId,
				'INDICATOR_TYPE' => $indicator::className(),
				'FILE_SIZE' => $indicator->getTotalSize() + $indicator->getPreviewSize(),
				'FILE_SIZE_FORMAT' => \CFile::formatSize($indicator->getTotalSize() + $indicator->getPreviewSize()),
				'FILE_COUNT' => $indicator->getTotalCount(),
				'DISK_SIZE' => $indicator->getDiskSize(),
				'DISK_SIZE_FORMAT' => \CFile::formatSize($indicator->getDiskSize()),
				'DISK_COUNT' => $indicator->getDiskCount(),
				'VERSION_COUNT' => $indicator->getTotalVersion(),
				'ATTACHED_COUNT' => $indicator->getTotalAttached(),
				'LINK_COUNT' => $indicator->getTotalLink(),
				'SHARING_COUNT' => $indicator->getTotalSharing(),
				'UNNECESSARY_VERSION_SIZE' => $indicator->getUnnecessaryVersionSize(),
				'UNNECESSARY_VERSION_SIZE_FORMAT' => \CFile::formatSize($indicator->getUnnecessaryVersionSize()),
				'UNNECESSARY_VERSION_COUNT' => $indicator->getUnnecessaryVersionCount(),
			);
		}

		return $this->arResult['INDICATOR_COLLECTED'][$indicatorId];
	}


	/**
	 * @param string $resId Result index to load data.
	 * @param string $indicatorId Indicator class name
	 * @param array $filter Filter parameter for indicator.
	 * @param array $order Sort order params.
	 * @param array $pagination Paging split params.
	 * @param boolean $recalculatePercent Recalculate percents.
	 *
	 * @return \Bitrix\Main\DB\Result|null
	 * @throws \Bitrix\Main\ObjectException
	 */
	private function loadMeasurementResult($resId, $indicatorId, $filter = array(), $order = array(), $pagination = array(), $recalculatePercent = false)
	{
		/** @var \Bitrix\Disk\Volume\IVolumeIndicator $indicator */
		$indicator = $this->getIndicator($indicatorId);

		$result = null;

		$this->arResult[$resId]['INDICATOR'] = $resId;
		$this->arResult[$resId]['INDICATOR_TYPE'] = $indicator::className();
		$this->arResult['INDICATOR_COLLECTED'][$indicatorId] = false;

		if (!isset($this->arResult[$resId]['LIST']))
		{
			$this->arResult[$resId]['FILE_SIZE'] = .0;
			$this->arResult[$resId]['FILE_SIZE_FORMAT'] = '';
			$this->arResult[$resId]['FILE_COUNT'] = .0;
			$this->arResult[$resId]['DISK_SIZE'] = .0;
			$this->arResult[$resId]['DISK_SIZE_FORMAT'] = '';
			$this->arResult[$resId]['DISK_COUNT'] = .0;
			$this->arResult[$resId]['ATTACHED_COUNT'] = .0;
			$this->arResult[$resId]['LINK_COUNT'] = .0;
			$this->arResult[$resId]['SHARING_COUNT'] = .0;
			$this->arResult[$resId]['UNNECESSARY_VERSION_SIZE'] = .0;
			$this->arResult[$resId]['UNNECESSARY_VERSION_SIZE_FORMAT'] = '';
			$this->arResult[$resId]['UNNECESSARY_VERSION_COUNT'] = .0;
			$this->arResult[$resId]['LIST'] = array();
			$this->arResult[$resId]['WORKER_COUNT'] = 0;
			$this->arResult[$resId]['WORKER_COUNT_DROP_UNNECESSARY_VERSION'] = 0;
			$this->arResult[$resId]['WORKER_COUNT_DROP_TRASHCAN'] = 0;
			$this->arResult[$resId]['WORKER_COUNT_EMPTY_FOLDER'] = 0;
			$this->arResult[$resId]['WORKER_COUNT_DROP_FOLDER'] = 0;
		}

		foreach ($filter as $key => $val)
		{
			$indicator->addFilter($key, $val);
		}

		$indicator->addFilter('>FILES_LEFT', 0);

		$indicator->loadTotals();

		if ($indicator->getTotalCount() > 0 || $indicator instanceof \Bitrix\Disk\Volume\File)
		{
			$this->arResult['INDICATOR_COLLECTED'][$indicatorId] = true;

			$this->arResult[$resId]['FILE_SIZE'] += $indicator->getTotalSize() + $indicator->getPreviewSize();
			$this->arResult[$resId]['FILE_SIZE_FORMAT'] = \CFile::formatSize($this->arResult[$resId]['FILE_SIZE']);
			$this->arResult[$resId]['FILE_COUNT'] += $indicator->getTotalCount();
			$this->arResult[$resId]['DISK_SIZE'] += $indicator->getDiskSize();
			$this->arResult[$resId]['DISK_SIZE_FORMAT'] = \CFile::formatSize($this->arResult[$resId]['DISK_SIZE']);
			$this->arResult[$resId]['DISK_COUNT'] += $indicator->getDiskCount();
			$this->arResult[$resId]['VERSION_COUNT'] += $indicator->getTotalVersion();
			$this->arResult[$resId]['ATTACHED_COUNT'] += $indicator->getTotalAttached();
			$this->arResult[$resId]['LINK_COUNT'] += $indicator->getTotalLink();
			$this->arResult[$resId]['SHARING_COUNT'] += $indicator->getTotalSharing();
			$this->arResult[$resId]['UNNECESSARY_VERSION_SIZE'] += $indicator->getUnnecessaryVersionSize();
			$this->arResult[$resId]['UNNECESSARY_VERSION_SIZE_FORMAT'] = \CFile::formatSize($this->arResult[$resId]['UNNECESSARY_VERSION_SIZE']);
			$this->arResult[$resId]['UNNECESSARY_VERSION_COUNT'] += $indicator->getUnnecessaryVersionCount();

			if (isset($pagination['limit']))
			{
				$indicator->setLimit($pagination['limit']);
			}
			if (isset($pagination['offset']))
			{
				$indicator->setOffset($pagination['offset']);
			}
			if (count($order) > 0)
			{
				$indicator->setOrder($order);
			}

			$result = $indicator->getMeasurementResult();

			$this->arResult[$resId]['ORDER'] = $indicator->getOrder();

			$totalSize = $this->arResult[$resId]['FILE_SIZE'];

			foreach ($result as $row)
			{
				$row['INDICATOR_TYPE'] = $indicator::className();
				if ($recalculatePercent && $totalSize > 0)
				{
					$percent = ((double)$row['FILE_SIZE'] + (double)$row['PREVIEW_SIZE']) * 100 / $totalSize;
					$row['PERCENT'] = round((double)$percent, 1);
				}

				// count workers
				if (\Bitrix\Disk\Volume\Task::isRunningMode($row['AGENT_LOCK']))
				{
					$this->arResult[$resId]['WORKER_COUNT'] ++;
					if (\Bitrix\Disk\Volume\Task::isRunningMode($row['DROP_UNNECESSARY_VERSION']))
					{
						$this->arResult[$resId]['WORKER_COUNT_DROP_UNNECESSARY_VERSION'] ++;
					}
					if (\Bitrix\Disk\Volume\Task::isRunningMode($row['DROP_TRASHCAN']))
					{
						$this->arResult[$resId]['WORKER_COUNT_DROP_TRASHCAN'] ++;
					}
					if (\Bitrix\Disk\Volume\Task::isRunningMode($row['EMPTY_FOLDER']))
					{
						$this->arResult[$resId]['WORKER_COUNT_EMPTY_FOLDER'] ++;
					}
					if (\Bitrix\Disk\Volume\Task::isRunningMode($row['DROP_FOLDER']))
					{
						$this->arResult[$resId]['WORKER_COUNT_DROP_FOLDER'] ++;
					}
				}

				$this->arResult[$resId]['LIST'][] = $row;
			}
		}

		return $result;
	}


	/**
	 * Accumulate measurement data of modules indicators.
	 *
	 * @return void
	 * @throws \Bitrix\Main\ObjectException
	 */
	private function loadModulesResult()
	{
		$resId = 'MODULES';
		$res = &$this->arResult[$resId];

		// full list available indicators
		$indicatorIdList = Volume\Base::listIndicator();
		foreach ($indicatorIdList as $indicatorId => $indicatorTypeClass)
		{
			$indicator = $this->getIndicator($indicatorId);
			if ($indicator instanceof Volume\IVolumeIndicatorModule)
			{
				$this->loadMeasurementTotals($resId, $indicatorId);
			}
		}
		unset($indicatorId, $indicatorTypeClass);

		if ($this->useDiskSizeAsTotalVolume())
		{
			$totals = $this->getIndicator(Volume\Module\Disk::getIndicatorId());
		}
		else
		{
			$totals = $this->getIndicator(Volume\Bfile::getIndicatorId());
		}

		$totals->loadTotals();
		if ($totals->getTotalSize() > 0)
		{
			if ($this->useDiskSizeAsTotalVolume())
			{
				$sizeField = 'DISK_SIZE';
				$totalSize = $res['FILE_SIZE'] = $totals->getDiskSize() + $totals->getPreviewSize();
				$totalCount = $res['FILE_COUNT'] = $totals->getTotalVersion();
				$diskSize = $totalSize;
				$diskCount = $totalCount;
				$otherFileSize = 0;
				$otherFileCount = 0;
			}
			else
			{
				$sizeField = 'FILE_SIZE';
				$totalSize = $res['FILE_SIZE'] = $totals->getTotalSize() + $totals->getPreviewSize();
				$totalCount = $res['FILE_COUNT'] = $totals->getTotalCount();
				$diskSize = (double)$totals->getDiskSize();
				$diskCount = (double)$totals->getDiskCount();
				$otherFileSize = $totalSize - $diskSize;
				$otherFileCount = $totalCount - $diskCount;
			}

			$res['FILE_SIZE_FORMAT'] = \CFile::formatSize($totalSize);


			// subtract module disk volume from Disk total size
			$indicatorDiskTypeId = Volume\Module\Disk::getIndicatorId();
			$moduleDiskSum = 0;
			$moduleOtherSum = 0;
			$moduleDiskCount = 0;
			$moduleOtherCount = 0;
			foreach ($res['LIST'] as $indicatorModuleTypeId => $moduleMeasurement)
			{
				if ($indicatorModuleTypeId != $indicatorDiskTypeId)
				{
					$moduleDiskSum += (double)$moduleMeasurement['DISK_SIZE'];
					$moduleOtherSum += ((double)$moduleMeasurement['FILE_SIZE'] + (double)$moduleMeasurement['PREVIEW_SIZE'] - (double)$moduleMeasurement['DISK_SIZE']);
					$moduleDiskCount += (double)$moduleMeasurement['DISK_COUNT'];
					$moduleOtherCount += ((double)$moduleMeasurement['FILE_COUNT'] - (double)$moduleMeasurement['DISK_COUNT']);

					$res['LIST'][$indicatorDiskTypeId]['DISK_SIZE'] -= (double)$moduleMeasurement['DISK_SIZE'];
					$res['LIST'][$indicatorDiskTypeId]['FILE_SIZE'] -= (double)$moduleMeasurement['FILE_SIZE'];
					$res['LIST'][$indicatorDiskTypeId]['FILE_COUNT'] -= (double)$moduleMeasurement['FILE_COUNT'];
				}
			}
			if (!$this->useDiskSizeAsTotalVolume())
			{
				$res['LIST'][$indicatorDiskTypeId]['FILE_SIZE'] = $diskSize - $moduleDiskSum;
				$res['LIST'][$indicatorDiskTypeId]['FILE_COUNT'] = $diskCount - $moduleDiskCount;
				$otherFileSize -= $moduleOtherSum;
				$otherFileCount -= $moduleOtherCount;
			}

			$res['LIST'][$indicatorDiskTypeId]['FILE_SIZE_FORMAT'] =
				\CFile::formatSize($res['LIST'][$indicatorDiskTypeId]['FILE_SIZE']);
			$res['LIST'][$indicatorDiskTypeId]['DISK_SIZE_FORMAT'] =
				\CFile::formatSize($res['LIST'][$indicatorDiskTypeId]['DISK_SIZE']);


			foreach ($res['LIST'] as $indicatorModuleTypeId => $moduleMeasurement)
			{
				$percent = 0;
				if ($totalSize > 0)
				{
					$percent = (double)$moduleMeasurement["$sizeField"] * 100 / $totalSize;
				}
				$res['LIST'][$indicatorModuleTypeId]['PERCENT'] = round((double)$percent, 1);
			}

			Main\Type\Collection::sortByColumn(
				$res['LIST'],
				array("$sizeField" => array(SORT_NUMERIC, SORT_DESC))
			);

			if (!$this->useDiskSizeAsTotalVolume() && $otherFileSize > 0 && $totalSize > 0)
			{
				$percent = (double)$otherFileSize * 100 / $totalSize;
				$res['OTHER'] = array(
					'TITLE' => Loc::getMessage('DISK_VOLUME_OTHER'),
					'FILE_SIZE' => $otherFileSize,
					'FILE_COUNT' => $otherFileCount,
					'FILE_SIZE_FORMAT' => \CFile::formatSize($otherFileSize),
					'PERCENT' => round((double)$percent, 1),
				);
			}
		}
	}

	/**
	 * Loads current user storage
	 *
	 * @return $this
	 * @throws Main\SystemException
	 */
	public function loadCurrentUserStorage()
	{
		// current user storageId
		$userStorageList = \Bitrix\Disk\Storage::getList(array(
			'filter' => array(
				'ENTITY_TYPE' => \Bitrix\Disk\ProxyType\User::className(),
				'ENTITY_ID' => $this->getUser()->getId(),
				'MODULE_ID' => 'disk',
			),
			'select' => array('ID')
		));
		if ($userStorage = $userStorageList->fetch())
		{
			$this->currentUserStorageId = $userStorage['ID'];
			$this->arResult['CURRENT_USER_STORAGE_ID'] = $this->currentUserStorageId;
		}
	}

	/**
	 * current user storageId
	 *
	 * @return integer
	 */
	public function getCurrentUserStorageId()
	{
		return $this->currentUserStorageId;
	}

	/**
	 * @return boolean
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	private function loadStorage()
	{
		if (empty($this->storageId))
		{
			throw new Main\ArgumentException('Undefined parameter: storageId');
		}

		$fragment = new Volume\Fragment(array('INDICATOR_TYPE' => \Bitrix\Disk\Volume\Storage\Storage::className(), 'STORAGE_ID' => $this->storageId));

		$this->storage = $fragment->getStorage();
		if (!$this->storage instanceof \Bitrix\Disk\Storage)
		{
			throw new Main\SystemException("Could not find storage {$this->storageId}");
		}

		if (empty($this->folderId))
		{
			$this->folderId = $this->storage->getRootObjectId();
		}

		return ($this->storage instanceof \Bitrix\Disk\Storage);
	}

	/**
	 * @return boolean
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	private function loadFolder()
	{
		if (empty($this->folderId))
		{
			throw new Main\ArgumentException('Undefined parameter: folderId');
		}

		$this->folder = \Bitrix\Disk\Folder::getById($this->folderId);
		if (!$this->folder instanceof \Bitrix\Disk\Folder)
		{
			throw new Main\SystemException("Could not find folder {$this->folderId}");
		}

		return ($this->folder instanceof \Bitrix\Disk\Folder);
	}

	/**
	 * @return array
	 * @throws Main\ArgumentException
	 */
	private function getBreadcrumb()
	{
		if (!$this->folder instanceof \Bitrix\Disk\Folder)
		{
			throw new Main\ArgumentTypeException("Fragment must be subclass of ".\Bitrix\Disk\Folder::className());
		}

		$crumbs = array();

		$parents = \Bitrix\Disk\CrumbStorage::getInstance()->getByObject($this->folder);
		if ($this->folderId != $this->storage->getRootObjectId())
		{
			$parents[$this->folderId] = $this->folder->getName();
		}

		foreach ($parents as $folderId => $folderName)
		{
			$res = VolumeTable::getList(array(
				'filter' => array(
					'=INDICATOR_TYPE' => Volume\Folder::className(),
					'=OWNER_ID' => $this->getUser()->getId(),
					'=FOLDER_ID' => $folderId,
				),
				'select' => array('ID', 'COLLECTED'),
				'limit' => 1,
			));
			if ($row = $res->fetch())
			{
				$params = array(
					'action' => self::ACTION_FILES,
					'storageId' => $this->storageId,
					'folderId' => $folderId,
					'filterId' => $row['ID'],
				);
				if ($row['COLLECTED'] != 1)
				{
					$params['reload'] = 'Y';
				}
				$crumbs[] = array(
					'LINK' => $this->getActionUrl($params),
					'NAME' => $folderName,//$row['TITLE']
					'STORAGE_ID' => $this->storageId,
					'FOLDER_ID' => $folderId,
					'FILTER_ID' => $row['ID'],
					'COLLECTED' => $row['COLLECTED'],
				);
			}
		}

		return $crumbs;
	}


	/**
	 * @return string
	 * @var array $params
	 */
	public function getActionUrl($params = array())
	{
		if (!empty($params['action']))
		{
			$action = $params['action'];
		}
		else
		{
			$action = self::ACTION_DEFAULT;
		}

		if ($this->arParams['SEF_MODE'] === 'Y')
		{
			$path = \CComponentEngine::makePathFromTemplate(
				$this->arParams['PATH_TO_DISK_VOLUME_'.mb_strtoupper($action)],
				$params
			);
			$path = str_replace('//', '/', $path);

			unset($params['action']);
			unset($params['storageId']);
			unset($params['folderId']);
			unset($params['indicatorId']);
		}
		else
		{
			$path = '';
		}

		return htmlspecialcharsbx($this->arParams['RELATIVE_PATH']). $path. (count($params) > 0 ? '?'.http_build_query($params) : '');
	}

	/**
	 * @param string $indicatorType - Indicator class name
	 *
	 * @return Volume\IVolumeIndicator
	 * @throws Main\ObjectException
	 */
	public function getIndicator($indicatorType)
	{
		/** @var Volume\IVolumeIndicator $indicator */
		$indicator = Volume\Base::getIndicator($indicatorType);
		$indicator->setOwner($this->getUser()->getId());

		return $indicator;
	}

	/**
	 * Is exists any worker.
	 *
	 * @return boolean
	 */
	public function hasWorkerInProcess()
	{
		$option = Volume\Cleaner::getProgressInfo((int)$this->getUser()->getId());
		if (!empty($option))
		{
			return (bool)($option['count'] > 0 && $option['steps'] < $option['count']);
		}

		return false;
	}

	/**
	 * Show agent progress bar.
	 *
	 * @return string
	 */
	public function getWorkerProgressBar()
	{
		$res = array();
		$res['disk'] = array(Volume\Cleaner::className().$this->getUser()->getId());

		\CJSCore::Init(array('update_stepper'));

		return \Bitrix\Main\Update\Stepper::getHtml(
			$res,
			Loc::getMessage('DISK_VOLUME_AGENT_STEPPER')
		);
	}

	/**
	 * Checks ability agent to use Crontab.
	 * @return bool
	 */
	public function canAgentUseCrontab()
	{
		return Volume\Cleaner::canAgentUseCrontab();
	}

		/**
	 * Returns queue's current step parameter.
	 *
	 * @return array|null
	 */
	public function getQueueStep()
	{
		if (!empty($_SESSION[self::SETTING_QUEUE_STEP]))
		{
			return $_SESSION[self::SETTING_QUEUE_STEP];
		}

		return null;
	}

	/**
	 * Sets queue step parameter.
	 *
	 * @param string $key Parameter name.
	 * @param string|null $value Parameter value.
	 *
	 * @return void
	 */
	public function setQueueStepParam($key, $value)
	{
		if (!isset($_SESSION[self::SETTING_QUEUE_STEP]))
		{
			$_SESSION[self::SETTING_QUEUE_STEP] = array();
		}
		if ($value === null)
		{
			unset($_SESSION[self::SETTING_QUEUE_STEP][$key]);
		}
		else
		{
			$_SESSION[self::SETTING_QUEUE_STEP][$key] = $value;
		}
		if (empty($_SESSION[self::SETTING_QUEUE_STEP]))
		{
			$this->clearQueueStep();
		}
	}

	/**
	 * Gets queue step parameter.
	 *
	 * @param string $key Parameter name.
	 * @param string|mixed|null $defaultValue default value.
	 *
	 * @return string|mixed|null
	 */
	public function getQueueStepParam($key, $defaultValue = null)
	{
		if (!empty($_SESSION[self::SETTING_QUEUE_STEP][$key]))
		{
			return $_SESSION[self::SETTING_QUEUE_STEP][$key];
		}

		return $defaultValue;
	}

	/**
	 * Remove queue's current step parameter.
	 *
	 * @return void
	 */
	public function clearQueueStep()
	{
		unset($_SESSION[self::SETTING_QUEUE_STEP]);
	}

	/**
	 * @param array $row Result row.
	 *
	 * @return \Bitrix\Disk\Volume\IVolumeIndicator
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\ObjectException
	 */
	public function getIndicatorResult(&$row)
	{
		if (!$row['_INDICATOR'] instanceof \Bitrix\Disk\Volume\IVolumeIndicator)
		{
			if (!is_array($row) || empty($row['INDICATOR_TYPE']))
			{
				throw new \Bitrix\Main\ArgumentTypeException('Parameter must contains an indicator class name.');
			}

			// IM folder
			if (
				$row['INDICATOR_TYPE'] === \Bitrix\Disk\Volume\Folder::className() &&
				in_array($row['ENTITY_TYPE'], \Bitrix\Disk\Volume\Module\Im::getEntityType())
			)
			{
				$row['_INDICATOR'] = $this->getIndicator(\Bitrix\Disk\Volume\Module\Im::className());
			}
			else
			{
				$row['_INDICATOR'] = $this->getIndicator($row['INDICATOR_TYPE']);
			}
		}

		return $row['_INDICATOR'];
	}

	/**
	 * @param array $row Result row.
	 *
	 * @return \Bitrix\Disk\Volume\Fragment
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\ObjectException
	 */
	public function getFragmentResult(&$row)
	{
		if (!$row['_FRAGMENT'] instanceof \Bitrix\Disk\Volume\Fragment)
		{
			/** @var \Bitrix\Disk\Volume\IVolumeIndicator $indicator */
			$indicator = $this->getIndicatorResult($row);

			$row['_FRAGMENT'] = $indicator::getFragment($row);
		}

		return $row['_FRAGMENT'];
	}

	/**
	 * @param array $row Result row.
	 *
	 * @return void
	 */
	public function decorateResult(&$row)
	{
		try
		{
			/** @var \Bitrix\Disk\Volume\IVolumeIndicator $indicator */
			$indicator = $this->getIndicatorResult($row);

			/** \Bitrix\Disk\Volume\Fragment $fragment */
			$fragment = $this->getFragmentResult($row);

			$row['TITLE'] = htmlspecialcharsbx($indicator::getTitle($fragment));

			if (isset($row['FILE_SIZE']) && !isset($row['FILE_SIZE_FORMAT']))
			{
				$row['FILE_SIZE_FORMAT'] = \CFile::FormatSize($row['FILE_SIZE']);
			}
			if (isset($row['SIZE_FILE']) && !isset($row['SIZE_FILE_FORMAT']))
			{
				$row['SIZE_FILE_FORMAT'] = \CFile::FormatSize($row['SIZE_FILE']);
			}
			if (isset($row['DISK_SIZE']) && !isset($row['DISK_SIZE_FORMAT']))
			{
				$row['DISK_SIZE_FORMAT'] = \CFile::FormatSize($row['DISK_SIZE']);
			}
			if (isset($row['VERSION_SIZE']) && !isset($row['VERSION_SIZE_FORMAT']))
			{
				$row['VERSION_SIZE_FORMAT'] = \CFile::FormatSize($row['VERSION_SIZE']);
			}
			if (isset($row['UNNECESSARY_VERSION_SIZE']) && !isset($row['UNNECESSARY_VERSION_SIZE_FORMAT']))
			{
				$row['UNNECESSARY_VERSION_SIZE_FORMAT'] = \CFile::FormatSize($row['UNNECESSARY_VERSION_SIZE']);
			}

			// Folder | File
			if ($indicator instanceof \Bitrix\Disk\Volume\IVolumeIndicatorParent)
			{
				/** @var \Bitrix\Disk\Volume\IVolumeIndicatorParent $indicator */
				$row['PARENTS'] = $indicator::getParents($fragment);

				/** @var \Bitrix\Disk\Volume\IVolumeIndicator $indicator */
				/** @var \Bitrix\Main\Type\DateTime $dateCreate */
				$updateTime = $indicator::getUpdateTime($fragment);
				if (!is_null($updateTime))
				{
					$nowTime = time() + \CTimeZone::getOffset();
					$fullFormatWithoutSec = preg_replace('/:s$/', '', \CAllDatabase::dateFormatToPHP(\CSite::GetDateFormat('FULL')));
					$row['UPDATE_TIME'] = formatDate($fullFormatWithoutSec, $updateTime->getTimestamp(), $nowTime);
				}
			}

			// Folder | Storage | User | Group | Common | TrashCan
			if ($indicator instanceof \Bitrix\Disk\Volume\IVolumeIndicatorLink)
			{
				/** @var \Bitrix\Disk\Volume\IVolumeIndicatorLink $indicator */
				$url = $indicator::getUrl($fragment);
				if (!empty($url))
				{
					$row['URL'] = $url;
				}
			}
		}
		catch (\Bitrix\Main\SystemException $ex)
		{
		}
	}

	/**
	 * @param array $row Result row.
	 *
	 * @return void
	 */
	public function decorateResultActionUrl(&$row)
	{
		try
		{
			/** @var \Bitrix\Disk\Volume\IVolumeIndicator $indicator */
			$indicator = $this->getIndicatorResult($row);

			if (
				$indicator instanceof \Bitrix\Disk\Volume\Folder ||
				(isset($row['STORAGE_ID']) && (int)$row['STORAGE_ID'] > 0 && isset($row['FOLDER_ID']) && (int)$row['FOLDER_ID'] > 0)
			)
			{
				$param = array(
					'action' => self::ACTION_FOLDER_FILES,
					'storageId' => $row['STORAGE_ID'],
					'folderId' => $row['FOLDER_ID'],
					'filterId' => $row['ID'],
				);
				if ($row['COLLECTED'] != 1)
				{
					$param['reload'] = 'Y';
				}
				// root folder
				if ($row['STORAGE_ID'] == $this->storageId && $row['FOLDER_ID'] == $this->storage->getRootObjectId())
				{
					$param['filterFolder'] = 'Y';
				}
				$row['ACTION_URL'] = $this->getActionUrl($param);
			}
			// Trashcan
			elseif
			(
				$indicator instanceof \Bitrix\Disk\Volume\Storage\TrashCan
			)
			{
				$param = array(
					'action' => self::ACTION_TRASH_FILES,
					'storageId' => $row['STORAGE_ID'],
					'filterId' => $row['ID'],
				);
				if ($row['COLLECTED'] != 1)
				{
					$param['reload'] = 'Y';
				}
				$row['ACTION_URL'] = $this->getActionUrl($param);
			}
			// Storage | User | Group | Common
			elseif
			(
				$indicator instanceof \Bitrix\Disk\Volume\IVolumeIndicatorStorage ||
				(isset($row['STORAGE_ID']) && (int)$row['STORAGE_ID'] > 0)
			)
			{
				$param = array(
					'action' => self::ACTION_STORAGE,
					'storageId' => $row['STORAGE_ID'],
					'filterId' => $row['ID'],
				);
				if ($row['COLLECTED'] != 1)
				{
					$param['reload'] = 'Y';
				}
				$row['ACTION_URL'] = $this->getActionUrl($param);
			}
			elseif ($indicator instanceof \Bitrix\Disk\Volume\IVolumeIndicatorModule)
			{
				/** @var \Bitrix\Disk\Volume\IVolumeIndicatorModule $indicator */
				$row['SPECIFIC'] = $this->getFragmentResult($row)->getSpecific();

				if (!$indicator instanceof \Bitrix\Disk\Volume\Module\Disk)
				{
					/** @var \Bitrix\Disk\Storage[] $storageList */
					$storageList = $indicator->getStorageList();
					if (count($storageList) > 0)
					{
						$row['ACTION_URL'] = $this->getActionUrl(array(
							'action' => 'moduleDetail',
							'moduleId' => $indicator::getModuleId()
						));
					}
				}
			}
		}
		catch (\Bitrix\Main\SystemException $ex)
		{
		}
	}

	/**
	 * @param integer $number Number to annotate.
	 * @param string[] $endings Numeric suffixes.
	 *
	 * @return string
	 */
	public function decorateNumber($number, $endings)
	{
		$number = $number % 100;
		if ($number >= 11 && $number <= 19)
		{
			$ending = $endings[2];
		}
		else
		{
			$i = $number % 10;
			switch ($i)
			{
				case 1:
					$ending = $endings[0];
					break;
				case 2:
				case 3:
				case 4:
					$ending = $endings[1];
					break;
				default:
					$ending = $endings[2];
			}
		}

		return $ending;
	}


	/**
	 * @param array $row Result row.
	 * @param \Bitrix\Disk\Storage $storage |null
	 * @param integer $width Icon width.
	 * @param integer $height Icon height.
	 *
	 * @return void
	 */
	public function decorateStorageIcon(&$row, &$storage, $width = 50, $height = 50)
	{
		$entityType = '';
		if ($storage instanceof \Bitrix\Disk\Storage)
		{
			$entityType = $storage->getEntityType();
		}
		elseif (!empty($row['ENTITY_TYPE']))
		{
			$entityType = $row['ENTITY_TYPE'];
		}

		if ($entityType ==  \Bitrix\Disk\ProxyType\User::className())
		{
			$row['STYLE'] = 'User';

			if ($storage instanceof \Bitrix\Disk\Storage)
			{
				$proxyType = $storage->getProxyType();
				if ($proxyType instanceof \Bitrix\Disk\ProxyType\User)
				{
					$user = $proxyType->getUser();
					if ($user instanceof \Bitrix\Disk\User)
					{
						$row['PICTURE'] = $user->getAvatarSrc($width, $height);
						$row['IS_EXTRANET'] = $user->isExtranetUser();
						$row['IS_EMAIL_AUTH'] = $user->isExternalAuthEmail();
						$row['IS_EMAIL_CRM'] = $user->isCrmEmail();
					}
				}
			}
		}
		elseif ($entityType == \Bitrix\Disk\ProxyType\Group::className())
		{
			$row['STYLE'] = 'Group';

			if ($storage instanceof \Bitrix\Disk\Storage)
			{
				$proxyType = $storage->getProxyType();
				if ($proxyType instanceof \Bitrix\Disk\ProxyType\Group)
				{
					$row['PICTURE'] = $proxyType->getEntityImageSrc($width, $height);
					$row['IS_EXTRANET'] = $proxyType->isExtranetGroup();
				}
			}
		}
		elseif ($entityType == \Bitrix\Disk\ProxyType\Common::className())
		{
			$row['STYLE'] = 'Common';
		}
	}

	/**
	 * Returns pastel color for css rules.
	 *
	 * @param bool $isOther Fixed grey color for other item.
	 *
	 * @return string
	 */
	public function pastelColors($isOther = false)
	{
		static $colors = array(
			'rgba(123,213,0,.9)',
			'rgba(247,204,0,.9)',
			'rgba(0,180,172,.9)',
			'rgba(0,99,198,.9)',
			'rgba(183,235,129,.9)',
			'rgba(255,123,119,.9)',
			'rgba(142,228,255,.9)',
			'rgba(250,243,138,.9)',
			'rgba(255,121,156,.9)',
			'rgba(202,89,222,.9)',
			'rgba(175,109,77,.9)',
			'rgba(62,211,179,.9)',
			'rgba(47,198,246,.9)',
			'rgba(178,187,204,.9)',
			'rgba(124,236,148,.9)',
			'rgba(168,173,180,.12)',
		);
		if ($isOther)
		{
			return 'rgba(100,100,100,.5)';
		}
		if (count($colors) > 0)
		{
			return array_shift($colors);
		}

		$r = round(mt_rand(0, 1) * 127) + 127;
		$g = round(mt_rand(0, 1) * 127) + 127;
		$b = round(mt_rand(0, 1) * 127) + 127;

		return "rgb({$r},{$g},{$b})";
	}


	/**
	 * Returns items for grid menu group action.
	 *
	 * @return array
	 */
	public function getMenuItems()
	{
		$menuItems = array();
		switch ($this->getAction())
		{
			case self::ACTION_DISKS:
			{
				$filterPresets = $this->getFilterPresetsDefinition($this->getAction());
				foreach ($filterPresets as $presetId => $filterPreset)
				{
					$menuItems[] = array(
						"TEXT" => $filterPreset['name'],
						"URL" => $this->getActionUrl(array('action' => $presetId, 'indicatorId' => $filterPreset['fields']['indicatorId'])),
						"ID" => $presetId,
						"IS_ACTIVE" => (bool)($this->indicatorId == $filterPreset['fields']['indicatorId']),
						"CLASS" => "disk-volume-menu-right-item",
					);
				}
				break;
			}

			case self::ACTION_STORAGE:
			case self::ACTION_FILES:
			{
				$menuItems[] = array(
					"TEXT" => Loc::getMessage('DISK_VOLUME_TOP_FOLDER'),
					"URL" => $this->getActionUrl(array('action' => self::ACTION_STORAGE, 'storageId' => $this->storageId)),
					"ID" => self::ACTION_STORAGE,
					"IS_ACTIVE" => (self::ACTION_STORAGE === $this->getAction()),
					"CLASS" => "disk-volume-menu-right-item",
				);
				$menuItems[] = array(
					"TEXT" => Loc::getMessage('DISK_VOLUME_TOP_FILE'),
					"URL" => $this->getActionUrl(array('action' => self::ACTION_FILES, 'storageId' => $this->storageId)),
					"ID" => self::ACTION_FILES,
					"IS_ACTIVE" => (self::ACTION_FILES === $this->getAction()),
					"CLASS" => "disk-volume-menu-right-item",
				);
				break;
			}
		}

		return $menuItems;
	}

	/**
	 * Returns items for storage action menu.
	 *
	 * @return array
	 */
	public function getStorageMenuItems()
	{
		$actionMenu = array();
		if (isset($this->arResult['Storage']['LIST'][0]))
		{
			if ($this->isAdminMode())
			{
				$menuItem = $this->getMenuItemDiskSendNotification($this->arResult['Storage']['LIST'][0], $this->storage);
				if (is_array($menuItem))
				{
					$actionMenu[] = $menuItem;
				}
			}
			$menuItem = $this->getMenuItemDiskClearance($this->arResult['Storage']['LIST'][0], $this->storage);
			if (is_array($menuItem))
			{
				$actionMenu[] = $menuItem;
			}
		}

		return $actionMenu;
	}

	/**
	 * Returns items for grid menu with group action.
	 *
	 * @param string $action Component action command.
	 *
	 * @return array
	 */
	public function getGridMenuGroupActions($action)
	{
		$snippet = new \Bitrix\Main\Grid\Panel\Snippet();

		$actionList = array(
			array('NAME' => Loc::getMessage('DISK_VOLUME_CHOOSE_ACTION'), 'VALUE' => 'none')
		);

		$applyButton = $snippet->getApplyButton(
			array(
				'ONCHANGE' => array(
					array(
						'ACTION' => \Bitrix\Main\Grid\Panel\Actions::CALLBACK,
						'DATA' => array(
							array(
								'JS' => 'BX.Disk.measureManager.groupAction()'
							)
						)
					)
				)
			)
		);
		switch ($action)
		{
			case self::ACTION_DISKS:
			{
				if ($this->isAdminMode())
				{
					$actionList[] = array(
						'NAME' => Loc::getMessage('DISK_VOLUME_ACTION_SEND'),
						'VALUE' => self::ACTION_SEND_NOTIFICATION,
						'ONCHANGE' => array(
							array(
								'ACTION' => \Bitrix\Main\Grid\Panel\Actions::RESET_CONTROLS
							)
						)
					);
				}

				$actionList[] = array(
					'NAME' => Loc::getMessage('DISK_VOLUME_ACTION_SAFE_CLEAR'),
					'VALUE' => self::ACTION_SETUP_CLEANER_JOB,
					'ONCHANGE' => array(
						array(
							'ACTION' => \Bitrix\Main\Grid\Panel\Actions::RESET_CONTROLS
						)
					)
				);
				break;
			}

			/*
			case self::ACTION_STORAGE:
			{
				if ($this->isAdminMode())
				{
					$actionList[] = array(
						'NAME' => Loc::getMessage('DISK_VOLUME_ACTION_SEND'),
						'VALUE' => self::ACTION_SEND_NOTIFICATION,
						'ONCHANGE' => array(
							array(
								'ACTION' => \Bitrix\Main\Grid\Panel\Actions::RESET_CONTROLS
							)
						)
					);
				}
				$actionList[] = array(
					'NAME' => Loc::getMessage('DISK_VOLUME_ACTION_DELETE_UNNECESSARY_VERSION'),
					'VALUE' => 'setupFolderCleanerJob',
					'ONCHANGE' => array(
						array(
							'ACTION' => \Bitrix\Main\Grid\Panel\Actions::RESET_CONTROLS
						)
					)
				);
				$actionList[] = array(
					'NAME' => Loc::getMessage('DISK_VOLUME_GROUP_FOLDER_EMPTY'),
					'VALUE' => 'setupFolderEmptyJob',
					'ONCHANGE' => array(
						array(
							'ACTION' => \Bitrix\Main\Grid\Panel\Actions::RESET_CONTROLS
						)
					)
				);
				$actionList[] = array(
					'NAME' => Loc::getMessage('DISK_VOLUME_GROUP_FOLDER_DROP'),
					'VALUE' => 'setupFolderDropJob',
					'ONCHANGE' => array(
						array(
							'ACTION' => \Bitrix\Main\Grid\Panel\Actions::RESET_CONTROLS
						)
					)
				);
				break;
			}
			*/

			case self::ACTION_FILES:
			{
				$actionList[] = array(
					'NAME' => Loc::getMessage('DISK_VOLUME_ACTION_DELETE_UNNECESSARY_VERSION'),
					'VALUE' => self::ACTION_DELETE_FILE_UNNECESSARY_VERSION,
					'ONCHANGE' => array(
						array(
							'ACTION' => \Bitrix\Main\Grid\Panel\Actions::RESET_CONTROLS
						)
					)
				);
				$actionList[] = array(
					'NAME' => Loc::getMessage('DISK_VOLUME_GROUP_FILE_DROP'),
					'VALUE' => self::ACTION_DELETE_FILE,
					'ONCHANGE' => array(
						array(
							'ACTION' => \Bitrix\Main\Grid\Panel\Actions::RESET_CONTROLS
						)
					)
				);
				break;
			}

			case self::ACTION_TRASH_FILES:
			{
				$actionList[] = array(
					'NAME' => Loc::getMessage('DISK_VOLUME_GROUP_FILE_DROP'),
					'VALUE' => self::ACTION_DELETE_FILE,
					'ONCHANGE' => array(
						array(
							'ACTION' => \Bitrix\Main\Grid\Panel\Actions::RESET_CONTROLS
						)
					)
				);
				break;
			}
		}
		$groupActions = array(
			'GROUPS' => array(
				array(
					'ITEMS' => array(
						array(
							"TYPE" => \Bitrix\Main\Grid\Panel\Types::DROPDOWN,
							"ID" => "action_button",
							"NAME" => "action_button",
							"ITEMS" => $actionList
						),
						$applyButton,
					)
				)
			)
		);

		return $groupActions;
	}

	/**
	 * Returns items for grid menu with group action.
	 *
	 * @param string $action Component action command.
	 * @param \Bitrix\Disk\Storage $storage Current storage.
	 *
	 * @return array
	 */
	public function getGridMenuGroupActionsStorage($action, &$storage)
	{
		$isImStorage = false;
		if ($storage instanceof \Bitrix\Disk\Storage)
		{
			$isImStorage = in_array($storage->getEntityType(), \Bitrix\Disk\Volume\Module\Im::getEntityType());
		}

		$snippet = new \Bitrix\Main\Grid\Panel\Snippet();

		$actionList = array(
			array('NAME' => Loc::getMessage('DISK_VOLUME_CHOOSE_ACTION'), 'VALUE' => 'none')
		);

		$applyButton = $snippet->getApplyButton(
			array(
				'ONCHANGE' => array(
					array(
						'ACTION' => \Bitrix\Main\Grid\Panel\Actions::CALLBACK,
						'DATA' => array(
							array(
								'JS' => 'BX.Disk.measureManager.groupAction()'
							)
						)
					)
				)
			)
		);
		switch ($action)
		{
			case self::ACTION_STORAGE:
			{
				if ($this->isAdminMode())
				{
					$actionList[] = array(
						'NAME' => Loc::getMessage('DISK_VOLUME_ACTION_SEND'),
						'VALUE' => self::ACTION_SEND_NOTIFICATION,
						'ONCHANGE' => array(
							array(
								'ACTION' => \Bitrix\Main\Grid\Panel\Actions::RESET_CONTROLS
							)
						)
					);
				}
				if ($this->isAllowClearStorage($storage))
				{
					$actionList[] = array(
						'NAME' => Loc::getMessage('DISK_VOLUME_ACTION_DELETE_UNNECESSARY_VERSION'),
						'VALUE' => 'setupFolderCleanerJob',
						'ONCHANGE' => array(
							array(
								'ACTION' => \Bitrix\Main\Grid\Panel\Actions::RESET_CONTROLS
							)
						)
					);
					$actionList[] = array(
						'NAME' => Loc::getMessage('DISK_VOLUME_GROUP_FOLDER_EMPTY'),
						'VALUE' => 'setupFolderEmptyJob',
						'ONCHANGE' => array(
							array(
								'ACTION' => \Bitrix\Main\Grid\Panel\Actions::RESET_CONTROLS
							)
						)
					);
					if (!$isImStorage)
					{
						$actionList[] = array(
							'NAME' => Loc::getMessage('DISK_VOLUME_GROUP_FOLDER_DROP'),
							'VALUE' => 'setupFolderDropJob',
							'ONCHANGE' => array(
								array(
									'ACTION' => \Bitrix\Main\Grid\Panel\Actions::RESET_CONTROLS
								)
							)
						);
					}
				}
				break;
			}
		}
		$groupActions = array(
			'GROUPS' => array(
				array(
					'ITEMS' => array(
						array(
							"TYPE" => \Bitrix\Main\Grid\Panel\Types::DROPDOWN,
							"ID" => "action_button",
							"NAME" => "action_button",
							"ITEMS" => $actionList
						),
						$applyButton,
					)
				)
			)
		);

		return $groupActions;
	}


	/**
	 * Returns menu item with a clearance action  for menu disk.
	 *
	 * @param array $row Result row.
	 * @param \Bitrix\Disk\Storage $storage |null
	 *
	 * @return array|null
	 */
	public function getMenuItemDiskClearance(&$row, &$storage)
	{
		$action = null;

		try
		{
			/** @var \Bitrix\Disk\Volume\IVolumeIndicator $indicator */
			$indicator = $this->getIndicatorResult($row);

			if (!($storage instanceof \Bitrix\Disk\Storage))
			{
				$fragment = $this->getFragmentResult($row);
				$storage = $fragment->getStorage();
			}

			// trashcan filter id
			$trashcanFilterId = -1;
			foreach ($this->arResult['TrashCan']['LIST'] as $trashCan)
			{
				if ($trashCan['STORAGE_ID'] == $row['STORAGE_ID'])
				{
					$trashcanFilterId = $trashCan['ID'];
					break;
				}
			}

			$dropTrashcanFlag = 'Y';

			switch ($indicator::getIndicatorId())
			{
				case \Bitrix\Disk\Volume\Storage\Storage::getIndicatorId():
				case \Bitrix\Disk\Volume\Storage\Common::getIndicatorId():
				case \Bitrix\Disk\Volume\Storage\Group::getIndicatorId():
				case \Bitrix\Disk\Volume\Storage\User::getIndicatorId():
				{
					if ($trashcanFilterId < 0)
					{
						$dropTrashcanFlag = 'N';
					}
					if (
						$this->isAllowClearStorage($storage) &&
						($trashcanFilterId > 0 || (int)$row['UNNECESSARY_VERSION_COUNT'] > 0)
					)
					{
						$action = array(
							"text" => Loc::getMessage('DISK_VOLUME_ACTION_SAFE_CLEAR'),
							'onclick' => 'BX.Disk.measureManager.openConfirm('.\CUtil::PhpToJSObject(array(
									'messageConfirmId' => 'DISK_VOLUME_DELETE_STORAGE_SAFE_CLEAR_CONFIRM',
									'name' => $row['TITLE'],
									'payload' => 'callAction',
									'action' => self::ACTION_SETUP_CLEANER_JOB,
									'metric' => self::METRIC_MARK_CERTAIN_DISK_CLEAN,
									self::ACTION_DELETE_UNNECESSARY_VERSION => 'Y',
									'filterIdsStorage' => array((int)$row['ID']),
									self::ACTION_EMPTY_TRASHCAN => $dropTrashcanFlag,
									'filterIdsTrashCan' => array((int)$trashcanFilterId),
									'storageId' => (int)$row['STORAGE_ID'],
									'before' => 'BX.Disk.measureManager.showAlertSetupProcess',
								)).');'
						);
					}
					break;
				}

				case \Bitrix\Disk\Volume\Storage\TrashCan::getIndicatorId():
				{
					$action = array(
						"text" => Loc::getMessage('DISK_VOLUME_ACTION_SAFE_CLEAR'),
						'onclick' => 'BX.Disk.measureManager.openConfirm('.\CUtil::PhpToJSObject(array(
								'messageConfirmId' => 'DISK_VOLUME_DELETE_TRASHCAN_SAFE_CLEAR_CONFIRM',
								'name' => $row['TITLE'],
								'payload' => 'callAction',
								'action' => self::ACTION_SETUP_CLEANER_JOB,
								'metric' => self::METRIC_MARK_CERTAIN_DISK_CLEAN,
								self::ACTION_DELETE_UNNECESSARY_VERSION => 'N',
								'filterIdsStorage' => array((int)$row['ID']),
								self::ACTION_EMPTY_TRASHCAN => 'Y',
								'filterIdsTrashCan' => array((int)$row['ID']),
								'storageId' => (int)$row['STORAGE_ID'],
								'before' => 'BX.Disk.measureManager.showAlertSetupProcess',
							)).');'
					);
					break;
				}
			}
		}
		catch (\Bitrix\Main\SystemException $ex)
		{
		}

		return $action;
	}

	/**
	 * Returns menu item with a send notification action for menu disk.
	 *
	 * @param array $row Result row.
	 * @param \Bitrix\Disk\Storage $storage |null
	 *
	 * @return array|null
	 */
	public function getMenuItemDiskSendNotification(&$row, &$storage)
	{
		$action = null;

		try
		{
			/** @var \Bitrix\Disk\Volume\IVolumeIndicator $indicator */
			$indicator = $this->getIndicatorResult($row);

			$entityType = '';
			if ($storage instanceof \Bitrix\Disk\Storage)
			{
				$entityType = $storage->getEntityType();
			}
			elseif (!empty($row['ENTITY_TYPE']))
			{
				$entityType = $row['ENTITY_TYPE'];
			}

			switch ($entityType)
			{
				case \Bitrix\Disk\ProxyType\User::className():
				case \Bitrix\Disk\ProxyType\Group::className():
				{
					$action = array(
						"text" => Loc::getMessage('DISK_VOLUME_ACTION_SEND'),
						'onclick' => "BX.Disk.measureManager.callAction(". \CUtil::PhpToJSObject(array(
										'action' => self::ACTION_SEND_NOTIFICATION,
										'storageId' => (int)$row['STORAGE_ID'],
										'indicatorId' => $indicator::getIndicatorId(),
										'filterId' => (int)$row['ID'],
									)). ");"
					);

					break;
				}
			}
		}
		catch (\Bitrix\Main\SystemException $ex)
		{
		}

		return $action;
	}

	/**
	 * Returns items for menu folder action.
	 *
	 * @param array $row Result row.
	 * @param \Bitrix\Disk\Storage $storage |null
	 *
	 * @return array
	 */
	public function getFolderActionMenu(&$row, &$storage)
	{
		$actions = array();

		try
		{
			/** @var \Bitrix\Disk\Volume\Fragment $fragment */
			$fragment = $this->getFragmentResult($row);

			if (!($storage instanceof \Bitrix\Disk\Storage))
			{
				$storage = $fragment->getStorage();
			}

			if (
				($fragment->getIndicatorType() == \Bitrix\Disk\Volume\Folder::className()) &&
				($storage instanceof \Bitrix\Disk\Storage) 
			)
			{
				if ($this->isAllowClearStorage($storage))
				{
					$isRootFolder = ($row['FOLDER_ID'] == $storage->getRootObjectId());

					if ($this->arResult["ADMIN_MODE"])
					{
						$menuItem = $this->getMenuItemDiskSendNotification($row, $storage);
						if (is_array($menuItem))
						{
							$actions[] = $menuItem;
						}
					}

					if ($fragment->getUnnecessaryVersionCount() > 0)
					{
						if ($isRootFolder)
						{
							$messageId = 'DISK_VOLUME_DELETE_ROOT_UNNECESSARY_VERSION_CONFIRM';
						}
						else
						{
							$messageId = 'DISK_VOLUME_DELETE_FOLDER_UNNECESSARY_VERSION_CONFIRM';
						}
						if ($fragment->getUnnecessaryVersionCount() <= self::SETUP_CLEANER_FILE_THRESHOLD_COUNT)
						{
							$actions[] = array(
								"text" => Loc::getMessage('DISK_VOLUME_ACTION_DELETE_UNNECESSARY_VERSION'),
								'onclick' => 'BX.Disk.measureManager.openConfirm('.\CUtil::PhpToJSObject(array(
										'messageConfirmId' => $messageId,
										'name' => $row['TITLE'],
										'payload' => self::ACTION_DELETE_UNNECESSARY_VERSION,
										'metric' => self::METRIC_MARK_CERTAIN_FOLDER_CLEAN,
										'indicatorId' => \Bitrix\Disk\Volume\Folder::getIndicatorId(),
										'storageId' => (int)$row['STORAGE_ID'],
										'filterId' => (int)$row['ID'],
										'before' => 'BX.Disk.measureManager.showAlertSetupProcess',
									)).');'
							);
						}
						else
						{
							$actions[] = array(
								"text" => Loc::getMessage('DISK_VOLUME_ACTION_DELETE_UNNECESSARY_VERSION'),
								'onclick' => 'BX.Disk.measureManager.openConfirm('.\CUtil::PhpToJSObject(array(
										'messageConfirmId' => $messageId,
										'name' => $row['TITLE'],
										'payload' => 'callAction',
										'action' => self::ACTION_SETUP_CLEANER_JOB,
										'metric' => self::METRIC_MARK_CERTAIN_FOLDER_CLEAN,
										self::ACTION_DELETE_UNNECESSARY_VERSION => 'Y',
										self::ACTION_EMPTY_TRASHCAN => 'N',
										'storageId' => (int)$row['STORAGE_ID'],
										'filterId' => (int)$row['ID'],
										'before' => 'BX.Disk.measureManager.showAlertSetupProcess',
									)).');'
							);
						}
					}

					if ($isRootFolder)
					{
						$messageId = 'DISK_VOLUME_EMPTY_ROOT_CONFIRM';
					}
					else
					{
						$messageId = 'DISK_VOLUME_EMPTY_FOLDER_CONFIRM';
					}
					if ($this->isAllowClearFolder($row))
					{
						if ($fragment->getFileCount() <= self::SETUP_CLEANER_FILE_THRESHOLD_COUNT)
						{
							$actions[] = array(
								"text" => Loc::getMessage('DISK_VOLUME_ACTION_EMPTY_FOLDER'),
								'onclick' => 'BX.Disk.measureManager.openConfirm('.\CUtil::PhpToJSObject(array(
										'messageConfirmId' => $messageId,
										'name' => $row['TITLE'],
										'payload' => self::ACTION_EMPTY_FOLDER,
										'metric' => self::METRIC_MARK_CERTAIN_FOLDER_CLEAN,
										'indicatorId' => \Bitrix\Disk\Volume\Folder::getIndicatorId(),
										'storageId' => (int)$row['STORAGE_ID'],
										'folderId' => (int)$row['FOLDER_ID'],
										'filterId' => (int)$row['ID'],
										'before' => 'BX.Disk.measureManager.showAlertSetupProcess',
									)).');'
							);
						}
						else
						{
							$actions[] = array(
								"text" => Loc::getMessage('DISK_VOLUME_ACTION_EMPTY_FOLDER'),
								'onclick' => 'BX.Disk.measureManager.openConfirm('.\CUtil::PhpToJSObject(array(
										'messageConfirmId' => $messageId,
										'name' => $row['TITLE'],
										'payload' => 'callAction',
										'action' => self::ACTION_SETUP_CLEANER_JOB,
										'metric' => self::METRIC_MARK_CERTAIN_FOLDER_CLEAN,
										self::ACTION_EMPTY_FOLDER => 'Y',
										'storageId' => (int)$row['STORAGE_ID'],
										'folderId' => (int)$row['FOLDER_ID'],
										'filterId' => (int)$row['ID'],
										'before' => 'BX.Disk.measureManager.showAlertSetupProcess',
									)).');'
							);
						}
					}

					if ($this->isAllowDeleteFolder($row))
					{
						if ($fragment->getFileCount() <= self::SETUP_CLEANER_FILE_THRESHOLD_COUNT)
						{
							$actions[] = array(
								"text" => Loc::getMessage('DISK_VOLUME_ACTION_DELETE_FOLDER'),
								'onclick' => 'BX.Disk.measureManager.openConfirm('.\CUtil::PhpToJSObject(array(
										'messageConfirmId' => $messageId,
										'name' => $row['TITLE'],
										'payload' => self::ACTION_DELETE_FOLDER,
										'metric' => self::METRIC_MARK_CERTAIN_FOLDER_CLEAN,
										'indicatorId' => \Bitrix\Disk\Volume\Folder::getIndicatorId(),
										'storageId' => (int)$row['STORAGE_ID'],
										'folderId' => (int)$row['FOLDER_ID'],
										'filterId' => (int)$row['ID'],
										'before' => 'BX.Disk.measureManager.showAlertSetupProcess',
									)).');'
							);
						}
						else
						{
							$actions[] = array(
								"text" => Loc::getMessage('DISK_VOLUME_ACTION_DELETE_FOLDER'),
								'onclick' => 'BX.Disk.measureManager.openConfirm('.\CUtil::PhpToJSObject(array(
										'messageConfirmId' => 'DISK_VOLUME_DELETE_FOLDER_CONFIRM',
										'name' => $row['TITLE'],
										'payload' => 'callAction',
										'action' => self::ACTION_SETUP_CLEANER_JOB,
										'metric' => self::METRIC_MARK_CERTAIN_FOLDER_CLEAN,
										self::ACTION_DELETE_FOLDER => 'Y',
										'storageId' => (int)$row['STORAGE_ID'],
										'folderId' => (int)$row['FOLDER_ID'],
										'filterId' => (int)$row['ID'],
										'before' => 'BX.Disk.measureManager.showAlertSetupProcess',
									)).');'
							);
						}
					}
				}

				if (!empty($row['URL']))
				{
					$actions[] = array(
						"text" => Loc::getMessage('DISK_VOLUME_OPEN'),
						'href' => $row['URL'],
					);
				}
			}
		}
		catch (\Bitrix\Main\SystemException $ex)
		{
		}

		return $actions;
	}

	/**
	 * Returns items for menu file action.
	 *
	 * @param array $row Result row.
	 * @param \Bitrix\Disk\Storage $storage |null
	 *
	 * @return array
	 */
	public function getFileActionMenu(&$row, &$storage)
	{
		$actions = array();

		try
		{
			/** @var \Bitrix\Disk\Volume\Fragment $fragment */
			$fragment = $this->getFragmentResult($row);

			if (!($storage instanceof \Bitrix\Disk\Storage))
			{
				$storage = $fragment->getStorage();
			}

			if (
				($fragment->getIndicatorType() == \Bitrix\Disk\Volume\File::className()) &&
				($storage instanceof \Bitrix\Disk\Storage)
			)
			{
				if ($this->isAllowClearStorage($storage))
				{
					if ($fragment->getUnnecessaryVersionCount() > 0)
					{
						$actions[] = array(
							"text" => Loc::getMessage('DISK_VOLUME_ACTION_DELETE_UNNECESSARY_VERSION'),
							'onclick' => 'BX.Disk.measureManager.openConfirm('.\CUtil::PhpToJSObject(array(
									'messageConfirmId' => 'DISK_VOLUME_DELETE_FILE_UNNECESSARY_VERSION_CONFIRM',
									'payload' => self::ACTION_DELETE_FILE_UNNECESSARY_VERSION,
									'name' => $row['TITLE'],
									'storageId' => (int)$row['STORAGE_ID'],
									'fileId' => (int)$fragment->getFileId()
								)).');'
						);
					}

					if ($this->isAllowClearFolder($row))
					{
						$actions[] = array(
							"text" => Loc::getMessage('DISK_VOLUME_ACTION_DELETE_FILE'),
							'onclick' => 'BX.Disk.measureManager.openConfirm('.\CUtil::PhpToJSObject(array(
									'messageConfirmId' => 'DISK_VOLUME_DELETE_FILE_CONFIRM',
									'payload' => self::ACTION_DELETE_FILE,
									'doNotShowModalAlert' => true,
									'name' => $row['TITLE'],
									'storageId' => (int)$row['STORAGE_ID'],
									'fileId' => (int)$fragment->getFileId()
								)).');'
						);
					}
				}
				if (isset($row['URL']))
				{
					$actions[] = array(
						"text" => Loc::getMessage('DISK_VOLUME_OPEN'),
						'href' => $row['URL'],
					);
					$actions[] = array(
						"text" => Loc::getMessage('DISK_VOLUME_OPEN_HISTORY'),
						'href' => $row['URL']. '#hl-'. (int)$fragment->getFileId(). '!history',
					);
				}
			}
		}
		catch (\Bitrix\Main\SystemException $ex)
		{
		}

		return $actions;
	}

	/**
	 * Returns  action items for deleted file.
	 *
	 * @param array $row Result row.
	 * @param \Bitrix\Disk\Storage $storage |null
	 *
	 * @return array
	 */
	public function getDeletedFileActionMenu(&$row)
	{
		$actions = array();

		try
		{
			/** @var \Bitrix\Disk\Volume\Fragment $fragment */
			$fragment = $this->getFragmentResult($row);

			if ($fragment->getIndicatorType() == \Bitrix\Disk\Volume\FileDeleted::className())
			{
				$actions[] = array(
					"text" => Loc::getMessage('DISK_VOLUME_ACTION_DELETE_FILE'),
					'onclick' => 'BX.Disk.measureManager.openConfirm('. \CUtil::PhpToJSObject(array(
									'messageConfirmId' => 'DISK_VOLUME_DELETE_FILE_CONFIRM',
									'payload' => self::ACTION_DELETE_FILE,
									'doNotShowModalAlert' => true,
									'name' => $row['TITLE'],
									'storageId' => (int)$row['STORAGE_ID'],
									'fileId' => (int)$fragment->getFileId()
								)). ');'
				);
				if (isset($row['URL']))
				{
					$actions[] = array(
						"text" => Loc::getMessage('DISK_VOLUME_OPEN'),
						'href' => $row['URL'],
					);
					$actions[] = array(
						"text" => Loc::getMessage('DISK_VOLUME_OPEN_HISTORY'),
						'href' => $row['URL']. '#hl-'. (int)$fragment->getFileId(). '!history',
					);
				}
			}
		}
		catch (\Bitrix\Main\SystemException $ex)
		{
		}

		return $actions;
	}


	/**
	 * Check ability to drop folder.
	 *
	 * @param array $row Result row.
	 *
	 * @return boolean
	 * @throws \Bitrix\Main\SystemException
	 */
	public function isAllowDeleteFolder(&$row)
	{
		$allowDrop = true;

		/** @var \Bitrix\Disk\Volume\IDeleteConstraint[] $deleteConstraintList */
		static $deleteConstraintList;
		if (empty($deleteConstraintList))
		{
			$deleteConstraintList = array();

			// full list available indicators
			$constraintIdList = \Bitrix\Disk\Volume\Base::listDeleteConstraint();
			foreach ($constraintIdList as $indicatorId => $indicatorIdClass)
			{
				$deleteConstraintList[$indicatorId] = new $indicatorIdClass();
			}
		}

		/** @var \Bitrix\Disk\Volume\Fragment $fragment */
		$fragment = $this->getFragmentResult($row);

		$folder = $fragment->getFolder();

		if ($folder instanceof \Bitrix\Disk\Folder)
		{
			foreach ($deleteConstraintList as $indicatorId => $indicator)
			{
				if (!$indicator->isAllowDeleteFolder($folder))
				{
					$allowDrop = false;
				}
			}
		}

		return $allowDrop;
	}

	/**
	 * Check ability to empty folder.
	 *
	 * @param array $row Result row.
	 *
	 * @return boolean
	 * @throws \Bitrix\Main\SystemException
	 */
	public function isAllowClearFolder(&$row)
	{
		$allowClear = true;

		/** @var \Bitrix\Disk\Volume\IClearFolderConstraint[] $clearFolderConstraintList */
		static $clearFolderConstraintList;
		if (empty($clearFolderConstraintList))
		{
			$clearFolderConstraintList = array();

			// full list available indicators
			$constraintIdList = \Bitrix\Disk\Volume\Base::listClearFolderConstraint();
			foreach ($constraintIdList as $indicatorId => $indicatorIdClass)
			{
				$clearFolderConstraintList[$indicatorId] = new $indicatorIdClass();
			}
		}

		/** @var \Bitrix\Disk\Volume\Fragment $fragment */
		$fragment = $this->getFragmentResult($row);

		$folder = $fragment->getFolder();

		if ($folder instanceof \Bitrix\Disk\Folder)
		{
			foreach ($clearFolderConstraintList as $indicatorId => $indicator)
			{
				if (!$indicator->isAllowClearFolder($folder))
				{
					$allowClear = false;
				}
			}
		}

		return $allowClear;
	}

	/**
	 * Check ability to clear storage.
	 * @param \Bitrix\Disk\Storage $storage Storage to clear.
	 * @return boolean
	 */
	public function isAllowClearStorage(&$storage)
	{
		$allowClear = true;

		/** @var \Bitrix\Disk\Volume\IClearConstraint[] $clearConstraintList */
		static $clearConstraintList;
		if (empty($clearConstraintList))
		{
			$clearConstraintList = array();

			// full list available indicators
			$constraintIdList = \Bitrix\Disk\Volume\Base::listClearConstraint();
			foreach ($constraintIdList as $indicatorId => $indicatorIdClass)
			{
				$clearConstraintList[$indicatorId] = new $indicatorIdClass();
			}
		}

		if ($storage instanceof \Bitrix\Disk\Storage)
		{
			/** @var \Bitrix\Disk\Volume\IClearConstraint $indicator */
			foreach ($clearConstraintList as $indicatorId => $indicator)
			{
				if (!$indicator->isAllowClearStorage($storage))
				{
					$allowClear = false;
				}
			}
		}

		return $allowClear;
	}
}