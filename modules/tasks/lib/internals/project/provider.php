<?php

namespace Bitrix\Tasks\Internals\Project;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Filter\Factory;
use Bitrix\Main\Filter\UserDataProvider;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Socialnetwork\Component\WorkgroupList;
use Bitrix\Socialnetwork\Component\WorkgroupList\RuntimeFieldsManager;
use Bitrix\Socialnetwork\Component\WorkgroupList\TasksCounter;
use Bitrix\Socialnetwork\Helper\Workgroup;
use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\Socialnetwork\WorkgroupFavoritesTable;
use Bitrix\Socialnetwork\WorkgroupPinTable;
use Bitrix\Socialnetwork\WorkgroupSiteTable;
use Bitrix\Socialnetwork\WorkgroupTable;
use Bitrix\Socialnetwork\WorkgroupTagTable;
use Bitrix\Tasks\Internals\Counter\Template\ProjectCounter;
use Bitrix\Tasks\Internals\Counter\Template\ScrumCounter;
use Bitrix\Tasks\Internals\Effective;
use Bitrix\Tasks\Internals\Project\UserOption\UserOptionTypeDictionary;
use Bitrix\Tasks\Internals\Task\ProjectLastActivityTable;
use Bitrix\Tasks\Internals\Task\ProjectUserOptionTable;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\User;

class Provider
{
	private int $userId;
	private bool $isScrum;
	private string $mode;
	private RuntimeFieldsManager $runtimeFieldsManager;

	public function __construct(int $userId = 0, string $mode = '')
	{
		$this->userId = ($userId ?: User::getId());
		$this->isScrum = false;
		$this->mode = $mode;
		$this->runtimeFieldsManager = new RuntimeFieldsManager();
	}

	private function getFieldsList(): array
	{
		return [
			'ID',
			'NAME',
			'PROJECT',
			'SCRUM_MASTER_ID',
			'CLOSED',
			'SCRUM',
			'LANDING',
			'PROJECT_DATE_START',
			'PROJECT_DATE_FINISH',
			'ROLE',
			'DATE_RELATION',
			'OWNER_ID',
			'MEMBER_ID',
			'TAG',
			'FAVORITES',
			'DATE_CREATE',
			'DATE_UPDATE',
			'DATE_ACTIVITY',
			'DATE_VIEW',
			'NUMBER_OF_MEMBERS',
			'MEMBERS',
			'TAGS',
			'PRIVACY_TYPE',
			'EFFICIENCY',
			'ACTIVITY_DATE',
		];
	}

	private function getAvailableEntityFields(): array
	{
		return [
			'ID',
			'NAME',
			'OPENED',
			'CLOSED',
			'VISIBLE',
			'PROJECT',
			'SCRUM_MASTER_ID',
			'LANDING',
			'PROJECT_DATE_START',
			'PROJECT_DATE_FINISH',
			'SEARCH_INDEX',
			'OWNER_ID',
			'DATE_CREATE',
			'DATE_UPDATE',
			'DATE_ACTIVITY',
			'NUMBER_OF_MEMBERS',
			'IMAGE_ID',
			'AVATAR_TYPE',
			'SCRUM',
			'ACTIVITY_DATE',
			'IS_PINNED',
		];
	}

	public function prepareQuerySelect(array $select): array
	{
		$allowedFields = [
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
			'VISIBLE',
			'USER_GROUP_ID',
			'ACTIVITY_DATE',
			'IS_PINNED',
		];
		$prepared = array_intersect($select, $allowedFields);

		if (in_array('USER_GROUP_ID', $prepared, true))
		{
			$prepared['USER_GROUP_ID'] = 'CONTEXT_RELATION.ID';
			unset($prepared[array_search('USER_GROUP_ID', $prepared, true)]);
		}
		if (in_array('IS_PINNED', $prepared, true))
		{
			$prepared[] = new ExpressionField(
				'IS_PINNED',
				ProjectUserOptionTable::getSelectExpression($this->userId, UserOptionTypeDictionary::OPTION_PINNED),
				['ID', 'CONTEXT_RELATION.USER_ID']
			);
			unset($prepared[array_search('IS_PINNED', $prepared, true)]);
		}

		return $prepared;
	}

	/**
	 * @param array $select
	 * @return Query
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public function getPrimaryProjectsQuery(array $select): Query
	{
		$query = WorkgroupTable::query();
		$query
			->setSelect($select)
			->registerRuntimeField(
				new Reference(
					'CONTEXT_RELATION',
					UserToGroupTable::getEntity(),
					Join::on('this.ID', 'ref.GROUP_ID')->where('ref.USER_ID', $this->userId),
					['join_type' => 'left']
				)
			)
			->registerRuntimeField(
				'PLA',
				new ReferenceField(
					'PLA',
					ProjectLastActivityTable::getEntity(),
					Join::on('this.ID', 'ref.PROJECT_ID'),
					['join_type' => 'left']
				)
			)
			->registerRuntimeField(
				null,
				new ExpressionField('ACTIVITY_DATE', 'IFNULL(%s, %s)', ['PLA.ACTIVITY_DATE', 'DATE_UPDATE'])
			)
			->registerRuntimeField(
				new Reference(
					'SITE',
					WorkgroupSiteTable::getEntity(),
					Join::on('this.ID', 'ref.GROUP_ID'),
					['join_type' => 'inner']
				)
			)
			->registerRuntimeField(
				'SCRUM',
				new \Bitrix\Main\ORM\Fields\ExpressionField(
					'SCRUM',
					"(CASE WHEN %s = 'Y' AND %s > 0 THEN 'Y' ELSE 'N' END)",
					['PROJECT', 'SCRUM_MASTER_ID']
				)
			)
			->registerRuntimeField(
				new Reference(
					'FAVORITES',
					WorkgroupFavoritesTable::getEntity(),
					Join::on('this.ID', 'ref.GROUP_ID')->where('ref.USER_ID', $this->userId),
					['join_type' => 'left']
				)
			)
			->registerRuntimeField(
				new Reference(
					'PIN',
					WorkgroupPinTable::getEntity(),
					Join::on('this.ID', 'ref.GROUP_ID')
						->where('ref.USER_ID', $this->userId)
						->where('ref.CONTEXT', $this->mode)
					,
					['join_type' => 'left']
				)
			)
			->registerRuntimeField(
				'IS_PINNED',
				new ExpressionField(
					'IS_PINNED',
					WorkgroupPinTable::getSelectExpression(),
					['ID', 'PIN.USER_ID', 'PIN.CONTEXT']
				)
			)
		;

		$this->runtimeFieldsManager->add('CONTEXT_RELATION');
		$this->runtimeFieldsManager->add('SITE');
		$this->runtimeFieldsManager->add('SCRUM');
		$this->runtimeFieldsManager->add('FAVORITES');
		$this->runtimeFieldsManager->add('PIN');
		$this->runtimeFieldsManager->add('IS_PINNED');

		return $query;
	}

	public function getQueryWithFilter(Query $query, array $filter, string $presetId): Query
	{
		$filterValues = $filter;

		if (array_key_exists('ID', $filterValues))
		{
			$ids = (is_array($filterValues['ID']) ? $filterValues['ID'] : [$filterValues['ID']]);
			$ids = array_map('intval', $ids);
			$ids = array_filter($ids);

			if (!empty($ids))
			{
				count($ids) > 1
					? $query->whereIn('ID', $ids)
					: $query->where('ID', $ids[0])
				;
			}
			unset($filterValues['ID']);
		}

		if ($presetId)
		{
			$filterId = $this->getFilterId();
			$presets = $this->getPresets();

			if (array_key_exists($presetId, $presets))
			{
				$filterOptions = new Options($filterId, $presets);
				$filterSettings = (
					$filterOptions->getFilterSettings($presetId)
					?? $filterOptions->getDefaultPresets()[$presetId]
				);
				$filterValues = array_merge(
					$filterValues,
					Options::fetchFieldValuesFromFilterSettings($filterSettings, [], $this->getFilterSourceFields())
				);
			}
		}

		return $this->addQueryFilter($query, $filterValues);
	}

	/**
	 * @param Query $query
	 * @param array $filterValues
	 * @return Query
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	private function addQueryFilter(Query $query, array $filterValues): Query
	{
		if (!\CSocNetUser::isCurrentUserModuleAdmin())
		{
			$query->addFilter(
				null,
				[
					'LOGIC' => 'OR',
					'=VISIBLE' => 'Y',
					'<=CONTEXT_RELATION.ROLE' => UserToGroupTable::ROLE_USER,
				]
			);
		}

		$filterManager = new WorkgroupList\FilterManager(
			$query,
			$this->runtimeFieldsManager,
			[
				'fieldsList' => $this->getFieldsList(),
				'gridFilter' => $filterValues,
				'currentUserId' => $this->userId,
				'contextUserId' => $this->userId,
				'mode' => $this->mode,
				'hasAccessToTasksCounters' => $this->getHasAccessToTasksCounters(),
			]
		);
		$filter = $filterManager->getFilter();
		$siteId = SITE_ID;
		if (
			Loader::includeModule('extranet')
			&& !\CExtranet::IsIntranetUser($siteId, $this->userId)
		)
		{
			$filter['=SITE.SITE_ID'] = \CExtranet::GetExtranetSiteID();
		}

		foreach ($filter as $fieldName => $value)
		{
			if (
				$fieldName === '=MEMBER_ID'
				&& (int)$value > 0
			)
			{
				$query->registerRuntimeField(
					new Reference(
						'MEMBER',
						UserToGroupTable::class,
						\Bitrix\Main\ORM\Query\Join::on('this.ID', 'ref.GROUP_ID'),
						['join_type' => 'INNER']
					)
				);
				$this->runtimeFieldsManager->add('MEMBER');

				$query->addFilter('=MEMBER.USER_ID', (int)$value);
				$query->addFilter('<=MEMBER.ROLE', UserToGroupTable::ROLE_USER);

				unset($filter[$fieldName]);
				continue;
			}

			if (
				$fieldName === 'INCLUDED_COUNTER'
				&& $this->runtimeFieldsManager->has('TASKS_COUNTER')
			)
			{
				$query->where(
					Query::filter()
						->whereNotNull('CONTEXT_RELATION.ID')
						->whereIn('CONTEXT_RELATION.ROLE', UserToGroupTable::getRolesMember())
				);

				$condition = Query::filter()->whereIn('TASKS_COUNTER.TYPE', $value);

				if ($this->runtimeFieldsManager->has('EXCLUDED_COUNTER_EXISTS'))
				{
					$condition->whereNull('EXCLUDED_COUNTER_EXISTS');
				}

				$query->where($condition);

				unset($filter[$fieldName]);
				continue;
			}

			if (
				$fieldName === '%=TAG'
				&& (string)$value !== ''
			)
			{
				$query->registerRuntimeField(
					new Reference(
						'TAG',
						WorkgroupTagTable::class,
						Join::on('this.ID', 'ref.GROUP_ID'),
						['join_type' => 'INNER']
					)
				);
				$this->runtimeFieldsManager->add('TAG');

				$query->addFilter('%=TAG.NAME', (string)$value);

				unset($filter[$fieldName]);
				continue;
			}

			if (
				$fieldName === '=SCRUM'
				&& (string)$value !== ''
				&& $this->runtimeFieldsManager->has('SCRUM')
			)
			{
				$query->addFilter('=SCRUM', (string)$value);

				unset($filter[$fieldName]);
				continue;
			}

			if (!$this->checkQueryFieldName($fieldName))
			{
				continue;
			}

			$query->addFilter($fieldName, $value);
		}

		return $query;
	}

	private function getHasAccessToTasksCounters(): bool
	{
		return TasksCounter::getAccessToTasksCounters([
			'mode' => $this->mode,
			'contextUserId' => $this->userId,
		]);
	}

	private function checkQueryFieldName(string $fieldName = ''): bool
	{
		$fieldName = trim($fieldName, '!=<>%*');

		return (
			in_array($fieldName, $this->getAvailableEntityFields())
			|| mb_strpos($fieldName, '.') !== false
		);
	}

	public function fillAvatars(array $projects, $mode = 'web'): array
	{
		foreach (array_keys($projects) as $id)
		{
			$projects[$id]['IMAGE'] = '';
		}

		$imageIds = array_filter(
			array_column($projects, 'IMAGE_ID', 'ID'),
			static function ($id) {
				return (int)$id > 0;
			}
		);

		$avatars = UI::getAvatars($imageIds);
		$imageIds = array_flip($imageIds);

		foreach ($imageIds as $imageId => $projectId)
		{
			$projects[$projectId]['IMAGE'] = $avatars[$imageId];
		}

		$avatarTypes = Workgroup::getAvatarTypes();
		foreach ($projects as $id => $project)
		{
			if (
				$project['IMAGE_ID'] === null
				&& $mode === 'mobile'
				&& array_key_exists($project['AVATAR_TYPE'], $avatarTypes)
			)
			{
				$projects[$id]['IMAGE'] = $avatarTypes[$project['AVATAR_TYPE']]['mobileUrl'];
			}
		}

		return $projects;
	}

	public function fillEfficiencies(array $projects): array
	{
		$efficiencies = Effective::getAverageEfficiencyForGroups(
			null,
			null,
			0,
			array_keys($projects)
		);

		foreach ($projects as $groupId => $group)
		{
			$projects[$groupId]['EFFICIENCY'] = ($efficiencies[$groupId] ?: 0);
		}

		return $projects;
	}

	public function fillTags(array $projects): array
	{
		$tags = [];

		$res = WorkgroupTagTable::getList([
			'select' => ['GROUP_ID', 'NAME'],
			'filter' => [
				'GROUP_ID' => array_keys($projects),
				'GROUP.ACTIVE' => 'Y',
			],
		]);
		while ($tag = $res->fetch())
		{
			$tags[$tag['GROUP_ID']][] = $tag['NAME'];
		}

		foreach (array_keys($projects) as $projectId)
		{
			$projects[$projectId]['TAGS'] = $tags[$projectId];
		}

		return $projects;
	}

	public function fillCounters(array $projects): array
	{
		$projectCounter = $this->isScrum ? new ScrumCounter($this->userId) : new ProjectCounter($this->userId);

		foreach (array_keys($projects) as $projectId)
		{
			$projects[$projectId]['COUNTER'] = $projectCounter->getRowCounter($projectId);
		}

		return $projects;
	}

	public function fillMembers(array $projects): array
	{
		$projectIds = array_keys($projects);
		$members = array_fill_keys($projectIds, []);

		$query = $this->getPrimaryUsersQuery($projectIds);
		$query
			->whereIn(
				'ROLE',
				[
					UserToGroupTable::ROLE_OWNER,
					UserToGroupTable::ROLE_MODERATOR,
					UserToGroupTable::ROLE_USER,
					UserToGroupTable::ROLE_REQUEST,
				]
			)
		;

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

		$scrumMasters = [];
		foreach ($projectIds as $projectId)
		{
			$group = \Bitrix\Socialnetwork\Item\Workgroup::getById($projectId);
			if ($group && $group->isScrumProject())
			{
				$scrumMasters[$projectId] = (int) $group->getScrumMaster();
			}
		}

		foreach ($resultMembers as $member)
		{
			$memberId = (int) $member['USER_ID'];
			$projectId = (int) $member['GROUP_ID'];

			$isScrumProject = array_key_exists($projectId, $scrumMasters);

			$isOwner = ($member['ROLE'] === UserToGroupTable::ROLE_OWNER);
			$isModerator = ($member['ROLE'] === UserToGroupTable::ROLE_MODERATOR);
			$isScrumMaster = ($isScrumProject && $scrumMasters[$projectId] === $memberId);
			$isAccessRequesting = ($member['ROLE'] === UserToGroupTable::ROLE_REQUEST);
			$isAccessRequestingByMe = (
				$isAccessRequesting && $member['INITIATED_BY_TYPE'] === UserToGroupTable::INITIATED_BY_USER
			);
			$isHead = ($isOwner || $isModerator);

			$members[$projectId][($isHead ? 'HEADS' : 'MEMBERS')][$memberId] = [
				'ID' => $memberId,
				'IS_OWNER' => ($isOwner ? 'Y' : 'N'),
				'IS_MODERATOR' => ($isModerator ? 'Y' : 'N'),
				'IS_SCRUM_MASTER' => ($isScrumMaster ? 'Y' : 'N'),
				'IS_ACCESS_REQUESTING' => ($isAccessRequesting ? 'Y' : 'N'),
				'IS_ACCESS_REQUESTING_BY_ME' => ($isAccessRequestingByMe ? 'Y' : 'N'),
				'IS_AUTO_MEMBER' => $member['AUTO_MEMBER'],
				'PHOTO' => (isset($imageIds[$memberId], $avatars[$imageIds[$memberId]]) ? $avatars[$imageIds[$memberId]] : null),
			];
		}

		foreach ($projectIds as $projectId)
		{
			$projects[$projectId]['MEMBERS'] = [
				'HEADS' => ($members[$projectId]['HEADS'] ?? []),
				'MEMBERS' => ($members[$projectId]['MEMBERS'] ?? []),
			];
		}

		return $projects;
	}

	public function getPrimaryUsersQuery(array $projectIds): Query
	{
		$query = UserToGroupTable::query();
		$query
			->setSelect([
				'GROUP_ID',
				'USER_ID',
				'ROLE',
				'INITIATED_BY_TYPE',
				'AUTO_MEMBER',
				'NAME' => 'USER.NAME',
				'LAST_NAME' => 'USER.LAST_NAME',
				'SECOND_NAME' => 'USER.SECOND_NAME',
				'LOGIN' => 'USER.LOGIN',
				'PERSONAL_PHOTO' => 'USER.PERSONAL_PHOTO',
			])
			->where('GROUP.ACTIVE', 'Y')
			->where('USER.ACTIVE', 'Y')
			->whereIn('GROUP_ID', $projectIds)
		;

		return $query;
	}

	public function getPresets(): array
	{
		$defaultPresets = $this->getFilterDefaultPresets();
		$filterOptions = new Options($this->getFilterId(), $defaultPresets);

		return array_merge($defaultPresets, $filterOptions->getOptions()['filters']);
	}

	public function getFilterDefaultPresets(): array
	{
		return \Bitrix\Socialnetwork\Integration\Main\UIFilter\Workgroup::getFilterPresetList([
			'currentUserId' => $this->userId,
			'contextUserId' => $this->userId,
			'extranetSiteId' => (UserDataProvider::getExtranetAvailability() ? Option::get('extranet', 'extranet_site') : ''),
			'mode' => $this->mode,
		]);
	}

	public function getFilterId(): string
	{
		$filtersByMode = [
			WorkgroupList::MODE_USER => 'SONET_GROUP_LIST_USER',
			WorkgroupList::MODE_TASKS_PROJECT => 'SONET_GROUP_LIST_PROJECT',
			WorkgroupList::MODE_TASKS_SCRUM => 'SONET_GROUP_LIST_SCRUM',
		];

		return (array_key_exists($this->mode, $filtersByMode) ? $filtersByMode[$this->mode] : 'SONET_GROUP_LIST');
	}

	private function getFilterSourceFields(): array
	{
		$entityFilter = Factory::createEntityFilter(
			WorkgroupTable::getUfId(),
			['ID' => $this->getFilterId()],
			[
				'MODE' => $this->mode,
				'CONTEXT_USER_ID' => $this->userId,
			]
		);

		return $entityFilter->getFieldArrays();
	}

	public function pin(array $projectIds): void
	{
		foreach ($projectIds as $projectId)
		{
			Workgroup::pin($projectId, $this->mode);
		}
	}

	public function unpin(array $projectIds): void
	{
		foreach ($projectIds as $projectId)
		{
			Workgroup::unpin($projectId, $this->mode);
		}
	}

	public function getIsScrum(): bool
	{
		return $this->isScrum;
	}

	public function setIsScrum(bool $isScrum): void
	{
		$this->isScrum = $isScrum;
	}
}
