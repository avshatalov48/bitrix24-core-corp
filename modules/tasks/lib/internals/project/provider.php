<?php

namespace Bitrix\Tasks\Internals\Project;

use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\Helper\Workgroup;
use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\Socialnetwork\WorkgroupSiteTable;
use Bitrix\Socialnetwork\WorkgroupTable;
use Bitrix\Socialnetwork\WorkgroupTagTable;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\Internals\Counter\Template\ProjectCounter;
use Bitrix\Tasks\Internals\Counter\Template\ScrumCounter;
use Bitrix\Tasks\Internals\Effective;
use Bitrix\Tasks\Internals\Project\UserOption\UserOptionController;
use Bitrix\Tasks\Internals\Project\UserOption\UserOptionTypeDictionary;
use Bitrix\Tasks\Internals\Task\ProjectLastActivityTable;
use Bitrix\Tasks\Internals\Task\ProjectUserOptionTable;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\User;

class Provider
{
	private $userId;
	private $isScrum;

	public function __construct(int $userId = 0, bool $isScrum = false)
	{
		$this->userId = ($userId ?: User::getId());
		$this->isScrum = $isScrum;
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
			$prepared['USER_GROUP_ID'] = 'UG.ID';
			unset($prepared[array_search('USER_GROUP_ID', $prepared, true)]);
		}
		if (in_array('IS_PINNED', $prepared, true))
		{
			$prepared[] = new ExpressionField(
				'IS_PINNED',
				ProjectUserOptionTable::getSelectExpression($this->userId, UserOptionTypeDictionary::OPTION_PINNED),
				['ID', 'UG.USER_ID']
			);
			unset($prepared[array_search('IS_PINNED', $prepared, true)]);
		}

		return $prepared;
	}

	public function getPrimaryProjectsQuery(array $select): Query
	{
		$siteId = SITE_ID;
		if (
			Loader::includeModule('extranet')
			&& !\CExtranet::IsIntranetUser($siteId, $this->userId)
		)
		{
			$siteId = \CExtranet::GetExtranetSiteID();
		}

		$query = WorkgroupTable::query();
		$query
			->setSelect($select)
			->registerRuntimeField(
				'UG',
				new ReferenceField(
					'UG',
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
				'GS',
				new ReferenceField(
					'GS',
					WorkgroupSiteTable::getEntity(),
					Join::on('this.ID', 'ref.GROUP_ID')->where('ref.SITE_ID', $siteId),
					['join_type' => 'inner']
				)
			)
			->registerRuntimeField(
				null,
				new ExpressionField('ACTIVITY_DATE', 'IFNULL(%s, %s)', ['PLA.ACTIVITY_DATE', 'DATE_UPDATE'])
			)
			->where((new Filter())->getProjectVisibilityCondition())
		;

		if ($this->isScrum)
		{
			$query->whereNotNull('SCRUM_MASTER_ID');
		}
		else
		{
			$query->whereNull('SCRUM_MASTER_ID');
		}

		return $query;
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
				'PHOTO' => $avatars[$imageIds[$memberId]],
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

	public function getLastActiveProjectIds(): array
	{
		$logDestination = SocialNetwork::getLogDestination();

		$projectIds = $logDestination['LAST']['SONETGROUPS'];
		foreach ($projectIds as $key => $group)
		{
			$projectIds[$key] = str_replace('SG', '', $group);
		}

		return $projectIds;
	}

	public function pin(array $projectIds): void
	{
		foreach ($projectIds as $projectId)
		{
			UserOptionController::getInstance($this->userId, $projectId)->add(UserOptionTypeDictionary::OPTION_PINNED);
		}
	}

	public function unpin(array $projectIds): void
	{
		foreach ($projectIds as $projectId)
		{
			UserOptionController::getInstance($this->userId, $projectId)->delete(UserOptionTypeDictionary::OPTION_PINNED);
		}
	}

	public function isScrum(): bool
	{
		return $this->isScrum;
	}
}
