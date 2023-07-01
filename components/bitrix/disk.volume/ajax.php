<?php

define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

$siteId = isset($_REQUEST['SITE_ID']) && is_string($_REQUEST['SITE_ID'])? $_REQUEST['SITE_ID'] : '';
$siteId = mb_substr(preg_replace('/[^a-z0-9_]/i', '', $siteId), 0, 2);
if(!empty($siteId) && is_string($siteId))
{
	define('SITE_ID', $siteId);
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Disk\ProxyType;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Volume;

if (!Main\Loader::includeModule('disk'))
{
	return;
}

Loc::loadMessages(__FILE__);

include_once ('class.php');


class DiskVolumeController extends \Bitrix\Disk\Internals\Controller
{
	/** @var  \CDiskVolumeComponent $component */
	private $component;

	/** @var string	 */
	private $relativePath = '';

	/** @var boolean */
	private $sefMode = false;

	/** @var string[] */
	private $sefPath = array();

	/** @var \Bitrix\Disk\Storage */
	private $storage;

	/** @var int */
	private $storageId;

	/** @var \Bitrix\Disk\Folder */
	private $folder;

	/** @var int */
	private $folderId;

	/** @var \Bitrix\Disk\File */
	private $file;

	/** @var int */
	private $fileId;

	/** @var int[] */
	private $fileIds;

	/** @var int */
	private $filterId = -1;

	/** @var string */
	private $indicatorId = '';

	/** @var int */
	private $queueStep = -1;

	/** @var int */
	private $queueLength = -1;

	/** string */
	private $subTask = null;

	/** @var int */
	private $subStep = null;

	/** @var bool */
	private $reload = false;

	/* must repeat action */
	const STATUS_TIMEOUT = 'timeout';

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->component = new \CDiskVolumeComponent();
	}

	/**
	 * @return array
	 */
	protected function listActions()
	{
		$listAction = array();

		$listAction[\CDiskVolumeComponent::ACTION_DEFAULT] = array(
			'method' => array('GET','POST'),
		);

		$listAction['reloadTotals'] = array(
			'method' => array('GET','POST'),
		);

		$listAction[\CDiskVolumeComponent::ACTION_PURIFY] = array(
			'method' => array('GET','POST'),
		);
		$listAction[\CDiskVolumeComponent::ACTION_MEASURE] = array(
			'method' => array('GET','POST'),
		);
		$listAction[\CDiskVolumeComponent::ACTION_MEASURE_STORAGE] = array(
			'method' => array('GET','POST'),
		);
		$listAction[\CDiskVolumeComponent::ACTION_MEASURE_FOLDER] = array(
			'method' => array('GET','POST'),
		);

		$listAction[\CDiskVolumeComponent::ACTION_DELETE_FILE] = array(
			'method' => array('GET','POST'),
		);
		$listAction[\CDiskVolumeComponent::ACTION_DELETE_FILE_UNNECESSARY_VERSION] = array(
			'method' => array('GET','POST'),
		);
		$listAction[\CDiskVolumeComponent::ACTION_DELETE_GROUP_FILE] = array(
			'method' => array('GET','POST'),
		);
		$listAction[\CDiskVolumeComponent::ACTION_DELETE_GROUP_FILE_UNNECESSARY_VERSION] = array(
			'method' => array('GET','POST'),
		);
		$listAction[\CDiskVolumeComponent::ACTION_DELETE_UNNECESSARY_VERSION] = array(
			'method' => array('GET','POST'),
		);


		$listAction[\CDiskVolumeComponent::ACTION_DELETE_FOLDER] = array(
			'method' => array('GET','POST'),
		);
		$listAction[\CDiskVolumeComponent::ACTION_EMPTY_FOLDER] = array(
			'method' => array('GET','POST'),
		);


		$listAction[\CDiskVolumeComponent::ACTION_SETUP_CLEANER_JOB] = array(
			'method' => array('GET','POST'),
		);

		$listAction[\CDiskVolumeComponent::ACTION_SEND_NOTIFICATION] = array(
			'method' => array('GET','POST'),
		);

		$listAction[\CDiskVolumeComponent::ACTION_CANCEL_WORKERS] = array(
			'method' => array('GET','POST'),
		);

		return $listAction;
	}


	/**
	 * @return bool
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function prepareParams()
	{
		$params = array();
		if (!is_null($this->request->get('componentParams')))
		{
			$params = \Bitrix\Main\Component\ParameterSigner::unsignParameters(
				'bitrix:disk.volume',
				$this->request->get('componentParams')
			);
		}

		if (!is_null($params['relUrl']))
		{
			$this->relativePath = $params['relUrl'];
		}

		if (!is_null($params['restrictStorageId']) && (int)$params['restrictStorageId'] > 0)
		{
			$this->component->arParams['STORAGE_ID'] = (int)$params['restrictStorageId'];
		}

		if (!is_null($params['sefMode']))
		{
			$this->sefMode = (bool)($params['sefMode'] === 'Y');
		}

		if ($this->sefMode)
		{
			$listAction = $this->component->listActions();
			foreach ($listAction as $action => $description)
			{
				if (empty($this->sefPath['PATH_TO_DISK_VOLUME_'.mb_strtoupper($action)]))
				{
					$this->sefPath['PATH_TO_DISK_VOLUME_'.mb_strtoupper($action)] = $description['sef_path'];
				}
			}
		}

		if (!is_null($this->request->get('indicatorId')))
		{
			$this->indicatorId = $this->request->get('indicatorId');
		}

		if (!is_null($this->request->get('queueStep')))
		{
			$this->queueStep = (int)$this->request->get('queueStep');
			$this->component->setQueueStepParam('queueStep', $this->queueStep);
		}
		if (!is_null($this->request->get('queueLength')))
		{
			$this->queueLength = (int)$this->request->get('queueLength');
			$this->component->setQueueStepParam('queueLength', $this->queueLength);
		}
		if (!is_null($this->request->get('subTask')))
		{
			$this->subTask = $this->request->get('subTask');
			$this->component->setQueueStepParam('subTask', $this->subTask);
		}
		if (!is_null($this->request->get('subStep')))
		{
			$this->subStep = (int)$this->request->get('subStep');
		}
		// cancel queue
		if (!is_null($this->request->get('queueStop')))
		{
			$this->component->clearQueueStep();
		}

		// current user storageId
		$this->component->loadCurrentUserStorage();

		if (!$this->component->isAdminMode())
		{
			$this->storageId = $this->component->getCurrentUserStorageId();
		}
		elseif (!is_null($this->request->get('storageId')))
		{
			$this->storageId = (int)$this->request->get('storageId');
		}



		if (!is_null($this->request->get('folderId')))
		{
			$this->folderId = (int)$this->request->get('folderId');
		}

		if (!is_null($this->request->get('fileId')))
		{
			$this->fileId = (int)$this->request->get('fileId');
		}

		if (!is_null($this->request->get('fileIds')) && is_array($this->request->get('fileIds')))
		{
			$this->fileIds = array_map('intVal', (array)$this->request->get('fileIds'));
		}

		if (!is_null($this->request->get('filterId')))
		{
			$this->filterId = (int)$this->request->get('filterId');
		}

		if (!is_null($this->request->get('reload')))
		{
			$this->reload = (bool)($this->request->get('reload') === 'Y');
		}

		return true;
	}

	/**
	 * @var string $actionName
	 * @return bool
	 */
	protected function processBeforeAction($actionName)
	{
		if ($this->indicatorId != '')
		{
			try
			{
				$this->component->getIndicator($this->indicatorId);
			}
			catch(\Bitrix\Main\ObjectException $ex)
			{
				$this->errorCollection->add(array(new Error('Wrong parameter: indicatorId')));
				$this->sendJsonErrorResponse();
			}
		}

		if (
			$actionName === \CDiskVolumeComponent::ACTION_MEASURE ||
			$actionName === \CDiskVolumeComponent::ACTION_DELETE_UNNECESSARY_VERSION
		)
		{
			if (is_null($this->indicatorId) || $this->indicatorId == '')
			{
				$this->errorCollection->add(array(new Error('Undefined parameter: indicatorId')));
				$this->sendJsonErrorResponse();
			}
		}

		if (
			$actionName === \CDiskVolumeComponent::ACTION_DELETE_UNNECESSARY_VERSION
		)
		{
			if (is_null($this->filterId) || $this->filterId <= 0)
			{
				$this->errorCollection->add(array(new Error('Undefined parameter: filterId')));
				$this->sendJsonErrorResponse();
			}
		}

		if (
			$actionName === \CDiskVolumeComponent::ACTION_MEASURE_STORAGE ||
			$actionName === \CDiskVolumeComponent::ACTION_MEASURE_FOLDER ||
			$actionName === \CDiskVolumeComponent::ACTION_DELETE_FILE_UNNECESSARY_VERSION ||
			$actionName === \CDiskVolumeComponent::ACTION_DELETE_FILE ||
			$actionName === \CDiskVolumeComponent::ACTION_DELETE_GROUP_FILE_UNNECESSARY_VERSION ||
			$actionName === \CDiskVolumeComponent::ACTION_DELETE_GROUP_FILE ||
			$actionName === \CDiskVolumeComponent::ACTION_DELETE_FOLDER ||
			$actionName === \CDiskVolumeComponent::ACTION_EMPTY_FOLDER ||
			($actionName === 'reloadTotals' && $this->storageId > 0)
		)
		{
			if (is_null($this->storageId) || $this->storageId <= 0)
			{
				$this->errorCollection->add(array(new Error('Undefined parameter: storageId')));
				$this->sendJsonErrorResponse();
			}

			$this->component->setStorageId($this->storageId);

			$this->storage = \Bitrix\Disk\Storage::loadById($this->storageId);
			if (!$this->storage instanceof \Bitrix\Disk\Storage)
			{
				$this->errorCollection->add(array(new Error("Could not find storage {$this->storageId}")));
				$this->sendJsonErrorResponse();
			}
		}

		if (
			$actionName === \CDiskVolumeComponent::ACTION_MEASURE_FOLDER ||
			$actionName === \CDiskVolumeComponent::ACTION_DELETE_FOLDER ||
			$actionName === \CDiskVolumeComponent::ACTION_EMPTY_FOLDER
		)
		{
			if (is_null($this->folderId) || $this->folderId <= 0)
			{
				$this->errorCollection->add(array(new Error('Undefined parameter: folderId')));
				$this->sendJsonErrorResponse();
			}

			$this->component->setFolderId($this->folderId);

			$this->folder = \Bitrix\Disk\Folder::loadById($this->folderId);
			if (!$this->folder instanceof \Bitrix\Disk\Folder)
			{
				$this->errorCollection->add(array(new Error("Could not find folder {$this->folderId}")));
				$this->sendJsonErrorResponse();
			}
		}
		elseif ($actionName === \CDiskVolumeComponent::ACTION_MEASURE_STORAGE)
		{
			$this->folder = $this->storage->getRootObject();
			if (!$this->folder instanceof \Bitrix\Disk\Folder)
			{
				$this->errorCollection->add(array(new Error("Could not find root folder of the storage {$this->storageId}")));
				$this->sendJsonErrorResponse();
			}
			else
			{
				$this->folderId = $this->folder->getId();
			}
		}

		if (
			$actionName === \CDiskVolumeComponent::ACTION_DELETE_FILE ||
			$actionName === \CDiskVolumeComponent::ACTION_DELETE_FILE_UNNECESSARY_VERSION
		)
		{
			if (is_null($this->fileId) || $this->fileId <= 0)
			{
				$this->errorCollection->add(array(new Error('Undefined parameter: fileId')));
				$this->sendJsonErrorResponse();
			}

			$this->file = \Bitrix\Disk\File::loadById($this->fileId);
			if (!$this->file instanceof \Bitrix\Disk\File)
			{
				$this->errorCollection->add(array(new Error("Could not find file {$this->fileId}")));
				$this->sendJsonErrorResponse();
			}
		}

		if (
			$actionName === \CDiskVolumeComponent::ACTION_DELETE_GROUP_FILE ||
			$actionName === \CDiskVolumeComponent::ACTION_DELETE_GROUP_FILE_UNNECESSARY_VERSION
		)
		{
			if (is_null($this->fileIds) || count($this->fileIds) == 0)
			{
				$this->errorCollection->add(array(new Error('Undefined parameter: fileId')));
				$this->sendJsonErrorResponse();
			}
		}

		return true;
	}

	/**
	 * @return void
	 */
	protected function processActionDefault()
	{
		// do nothing
		$this->sendJsonSuccessResponse();
	}


	/**
	 * @return void
	 */
	protected function processActionReloadTotals()
	{
		$answer = array();

		try
		{
			$answer = $this->component->getTotals();
		}
		catch(\Bitrix\Main\SystemException $exception)
		{
			$this->errorCollection->add(array(new Error($exception->getMessage(), $exception->getCode())));
		}

		$this->sendJsonSuccessResponse($answer);
	}

	/**
	 * Deletes file.
	 * @return void
	 */
	protected function processActionDeleteFile()
	{
		$securityContext = $this->getSecurityContextByObject($this->file);
		if(!$this->file->canDelete($securityContext))
		{
			$this->sendJsonAccessDeniedResponse(Loc::getMessage('DISK_VOLUME_ERROR_BAD_RIGHTS_FILE'));
		}

		$cleaner = new Volume\Cleaner($this->getUser()->getId());

		if (!$cleaner->isAllowClearFolder($this->file->getParent()))
		{
			$this->sendJsonAccessDeniedResponse(Loc::getMessage('DISK_VOLUME_ERROR_BAD_RIGHTS_FILE'));
		}

		if(!$cleaner->deleteFile($this->file))
		{
			$this->errorCollection->add($cleaner->getErrors());
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse(array(
			'message' => Loc::getMessage('DISK_VOLUME_FILE_DELETE_OK'),
		));
	}

	/**
	 * @return void
	 */
	protected function processActionDeleteFileUnnecessaryVersion()
	{
		$securityContext = $this->getSecurityContextByObject($this->file);
		if(!$this->file->canDelete($securityContext))
		{
			$this->sendJsonAccessDeniedResponse(Loc::getMessage('DISK_VOLUME_ERROR_BAD_RIGHTS_FILE'));
		}

		$cleaner = new Volume\Cleaner($this->getUser()->getId());
		$cleaner->startTimer();

		if(!$cleaner->deleteFileUnnecessaryVersion($this->file))
		{
			if ($cleaner->hasErrors())
			{
				$this->errorCollection->add($cleaner->getErrors());
				$this->sendJsonErrorResponse();
			}
			else
			{
				if ($cleaner->hasTimeLimitReached())
				{
					$this->sendJsonSuccessResponse(array(
						'timeout' => 'Y',
						'dropped_file_count' => $cleaner->getDroppedFileCount(),
						'dropped_file_version_count' => $cleaner->getDroppedVersionCount(),
					));
				}
			}
		}

		$this->sendJsonSuccessResponse(array(
			'message' => Loc::getMessage('DISK_VOLUME_FILE_VERSION_DELETE_OK'),
		));
	}

	/**
	 * Deletes file.
	 * @return void
	 */
	protected function processActionDeleteGroupFile()
	{
		$cleaner = new Volume\Cleaner($this->getUser()->getId());

		foreach ($this->fileIds as $fileId)
		{
			if ($fileId <= 0)
			{
				continue;
			}

			$file = \Bitrix\Disk\File::loadById($fileId);
			if (!$file instanceof \Bitrix\Disk\File)
			{
				$this->errorCollection->add(array(new Error("Could not find file {$fileId}")));
				continue;
			}

			$securityContext = $this->getSecurityContextByObject($file);
			if(!$file->canDelete($securityContext))
			{
				$this->errorCollection->add(array(new Error("Could not delete file {$fileId}")));
				continue;
			}

			if (!$cleaner->isAllowClearFolder($file->getParent()))
			{
				$this->errorCollection->add(array(new Error("Could not delete file {$fileId}")));
				continue;
			}

			if (!$cleaner->deleteFile($file))
			{
				$this->errorCollection->add($cleaner->getErrors());
			}
		}

		if ($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse(array(
			'message' => Loc::getMessage('DISK_VOLUME_GROUP_FILE_DELETE_OK'),
		));
	}


	/**
	 * @return void
	 */
	protected function processActionDeleteGroupFileUnnecessaryVersion()
	{
		$cleaner = new Volume\Cleaner($this->getUser()->getId());

		foreach ($this->fileIds as $fileId)
		{
			if ($fileId <= 0)
			{
				continue;
			}

			$file = \Bitrix\Disk\File::loadById($fileId);
			if (!$file instanceof \Bitrix\Disk\File)
			{
				$this->errorCollection->add(array(new Error("Could not find file {$fileId}")));
				continue;
			}

			$securityContext = $this->getSecurityContextByObject($file);
			if(!$file->canDelete($securityContext))
			{
				$this->errorCollection->add(array(new Error("Could not drop file {$fileId}")));
				continue;
			}

			if (!$cleaner->deleteFileUnnecessaryVersion($file))
			{
				if ($cleaner->hasErrors())
				{
					$this->errorCollection->add($cleaner->getErrors());
				}
			}
		}

		if ($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse(array(
			'message' => Loc::getMessage('DISK_VOLUME_GROUP_FILE_VERSION_DELETE_OK'),
		));
	}

	/**
	 * @return void
	 */
	protected function processActionDeleteUnnecessaryVersion()
	{
		/** @var Volume\IVolumeIndicator $indicator */
		$indicator = $this->component->getIndicator($this->indicatorId);

		$cleaner = new Volume\Cleaner($this->getUser()->getId());
		$cleaner->startTimer();

		$indicator->restoreFilter($this->filterId);

		if(!$cleaner->deleteUnnecessaryVersionByFilter($indicator))
		{
			if ($cleaner->hasErrors())
			{
				$this->errorCollection->add($cleaner->getErrors());
				$this->sendJsonErrorResponse();
			}
			else
			{
				if ($cleaner->hasTimeLimitReached())
				{
					$this->sendJsonSuccessResponse(array(
						'timeout' => 'Y',
						'dropped_file_count' => $cleaner->getDroppedFileCount(),
						'dropped_file_version_count' => $cleaner->getDroppedVersionCount(),
					));
				}
			}
		}

		$this->sendJsonSuccessResponse(array(
			'message' => Loc::getMessage('DISK_VOLUME_UNNECESSARY_VERSION_DELETE_OK'),
		));
	}


	/**
	 * Deletes folder and it's content.
	 * @return void
	 */
	protected function processActionDeleteFolder()
	{
		$securityContext = $this->getSecurityContextByObject($this->folder);
		if(!$this->folder->canDelete($securityContext))
		{
			$this->sendJsonAccessDeniedResponse(Loc::getMessage('DISK_VOLUME_ERROR_BAD_RIGHTS_FOLDER'));
		}

		$cleaner = new Volume\Cleaner($this->getUser()->getId());

		if (!$cleaner->isAllowClearFolder($this->folder))
		{
			$this->sendJsonAccessDeniedResponse(Loc::getMessage('DISK_VOLUME_ERROR_BAD_RIGHTS_FOLDER'));
		}

		$cleaner->startTimer();

		if(!$cleaner->deleteFolder($this->folder))
		{
			if ($cleaner->hasErrors())
			{
				$this->errorCollection->add($cleaner->getErrors());
				$this->sendJsonErrorResponse();
			}
			else
			{
				if ($cleaner->hasTimeLimitReached())
				{
					$this->sendJsonSuccessResponse(array(
						'timeout' => 'Y',
						'dropped_file_count' => $cleaner->getDroppedFileCount(),
						'dropped_file_version_count' => $cleaner->getDroppedVersionCount(),
					));
				}
			}
		}

		$this->sendJsonSuccessResponse(array(
			'message' => Loc::getMessage('DISK_VOLUME_FOLDER_DELETE_OK'),
		));
	}

	/**
	 * Deletes folder's content.
	 * @return void
	 */
	protected function processActionEmptyFolder()
	{
		$securityContext = $this->getSecurityContextByObject($this->folder);
		if(!$this->folder->canDelete($securityContext))
		{
			$this->sendJsonAccessDeniedResponse(Loc::getMessage('DISK_VOLUME_ERROR_BAD_RIGHTS_FOLDER'));
		}

		$cleaner = new Volume\Cleaner($this->getUser()->getId());

		if (!$cleaner->isAllowClearFolder($this->folder))
		{
			$this->sendJsonAccessDeniedResponse(Loc::getMessage('DISK_VOLUME_ERROR_BAD_RIGHTS_FOLDER'));
		}

		$cleaner->startTimer();

		if(!$cleaner->deleteFolder($this->folder, true))
		{
			if ($cleaner->hasErrors())
			{
				$this->errorCollection->add($cleaner->getErrors());
				$this->sendJsonErrorResponse();
			}
			else
			{
				if ($cleaner->hasTimeLimitReached())
				{
					$this->sendJsonSuccessResponse(array(
						'timeout' => 'Y',
						'dropped_file_count' => $cleaner->getDroppedFileCount(),
						'dropped_file_version_count' => $cleaner->getDroppedVersionCount(),
					));
				}
			}
		}

		$this->sendJsonSuccessResponse(array(
			'message' => Loc::getMessage('DISK_VOLUME_FOLDER_EMPTY_OK'),
		));
	}


	/**
	 * @return void
	 */
	protected function processActionCancelWorkers()
	{
		Volume\Cleaner::cancelWorkers((int)$this->getUser()->getId());

		if ($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		$responseParams = array(
			'stepper' => $this->component->getWorkerProgressBar(),
		);
		if ($this->queueLength > 0 && $this->queueStep > 0)
		{
			$responseParams['queueStep'] = $this->queueStep;
		}
		$this->sendJsonSuccessResponse($responseParams);
	}


	/**
	 * @return void
	 */
	protected function processActionSetupCleanerJob()
	{

		$agentParamsDefault = array(
			'ownerId' => $this->getUser()->getId(),
			'storageId' => $this->storageId,
		);

		$checkRequestParameters = array(
			\CDiskVolumeComponent::ACTION_DELETE_UNNECESSARY_VERSION => Volume\Task::DROP_UNNECESSARY_VERSION,
			\CDiskVolumeComponent::ACTION_DELETE_FOLDER => Volume\Task::DROP_FOLDER,
			\CDiskVolumeComponent::ACTION_EMPTY_FOLDER => Volume\Task::EMPTY_FOLDER,
			\CDiskVolumeComponent::ACTION_EMPTY_TRASHCAN => Volume\Task::DROP_TRASHCAN,
		);

		foreach ($checkRequestParameters as $parameter => $command)
		{
			if ($this->request->get($parameter) === 'Y')
			{
				$filterIds = array();
				$requestParameterForFilterIds = 'filterIdsStorage';

				if ($parameter === \CDiskVolumeComponent::ACTION_EMPTY_TRASHCAN)
				{
					$requestParameterForFilterIds = 'filterIdsTrashCan';
				}
				if (!is_null($this->request->get($requestParameterForFilterIds)))
				{
					$filterIds = array_map('intVal', (array)$this->request->get($requestParameterForFilterIds));
				}
				elseif ($this->filterId > 0)
				{
					$filterIds[] = (int)$this->filterId;
				}

				foreach ($filterIds as $fid)
				{
					if (intval($fid) <= 0) continue;
					$agentParams = $agentParamsDefault;
					$agentParams[$command] = true;
					$agentParams['filterId'] = $fid;
					$agentParams['manual'] = true;

					if (!Volume\Cleaner::addWorker($agentParams))
					{
						$this->errorCollection->add(array(new Error('Agent add fail')));
					}
				}
			}
		}

		if ($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse(array(
			'stepper' => $this->component->getWorkerProgressBar(),
		));
	}


	/**
	 * @return void
	 */
	protected function processActionSendNotification()
	{
		Main\Loader::includeModule('im');
		Main\Loader::includeModule('socialnetwork');

		/** @var Volume\IVolumeIndicator $indicator */
		$indicator = $this->component->getIndicator($this->indicatorId);

		$filterIds = array();
		if (!is_null($this->request->get('filterIdsStorage')))
		{
			$filterIds = array_map('intVal', (array)$this->request->get('filterIdsStorage'));
		}
		elseif ($this->filterId > 0)
		{
			$filterIds[] = $this->filterId;
		}

		$userIdFrom = $this->getUser()->getId();

		$filterList = \Bitrix\Disk\Internals\VolumeTable::getList(array(
			'filter' => array(
				'=ID' => $filterIds,
				'=OWNER_ID' =>  $this->getUser()->getId(),
			)
		));
		foreach ($filterList as $filter)
		{
			$fragment = $indicator::getFragment($filter);

			if (!($fragment->getStorage() instanceof \Bitrix\Disk\Storage))
			{
				continue;
			}

			$userIdTo = -1;
			$storageId = $fragment->getStorageId();
			$messageType = '';
			$messageTags = array('DISK', 'VOLUME', $userIdFrom);
			$fileSize = 0;

			if ($indicator instanceof Volume\IVolumeIndicatorStorage)
			{
				$messageType = '';
				if ($indicator instanceof Volume\Storage\Common || $fragment->getStorage()->getEntityType() === ProxyType\Common::className())
				{
					continue;
				}
				if ($indicator instanceof Volume\Storage\User || $fragment->getStorage()->getEntityType() === ProxyType\User::className())
				{
					$userIdTo = (int)$fragment->getStorage()->getEntityId();
					$messageType = 'STORAGE_USER';
				}
				elseif ($indicator instanceof Volume\Storage\Group || $fragment->getStorage()->getEntityType() === ProxyType\Group::className())
				{
					$groupIdTo = (int)$fragment->getStorage()->getEntityId();
					$groupTo = \Bitrix\Socialnetwork\WorkgroupTable::getById($groupIdTo)->fetch();
					$userIdTo = (int)$groupTo['OWNER_ID'];
					$messageType = 'STORAGE_GROUP';
				}

				$fileSize = $fragment->getFileSize();

				// add trashcan volume
				$indicatorTrashcan = $this->component->getIndicator(Volume\Storage\TrashCan::className());
				$indicatorTrashcan->addFilter('=STORAGE_ID', $fragment->getStorage()->getId());
				$indicatorTrashcan->loadTotals();
				if ($indicatorTrashcan->getTotalSize() > 0)
				{
					$fileSize += $indicatorTrashcan->getTotalSize();
				}

				if ($this->indicatorId != Volume\Storage\Storage::getIndicatorId())
				{
					$messageType = mb_strtoupper($this->indicatorId);
				}
			}
			elseif ($indicator instanceof Volume\Folder)
			{
				$messageType = 'FOLDER';

				if ($fragment->getStorage()->getEntityType() === ProxyType\Common::className())
				{
					continue;
				}
				if ($fragment->getStorage()->getEntityType() === ProxyType\User::className())
				{
					$userIdTo = (int)$fragment->getStorage()->getEntityId();
				}
				elseif ($fragment->getStorage()->getEntityType() === ProxyType\Group::className())
				{
					$groupIdTo = (int)$fragment->getStorage()->getEntityId();
					$groupTo = \Bitrix\Socialnetwork\WorkgroupTable::getById($groupIdTo)->fetch();
					$userIdTo = (int)$groupTo['OWNER_ID'];
				}

				$fileSize = $fragment->getFileSize();
			}
			elseif ($indicator instanceof Volume\Module\Im)
			{
				if (!in_array($fragment->getStorage()->getEntityType(), \Bitrix\Disk\Volume\Module\Im::getEntityType()))
				{
					continue;
				}
				$specific = $fragment->getSpecific();
				if ($specific['chat'])
				{
					$userIdTo = (int)$specific['chat']['owner'];
				}

				$fileSize = $fragment->getFileSize();
			}

			if ($userIdTo > 0 && $fileSize > 0)
			{
				$urlClear = $this->getActionUrl(array(
					'action' => \CDiskVolumeComponent::ACTION_STORAGE,
					'storageId' => $storageId,
					'reload' => 'Y',
					'expert' => 'off',
				));
				$urlTrashcan = $fragment->getStorage()->getProxyType()->getBaseUrlTashcanList();

				$messageTags[] = $userIdTo;
				$messageTags[] = $storageId;

				$message = Loc::getMessage(
					"DISK_VOLUME_NOTIFY_CHAT",
					array(
						'#TITLE#' => $indicator::getTitle($fragment),
						'#URL_CLEAR#' => $urlClear,
						'#URL_TRASHCAN#' => $urlTrashcan,
						'#FILE_SIZE#' => \CFile::FormatSize($fileSize),
					)
				);
				$message .= ' '. Loc::getMessage(
					'DISK_VOLUME_NOTIFY_RECOMMENDATION',
					array(
						'#URL_CLEAR#' => $urlClear,
						'#URL_TRASHCAN#' => $urlTrashcan,
					));

				\Bitrix\Disk\Driver::getInstance()->sendNotify(
					$userIdTo,
					array(
						'FROM_USER_ID' => $userIdFrom,
						'NOTIFY_EVENT' => 'volume',
						'NOTIFY_TAG' => implode('|', $messageTags),
						'NOTIFY_MESSAGE' => $message,
						'NOTIFY_TYPE' => 'IM_NOTIFY_FROM',
					)
				);
			}
		}

		if ($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse(array(
			'message' => Loc::getMessage('DISK_VOLUME_NOTIFICATION_SEND_OK'),
		));
	}

	/**
	 * @return void
	 */
	protected function processActionPurify()
	{
		$filter = array();

		if (!$this->component->isAdminMode())
		{
			$filter['STORAGE_ID'] = $this->component->getCurrentUserStorageId();
		}
		elseif ($this->storageId > 0)
		{
			$filter['STORAGE_ID'] = $this->storageId;
		}

		if ($this->queueLength > 0 && $this->queueStep > 0)
		{
			if (!empty($this->indicatorId))
			{
				$retStatus = $this->purify($this->indicatorId, $filter);
			}
			else
			{
				$retStatus = $this->purify('*', $filter);
			}

			if ($retStatus == self::STATUS_ERROR)
			{
				$this->sendJsonErrorResponse();
			}
			else
			{
				$this->sendJsonSuccessResponse(array(
					'message' => Loc::getMessage('DISK_VOLUME_DATA_DELETED_QUEUE', array(
						'#QUEUE_STEP#' => $this->queueStep,
						'#QUEUE_LENGTH#' => $this->queueLength,
					)),
					'queueStep' => $this->queueStep,
				));
			}
		}
		elseif ($this->indicatorId !== '')
		{
			$retStatus = $this->purify($this->indicatorId, $filter);

			if ($retStatus == self::STATUS_ERROR)
			{
				$this->sendJsonErrorResponse();
			}
			else
			{
				$this->sendJsonSuccessResponse(array(
					'message' => Loc::getMessage('DISK_VOLUME_DATA_DELETED'),
					'url' => $this->getActionUrl(array('indicatorId' => $this->indicatorId, 'action' => 'report')),
				));
			}
		}
		else
		{
			$retStatus = $this->purify('*', $filter);

			if ($retStatus === self::STATUS_ERROR)
			{
				$this->sendJsonErrorResponse();
			}
			else
			{
				$this->sendJsonSuccessResponse(array(
					'message' => Loc::getMessage('DISK_VOLUME_DATA_DELETED'),
					'url' => $this->getActionUrl(array('expert' => 'off')),
				));
			}
		}
	}


	/**
	 * @return void
	 */
	protected function processActionMeasure()
	{
		$filter = array();

		if (!$this->component->isAdminMode())
		{
			$filter['STORAGE_ID'] = $this->component->getCurrentUserStorageId();
		}
		elseif ($this->storageId > 0)
		{
			$filter['STORAGE_ID'] = $this->storageId;
		}

		$responseParams = array();

		if ($this->queueLength > 0 && $this->queueStep > 0)
		{
			$retStatus = $this->measure($this->indicatorId, $filter);

			if ($retStatus === self::STATUS_ERROR)
			{
				$this->sendJsonErrorResponse();
			}
			elseif ($retStatus === self::STATUS_TIMEOUT)
			{
				$responseParams['timeout'] = 'Y';
				$responseParams['queueStep'] = $this->queueStep;

				if (!empty($this->subTask))
				{
					$responseParams['subTask'] = $this->subTask;

					$this->subStep ++;
					$responseParams['subStep'] = $this->subStep;
				}
			}
			else
			{
				$responseParams['queueStep'] = $this->queueStep;
				if ($this->queueStep >= $this->queueLength)
				{
					$this->component->clearQueueStep();
					$responseParams['url'] = $this->getActionUrl(array());
				}
				else
				{
					$responseParams['message'] = Loc::getMessage('DISK_VOLUME_PERFORMING_QUEUE', array(
							'#QUEUE_STEP#' => $this->queueStep,
							'#QUEUE_LENGTH#' => $this->queueLength,
					));
				}
			}
		}
		else
		{
			$retStatus = $this->measure($this->indicatorId, $filter);

			if ($retStatus === self::STATUS_ERROR)
			{
				$this->sendJsonErrorResponse();
			}
			elseif ($retStatus === self::STATUS_TIMEOUT)
			{
				$responseParams['timeout'] = 'Y';
			}
			else
			{
				$this->component->clearQueueStep();
				$responseParams['url'] = $this->getActionUrl(array('indicatorId' => $this->indicatorId, 'action' => 'report'));
			}
		}

		$this->sendJsonSuccessResponse($responseParams);
	}


	/**
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function processActionMeasureStorage()
	{
		$timer = new Volume\Timer();
		$timer->startTimer();

		$subTaskDone = true;

		if (empty($this->subTask))
		{
			$this->subTask = \CDiskVolumeComponent::ACTION_PURIFY;
			$this->subStep = 1;
		}

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

		if ($this->filterId < 0 && $this->storageId > 0)
		{
			// detect previous measured data to prevent dublication
			$storageList = \Bitrix\Disk\Internals\VolumeTable::getList(array(
				'filter' => array(
					'=OWNER_ID' =>  $this->getUser()->getId(),
					'=STORAGE_ID' => $this->storageId,
					'=INDICATOR_TYPE' => $storageIndicatorType,
				),
				'select' => ['ID']
			));
			if ($filter = $storageList->fetch())
			{
				$this->filterId = $filter['ID'];
			}
		}

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

		do
		{
			if ($this->subTask === \CDiskVolumeComponent::ACTION_PURIFY)
			{
				$this->purify(
					$folderIndicatorId,
					array(
						'=STORAGE_ID' => $this->storageId
					)
				);

				$this->subTask = 'storage:purify';
				$this->subStep ++;
				if (!$timer->checkTimeEnd())
				{
					$subTaskDone = false;
					break;
				}
			}

			if (mb_strpos($this->subTask, 'storage:') === 0)
			{
				$storageSubTask = str_replace('storage:', '', $this->subTask);

				if ($storageSubTask == 'purify')
				{
					/*
					$this->purify(
						$storageIndicatorId,
						array(
							'=STORAGE_ID' => $this->storageId
						),
						$this->filterId
					);
					*/

					$storageSubTask = null;
				}

				$this->component->setQueueStepParam('subTask', $storageSubTask);

				$this->measure(
					$storageIndicatorId,
					array(
						'=STORAGE_ID' => $this->storageId,
					),
					$this->filterId
				);

				if ($this->component->getQueueStepParam('subTask') !== null)
				{
					$this->subTask = 'storage:'. $this->component->getQueueStepParam('subTask');
					$subTaskDone = false;
					break;
				}

				// next
				$this->subTask = 'folder';
				$this->subStep ++;
				if (!$timer->checkTimeEnd())
				{
					$subTaskDone = false;
					break;
				}
			}

			if ($this->subTask === 'folder')
			{
				$this->reload(
					$folderIndicatorId,
					array(
						'=STORAGE_ID' => $this->storageId,
						'=FOLDER_ID' => $this->folderId,
					)
				);

				$this->subTask = 'folderRoot';
				$this->subStep ++;
				if (!$timer->checkTimeEnd())
				{
					$subTaskDone = false;
					break;
				}
			}

			if ($this->subTask === 'folderRoot')
			{
				$this->purify(
					Bitrix\Disk\Volume\Folder::getIndicatorId(),
					array(
						'=STORAGE_ID' => $this->storageId,
						'=PARENT_ID' => null,
						'=FOLDER_ID' => $this->folderId,
					)
				);
				$this->measure(
					Bitrix\Disk\Volume\Folder::getIndicatorId(),
					array(
						'=STORAGE_ID' => $this->storageId,
						'=FOLDER_ID' => $this->folderId,
					)
				);

				$this->subTask = 'fileType';
				$this->subStep ++;
				if (!$timer->checkTimeEnd())
				{
					$subTaskDone = false;
					break;
				}
			}

			if ($this->subTask === 'fileType')
			{
				$this->reload(
					Volume\FileType::getIndicatorId(),
					array(
						'=STORAGE_ID' => $this->storageId,
					)
				);

				$this->subTask = 'trashcan:purify';
				$this->subStep ++;
				if (!$timer->checkTimeEnd())
				{
					$subTaskDone = false;
					break;
				}
			}


			if (mb_strpos($this->subTask, 'trashcan:') === 0)
			{
				$storageSubTask = str_replace('trashcan:', '', $this->subTask);

				if ($storageIndicatorType != Volume\Storage\TrashCan::className())
				{
					if ($storageSubTask == 'purify')
					{
						$this->purify(
							Volume\Storage\TrashCan::getIndicatorId(),
							array(
								'=STORAGE_ID' => $this->storageId
							)
						);

						$storageSubTask = null;
					}

					$this->component->setQueueStepParam('subTask', $storageSubTask);

					$this->measure(
						Volume\Storage\TrashCan::getIndicatorId(),
						array(
							'=STORAGE_ID' => $this->storageId,
						)
					);

					if ($this->component->getQueueStepParam('subTask') !== null)
					{
						$this->subTask = 'trashcan:'. $this->component->getQueueStepParam('subTask');
						$subTaskDone = false;
						break;
					}
				}

				// next
				$this->subTask = 'fileStorage';
				$this->subStep ++;
				if (!$timer->checkTimeEnd())
				{
					$subTaskDone = false;
					break;
				}
			}

			if ($this->subTask === 'fileStorage')
			{
				$this->reload(
					$fileIndicatorId,
					array(
						'=STORAGE_ID' => $this->storageId,
						'=PARENT_ID' => $this->folderId
					)
				);

				$this->subTask = 'fileFolder';
				$this->subStep ++;
				if (!$timer->checkTimeEnd())
				{
					$subTaskDone = false;
					break;
				}
			}

			if ($this->subTask === 'fileFolder')
			{
				$this->subStep ++;
				$this->reload(
					$fileIndicatorId,
					array(
						'=STORAGE_ID' => $this->storageId,
						'=FOLDER_ID' => $this->folderId
					)
				);
			}

			$this->subTask = null;

			if ($this->filterId > 0)
			{
				\Bitrix\Disk\Internals\VolumeTable::update($this->filterId, array('COLLECTED' => 1));
			}
		}
		while(false);

		$responseParams = array(
			'subStep' => $this->subStep,
		);
		if ($subTaskDone)
		{
			// queue
			if ($this->queueLength > 0 && $this->queueStep > 0)
			{
				$this->component->setQueueStepParam('subTask', null);

				if ($this->queueStep >= $this->queueLength)
				{
					$this->component->clearQueueStep();
					$responseParams['queueStep'] = $this->queueStep;
					$responseParams['url'] = $this->getActionUrl(array());
				}
				else
				{
					$responseParams['message'] = Loc::getMessage('DISK_VOLUME_PERFORMING_QUEUE', array(
						'#QUEUE_STEP#' => $this->queueStep,
						'#QUEUE_LENGTH#' => $this->queueLength,
					));
					$responseParams['queueStep'] = $this->queueStep;
					unset($responseParams['url']);
				}
			}
			else
			{
				$this->component->clearQueueStep();

				if ($this->component->isAdminMode() || $this->component->isExpertMode())
				{
					if ($storageIndicatorType == Volume\Storage\TrashCan::className())
					{
						$responseParams['url'] = $this->getActionUrl(array(
							'action' => \CDiskVolumeComponent::ACTION_TRASH_FILES,
							'storageId' => $this->storageId
						));
					}
					else
					{
						$responseParams['url'] = $this->getActionUrl(array(
							'action' => \CDiskVolumeComponent::ACTION_STORAGE,
							'storageId' => $this->storageId
						));
					}
				}
				else
				{
					$responseParams['url'] = $this->getActionUrl();
				}
			}
		}
		else
		{
			$responseParams['timeout'] = 'Y';
			$responseParams['subTask'] = $this->subTask;
			if ($this->queueLength > 0 && $this->queueStep > 0)
			{
				$this->component->setQueueStepParam('subTask', $this->subTask);
				$responseParams['queueStep'] = $this->queueStep;
			}
		}

		$this->sendJsonSuccessResponse($responseParams);
	}

	/**
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function processActionMeasureFolder()
	{
		$timer = new Volume\Timer();
		$timer->startTimer();

		$subTaskDone = true;

		if (empty($this->subTask))
		{
			$this->subTask = 'fileFolder';
			$this->subStep = 1;
		}

		do
		{
			if ($this->subTask === 'fileFolder')
			{
				$this->subStep ++;
				$this->reload(Volume\File::getIndicatorId(), array(
					'STORAGE_ID' => $this->storageId,
					'FOLDER_ID' => $this->folderId
				));
			}

			$this->subTask = '';

			if ($this->filterId > 0)
			{
				\Bitrix\Disk\Internals\VolumeTable::update($this->filterId, array('COLLECTED' => 1));
			}
		}
		while(false);

		$responseParams = array(
			'subStep' => $this->subStep,
		);
		if ($subTaskDone)
		{
			$urlParams = array(
				'action' => \CDiskVolumeComponent::ACTION_FOLDER_FILES,
				'storageId' => $this->storageId,
				'folderId' => $this->folderId,
			);
			if ($this->folderId == $this->storage->getRootObjectId())
			{
				$urlParams['filterFolder'] = 'Y';
			}
			if ($this->queueLength > 0 && $this->queueStep > 0)
			{
				$this->component->setQueueStepParam('subTask', null);
				if ($this->queueStep >= $this->queueLength)
				{
					$this->component->clearQueueStep();
					$responseParams['queueStep'] = $this->queueStep;
					$responseParams['url'] = $this->getActionUrl($urlParams);
				}
				else
				{
					$responseParams['message'] = Loc::getMessage('DISK_VOLUME_PERFORMING_QUEUE', array(
						'#QUEUE_STEP#' => $this->queueStep,
						'#QUEUE_LENGTH#' => $this->queueLength,
					));
					$responseParams['queueStep'] = $this->queueStep;
					unset($responseParams['url']);
				}
			}
			else
			{
				$this->component->clearQueueStep();
				$responseParams['url'] = $this->getActionUrl($urlParams);
			}
		}
		else
		{
			$responseParams['timeout'] = 'Y';
			$responseParams['subTask'] = $this->subTask;
			if ($this->queueLength > 0 && $this->queueStep > 0)
			{
				$this->component->setQueueStepParam('subTask', $this->subTask);
				$responseParams['queueStep'] = $this->queueStep;
			}
		}

		$this->sendJsonSuccessResponse($responseParams);
	}


	/**
	 * @param string $indicatorType - Indicator class name
	 * @param array $filter
	 * @param integer $filterId Saved filter row id.
	 * @return String Status code STATUS_SUCCESS | STATUS_ERROR.
	 */
	private function purify($indicatorType, $filter = array(), $filterId = -1)
	{
		$retStatus = $this->component->purify($indicatorType, $filter, $filterId);

		if ($retStatus === self::STATUS_ERROR && $this->component->hasErrors())
		{
			$this->errorCollection->add($this->component->getErrors());
		}

		return $retStatus;
	}

	/**
	 * Preforms measurement operation.
	 * @param string $indicatorType - Indicator class name
	 * @param array $filter
	 * @param integer $filterId Saved filter row id.
	 * @return String Status code STATUS_SUCCESS | STATUS_ERROR | STATUS_TIMEOUT.
	 */
	private function measure($indicatorType, $filter = array(), $filterId = -1)
	{
		$retStatus = $this->component->measure($indicatorType, $filter, $filterId);

		if ($retStatus === self::STATUS_ERROR && $this->component->hasErrors())
		{
			$this->errorCollection->add($this->component->getErrors());
		}

		$this->subTask = $this->component->getQueueStepParam('subTask');

		return $retStatus;
	}

	/**
	 * Preforms data preparation.
	 * @param string $indicatorType - Indicator class name
	 * @param array $filter
	 * @return String Status code STATUS_SUCCESS | STATUS_ERROR | STATUS_TIMEOUT.
	 */
	private function prepareData($indicatorType, $filter = array())
	{
		$retStatus = $this->component->prepareData($indicatorType, $filter);

		if ($retStatus === self::STATUS_ERROR && $this->component->hasErrors())
		{
			$this->errorCollection->add($this->component->getErrors());
		}

		return $retStatus;
	}

	/**
	 * Just continuously start methods clear and measure.
	 * @param string $indicatorType Indicator class name.
	 * @param array $filter Filter parameter for indicator.
	 * @param integer $filterId Saved filter row id.
	 * @return String Status code STATUS_SUCCESS | STATUS_ERROR | STATUS_TIMEOUT.
	 */
	private function reload($indicatorType, $filter = array(), $filterId = -1)
	{
		$retStatus = $this->component->reload($indicatorType, $filter, $filterId);

		if ($retStatus === self::STATUS_ERROR && $this->component->hasErrors())
		{
			$this->errorCollection->add($this->component->getErrors());
		}

		return $retStatus;
	}


	/**
	 * Returns url for ui.
	 * @return string
	 * @param array $params Url params with value.
	 */
	public function getActionUrl($params = array())
	{
		if (!empty($params['action']))
		{
			$action = $params['action'];
		}
		else
		{
			$action = \CDiskVolumeComponent::ACTION_DEFAULT;
		}

		if ($this->sefMode)
		{
			$path = \CComponentEngine::makePathFromTemplate(
				$this->sefPath['PATH_TO_DISK_VOLUME_'.mb_strtoupper($action)],
				$params
			);
			$path = str_replace('//', '/', $path);

			unset($params['action']);
			unset($params['storageId']);
			unset($params['folderId']);
		}
		else
		{
			$path = '';
		}

		return $this->relativePath. $path. (count($params) > 0 ? '?'. http_build_query($params) : '');
	}


	/**
	 * Returns disk security context.
	 * @param \Bitrix\Disk\BaseObject $object File or folder.
	 * @return \Bitrix\Disk\Security\SecurityContext
	 */
	private function getSecurityContextByObject($object)
	{
		static $securityContextCache = array();

		if (!($securityContextCache[$object->getStorageId()] instanceof \Bitrix\Disk\Security\SecurityContext))
		{
			if (\Bitrix\Disk\User::isCurrentUserAdmin())
			{
				$securityContextCache[$object->getStorageId()] = new \Bitrix\Disk\Security\FakeSecurityContext($this->getUser());
			}
			else
			{
				$securityContextCache[$object->getStorageId()] = $object->getStorage()->getCurrentUserSecurityContext();
			}
		}

		return $securityContextCache[$object->getStorageId()];
	}
}


$controller = new DiskVolumeController();
$controller
	->setActionName(Main\Application::getInstance()->getContext()->getRequest()->get('action'))
	->exec()
;

