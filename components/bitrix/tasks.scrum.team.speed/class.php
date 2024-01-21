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
use Bitrix\Main\UI\Filter;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Tasks\Component\Scrum\TeamSpeed\BaseActionFilter;
use Bitrix\Tasks\Integration\Intranet\Settings;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Scrum\Form\EntityForm;
use Bitrix\Tasks\Scrum\Service\EntityService;
use Bitrix\Tasks\Scrum\Service\ItemService;
use Bitrix\Tasks\Scrum\Service\KanbanService;
use Bitrix\Tasks\Scrum\Service\SprintService;
use Bitrix\Tasks\Scrum\Utility\StoryPoints;
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
		$params['groupId'] = (is_numeric($params['groupId'] ?? null) ? (int) $params['groupId'] : 0);

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

			if (!$this->isScrumEnabled())
			{
				$this->includeComponentTemplate('scrum_disabled');

				return;
			}

			if (!$this->canReadGroupTasks($this->arResult['groupId']))
			{
				throw new SystemException(Loc::getMessage('TASKS_SCRUM_TEAM_SPEED_ACCESS_DENIED'));
			}

			$this->arResult['filterId'] = self::FILTER_ID . $this->arResult['groupId'];
			$this->arResult['filterFields'] = $this->getFilterFields();
			$this->arResult['filterPresets'] = $this->getFilterPresets();

			$filterOptions = new Filter\Options(
				self::FILTER_ID . $this->arResult['groupId'],
				$this->getFilterPresets()
			);

			$filterData = $filterOptions->getFilter($this->getFilterFields());

			$data = $this->getData($this->arResult['groupId'], $filterData);

			$this->arResult['chartData'] = $data['chartData'];
			$this->arResult['statsData'] = $data['statsData'];

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

		$data = $this->getData($groupId, $filterData);

		return [
			'chartData' => $data['chartData'],
			'statsData' => $data['statsData'],
		];
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

	private function isScrumEnabled(): bool
	{
		return (new Settings())->isToolAvailable(Settings::TOOLS['scrum']);
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
	private function getData(int $groupId, array $filterData): array
	{
		$chartData = [];

		$sprintService = new SprintService();
		$itemService = new ItemService();
		$kanbanService = new KanbanService();
		$storyPoints = new StoryPoints();

		$last = (int) (array_key_exists('last_to', $filterData) ? $filterData['last_to'] : 0);

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

		$order = ['DATE_END' => 'DESC'];

		$queryResult = (new EntityService())->getList($nav, $filter, $select, $order);

		$sprints = [];

		$n = 0;
		while ($sprintData = $queryResult->fetch())
		{
			if ($nav && (++$n > $nav->getPageSize()))
			{
				break;
			}

			$sprint = new EntityForm();

			$sprint->fillFromDatabase($sprintData);

			$sprints[] = $sprint;

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
				'sprintName' => $sprint->getName() . " ({$sprint->getId()})",
				'plan' => round(($completedPoints + $uncompletedPoints), 2),
				'done' => $completedPoints,
			];
		}

		return [
			'chartData' => array_reverse($chartData),
			'statsData' => $storyPoints->calculateStoryPointsStats($groupId, $sprints),
		];
	}

	private function getFilterFields(): array
	{
		return [
			'last' => [
				'id' => 'last',
				'name' => Loc::getMessage('TASKS_SCRUM_TEAM_SPEED_FILTER_LAST_LABEL'),
				'type' => 'number',
				'default' => true,
				'required' => true,
				'valueRequired' => true,
				'include' => [
					Filter\AdditionalNumberType::BEFORE_N,
				],
				'exclude' => [
					Filter\NumberType::SINGLE,
					Filter\NumberType::RANGE,
					Filter\NumberType::MORE,
					Filter\NumberType::LESS,
				],
				'messages' => [
					'MAIN_UI_FILTER__NUMBER_BEFORE_N' => Loc::getMessage(
						'TASKS_SCRUM_TEAM_SPEED_FILTER_LAST_FIELD_LABEL'
					),
				],
			],
		];
	}

	private function getFilterPresets(): array
	{
		return [
			'filter_last' => [
				'name' => Loc::getMessage('TASKS_SCRUM_TEAM_SPEED_FILTER_PRESET_LAST'),
				'fields' => [
					'last_numsel' => Filter\AdditionalNumberType::BEFORE_N,
					'last_from' => '',
					'last_to' => '5',
				],
				'default' => true,
			],
		];
	}

	private function getNav(int $pageSize): PageNavigation
	{
		$nav = new PageNavigation('team-speed-sprints');

		$nav->setCurrentPage(1);

		$nav->setPageSize($pageSize ?: 1);

		return $nav;
	}

	private function includeErrorTemplate(string $errorMessage, string $code = ''): void
	{
		$this->arResult['ERROR'] = $errorMessage;
		$this->arResult['ERROR_CODE'] = ($code ? $code : self::ERROR_UNKNOWN_SYSTEM_ERROR);

		$this->includeComponentTemplate('error');
	}
}