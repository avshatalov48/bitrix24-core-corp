<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;
use Bitrix\Crm\Volume;


class CrmVolumeComponent extends \CBitrixComponent
{
	/* ajax component actions */
	const ACTION_MEASURE = 'MEASURE';
	const ACTION_MEASURE_FILE = 'MEASURE_FILE';
	const ACTION_MEASURE_ACTIVITY = 'MEASURE_ACTIVITY';
	const ACTION_MEASURE_EVENT = 'MEASURE_EVENT';
	const ACTION_PURIFY = 'PURIFY';

	const ACTION_DEFAULT = 'VIEW';
	const ACTION_SETUP_TASK = 'SETUP_TASK';
	const ACTION_CANCEL_TASKS = 'CANCEL_TASKS';

	const TASK_CLEAR = 'CLEAR';
	const TASK_CLEAR_FILE = 'CLEAR_FILE';
	const TASK_CLEAR_ACTIVITY = 'CLEAR_ACTIVITY';
	const TASK_CLEAR_EVENT = 'CLEAR_EVENT';

	const STATUS_SUCCESS = 'success';
	const STATUS_DENIED = 'denied';
	const STATUS_ERROR = 'error';
	const STATUS_TIMEOUT = 'timeout';

	/** @const string Session storage name. */
	const QUEUE_STEP = 'crm_queue_step';
	const FILTER_ID = 'crm_volume_filter';
	const GRID_ID = 'crm_volume_list';

	/** @var string */
	private $indicatorId = '';

	/** @var int */
	private $queueStep = -1;

	/** @var int */
	private $queueLength = -1;

	/** Main\ErrorCollection */
	protected $errorCollection;

	/**
	 * CrmVolumeComponent constructor.
	 *
	 * @param \CBitrixComponent|null $component
	 */
	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->errorCollection = new Main\ErrorCollection();
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
		elseif (!is_null($this->request->get('relUrl')) && $this->request->get('relUrl') <> '')
		{
			$this->arParams['RELATIVE_PATH'] = $this->request->get('relUrl');
		}
		else
		{
			$context = Main\Application::getInstance()->getContext();
			$this->arParams['RELATIVE_PATH'] = $context->getRequest()->getRequestedPage();
		}

		if (empty($this->arParams['AJAX_PATH']))
		{
			$this->arParams['AJAX_PATH'] = $this->getPath().'/ajax.php';
		}

		$this->arParams['IS_AJAX_REQUEST'] = $this->isAjaxRequest();


		$this->arResult['RELOAD'] = false;
		if (!is_null($this->request->get('reload')))
		{
			$this->arResult['RELOAD'] = ($this->request->get('reload') === 'Y');
		}

		if (!is_null($this->request->get('indicatorId')))
		{
			$this->indicatorId = $this->request->get('indicatorId');
		}

		if (!is_null($this->request->get('queueStep')))
		{
			$this->queueStep = (int)$this->request->get('queueStep');
		}
		if (!is_null($this->request->get('queueLength')))
		{
			$this->queueLength = (int)$this->request->get('queueLength');
		}

		return $this;
	}


	/**
	 * @return void
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\LoaderException
	 */
	public function executeComponent()
	{
		if (!Main\Loader::includeModule('crm'))
		{
			if ($this->isAjaxRequest())
			{
				$this->sendJsonResponse(new Main\Error(Loc::getMessage('CRM_VOLUME_MODULE_NOT_INSTALLED'), self::STATUS_DENIED));
			}
			else
			{
				\ShowError(Loc::getMessage('CRM_VOLUME_MODULE_NOT_INSTALLED'));
			}

			return;
		}

		$crmPerms = new \CCrmPerms($this->getUser()->getID());
		if (!$crmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
		{
			if ($this->isAjaxRequest())
			{
				$this->sendJsonResponse(new Main\Error(Loc::getMessage('CRM_VOLUME_ERROR_ACCESS_DENIED'), self::STATUS_DENIED));
			}
			else
			{
				\ShowError(Loc::getMessage('CRM_VOLUME_ERROR_ACCESS_DENIED'));
			}

			return;
		}

		if (\Bitrix\Crm\DbHelper::isPgSqlDb())
		{
			Crm\Service\Container::getInstance()->getLocalization()->loadMessages();
			\ShowError(Loc::getMessage('CRM_LOCALIZATION_DB_TYPE_NOT_SUPPORTED'));

			return;
		}

		$action = $this->getAction();

		$checkCsrfToken = false;
		$checkAjaxRequest = false;
		switch ($action)
		{
			case self::ACTION_PURIFY:
			case self::ACTION_MEASURE:
			case self::ACTION_MEASURE_FILE:
			case self::ACTION_MEASURE_EVENT:
			case self::ACTION_MEASURE_ACTIVITY:
			case self::ACTION_SETUP_TASK:
			case self::ACTION_CANCEL_TASKS:
				$checkCsrfToken = true;
				$checkAjaxRequest = true;
				break;
		}

		if (!$this->checkAccessPermissions($checkCsrfToken))
		{
			if ($this->isAjaxRequest())
			{
				$this->sendJsonResponse(new Main\Error(Loc::getMessage('CRM_VOLUME_ERROR_ACCESS_DENIED'), self::STATUS_DENIED));
			}
			else
			{
				\ShowError(Loc::getMessage('CRM_VOLUME_ERROR_ACCESS_DENIED'));
			}

			return;
		}

		if ($checkAjaxRequest && !$this->isAjaxRequest())
		{
			\ShowError(Loc::getMessage('CRM_VOLUME_ERROR_WRONG_ACTION'));

			return;
		}

		$this->prepareParams();

		switch ($action)
		{
			case self::ACTION_MEASURE:
			case self::ACTION_MEASURE_FILE:
			case self::ACTION_MEASURE_EVENT:
			case self::ACTION_MEASURE_ACTIVITY:
			case self::ACTION_SETUP_TASK:
				if (is_null($this->indicatorId) || $this->indicatorId == '')
				{
					$this->sendJsonResponse(new Main\Error('Undefined parameter: indicatorId', self::STATUS_ERROR));
					return;
				}

				try
				{
					$this->getIndicator($this->indicatorId);
				}
				catch(Main\ObjectException $ex)
				{
					$this->sendJsonResponse(new Main\Error('Undefined parameter: indicatorId', self::STATUS_ERROR));
					return;
				}
				break;
		}

		switch ($action)
		{
			case self::ACTION_PURIFY:
				$result = $this->executePurifyAction();
				$result['url'] = $this->getActionUrl();

				if ($this->queueLength > 0 && $this->queueStep > 0)
				{
					$result['queueStep'] = $this->queueStep;
				}

				Volume\Cleaner::clearProgressInfo($this->getUser()->getId());
				$result['stepper'] = '';

				$this->sendJsonResponse($result);
				break;

			case self::ACTION_CANCEL_TASKS:
				$result = $this->executeCancelAction();
				$result['url'] = $this->getActionUrl();
				if ($this->queueLength > 0 && $this->queueStep > 0)
				{
					$result['queueStep'] = $this->queueStep;
				}

				Volume\Cleaner::clearProgressInfo($this->getUser()->getId());
				$result['stepper'] = '';

				$this->sendJsonResponse($result);
				break;


			case self::ACTION_MEASURE:
				$result = $this->executeMeasureAction('measureEntity');
				$result['url'] = $this->getActionUrl();
				if ($this->queueLength > 0 && $this->queueStep > 0)
				{
					$result['queueStep'] = $this->queueStep;
				}
				$this->sendJsonResponse($result);
				break;

			case self::ACTION_MEASURE_FILE:
				$result = $this->executeMeasureAction('measureFiles');
				$result['url'] = $this->getActionUrl();
				if ($this->queueLength > 0 && $this->queueStep > 0)
				{
					$result['queueStep'] = $this->queueStep;
				}
				$this->sendJsonResponse($result);
				break;

			case self::ACTION_MEASURE_EVENT:
				$result = $this->executeMeasureAction('measureEvent');
				$result['url'] = $this->getActionUrl();
				if ($this->queueLength > 0 && $this->queueStep > 0)
				{
					$result['queueStep'] = $this->queueStep;
				}
				$this->sendJsonResponse($result);
				break;

			case self::ACTION_MEASURE_ACTIVITY:
				$result = $this->executeMeasureAction('measureActivity');
				$result['url'] = $this->getActionUrl();
				if ($this->queueLength > 0 && $this->queueStep > 0)
				{
					$result['queueStep'] = $this->queueStep;
				}
				$this->sendJsonResponse($result);
				break;


			case self::ACTION_SETUP_TASK:

				$result = $this->executeSetupTaskAction();

				$this->sendJsonResponse($result);
				break;


			case self::ACTION_DEFAULT:
			default:
			{
				if ($this->isAjaxRequest())
				{
					$this->getApplication()->restartBuffer();
				}

				$this->executeViewAction();

				$this->arResult['ENABLE_CONTROL_PANEL'] = true;
				if (isset($this->arParams['ENABLE_CONTROL_PANEL']))
				{
					$this->arResult['ENABLE_CONTROL_PANEL'] = $this->arParams['ENABLE_CONTROL_PANEL'];
				}

				$this->includeComponentTemplate('');

				if ($this->isAjaxRequest())
				{
					\CMain::FinalActions();
					die();
				}
				break;
			}
		}

	}




	/**
	 * @return array|bool|\CUser
	 */
	private function getUser()
	{
		/** @global \CUser $USER */
		global $USER;
		return $USER;
	}

	/**
	 * @return CMain
	 */
	protected function getApplication()
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;
		return $APPLICATION;
	}

	/**
	 * @return string
	 */
	private function getAction()
	{
		return isset($this->arParams['ACTION']) ? $this->arParams['ACTION'] : self::ACTION_DEFAULT;
	}

	/**
	 * Returns whether this is an AJAX (XMLHttpRequest) request.
	 * @return boolean
	 */
	private function isAjaxRequest()
	{
		return $this->request->isAjaxRequest() || !empty($this->request->get('AJAX_CALL'));
	}


		/**
	 * @param array|object|Main\Error $response
	 *
	 * @throws Main\ArgumentException
	 * @return void
	 */
	private function sendJsonResponse($response)
	{
		$this->getApplication()->restartBuffer();

		if ($response instanceof Main\Error)
		{
			$this->errorCollection->add(array($response));
			$response = array();
		}

		$response['result'] = true;

		if ($this->hasWorkerInProcess())
		{
			$response['stepper'] = $this->getProgressBar();
		}

		if (count($this->errorCollection) > 0)
		{
			$response['status'] = self::STATUS_ERROR;
			$errors = array();
			foreach ($this->errorCollection->toArray() as $error)
			{
				/** @var Main\Error $error */
				if ($error->getCode() === Volume\Base::ERROR_PERMISSION_DENIED)
				{
					$message = Loc::getMessage('CRM_PERMISSION_DENIED');
				}
				else
				{
					$message = $error->getMessage();
				}
				$errors[] = array(
					'message' => $message,
					'code' => $error->getCode(),
				);
				if ($error->getCode() === self::STATUS_DENIED)
				{
					$response['status'] = self::STATUS_DENIED;
				}
			}
			$response['result'] = false;
			$response['errors'] = $errors;
		}
		elseif (!isset($response['status']))
		{
			$response['status'] = self::STATUS_SUCCESS;
		}

		header('Content-Type:application/x-javascript; charset=UTF-8');
		echo Main\Web\Json::encode($response);

		\CMain::finalActions();
		die;
	}


	/**
	 * @return boolean
	 */
	private function checkAccessPermissions($checkCsrfToken = false)
	{
		if (!(\CCrmPerms::IsAccessEnabled()))
		{
			$this->errorCollection->add(array(new Main\Error(Loc::getMessage('CRM_VOLUME_ERROR_ACCESS_DENIED'))));
		}

		if($checkCsrfToken && $this->request->isPost())
		{
			if(check_bitrix_sessid() !== true)
			{
				$this->errorCollection->add(array(new Main\Error('CSRF token is not valid')));
			}
		}

		return (bool)(count($this->errorCollection) === 0);
	}


	/**
	 * @param string $indicatorId - Indicator class name
	 *
	 * @return Volume\IVolumeIndicator
	 * @throws Main\ObjectException
	 */
	public function getIndicator($indicatorId)
	{
		return Volume\Base::getIndicator($indicatorId);
	}



	/**
	 * @return array
	 */
	protected function executeSetupTaskAction()
	{
		$result = array();

		$taskParams = array(
			'ownerId' => $this->getUser()->getId(),
		);

		$isFilterApplied = $this->isFilterApplied();
		if ($isFilterApplied)
		{
			$filter = $this->getFilter();
		}

		try
		{
			/** @var Volume\IVolumeIndicator $indicator */
			$indicator = $this->getIndicator($this->indicatorId);
			if ($indicator instanceof Volume\IVolumeIndicator)
			{
				$indicator->setOwner($this->getUser()->getId());
				if ($isFilterApplied)
				{
					$indicator->setFilter($filter);
				}
			}

			if ($this->request->get(self::TASK_CLEAR) === 'Y')
			{
				$taskParams[Volume\Cleaner::DROP_ENTITY] = true;
				if (!Volume\Cleaner::addWorker($taskParams, $indicator))
				{
					$this->errorCollection->add(array(new Main\Error('Agent add fail')));
				}
				unset($taskParams[Volume\Cleaner::DROP_ENTITY]);
			}
			if ($this->request->get(self::TASK_CLEAR_FILE) === 'Y')
			{
				$taskParams[Volume\Cleaner::DROP_FILE] = true;
				if (!Volume\Cleaner::addWorker($taskParams, $indicator))
				{
					$this->errorCollection->add(array(new Main\Error('Agent add fail')));
				}
				unset($taskParams[Volume\Cleaner::DROP_FILE]);
			}
			if ($this->request->get(self::TASK_CLEAR_EVENT) === 'Y')
			{
				$taskParams[Volume\Cleaner::DROP_EVENT] = true;
				if (!Volume\Cleaner::addWorker($taskParams, $indicator))
				{
					$this->errorCollection->add(array(new Main\Error('Agent add fail')));
				}
				unset($taskParams[Volume\Cleaner::DROP_EVENT]);
			}
			if ($this->request->get(self::TASK_CLEAR_ACTIVITY) === 'Y')
			{
				$taskParams[Volume\Cleaner::DROP_ACTIVITY] = true;
				if (!Volume\Cleaner::addWorker($taskParams, $indicator))
				{
					$this->errorCollection->add(array(new Main\Error('Agent add fail')));
				}
				unset($taskParams[Volume\Cleaner::DROP_ACTIVITY]);
			}

		}
		catch(Main\SystemException $exception)
		{
			$this->errorCollection->add(array(new Main\Error($exception->getMessage(), $exception->getCode())));
		}

		$result['stepper'] = $this->getProgressBar();

		return $result;
	}

	/**
	 * @return array
	 */
	private function executePurifyAction()
	{
		$result = array();
		try
		{
			Crm\VolumeTable::deleteBatch(array('=OWNER_ID' => $this->getUser()->getId()));
			Crm\VolumeTmpTable::clearTemporally();
		}
		catch(Main\SystemException $exception)
		{
			$this->errorCollection->add(array(new Main\Error($exception->getMessage(), $exception->getCode())));
		}

		return $result;
	}


	/**
	 * @return array
	 */
	private function executeMeasureAction($method = 'measure')
	{
		$result = array();

		try
		{
			/** @var Volume\IVolumeIndicator $indicator */
			$indicator = $this->getIndicator($this->indicatorId);
			if ($indicator instanceof Volume\IVolumeIndicator)
			{
				$indicator->setOwner($this->getUser()->getId());


				if ($this->request->get('period') !== null)
				{
					$period = explode('-', $this->request->get('period'));
					$indicator->addFilter('>=DATE_CREATE', new Main\Type\DateTime($period[0].'.01', 'Y.m.d'));
					$indicator->addFilter('<DATE_CREATE', new Main\Type\DateTime($period[1].'.01', 'Y.m.d'));
				}

				if ($this->request->get('range') !== null)
				{
					$period = explode('-', $this->request->get('range'));
					if ((int)$period[0] > 0)
					{
						$indicator->addFilter('>SORT_ID', (int)$period[0]);
					}
					if ((int)$period[1] > 0)
					{
						$indicator->addFilter('<=SORT_ID', (int)$period[1]);
					}
				}

				if (is_callable(array($indicator, $method)))
				{
					call_user_func(array($indicator, $method));
				}
			}
		}
		catch(Main\SystemException $exception)
		{
			if ($exception instanceof \Bitrix\Rest\AccessException)
			{
				$this->errorCollection->add(array(new Main\Error(Loc::getMessage('CRM_PERMISSION_DENIED'), self::STATUS_DENIED)));
			}
			else
			{
				$this->errorCollection->add(array(new Main\Error($exception->getMessage(), $exception->getCode())));
			}
		}

		return $result;
	}


	/**
	 * @return array
	 * @throws Main\ArgumentOutOfRangeException
	 */
	private function executeViewAction()
	{
		$this->arResult['QUEUE_RUNNING'] = false;
		$this->arResult['DATA_COLLECTED'] = false;

		if (!is_null($this->getQueueStep()))
		{
			$this->arResult['RUN_QUEUE'] = 'continue';
			$this->arResult['QUEUE_STEP'] = $this->getQueueStep();
			$this->arResult['QUEUE_RUNNING'] = true;
		}
		elseif ($this->arResult['RELOAD'])
		{
			$this->arResult['RUN_QUEUE'] = 'full';
			$this->arResult['QUEUE_RUNNING'] = true;
		}

		$this->arResult['SCAN_ACTION_LIST'] = $this->queueScanActionList();


		// grid
		$this->arResult['FILTER_ID'] = self::FILTER_ID;
		$this->arResult['GRID_ID'] = self::GRID_ID;
		$this->arResult['HEADERS'] = $this->getHeaderDefinition();

		$gridOptions = new Main\Grid\Options(self::GRID_ID);

		$this->arResult['FILTER'] = $this->getFilterDefinition();
		$this->arResult['FILTER_PRESETS'] = $this->getFilterPresetsDefinition();

		$isFilterApplied = $this->isFilterApplied();

		if ($isFilterApplied)
		{
			$filter = $this->getFilter();
		}

		// Sorting order
		$sorting = $gridOptions->GetSorting(array('sort' => array('total' => 'desc')));
		$sort = $this->arResult['SORT'] = $sorting['sort'];
		$this->arResult['SORT_VARS'] = $sorting['vars'];

		$this->arResult['REPORTS'] = array();

		$this->arResult['TOTALS'] = array(
			'TOTAL_SIZE' => 0,
			'ENTITY_SIZE' => 0,
			'ENTITY_COUNT' => 0,
			'FILE_SIZE' => 0,
			'FILE_COUNT' => 0,
			'DISK_SIZE' => 0,
			'DISK_COUNT' => 0,
			'ACTIVITY_SIZE' => 0,
			'ACTIVITY_COUNT' => 0,
			'EVENT_SIZE' => 0,
			'EVENT_COUNT' => 0,
		);

		$indicatorList = Volume\Base::getListIndicator();

		$otherIndicatorList = Volume\Other::getSubIndicatorList();

		/** @var Volume\IVolumeIndicator $indicatorType */
		foreach ($indicatorList as $indicatorType)
		{
			if (in_array($indicatorType, $otherIndicatorList))
			{
				continue;
			}

			try
			{
				/** @var Volume\IVolumeIndicator $indicator */
				$indicator = new $indicatorType();
				$indicator->setOwner($this->getUser()->getId());
				if ($indicator instanceof Volume\IVolumeIndicator)
				{
					if ($isFilterApplied)
					{
						$indicator->setFilter($filter);
					}
					if ($indicator->loadTotals())
					{
						$id = $indicator::getIndicatorId();
						$this->arResult['REPORTS'][$id] = array(
							'TITLE' => htmlspecialcharsbx($indicator->getTitle()),
							'TOTAL_SIZE' => $indicator->getTotalSize(),
							'ENTITY_SIZE' => $indicator->getEntitySize(),
							'ENTITY_COUNT' => $indicator->getEntityCount(),
							'FILE_SIZE' => $indicator->getFileSize(),
							'FILE_COUNT' => $indicator->getFileCount(),
							'DISK_SIZE' => $indicator->getDiskSize(),
							'DISK_COUNT' => $indicator->getDiskCount(),
							'ACTIVITY_SIZE' => $indicator->getActivitySize(),
							'ACTIVITY_COUNT' => $indicator->getActivityCount(),
							'EVENT_SIZE' => $indicator->getEventSize(),
							'EVENT_COUNT' => $indicator->getEventCount(),
							'TOTAL_SIZE_FORMAT' => '',
							'ENTITY_SIZE_FORMAT' => '',
							'FILE_SIZE_FORMAT' => '',
							'DISK_SIZE_FORMAT' => '',
							'ACTIVITY_SIZE_FORMAT' => '',
							'EVENT_SIZE_FORMAT' => '',
						);

						if ($indicator->getTotalSize() > 0)
						{
							$this->arResult['REPORTS'][$id]['TOTAL_SIZE_FORMAT'] = \CFile::formatSize($indicator->getTotalSize());
						}
						if ($indicator->getEntitySize() > 0)
						{
							$this->arResult['REPORTS'][$id]['ENTITY_SIZE_FORMAT'] = \CFile::formatSize($indicator->getEntitySize());
						}
						if ($indicator->getFileSize() > 0)
						{
							$this->arResult['REPORTS'][$id]['FILE_SIZE_FORMAT'] = \CFile::formatSize($indicator->getFileSize());
						}
						if ($indicator->getDiskSize() > 0)
						{
							$this->arResult['REPORTS'][$id]['DISK_SIZE_FORMAT'] = \CFile::formatSize($indicator->getDiskSize());
						}
						if ($indicator->getActivitySize() > 0)
						{
							$this->arResult['REPORTS'][$id]['ACTIVITY_SIZE_FORMAT'] = \CFile::formatSize($indicator->getActivitySize());
						}
						if ($indicator->getEventSize() > 0)
						{
							$this->arResult['REPORTS'][$id]['EVENT_SIZE_FORMAT'] = \CFile::formatSize($indicator->getEventSize());
						}

						if ($indicator->isParticipatedTotal())
						{
							$this->arResult['TOTALS']['TOTAL_SIZE'] += $indicator->getTotalSize();
							$this->arResult['TOTALS']['ENTITY_SIZE'] += $indicator->getEntitySize();
							$this->arResult['TOTALS']['ENTITY_COUNT'] += $indicator->getEntityCount();
							$this->arResult['TOTALS']['FILE_SIZE'] += $indicator->getFileSize();
							$this->arResult['TOTALS']['FILE_COUNT'] += $indicator->getFileCount();
							$this->arResult['TOTALS']['DISK_SIZE'] += $indicator->getDiskSize();
							$this->arResult['TOTALS']['DISK_COUNT'] += $indicator->getDiskCount();
							$this->arResult['TOTALS']['ACTIVITY_SIZE'] += $indicator->getActivitySize();
							$this->arResult['TOTALS']['ACTIVITY_COUNT'] += $indicator->getActivityCount();
							$this->arResult['TOTALS']['EVENT_SIZE'] += $indicator->getEventSize();
							$this->arResult['TOTALS']['EVENT_COUNT'] += $indicator->getEventCount();
						}
					}
				}
				if ($indicator instanceof Volume\IVolumeUrl)
				{
					$this->arResult['REPORTS'][$id]['LIST_URL'] = $indicator->getUrl();
					$this->arResult['REPORTS'][$id]['LIST_FILTER_PARAM'] = $this->getEntityFilterParam($indicator);

					$gridFilterReset = $indicator->getGridFilterResetParam();
					$this->arResult['REPORTS'][$id]['FILTER_ID'] = $gridFilterReset['FILTER_ID'];
					$this->arResult['REPORTS'][$id]['GRID_ID'] = $gridFilterReset['GRID_ID'];
					$this->arResult['REPORTS'][$id]['FILTER_FIELDS'] = $gridFilterReset['FILTER_FIELDS'];
				}
				if ($indicator instanceof Volume\IVolumeClear)
				{
					$this->arResult['REPORTS'][$id]['CAN_CLEAR_ENTITY'] = $indicator->canClearEntity();
				}
				if ($indicator instanceof Volume\IVolumeClearFile)
				{
					$this->arResult['REPORTS'][$id]['CAN_CLEAR_FILE'] = $indicator->canClearFile();
				}
				if ($indicator instanceof Volume\IVolumeClearActivity)
				{
					$this->arResult['REPORTS'][$id]['CAN_CLEAR_ACTIVITY'] = $indicator->canClearActivity();
				}
				if ($indicator instanceof Volume\IVolumeClearEvent)
				{
					$this->arResult['REPORTS'][$id]['CAN_CLEAR_EVENT'] = $indicator->canClearEvent();
				}

				if (
					$indicator instanceof Volume\Activity ||
					$indicator instanceof Volume\Event
				)
				{
					$this->arResult['REPORTS'][$id]['ACTIVITY_NAN'] = true;
					$this->arResult['REPORTS'][$id]['EVENT_NAN'] = true;
				}

			}
			catch(Main\SystemException $exception)
			{
				if ($exception instanceof \Bitrix\Rest\AccessException)
				{
					\ShowError(Loc::getMessage('CRM_PERMISSION_DENIED'));
				}
				else
				{
					\ShowError(Loc::getMessage('CRM_VOLUME_ERROR_GENERAL'));
				}
			}
		}

		$this->arResult['TOTALS']['TOTAL_SIZE_FORMAT'] = \CFile::formatSize($this->arResult['TOTALS']['TOTAL_SIZE']);
		$this->arResult['TOTALS']['ENTITY_SIZE_FORMAT'] = \CFile::formatSize($this->arResult['TOTALS']['ENTITY_SIZE']);
		$this->arResult['TOTALS']['FILE_SIZE_FORMAT'] = \CFile::formatSize($this->arResult['TOTALS']['FILE_SIZE']);
		$this->arResult['TOTALS']['DISK_SIZE_FORMAT'] = \CFile::formatSize($this->arResult['TOTALS']['DISK_SIZE']);
		$this->arResult['TOTALS']['ACTIVITY_SIZE_FORMAT'] = \CFile::formatSize($this->arResult['TOTALS']['ACTIVITY_SIZE']);
		$this->arResult['TOTALS']['EVENT_SIZE_FORMAT'] = \CFile::formatSize($this->arResult['TOTALS']['EVENT_SIZE']);

		$sortAlias = array(
			'title' => 'TITLE',
			'total' => 'TOTAL_SIZE',
			'table' => 'ENTITY_SIZE',
			'cnt' => 'ENTITY_COUNT',
			'file' => 'FILE_SIZE',
			'file_cnt' => 'FILE_COUNT',
			'disk' => 'DISK_SIZE',
			'disk_cnt' => 'DISK_COUNT',
			'activity' => 'ACTIVITY_SIZE',
			'activity_cnt' => 'ACTIVITY_COUNT',
			'event' => 'EVENT_SIZE',
			'event_cnt' => 'EVENT_COUNT',
		);
		$arrKeys = array_keys($sort);
		$sortKey = array_shift($arrKeys);
		if(!isset($sortAlias[$sortKey]))
		{
			$sortKey = 'total';
		}

		$column = $sortAlias[$sortKey];
		$order = ($sort[$sortKey] === 'asc' ? SORT_ASC : SORT_DESC);
		$type = ($sortKey === 'title' ? SORT_REGULAR : SORT_NUMERIC);

		Main\Type\Collection::sortByColumn(
			$this->arResult['REPORTS'],
			array($column => array($type, $order))
		);

		$this->arResult['GRID_DATA'] = $this->getGridData($this->arResult['REPORTS'], $this->arResult['TOTALS']['TOTAL_SIZE']);

		$this->arResult['PERCENT_DATA'] = array();
		foreach ($this->arResult['GRID_DATA'] as $key => $row)
		{
			$this->arResult['PERCENT_DATA'][$key] = array(
				'id' => $row['id'],
				'TITLE' => $row['columns']['title'],
				'PERCENT' => $row['columns']['percent'],
				'SIZE_FORMAT' => $row['columns']['total'],
			);
		}

		Main\Type\Collection::sortByColumn(
			$this->arResult['PERCENT_DATA'],
			array('PERCENT' => array(SORT_NUMERIC, SORT_DESC))
		);

		if ($this->arResult['REPORTS'] > 0)
		{
			if  ($this->arResult['QUEUE_RUNNING'] !== true)
			{
				$this->arResult['DATA_COLLECTED'] = $this->hasDataCollected();
			}
		}


		$this->arResult['NEED_RELOAD'] = $this->isNeedReload();

		if (isset($this->arResult['RELOAD']) && $this->arResult['RELOAD'] === true)
		{
			$this->executeCancelAction();
			$this->arResult['HAS_WORKER_IN_PROCESS'] = false;
		}
		elseif ($this->hasWorkerInProcess())
		{
			$this->arResult['HAS_WORKER_IN_PROCESS'] = true;
			$this->arResult['PROCESS_BAR'] = $this->getProgressBar();
		}

		return $this->arResult;
	}


	/**
	 * Returns true if data needed to be refreshed.
	 * @return int
	 */
	public function isNeedReload()
	{
		// there are no running task
		$filter = array(
			'=OWNER_ID' => $this->getUser()->getId(),
			'=AGENT_LOCK' => array(
				Volume\Cleaner::TASK_STATUS_RUNNING,
				Volume\Cleaner::TASK_STATUS_WAIT,
			),
		);
		$workerResult = Crm\VolumeTable::getList(array(
			'select' => array('ID'),
			'filter' => $filter,
			'limit' => 1,
		));
		if ($workerResult)
		{
			if ($row = $workerResult->fetch())
			{
				return 0;
			}
		}

		// are there finished tasks with dropped itemts
		$filter = array(
			'=OWNER_ID' => $this->getUser()->getId(),
			array(
				'=AGENT_LOCK' => array(
					Volume\Cleaner::TASK_STATUS_DONE,
					Volume\Cleaner::TASK_STATUS_CANCEL,
				),
				array(
					'LOGIC' => 'OR',
					'>DROPPED_ENTITY_COUNT' => 0,
					'>DROPPED_FILE_COUNT' => 0,
					'>DROPPED_EVENT_COUNT' => 0,
					'>DROPPED_ACTIVITY_COUNT' => 0,
					'>FAIL_COUNT' => 0,
				)
			),
		);
		$workerResult = Crm\VolumeTable::getList(array(
			'select' => array('ID'),
			'filter' => $filter,
			'limit' => 1,
		));
		if ($workerResult)
		{
			if ($row = $workerResult->fetch())
			{
				return 1;
			}
		}

		// last measure a day ago
		$filter = array(
			'=OWNER_ID' => $this->getUser()->getId(),
			array(
				'=AGENT_LOCK' => array(
					Volume\Cleaner::TASK_STATUS_NONE,
				),
				'<TIMESTAMP_X' => new Main\Type\DateTime(date('Y-m-d H:i:s', strtotime('-1 days')), 'Y-m-d H:i:s'),
			),
		);
		$workerResult = Crm\VolumeTable::getList(array(
			'select' => array('ID'),
			'filter' => $filter,
			'order' => array('TIMESTAMP_X' => 'DESC'),
			'limit' => 1,
		));
		if ($workerResult)
		{
			if ($row = $workerResult->fetch())
			{
				return 2;
			}
		}

		return 0;
	}

	/**
	 * Returns true if data has been collected.
	 * @return bool
	 */
	private function hasDataCollected()
	{
		$query = Crm\VolumeTable::query();

		$filter = array(
			'=OWNER_ID' => $this->getUser()->getId(),
		);

		$query
			->setFilter($filter)
			->registerRuntimeField('', new Main\Entity\ExpressionField('CNT', 'COUNT(*)'))
			->addSelect('CNT')
		;

		$res = $query->exec();
		if ($row = $res->fetch())
		{
			return ($row['CNT'] > 0);
		}

		return false;
	}


	/**
	 * Is exists any worker.
	 *
	 * @return boolean
	 */
	public function hasWorkerInProcess()
	{
		if (Volume\Cleaner::countWorker($this->getUser()->getId()) > 0)
		{
			$option = Volume\Cleaner::getProgressInfo($this->getUser()->getId());
			if (!empty($option))
			{
				return (bool)($option['count'] > 0 && $option['steps'] < $option['count']);
			}
		}

		return false;
	}

	/**
	 * Show agent progress bar.
	 * @return string
	 */
	private function getProgressBar()
	{
		$res = array();
		$res['crm'] = array(Volume\Cleaner::class. $this->getUser()->getId());

		\CJSCore::Init(array('update_stepper'));

		return Main\Update\Stepper::getHtml(
			$res,
			Loc::getMessage('CRM_VOLUME_AGENT_STEPPER')
		);
	}


	/**
	 * @return array
	 */
	private function executeCancelAction()
	{
		$result = array();

		Volume\Cleaner::cancelWorker($this->getUser()->getId());
		Volume\Cleaner::clearProgressInfo($this->getUser()->getId());

		return $result;
	}


	/**
	 * @return boolean
	 */
	private function isFilterApplied()
	{
		$isFilterApplied = false;

		$filterPresetList = $this->getFilterPresetsDefinition();
		$filterOptions = new Main\UI\Filter\Options(self::FILTER_ID, $filterPresetList);
		$filterDefinition = $this->getFilterDefinition();
		$filterInp = $filterOptions->getFilter($filterDefinition);

		if (isset($filterInp['FILTER_APPLIED']) && $filterInp['FILTER_APPLIED'])
		{
			foreach ($filterDefinition as $field)
			{
				$alias = $field['id'];

				if ($field['type'] === 'date')
				{
					$dateCreate = Main\UI\Filter\Options::fetchDateFieldValue($alias, $filterInp);
					if (
						!empty($dateCreate["{$alias}"]) ||
						!empty($dateCreate["{$alias}_from"]) ||
						!empty($dateCreate["{$alias}_to"])
					)
					{
						$isFilterApplied = true;
						break;
					}
				}
				elseif (!empty($filterInp[$alias]))
				{
					$isFilterApplied = true;
					break;
				}
			}
		}

		return $isFilterApplied;
	}

	/**
	 * @return array
	 */
	private function getFilter()
	{
		$filter = array();

		$filterPresetList = $this->getFilterPresetsDefinition();
		$filterOptions = new Main\UI\Filter\Options(self::FILTER_ID, $filterPresetList);
		$filterDefinition = $this->getFilterDefinition();
		$filterInp = $filterOptions->getFilter($filterDefinition);

		// Predefined filter sets
		$dateField = array();
		$isRange = array();
		foreach ($filterDefinition as $field)
		{
			$alias = $field['id'];

			if ($field['type'] === 'date')
			{
				$dateCreate = Main\UI\Filter\Options::fetchDateFieldValue($alias, $filterInp);
				$format = Main\Type\DateTime::getFormat();
				/*
				[DATE_CREATE] =>
				[DATE_CREATE_datesel] => LAST_30_DAYS
				[DATE_CREATE_month] => 2
				[DATE_CREATE_quarter] => 1
				[DATE_CREATE_year] => 2018
				[DATE_CREATE_from] => 16.01.2018 00:00:00
				[DATE_CREATE_to] => 15.02.2018 23:59:59
				*/
				if (!empty($dateCreate["{$alias}_datesel"]))
				{
					$isRange[$alias] = ($dateCreate["{$alias}_datesel"] === Main\UI\Filter\DateType::RANGE);
				}
				if (!empty($dateCreate["{$alias}"]))
				{
					$dateField[] = "<={$alias}";
					$filter["<={$alias}"] = new Main\Type\DateTime($dateCreate["{$alias}"], $format);
				}
				if (!empty($dateCreate["{$alias}_from"]))
				{
					$dateField[] = ">={$alias}";

					$dt = new Main\Type\DateTime($dateCreate["{$alias}_from"], $format);
					if ($isRange[$alias])
					{
						$dt->add('+1 seconds');
					}
					$filter[">={$alias}"] = $dt;
				}
				if (!empty($dateCreate["{$alias}_to"]))
				{
					$dt = new Main\Type\DateTime($dateCreate["{$alias}_to"], $format);
					$filter["<={$alias}"] = $dt;

					if ($isRange[$alias])
					{
						$dt->add('+1 seconds');
					}
					$dateField[] = "<={$alias}";
				}
			}
			elseif (!empty($filterInp[$alias]))
			{
				$filter[$alias] = $filterInp[$alias];
			}
		}

		// remove and convert some cases to period "past - start date"
		foreach ($dateField as $key)
		{
			$key0 = trim($key, '<>=!');
			if ($isRange[$key0])
			{
				continue;
			}

			// remove end date selected period
			if (mb_strpos($key, '<=') === 0)
			{
				unset($filter[$key]);
			}
		}
		foreach ($dateField as $key)
		{
			$key0 = trim($key, '<>=!');
			if ($isRange[$key0])
			{
				continue;
			}

			// remove end date selected period
			if (mb_strpos($key, '>=') === 0)
			{
				// convert start to end date period
				$key1 = str_replace('>=', '<=', $key);
				// include day
				$filter[$key1] = $filter[$key];//->add('+23 hours +59 minutes +59 seconds');
				unset($filter[$key]);
			}
		}

		return $filter;
	}

	/**
	 * Get params to filter entity list.
	 * Volume\IVolumeUrl $indicator
	 * @return array|null
	 */
	private function getEntityFilterParam($indicator)
	{
		/** @var Volume\IVolumeUrl $indicator */
		if (!($indicator instanceof Volume\IVolumeUrl))
		{
			return null;
		}

		$params = array();

		if ($indicator instanceof Volume\Callrecord)
		{
			$params['TYPE_ID'][] = (string)\CCrmActivityType::Call. '.'. (string)\CCrmActivityDirection::Incoming;
			$params['TYPE_ID'][] = (string)\CCrmActivityType::Call. '.'. (string)\CCrmActivityDirection::Outgoing;
		}

		if ($indicator instanceof Volume\EmailAttachment)
		{
			$params['TYPE_ID'][] = (string)\CCrmActivityType::Email. '.'. (string)\CCrmActivityDirection::Incoming;
			$params['TYPE_ID'][] = (string)\CCrmActivityType::Email. '.'. (string)\CCrmActivityDirection::Outgoing;
		}

		if (!$this->isFilterApplied())
		{
			return $params;
		}

		$targetAlias = $indicator->getFilterAlias();
		if (count($targetAlias) == 0)
		{
			return $params;
		}

		$filterPresetList = $this->getFilterPresetsDefinition();
		$filterOptions = new Main\UI\Filter\Options(self::FILTER_ID, $filterPresetList);
		$filterDefinition = $this->getFilterDefinition();
		$filterInp = $filterOptions->getFilter($filterDefinition);

		// Predefined filter sets
		foreach ($filterDefinition as $field)
		{
			$alias = $field['id'];
			if (!isset($targetAlias[$alias]))
			{
				continue;
			}

			$target = $targetAlias[$alias];

			if ($target instanceof \Closure)
			{
				if (!empty($filterInp[$alias]))
				{
					$target($params, $filterInp[$alias]);
				}
			}
			elseif ($field['type'] === 'date')
			{
				$dateCreate = Main\UI\Filter\Options::fetchDateFieldValue($alias, $filterInp);
				$dateTimeFormat = Main\Type\DateTime::getFormat();
				$dateFormat = Main\Type\Date::getFormat();

				/*
				[DATE_CREATE] =>
				[DATE_CREATE_datesel] => LAST_30_DAYS
				[DATE_CREATE_month] => 2
				[DATE_CREATE_quarter] => 1
				[DATE_CREATE_year] => 2018
				[DATE_CREATE_from] => 16.01.2018 00:00:00
				[DATE_CREATE_to] => 15.02.2018 23:59:59
				*/
				if (!empty($dateCreate["{$alias}_from"]) || !empty($dateCreate["{$alias}_to"]))
				{
					if ($dateCreate["{$alias}_datesel"] === Main\UI\Filter\DateType::RANGE)
					{
						$params["{$target}_datesel"] = Main\UI\Filter\DateType::RANGE;

						$from = new Main\Type\DateTime($dateCreate["{$alias}_from"], $dateTimeFormat);
						$params["{$target}_from"] = htmlspecialcharsbx($from->format($dateFormat));

						$to = new Main\Type\DateTime($dateCreate["{$alias}_to"], $dateTimeFormat);
						$params["{$target}_to"] = htmlspecialcharsbx($to->format($dateFormat));
					}
					elseif (!empty($dateCreate["{$alias}_from"]))
					{
						$params["{$target}_datesel"] = Main\UI\Filter\DateType::RANGE;
						$dt = new Main\Type\DateTime($dateCreate["{$alias}_from"], $dateTimeFormat);
						$dt->add('-1 seconds');

						$params["{$target}_to"] = htmlspecialcharsbx($dt->format($dateFormat));
					}
				}
			}
			elseif (!empty($filterInp[$alias]) && (isset($field['params']['multiple']) && $field['params']['multiple'] === 'Y'))
			{
				foreach ($filterInp[$alias] as $val)
				{
					if(!empty($val))
					{
						if(!isset($params["{$target}"]))
						{
							$params["{$target}"] = array();
						}
						$params["{$target}"][] = htmlspecialcharsbx($val);
					}
				}
			}
			elseif (!empty($filterInp[$alias]))
			{
				$params["{$target}"] = htmlspecialcharsbx($filterInp[$alias]);
			}
		}

		return $params;
	}

	/**
	 * @return array
	 */
	private function getHeaderDefinition()
	{
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

		$result = array(
			array(
				'id' => 'title_format',
				'name' => Loc::getMessage('CRM_VOLUME_REPORT_TITLE'),
				'default' => true,
				'sort' => 'title',
				'first_order' => 'ASC',
			),

			array(
				'id' => 'table',
				'name' => Loc::getMessage('CRM_VOLUME_REPORT_TABLE_SIZE'),
				'default' => true,
				'align' => 'right',
				'sort' => 'table',
				'first_order' => 'desc',
			),
			array(
				'id' => 'cnt',
				'name' => Loc::getMessage('CRM_VOLUME_REPORT_TABLE_COUNT'),
				'align' => 'right',
				'sort' => 'cnt',
				'first_order' => 'desc',
			),

			array(
				'id' => 'file',
				'name' => Loc::getMessage('CRM_VOLUME_REPORT_FILE_SIZE'),
				'default' => true,
				'align' => 'right',
				'sort' => 'file',
				'first_order' => 'desc',
			),
			array(
				'id' => 'file_cnt',
				'name' => Loc::getMessage('CRM_VOLUME_REPORT_FILE_COUNT'),
				'align' => 'right',
				'sort' => 'file_cnt',
				'first_order' => 'desc',
			),

			array(
				'id' => 'event',
				'name' => Loc::getMessage('CRM_VOLUME_REPORT_EVENT_SIZE'),
				'align' => 'right',
				'sort' => 'event',
				'first_order' => 'desc',
			),
			array(
				'id' => 'event_cnt',
				'name' => Loc::getMessage('CRM_VOLUME_REPORT_EVENT_COUNT'),
				'align' => 'right',
				'sort' => 'event_cnt',
				'first_order' => 'desc',
			),

			array(
				'id' => 'activity',
				'name' => Loc::getMessage('CRM_VOLUME_REPORT_ACTIVITY_SIZE'),
				'align' => 'right',
				'sort' => 'activity',
				'first_order' => 'desc',
			),
			array(
				'id' => 'activity_cnt',
				'name' => Loc::getMessage('CRM_VOLUME_REPORT_ACTIVITY_COUNT'),
				'align' => 'right',
				'sort' => 'activity_cnt',
				'first_order' => 'desc',
			),

			array(
				'id' => 'disk',
				'name' => Loc::getMessage('CRM_VOLUME_REPORT_DISK_SIZE_MSGVER_1'),
				'align' => 'right',
				'sort' => 'disk',
				'first_order' => 'desc',
			),
			array(
				'id' => 'disk_cnt',
				'name' => Loc::getMessage('CRM_VOLUME_REPORT_DISK_COUNT_MSGVER_1'),
				'align' => 'right',
				'sort' => 'disk_cnt',
				'first_order' => 'desc',
			),

			array(
				'id' => 'total',
				'name' => Loc::getMessage('CRM_VOLUME_REPORT_TOTAL_SIZE'),
				'default' => true,
				'align' => 'right',
				'sort' => 'total',
				'first_order' => 'desc',
			),
		);

		return $result;
	}

	/**
	 * @return array
	 */
	private function getFilterPresetsDefinition()
	{
		$result = array();

		return $result;
	}

	/**
	 * @return array
	 */
	private function getFilterDefinition()
	{
		$filter = array();

		$excludeDate = array(
			//Main\UI\Filter\DateType::NONE,
			//Main\UI\Filter\DateType::EXACT,
			Main\UI\Filter\DateType::YESTERDAY,
			Main\UI\Filter\DateType::CURRENT_DAY,
			Main\UI\Filter\DateType::TOMORROW,
			Main\UI\Filter\DateType::NEXT_DAYS,
			Main\UI\Filter\DateType::NEXT_WEEK,
			Main\UI\Filter\DateType::NEXT_MONTH,
			Main\UI\Filter\DateType::LAST_WEEK,
			Main\UI\Filter\DateType::LAST_MONTH,
		);

		$stageFilter  = Crm\PhaseSemantics::getListFilterInfo(
			false,
			array(
				'id' => 'STAGE_SEMANTIC_ID',
				'name' => Loc::getMessage('CRM_VOLUME_STAGE'),
				'default' => true,
				'params' => array('multiple' => 'Y'),
				'exclude' => array(
					//Crm\PhaseSemantics::PROCESS,
					//Crm\PhaseSemantics::SUCCESS,
					//Crm\PhaseSemantics::FAILURE,
				),
			),
			true
		);
		foreach ($stageFilter['items'] as $key => $name)
		{
			if (in_array($key, $stageFilter['exclude']))
			{
				unset($stageFilter['items'][$key]);
			}
		}
		$filter[] = $stageFilter;

		$filter[] = array(
			'id' => 'DATE_CREATE',
			'name' => Loc::getMessage('CRM_VOLUME_DATE_CREATE'),
			'default' => true,
			'type' => 'date',
			'exclude' => $excludeDate,
			'messages' => array(
				'MAIN_UI_FILTER_FIELD_SUBTYPE_NONE' => Loc::getMessage('CRM_VOLUME_DATE_PERIOD_NONE'),
				'MAIN_UI_FILTER_FIELD_SUBTYPE_EXACT' => Loc::getMessage('CRM_VOLUME_DATE_PERIOD_EXACT'),
				'MAIN_UI_FILTER_FIELD_SUBTYPE_CURRENT_WEEK' => Loc::getMessage('CRM_VOLUME_DATE_PERIOD_CURRENT_WEEK'),
				'MAIN_UI_FILTER_FIELD_SUBTYPE_CURRENT_MONTH' => Loc::getMessage('CRM_VOLUME_DATE_PERIOD_CURRENT_MONTH'),
				'MAIN_UI_FILTER_FIELD_SUBTYPE_CURRENT_QUARTER' => Loc::getMessage('CRM_VOLUME_DATE_PERIOD_CURRENT_QUARTER'),
				'MAIN_UI_FILTER_FIELD_SUBTYPE_LAST_7_DAYS' => Loc::getMessage('CRM_VOLUME_DATE_PERIOD_LAST_7_DAYS'),
				'MAIN_UI_FILTER_FIELD_SUBTYPE_LAST_30_DAYS' => Loc::getMessage('CRM_VOLUME_DATE_PERIOD_LAST_30_DAYS'),
				'MAIN_UI_FILTER_FIELD_SUBTYPE_LAST_60_DAYS' => Loc::getMessage('CRM_VOLUME_DATE_PERIOD_LAST_60_DAYS'),
				'MAIN_UI_FILTER_FIELD_SUBTYPE_LAST_90_DAYS' => Loc::getMessage('CRM_VOLUME_DATE_PERIOD_LAST_90_DAYS'),
				'MAIN_UI_FILTER_FIELD_SUBTYPE_PREV_DAYS' => Loc::getMessage('CRM_VOLUME_DATE_PERIOD_PREV_DAYS'),
				'MAIN_UI_FILTER_FIELD_SUBTYPE_MONTH' => Loc::getMessage('CRM_VOLUME_DATE_PERIOD_MONTH'),
				'MAIN_UI_FILTER_FIELD_SUBTYPE_QUARTER' => Loc::getMessage('CRM_VOLUME_DATE_PERIOD_QUARTER'),
				'MAIN_UI_FILTER_FIELD_SUBTYPE_YEAR' => Loc::getMessage('CRM_VOLUME_DATE_PERIOD_YEAR'),
				'MAIN_UI_FILTER_FIELD_SUBTYPE_LAST_WEEK' => Loc::getMessage('CRM_VOLUME_DATE_PERIOD_LAST_WEEK'),
				'MAIN_UI_FILTER_FIELD_SUBTYPE_LAST_MONTH' => Loc::getMessage('CRM_VOLUME_DATE_PERIOD_LAST_MONTH'),
				'MAIN_UI_FILTER__DATE_PREV_DAYS_LABEL' => Loc::getMessage('CRM_VOLUME_DATE_PERIOD_PREV_DAYS_LABEL'),
			),
		);

		return $filter;
	}

	/**
	 * @param array $data
	 * @return array
	 */
	private function getGridData($data, $totalSize)
	{
		$result = array();

		// put Other down
		$keys = array_keys($data);
		if ( ($inx = array_search('Other', $keys)) !== false )
		{
			unset($keys[$inx]);
			$keys[] = 'Other';
		}

		foreach ($keys as $key)
		{
			$report =& $data[$key];

			if ($report['TOTAL_SIZE'] <= 0)
			{
				continue;
			}

			$keyUpper = mb_strtoupper($key);
			$actions = array();
			if (isset($report['CAN_CLEAR_ENTITY']) && $report['CAN_CLEAR_ENTITY'] === true)
			{
				$menuTitle = Loc::getMessage('CRM_VOLUME_DELETE', array('#NAME#' => $report['TITLE']));
				$confirm = Loc::getMessage('CRM_VOLUME_DELETE_CONFIRM', array('#NAME#' => $report['TITLE']));
				$confirmAll = Loc::getMessage('CRM_VOLUME_DELETE_CONFIRM_ALL', array('#NAME#' => $report['TITLE']));
				if (Loc::getMessage("CRM_VOLUME_DELETE_{$keyUpper}") != '')
				{
					$menuTitle = Loc::getMessage("CRM_VOLUME_DELETE_{$keyUpper}");
				}
				if (Loc::getMessage("CRM_VOLUME_DELETE_{$keyUpper}_MSGVER_1") != '')
				{
					$menuTitle = Loc::getMessage("CRM_VOLUME_DELETE_{$keyUpper}_MSGVER_1");
				}
				if (Loc::getMessage("CRM_VOLUME_DELETE_CONFIRM_{$keyUpper}") != '')
				{
					$confirm = Loc::getMessage("CRM_VOLUME_DELETE_CONFIRM_{$keyUpper}");
				}
				if (Loc::getMessage("CRM_VOLUME_DELETE_CONFIRM_{$keyUpper}_MSGVER_1") != '')
				{
					$confirm = Loc::getMessage("CRM_VOLUME_DELETE_CONFIRM_{$keyUpper}_MSGVER_1");
				}
				if (Loc::getMessage("CRM_VOLUME_DELETE_CONFIRM_ALL_{$keyUpper}") != '')
				{
					$confirmAll = Loc::getMessage("CRM_VOLUME_DELETE_CONFIRM_ALL_{$keyUpper}");
				}
				if (Loc::getMessage("CRM_VOLUME_DELETE_CONFIRM_ALL_{$keyUpper}_MSGVER_1") != '')
				{
					$confirmAll = Loc::getMessage("CRM_VOLUME_DELETE_CONFIRM_ALL_{$keyUpper}_MSGVER_1");
				}

				$actions[] = array(
					'text' => $menuTitle,
					'onclick' => 'BX.Crm.measureManager.openConfirm('.\CUtil::PhpToJSObject(array(
						'action' => self::ACTION_SETUP_TASK,
						self::TASK_CLEAR => 'Y',
						'indicatorId' => $key,
						'name' => $report['TITLE'],
						'title' => $menuTitle,
						'messageConfirm' => $confirm,
						'messageConfirmAll' => $confirmAll,
						'payload' => 'callAction',
						'before' => 'showAlertSetupProcess',
					)).');'
				);
			}
			if (isset($report['CAN_CLEAR_FILE']) && $report['CAN_CLEAR_FILE'] === true)
			{
				if ($report['FILE_SIZE'] > 0)
				{
					$confirm = Loc::getMessage('CRM_VOLUME_DELETE_CONFIRM_FILE', array('#NAME#' => $report['TITLE']));;
					if (Loc::getMessage("CRM_VOLUME_DELETE_CONFIRM_FILE_{$keyUpper}") !== '')
					{
						$confirm = Loc::getMessage("CRM_VOLUME_DELETE_CONFIRM_FILE_{$keyUpper}");
					}
					$confirmAll = Loc::getMessage('CRM_VOLUME_DELETE_CONFIRM_FILE_ALL', array('#NAME#' => $report['TITLE']));;
					if (Loc::getMessage("CRM_VOLUME_DELETE_CONFIRM_FILE_ALL_{$keyUpper}") !== '')
					{
						$confirmAll = Loc::getMessage("CRM_VOLUME_DELETE_CONFIRM_FILE_ALL_{$keyUpper}");
					}
					$menuTitle = Loc::getMessage('CRM_VOLUME_DELETE_FILE', array('#NAME#' => $report['TITLE']));
					if (Loc::getMessage("CRM_VOLUME_DELETE_FILE_{$keyUpper}") != '')
					{
						$menuTitle = Loc::getMessage("CRM_VOLUME_DELETE_FILE_{$keyUpper}");
					}

					$actions[] = array(
						'text' => $menuTitle,
						'onclick' => 'BX.Crm.measureManager.openConfirm('.\CUtil::PhpToJSObject(array(
							'action' => self::ACTION_SETUP_TASK,
							self::TASK_CLEAR_FILE => 'Y',
							'indicatorId' => $key,
							'name' => $report['TITLE'],
							'title' => $menuTitle,
							'messageConfirm' => $confirm,
							'messageConfirmAll' => $confirmAll,
							'payload' => 'callAction',
							'before' => 'showAlertSetupProcess',
						)).');'
					);
				}
			}
			if ((isset($report['CAN_CLEAR_ACTIVITY']) && $report['CAN_CLEAR_ACTIVITY'] == true) || (isset($report['CAN_CLEAR_EVENT']) && $report['CAN_CLEAR_EVENT'] == true))
			{
				if ($report['ACTIVITY_SIZE'] > 0 || $report['EVENT_SIZE'] > 0)
				{
					$confirm = Loc::getMessage('CRM_VOLUME_DELETE_CONFIRM_HISTORY', array('#NAME#' => $report['TITLE']));
					if (Loc::getMessage("CRM_VOLUME_DELETE_CONFIRM_HISTORY_{$keyUpper}") !== '')
					{
						$confirm = Loc::getMessage("CRM_VOLUME_DELETE_CONFIRM_HISTORY_{$keyUpper}");
					}
					if (Loc::getMessage("CRM_VOLUME_DELETE_CONFIRM_HISTORY_{$keyUpper}_MSGVER_1") !== '')
					{
						$confirm = Loc::getMessage("CRM_VOLUME_DELETE_CONFIRM_HISTORY_{$keyUpper}_MSGVER_1");
					}
					$confirmAll = Loc::getMessage('CRM_VOLUME_DELETE_CONFIRM_ALL_HISTORY', array('#NAME#' => $report['TITLE']));
					if (Loc::getMessage("CRM_VOLUME_DELETE_CONFIRM_ALL_HISTORY_{$keyUpper}") !== '')
					{
						$confirmAll = Loc::getMessage("CRM_VOLUME_DELETE_CONFIRM_ALL_HISTORY_{$keyUpper}");
					}
					if (Loc::getMessage("CRM_VOLUME_DELETE_CONFIRM_ALL_HISTORY_{$keyUpper}_MSGVER_1") !== '')
					{
						$confirmAll = Loc::getMessage("CRM_VOLUME_DELETE_CONFIRM_ALL_HISTORY_{$keyUpper}_MSGVER_1");
					}
					$menuTitle = Loc::getMessage('CRM_VOLUME_DELETE_HISTORY', array('#NAME#' => $report['TITLE']));
					if (Loc::getMessage("CRM_VOLUME_DELETE_HISTORY_{$keyUpper}") != '')
					{
						$menuTitle = Loc::getMessage("CRM_VOLUME_DELETE_HISTORY_{$keyUpper}");
					}
					if (Loc::getMessage("CRM_VOLUME_DELETE_HISTORY_{$keyUpper}_MSGVER_1") != '')
					{
						$menuTitle = Loc::getMessage("CRM_VOLUME_DELETE_HISTORY_{$keyUpper}_MSGVER_1");
					}

					$actions[] = array(
						'text' => $menuTitle,
						'onclick' => 'BX.Crm.measureManager.openConfirm('.\CUtil::PhpToJSObject(array(
							'action' => self::ACTION_SETUP_TASK,
							self::TASK_CLEAR_EVENT => 'Y',
							self::TASK_CLEAR_ACTIVITY => 'Y',
							'indicatorId' => $key,
							'name' => $report['TITLE'],
							'title' => $menuTitle,
							'messageConfirm' => $confirm,
							'messageConfirmAll' => $confirmAll,
							'payload' => 'callAction',
							'before' => 'showAlertSetupProcess',
						)).');'
					);
				}
			}

			if ($totalSize > 0)
			{
				$percent = round((double)$report['TOTAL_SIZE'] * 100 / $totalSize, 2);
			}
			else
			{
				$percent = '&ndash;';
			}

			$result[] = array(
				'id' => $key,
				'data' => $report,
				'columns' => array(
					'title' => $report['TITLE'],
					'title_format' => (!empty($report['LIST_URL']) ?
						'<a href="'.$report['LIST_URL'].'"'.
						' class="crm-volume-link crm-volume-link-grid"'.
						' data-gridId="'.$report['GRID_ID'].'"'.
						' data-filterId="'.$report['FILTER_ID'].'"'.
						' data-fields="'.$report['FILTER_FIELDS'].'"'.
						' data-filter=\''. json_encode($report['LIST_FILTER_PARAM'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE). '\''.
						'>'.$report['TITLE'].'</a>' :
						$report['TITLE']),
					'total' => ($report['TOTAL_SIZE'] > 0 ? $report['TOTAL_SIZE_FORMAT'] : '&ndash;'),
					'table' => ($report['ENTITY_SIZE'] > 0 ? $report['ENTITY_SIZE_FORMAT'] : '&ndash;'),
					'cnt' => ($report['ENTITY_COUNT'] > 0 ? $report['ENTITY_COUNT'] : '&ndash;'),
					'file' => ($report['FILE_SIZE'] > 0 ? $report['FILE_SIZE_FORMAT'] : '&ndash;'),
					'file_cnt' => ($report['FILE_COUNT'] > 0 ? $report['FILE_COUNT'] : '&ndash;'),
					'disk' => ($report['DISK_SIZE'] > 0 ? $report['DISK_SIZE_FORMAT'] : '&ndash;'),
					'disk_cnt' => ($report['DISK_COUNT'] > 0 ? $report['DISK_COUNT'] : '&ndash;'),
					'activity' => ($report['ACTIVITY_SIZE'] > 0 ? $report['ACTIVITY_SIZE_FORMAT'] : (isset($report['ACTIVITY_NAN']) ? '&#x207F;/&#x2090;' : '&ndash;')),
					'activity_cnt' => ($report['ACTIVITY_COUNT'] > 0 ? $report['ACTIVITY_COUNT'] : (isset($report['ACTIVITY_NAN']) ? '&#x207F;/&#x2090;' : '&ndash;')),
					'event' => ($report['EVENT_SIZE'] > 0 ? $report['EVENT_SIZE_FORMAT'] : (isset($report['EVENT_NAN']) ? '&#x207F;/&#x2090;' : '&ndash;')),
					'event_cnt' => ($report['EVENT_COUNT'] > 0 ? $report['EVENT_COUNT'] : (isset($report['EVENT_NAN']) ? '&#x207F;/&#x2090;' : '&ndash;')),
					'percent' => $percent,
				),
				'actions' => $actions,
				'attrs' => array(
					"data-indicatorId" => $key
				),
			);
		}

		return $result;
	}

	/**
	 * Returns pastel color for css rules.
	 * @param bool $isOther Fixed grey color for other item.
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
	 * Returns queue's current step parameter.
	 *
	 * @return array|null
	 */
	public function getQueueStep()
	{
		if (isset($_SESSION[self::QUEUE_STEP]))
		{
			return $_SESSION[self::QUEUE_STEP];
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
		if (!isset($_SESSION[self::QUEUE_STEP]))
		{
			$_SESSION[self::QUEUE_STEP] = array();
		}
		$_SESSION[self::QUEUE_STEP][$key] = $value;
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
		if (!empty($_SESSION[self::QUEUE_STEP][$key]))
		{
			return $_SESSION[self::QUEUE_STEP][$key];
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
		unset($_SESSION[self::QUEUE_STEP]);
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
				$this->arParams['PATH_TO_CRM_VOLUME_'.mb_strtoupper($action)],
				$params
			);
			$path = str_replace('//', '/', $path);

			unset($params['action']);
		}
		else
		{
			$path = '';
		}

		return htmlspecialcharsbx($this->arParams['RELATIVE_PATH']). $path. (count($params) > 0 ? '?'.http_build_query($params) : '');
	}



	/**
	 * Returns queue list of action for full scanning.
	 *
	 * @return array
	 */
	private function queueScanActionList()
	{
		$queueList = array();

		$queueList[] = array(
			'action' => self::ACTION_CANCEL_TASKS,
		);
		$queueList[] = array(
			'action' => self::ACTION_PURIFY,
		);

		$indicatorList = Volume\Base::getListIndicator();

		$otherIndicatorList = Volume\Other::getSubIndicatorList();

		$componentCommandAlias = array(
			'MEASURE_ENTITY' => self::ACTION_MEASURE,
			'MEASURE_FILE' => self::ACTION_MEASURE_FILE,
			'MEASURE_ACTIVITY' => self::ACTION_MEASURE_ACTIVITY,
			'MEASURE_EVENT' => self::ACTION_MEASURE_EVENT,
		);

		/** @var Volume\IVolumeIndicator $indicatorType */
		foreach ($indicatorList as $indicatorType)
		{
			if (in_array($indicatorType, $otherIndicatorList))
			{
				continue;
			}

			//$indicatorId = $indicatorType::getIndicatorId();
			$indicator = $this->getIndicator($indicatorType);

			$queueList = array_merge($queueList, $indicator->getActionList($componentCommandAlias));
		}

		$queueList[] = array(
			'indicatorId' => Volume\Other::getIndicatorId(),
			'action' => self::ACTION_MEASURE,
		);

		return $queueList;
	}

}

