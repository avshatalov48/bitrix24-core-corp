<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Web\Uri;
use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Internals\Project\Filter\GridFilter;
use Bitrix\Tasks\Internals\Project\Order;
use Bitrix\Tasks\Internals\Project\Provider;
use Bitrix\Tasks\Internals\Project\UserOption\UserOptionTypeDictionary;
use Bitrix\Tasks\Internals\Task\ProjectUserOptionTable;
use Bitrix\Tasks\TourGuide;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\Error\Collection;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Helper\Analytics;

Loc::loadMessages(__FILE__);

/**
 * Class TasksProjectsComponent
 */
class TasksProjectsComponent extends CBitrixComponent implements Controllerable
{
	/** @var Collection */
	private $errors;
	/** @var GridFilter */
	private $filter;
	/** @var Order */
	private $order;
	/** @var Provider */
	private $provider;

	private function checkModules(): bool
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			$this->errors->add(
				'SOCIALNETWORK_MODULE_NOT_INSTALLED',
				'Socialnetwork module is not installed'
			);
		}

		return $this->errors->checkNoFatals();
	}

	private function checkPermissions(): bool
	{
		$currentUserId = $this->arResult['USER_ID'] = User::getId();
		$targetUserId = (int)$this->arParams['USER_ID'];

		if (!$currentUserId)
		{
			$this->errors->add(
				'ACCESS_DENIED',
				'Current user is not defined'
			);
		}

		if ($currentUserId !== $targetUserId)
		{
			$this->errors->add(
				'ACCESS_DENIED',
				'Access denied'
			);
		}

		return $this->errors->checkNoFatals();
	}

	private function checkParameters(): void
	{
		$this->arParams['PATH_TO_USER_TASKS_TASK'] = CComponentEngine::makePathFromTemplate(
			'/company/personal/user/#user_id#/tasks/task/#action#/0/',
			[
				'user_id' => $this->arParams['USER_ID'],
				'action' => 'edit',
			]
		);
		$pathToGroupCreate = CComponentEngine::makePathFromTemplate(
			($this->arParams['PATH_TO_GROUP_CREATE'] ?? '/company/personal/user/#user_id#/groups/create/'),
			['user_id' => $this->arParams['USER_ID']]
		);
		$this->arParams['PATH_TO_GROUP_CREATE'] =
			(new Uri($pathToGroupCreate))
				->addParams([
					'firstRow' => 'project',
					'refresh' => 'N',
				])
				->getUri()
		;
		$this->arResult['GRID_ID'] = $this->arParams['GRID_ID'];

		$this->arResult['isScrumList'] = $this->arParams['SCRUM'] === 'Y';

		$this->arParams['MARK_SECTION_PROJECTS_LIST'] =
			$this->arParams['MARK_SECTION_PROJECTS_LIST'] === 'Y'
				? 'Y'
				: 'N'
		;

		$this->arParams['MARK_SECTION_SCRUM_LIST'] =
			$this->arParams['MARK_SECTION_SCRUM_LIST'] === 'Y'
				? 'Y'
				: 'N'
		;
	}

	private function init(): void
	{
		$this->filter = new GridFilter(
			$this->arParams['USER_ID'],
			$this->arParams['GRID_ID'],
			['NAME_TEMPLATE' => $this->arParams['NAME_TEMPLATE']]
		);
		if ($this->arResult['isScrumList'] || $this->arParams['SCRUM'] === 'Y')
		{
			$this->filter->setIsScrum(true);
		}
		$this->order = new Order($this->arParams['GRID_ID']);
		$this->provider = new Provider(
			User::getId(),
			($this->arResult['isScrumList'] || $this->arParams['SCRUM'] === 'Y')
		);
	}

	private function doPreAction(): void
	{
		$this->init();

		if (!$this->request->isAjaxRequest())
		{
			$this->filter->resetFilter();
		}
	}

	private function getData(): void
	{
		$this->arResult['FILTERS'] = $this->filter->getFilterFields();
		$this->arResult['PRESETS'] = $this->filter->getPresets();

		$this->arParams['FILTER_DATA'] = $this->filter->getFilterData();

		$this->arResult['SORT'] = $this->order->getGridSorting();
		$this->arResult['GROUPS'] = $this->getGroups();

		if ($this->arResult['isScrumList'])
		{
			$grid = new Bitrix\Tasks\Grid\Scrum\Grid($this->arResult['GROUPS'], $this->arParams);
		}
		else
		{
			$grid = new Bitrix\Tasks\Grid\Project\Grid($this->arResult['GROUPS'], $this->arParams);
		}

		$this->arResult['GRID'] = $grid;
		$this->arResult['HEADERS'] = $this->arResult['GRID']->prepareHeaders();
		$this->arResult['STUB'] = $this->getStub();

		$preparedRows = $this->arResult['GRID']->prepareRows();

		$this->arResult['ROWS'] = [];
		foreach ($this->arResult['GROUPS'] as $id => $row)
		{
			$this->arResult['ROWS'][] = [
				'id' => $row['ID'],
				'columns' => $preparedRows[$id]['content'],
				'actions' => $preparedRows[$id]['actions'],
				'cellActions' => $preparedRows[$id]['cellActions'],
				'counters' => $preparedRows[$id]['counters'],
			];
		}
	}

	private function getStub(): array
	{
		if ($this->filter->isUserFilterApplied())
		{
			return [
				'title' => Loc::getMessage('TASKS_PROJECTS_GRID_STUB_NO_DATA_TITLE'),
				'description' => Loc::getMessage('TASKS_PROJECTS_GRID_STUB_NO_DATA_DESCRIPTION'),
			];
		}

		if ($this->arResult['isScrumList'])
		{
			return [
				'title' => Loc::getMessage('TASKS_SCRUM_GRID_STUB_TITLE'),
				'description' => Loc::getMessage('TASKS_SCRUM_GRID_STUB_DESCRIPTION'),
				'migrationTitle' => Loc::getMessage('TASKS_SCRUM_GRID_STUB_MIGRATION_TITLE'),
				'migrationButton' => Loc::getMessage('TASKS_SCRUM_GRID_STUB_MIGRATION_BUTTON'),
				'migrationOther' => Loc::getMessage('TASKS_SCRUM_GRID_STUB_MIGRATION_OTHER'),
			];
		}

		return [
			'title' => Loc::getMessage('TASKS_PROJECTS_GRID_STUB_TITLE'),
			'description' => Loc::getMessage('TASKS_PROJECTS_GRID_STUB_DESCRIPTION'),
		];
	}

	private function getCurrentPage(): int
	{
		if (isset($this->arParams['PAGE_NUMBER']) || isset($_REQUEST['page']))
		{
			$pageNum = (int)($this->arParams['PAGE_NUMBER'] ?? $_REQUEST['page']);

			return ($pageNum < 0 ? 1 : $pageNum);
		}

		return 1;
	}

	private function getSelect(): array
	{
		return [
			'ID',
			'NAME',
			'PROJECT_DATE_START',
			'PROJECT_DATE_FINISH',
			'IMAGE_ID',
			'AVATAR_TYPE',
			'NUMBER_OF_MODERATORS',
			'NUMBER_OF_MEMBERS',
			'OPENED',
			'CLOSED',
			'USER_GROUP_ID',
			'ACTIVITY_DATE',
			'IS_PINNED',
			'COUNTERS',
			'MEMBERS',
			// 'ACTIONS',
			'TAGS',
			'EFFICIENCY',
		];
	}

	private function doPostAction(): void
	{
		if (!$this->request->isAjaxRequest())
		{
			if (
				count($this->arResult['GROUPS']) > 0
				|| !\Bitrix\Socialnetwork\Helper\Workgroup::canCreate()
			)
			{
				$popupData = [];
				$showTour = false;
			}
			else
			{
				if ($this->arResult['isScrumList'])
				{
					/** @var TourGuide\FirstScrumCreation $firstScrumCreationTour */
					$firstScrumCreationTour = TourGuide\FirstScrumCreation::getInstance($this->arParams['USER_ID']);
					$popupData = $firstScrumCreationTour->getCurrentStepPopupData();
					$showTour = $firstScrumCreationTour->proceed();
				}
				else
				{
					/** @var TourGuide\FirstProjectCreation $firstProjectCreationTour */
					$firstProjectCreationTour = TourGuide\FirstProjectCreation::getInstance($this->arParams['USER_ID']);
					$popupData = $firstProjectCreationTour->getCurrentStepPopupData();
					$showTour = $firstProjectCreationTour->proceed();
				}
			}

			if ($this->arResult['isScrumList'])
			{
				$this->arResult['TOURS'] = [
					'firstScrumCreation' => [
						'popupData' => $popupData,
						'show' => $showTour,
					],
				];
			}
			else
			{
				$this->arResult['TOURS'] = [
					'firstProjectCreation' => [
						'popupData' => $popupData,
						'show' => $showTour,
					],
				];
			}

			if ($showTour)
			{
				$logger = Analytics::getInstance();
				$this->arResult['isScrumList']
					? $logger->onFirstScrumCreation()
					: $logger->onFirstProjectCreation();
			}
		}
	}

	public function executeComponent()
	{
		$this->arResult['ERRORS'] = [];

		if (Loader::includeModule('tasks'))
		{
			$this->errors = new Collection();

			if (
				$this->checkModules()
				&& $this->checkPermissions()
			)
			{
				$this->checkParameters();
				$this->doPreAction();
				$this->getData();
				$this->doPostAction();
			}

			$this->arResult['ERRORS'] = $this->errors->getArrayMeta();
		}
		else
		{
			$this->arResult['ERRORS'][] = [
				'CODE' => 'TASKS_MODULE_NOT_INSTALLED',
				'MESSAGE' => 'Tasks module is not installed',
			];
		}

		$this->includeComponentTemplate();
	}

	public function configureActions()
	{
		return [];
	}

	protected function listKeysSignedParameters()
	{
		return [
			'USER_ID',
			'GRID_ID',
			'PATH_TO_USER',
			'PATH_TO_USER_TASKS',
			'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW',
			'PATH_TO_USER_TASKS_TEMPLATES',
			'PATH_TO_USER_LEAVE_GROUP',
			'PATH_TO_USER_REQUEST_GROUP',
			'PATH_TO_USER_REQUESTS',
			'PATH_TO_GROUP',
			'PATH_TO_GROUP_CREATE',
			'PATH_TO_GROUP_EDIT',
			'PATH_TO_GROUP_DELETE',
			'PATH_TO_GROUP_TASKS',
			'PATH_TO_TASKS',
			'PATH_TO_TASKS_REPORT_CONSTRUCT',
			'PATH_TO_TASKS_REPORT_VIEW',
			'PATH_TO_REPORTS',
			'NAME_TEMPLATE',
			'SCRUM',
		];
	}

	private function checkRequirementsForAjaxCalls(): bool
	{
		if (!Loader::includeModule('tasks'))
		{
			return false;
		}

		$this->errors = new Collection();

		return $this->checkModules() && $this->checkPermissions();
	}

	private function initForAjaxCalls(): void
	{
		$this->init();
	}

	public function processActionAction(string $action, array $ids, array $data = []): ?array
	{
		if (!$this->checkRequirementsForAjaxCalls())
		{
			return null;
		}

		$this->initForAjaxCalls();

		foreach ($data as $key => $value)
		{
			if (mb_strpos($key, '~') === 0 || mb_strpos($key, '=') === 0)
			{
				unset($data[$key]);
			}
		}

		$result = [];

		switch ($action)
		{
			case 'pin':
				$this->provider->pin($ids);
				break;

			case 'unpin':
				$this->provider->unpin($ids);
				break;

			case 'request':
				$result = $this->request($ids);
				break;

			case 'addToArchive':
				$result = Group::addToArchive($ids);
				break;

			case 'removeFromArchive':
				$result = Group::removeFromArchive($ids);
				break;

			case 'update':
				$result = Group::update($ids, $data);
				break;

			case 'delete':
				$result = Group::delete($ids);
				break;

			default:
				break;
		}

		return $result;
	}

	private function request(array $ids): array
	{
		return [];
	}

	public function getPopupMembersAction(int $groupId, string $type, int $page): ?array
	{
		if (!$this->checkRequirementsForAjaxCalls())
		{
			return null;
		}

		$this->initForAjaxCalls();

		$members = [];

		$rolesMap = [
			'all' => [
				UserToGroupTable::ROLE_OWNER,
				UserToGroupTable::ROLE_MODERATOR,
				UserToGroupTable::ROLE_USER,
			],
			'heads' => [
				UserToGroupTable::ROLE_OWNER,
				UserToGroupTable::ROLE_MODERATOR,
			],
			'members' => [
				UserToGroupTable::ROLE_USER,
			],
			'scrumTeam' => [
				UserToGroupTable::ROLE_OWNER,
				UserToGroupTable::ROLE_MODERATOR,
			],
		];
		$limit = 10;

		$query = $this->provider->getPrimaryUsersQuery([$groupId]);
		$query
			->whereIn('ROLE', $rolesMap[$type])
			->setLimit($limit)
			->setOffset(($page - 1) * $limit)
		;

		$isScrumMembers = ($type === 'scrumTeam');
		if ($isScrumMembers)
		{
			$query->addSelect('GROUP.SCRUM_MASTER_ID', 'SCRUM_MASTER_ID');
		}

		$imageIds = [];
		$resultMembers = [];

		$result = $query->exec();
		while ($member = $result->fetch())
		{
			$imageIds[$member['USER_ID']] = $member['PERSONAL_PHOTO'];
			$resultMembers[] = $member;
		}

		$imageIds = array_filter(
			$imageIds,
			static function ($id) {
				return (int)$id > 0;
			}
		);
		$avatars = UI::getAvatars($imageIds);

		foreach ($resultMembers as $member)
		{
			$id = $member['USER_ID'];
			$members[] = [
				'ID' => $id,
				'PHOTO' => $avatars[$imageIds[$id]],
				'HREF' => CComponentEngine::MakePathFromTemplate($this->arParams['PATH_TO_USER'], ['user_id' => $id]),
				'FORMATTED_NAME' => CUser::FormatName($this->arParams['NAME_TEMPLATE'], $member, true),
				'ROLE' => $isScrumMembers ? $this->getScrumRole($member) : $member['ROLE'],
			];

			if ($isScrumMembers)
			{
				if (
					$member['USER_ID'] === $member['SCRUM_MASTER_ID']
					&& $member['ROLE'] === UserToGroupTable::ROLE_OWNER
				)
				{
					$members[] = [
						'ID' => $id,
						'PHOTO' => $avatars[$imageIds[$id]],
						'HREF' => CComponentEngine::makePathFromTemplate(
							$this->arParams['PATH_TO_USER'],
							['user_id' => $id]
						),
						'FORMATTED_NAME' => CUser::formatName($this->arParams['NAME_TEMPLATE'], $member, true),
						'ROLE' => 'M',
					];
				}
			}
		}

		return $members;
	}

	public function checkExistenceAction(array $groupIds): ?array
	{
		if (!$this->checkRequirementsForAjaxCalls())
		{
			return null;
		}

		$this->initForAjaxCalls();

		return $this->getGroupsData($groupIds, $this->getSelect());
	}

	public function prepareGridRowsAction(array $groupIds, array $data = []): array
	{
		if (!$this->checkRequirementsForAjaxCalls())
		{
			return [];
		}

		$this->initForAjaxCalls();

		$select = $this->getSelect();
		$groups = $this->getGroupsData($groupIds, $select);
		if (!empty($groups))
		{
			if (in_array('IMAGE_ID', $select, true))
			{
				$groups = $this->provider->fillAvatars($groups);
			}
			if (in_array('EFFICIENCY', $select, true))
			{
				$groups = $this->provider->fillEfficiencies($groups);
			}
			if (in_array('MEMBERS', $select, true))
			{
				$groups = $this->provider->fillMembers($groups);
			}
			if (in_array('TAGS', $select, true))
			{
				$groups = $this->provider->fillTags($groups);
			}
			if (in_array('COUNTERS', $select, true))
			{
				$groups = $this->provider->fillCounters($groups);
			}
		}

		if ($this->provider->getIsScrum())
		{
			return (new Bitrix\Tasks\Grid\Scrum\Grid($groups, $this->arParams))->prepareRows();
		}

		return (new Bitrix\Tasks\Grid\Project\Grid($groups, $this->arParams))->prepareRows();
	}

	private function checkGroupId(int $groupId): bool
	{
		$isScrumList = ($this->arParams['SCRUM'] === 'Y');

		$group = Workgroup::getById($groupId);
		$isScrumProject = $group && $group->isScrumProject();

		return (
			($isScrumList && $isScrumProject)
			|| (!$isScrumList && !$isScrumProject)
		);
	}

	private function getScrumRole(array $member): string
	{
		if (
			$member['USER_ID'] === $member['SCRUM_MASTER_ID']
			&& $member['ROLE'] !== UserToGroupTable::ROLE_OWNER
		)
		{
			return 'M';
		}
		else
		{
			return $member['ROLE'];
		}
	}
}