<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Scrum\Service\ItemService;
use Bitrix\Tasks\Scrum\Service\KanbanService;
use Bitrix\Tasks\Scrum\Service\SprintService;
use Bitrix\Tasks\Scrum\Service\TaskService;
use Bitrix\Tasks\Scrum\Utility\BurnDownChart;
use Bitrix\Tasks\Scrum\Utility\StoryPoints;
use Bitrix\Tasks\Util;

class TasksScrumBurnDownComponent extends \CBitrixComponent implements Controllerable, Errorable
{
	const ERROR_UNKNOWN_SYSTEM_ERROR = 'TASKS_TSBD_01';

	private $application;
	private $userId;
	private $errorCollection;

	public function __construct($component = null)
	{
		parent::__construct($component);

		global $APPLICATION;
		$this->application = $APPLICATION;

		$this->errorCollection = new ErrorCollection();
	}

	public function onIncludeComponentLang()
	{
		$this->includeComponentLang(basename(__FILE__));
		Loc::loadMessages(__FILE__);
	}

	public function onPrepareComponentParams($params)
	{
		$params['groupId'] = (is_numeric($params['groupId'] ?? null) ? (int) $params['groupId'] : 0);
		$params['sprintId'] = (is_numeric($params['sprintId'] ?? null) ? (int) $params['sprintId'] : 0);

		return $params;
	}

	public function executeComponent()
	{
		try
		{
			$this->checkModules();

			$this->arResult['groupId'] = (int) $this->arParams['groupId'];

			$this->setTitle();
			$this->init();

			if (!$this->canReadGroupTasks($this->arResult['groupId']))
			{
				throw new SystemException(Loc::getMessage('TASKS_SCRUM_BURN_DOWN_ACCESS_DENIED'));
			}

			$chartData = $this->getChartData($this->arParams['sprintId']);

			$this->arResult['sprint'] = $chartData['sprint'];
			$this->arResult['chart'] = $chartData['chart'];

			$this->includeComponentTemplate();
		}
		catch (SystemException $exception)
		{
			$this->includeErrorTemplate($exception->getMessage());
		}
	}

	public function configureActions()
	{
		return [];
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	public function changeChartAction(int $groupId, int $sprintId)
	{
		$this->checkModules();

		$this->init();

		if (!$this->canReadGroupTasks($groupId))
		{
			$this->errorCollection->setError(
				new Error(Loc::getMessage('TASKS_SCRUM_BURN_DOWN_ACCESS_DENIED'))
			);

			return null;
		}

		$chartData = $this->getChartData($sprintId);

		return [
			'sprint' => $chartData['sprint'],
			'chart' => $chartData['chart']
		];
	}

	/**
	 * @throws SystemException
	 */
	private function checkModules()
	{
		try
		{
			if (!Loader::includeModule('tasks'))
			{
				throw new SystemException('Cannot connect required modules.');
			}
			if (!Loader::includeModule('socialnetwork'))
			{
				throw new SystemException('Cannot connect required modules.');
			}
		}
		catch (LoaderException $exception)
		{
			throw new SystemException('Cannot connect required modules.');
		}
	}

	private function setTitle()
	{
		$this->application->setTitle(Loc::getMessage('TASKS_SCRUM_BURN_DOWN_TITLE'));
	}

	private function init()
	{
		$this->userId = Util\User::getId();
	}

	private function canReadGroupTasks(int $groupId): bool
	{
		return Group::canReadGroupTasks($this->userId, $groupId);
	}

	private function getChartData(int $inputSprintId): array
	{
		$sprintService = new SprintService();

		$sprint = $sprintService->getSprintById($inputSprintId);

		$kanbanService = new KanbanService();
		$itemService = new ItemService();
		$taskService = new TaskService($this->userId);

		$completedTaskIds = $kanbanService->getFinishedTaskIdsInSprint($sprint->getId());
		$uncompletedTaskIds = $kanbanService->getUnfinishedTaskIdsInSprint($sprint->getId());
		$taskIds = array_merge($completedTaskIds, $uncompletedTaskIds);

		$itemsStoryPoints = $itemService->getItemsStoryPointsBySourceId($taskIds);

		$storyPointsService = new StoryPoints();
		$sumStoryPoints = $storyPointsService->calculateSumStoryPoints($itemsStoryPoints);

		$calendar = new Util\Calendar();
		$sprintRanges = $sprintService->getSprintRanges($sprint, $calendar);

		$currentDateEnd = $sprint->getDateEnd();

		if ($sprint->isActiveSprint())
		{
			$currentDateTime = new Datetime();
			$sprint->setDateEnd(
				$currentDateEnd->getTimestamp() > $currentDateTime->getTimestamp()
					? $currentDateTime
					: $currentDateEnd
			);
			$sprintRangesForRemainingData = $sprintService->getSprintRanges($sprint, $calendar);
		}
		else
		{
			$sprintRangesForRemainingData = $sprintRanges;
		}

		$completedTasksMap = $sprintService->getCompletedTasksMap(
			$sprintRanges,
			$taskService,
			$completedTaskIds
		);
		$completedStoryPointsMap = $sprintService->getCompletedStoryPointsMap(
			$sumStoryPoints,
			$completedTasksMap,
			$itemsStoryPoints
		);

		$burnDownChart = new BurnDownChart();

		$idealData = $burnDownChart->prepareIdealBurnDownChartData($sumStoryPoints, $sprintRanges);

		$remainingData = $burnDownChart->prepareRemainBurnDownChartData(
			$sumStoryPoints,
			$sprintRangesForRemainingData,
			$completedStoryPointsMap
		);

		$sprintData = $sprint->toArray();
		$sprintData['dateStartFormatted'] = $sprint->getDateStart()->format(Bitrix\Main\Type\Date::getFormat());
		$sprintData['dateEndFormatted'] = $currentDateEnd->format(Bitrix\Main\Type\Date::getFormat());

		return [
			'sprint' => $sprintData,
			'chart' => array_merge($idealData, $remainingData),
		];
	}

	private function includeErrorTemplate(string $errorMessage, string $code = ''): void
	{
		$this->arResult['ERROR'] = $errorMessage;
		$this->arResult['ERROR_CODE'] = ($code ? $code : self::ERROR_UNKNOWN_SYSTEM_ERROR);

		$this->includeComponentTemplate('error');
	}
}