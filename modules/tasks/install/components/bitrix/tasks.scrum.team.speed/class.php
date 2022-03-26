<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\Filter;
use Bitrix\Main\UI\Filter\DateType;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Tasks\Component\Scrum\TeamSpeed\BaseActionFilter;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Scrum\Form\EntityForm;
use Bitrix\Tasks\Scrum\Service\EntityService;
use Bitrix\Tasks\Scrum\Service\ItemService;
use Bitrix\Tasks\Scrum\Service\KanbanService;
use Bitrix\Tasks\Scrum\Service\SprintService;
use Bitrix\Tasks\Util;

require_once __DIR__ . '/baseactionfilter.php';

class TasksScrumTeamSpeedComponent extends \CBitrixComponent implements Controllerable, Errorable
{
	const ERROR_UNKNOWN_SYSTEM_ERROR = 'TASKS_TSTSC_01';

	const FILTER_ID = 'tasks_scrum_team_speed_filter_';

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
		$params['groupId'] = (is_numeric($params['groupId']) ? (int) $params['groupId'] : 0);

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
				throw new SystemException(Loc::getMessage('TASKS_SCRUM_TEAM_SPEED_ACCESS_DENIED'));
			}

			$this->arResult['filterId'] = self::FILTER_ID . $this->arResult['groupId'];
			$this->arResult['filterFields'] = $this->getFilterFields();
			$this->arResult['filterPresets'] = $this->getFilterPresets();

			$filterData = [];
			if (false) // todo remove later
			{
				$filterOptions = new Filter\Options(
					self::FILTER_ID . $this->arResult['groupId'],
					$this->getFilterPresets()
				);

				$filterData = $filterOptions->getFilter($this->getFilterFields());
			}

			$this->arResult['chartData'] = $this->getChartData($this->arResult['groupId'], $filterData);

			$this->includeComponentTemplate();
		}
		catch (SystemException $exception)
		{
			$this->includeErrorTemplate($exception->getMessage());
		}
	}

	public function configureActions()
	{
		$groupId = (int) $this->arParams['groupId'];

		$basePrefilters = [
			new BaseActionFilter($groupId),
		];

		return [
			'applyFilter' => [
				'prefilters' => $basePrefilters
			]
		];
	}

	protected function listKeysSignedParameters()
	{
		return [
			'groupId',
		];
	}

	public function applyFilterAction()
	{
		$groupId = (int) $this->arParams['groupId'];

		$filterOptions = new Filter\Options(self::FILTER_ID . $groupId, $this->getFilterPresets());

		$filterData = $filterOptions->getFilter($this->getFilterFields());

		return $this->getChartData($groupId, $filterData);
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	/**
	 * @throws SystemException
	 */
	private function checkModules()
	{
		try
		{
			if (
				!Loader::includeModule('tasks')
				|| !Loader::includeModule('socialnetwork')
			)
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
		$this->application->setTitle(Loc::getMessage('TASKS_SCRUM_TEAM_SPEED_TITLE'));
	}

	private function init()
	{
		$this->userId = Util\User::getId();
	}

	private function canReadGroupTasks(int $groupId): bool
	{
		return Group::canReadGroupTasks($this->userId, $groupId);
	}

	/**
	 * @param int $groupId
	 * @param array $filterData
	 * @return array
	 * @throws \Bitrix\Main\ObjectException
	 */
	private function getChartData(int $groupId, array $filterData): array
	{
		$chartData = [];

		$sprintService = new SprintService();
		$itemService = new ItemService();
		$kanbanService = new KanbanService();

		$last = (array_key_exists('last', $filterData) ? $filterData['last'] : '');
		$period = (array_key_exists('period_from', $filterData) ? $filterData['period_from'] : '');

		$nav = $last ? $this->getNav($last) : null;

		$select = [
			'ID',
			'NAME',
		];

		$filter = [
			'GROUP_ID' => $groupId,
			'=ENTITY_TYPE' => EntityForm::SPRINT_TYPE,
			'=STATUS' => EntityForm::SPRINT_COMPLETED,
		];
		if ($period)
		{
			$filter['>=DATE_END'] = new DateTime($period, Date::convertFormatToPhp(FORMAT_DATETIME));
		}

		$order = ['DATE_END' => 'DESC'];

		$queryResult = (new EntityService())->getList($nav, $filter, $select, $order);

		$n = 0;
		while ($sprintData = $queryResult->fetch())
		{
			$n++;
			if ($nav && $n > $nav->getPageSize())
			{
				break;
			}

			$sprint = new EntityForm();

			$sprint->fillFromDatabase($sprintData);

			$completedPoints = $sprintService->getCompletedStoryPoints(
				$sprint,
				$kanbanService,
				$itemService
			);

			$uncompletedPoints = $sprintService->getUnCompletedStoryPoints(
				$sprint,
				$kanbanService,
				$itemService
			);

			$chartData[] = [
				'sprintName' => $sprint->getName(),
				'plan' => round(($completedPoints + $uncompletedPoints), 2),
				'done' => $completedPoints,
			];
		}

		return array_reverse($chartData);
	}

	private function getFilterFields(): array
	{
		return [
			'last' => [
				'id' => 'last',
				'name' => Loc::getMessage('TASKS_SCRUM_TEAM_SPEED_FILTER_LAST'),
				'type' => 'list',
				'items' => $this->getAvailablePeriods(),
				'default' => true,
				'required' => true,
				'valueRequired' => true,
			],
			'period' => [
				'id' => 'period',
				'name' => Loc::getMessage('TASKS_SCRUM_TEAM_SPEED_FILTER_PERIOD'),
				'type' => 'date',
				'exclude' => [
					DateType::CURRENT_DAY,
					DateType::CURRENT_WEEK,
					DateType::CURRENT_MONTH,
					DateType::CURRENT_QUARTER,
					DateType::YESTERDAY,
					DateType::TOMORROW,
					DateType::NEXT_DAYS,
					DateType::LAST_7_DAYS,
					DateType::LAST_WEEK,
					DateType::LAST_MONTH,
					DateType::RANGE,
					DateType::NEXT_WEEK,
					DateType::NEXT_MONTH,
					DateType::MONTH,
					DateType::QUARTER,
					DateType::YEAR,
					DateType::EXACT,
				]
			],
		];
	}

	private function getFilterPresets(): array
	{
		return [
			'filter_last' => [
				'name' => Loc::getMessage('TASKS_SCRUM_TEAM_SPEED_FILTER_PRESET_LAST'),
				'fields' => [
					'last' => 'last_10',
				],
				'default' => true,
			],
		];
	}

	private function getAvailablePeriods(): array
	{
		return [
			'last_3' => Loc::getMessage('TASKS_SCRUM_TEAM_SPEED_FILTER_PERIOD_LAST_3'),
			'last_10' => Loc::getMessage('TASKS_SCRUM_TEAM_SPEED_FILTER_PERIOD_LAST_10'),
			'last_15' => Loc::getMessage('TASKS_SCRUM_TEAM_SPEED_FILTER_PERIOD_LAST_15'),
		];
	}

	private function getNav(string $last): PageNavigation
	{
		$nav = new PageNavigation('team-speed-sprints');

		$nav->setCurrentPage(1);

		$pageSize = (int) mb_substr($last, 5, 2);

		$nav->setPageSize($pageSize);

		return $nav;
	}

	private function includeErrorTemplate(string $errorMessage, string $code = ''): void
	{
		$this->arResult['ERROR'] = $errorMessage;
		$this->arResult['ERROR_CODE'] = ($code ? $code : self::ERROR_UNKNOWN_SYSTEM_ERROR);

		$this->includeComponentTemplate('error');
	}
}