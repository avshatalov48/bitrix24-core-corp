<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Repository;

use Bitrix\AI\BaseRepository;
use Bitrix\AI\Container;
use Bitrix\AI\Integration\Socialnetwork\GroupService;
use Bitrix\AI\ShareRole\Service\ShareService;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\UserAccessTable;

class UserAccessRepository extends BaseRepository
{
	protected array $accessGroupsByUserId = [];
	public const CODE_ALL_USER = 'UA';

	public function getAccessConditionsForUser(int $userId, string $prefix = 'ROLE_SHARES.'): ConditionTree
	{
		$accessConditions = (new ConditionTree())
			->logic(ConditionTree::LOGIC_OR)
			->whereIn(
				$prefix . 'ACCESS_CODE',
				UserAccessTable::query()
					->setSelect(['ACCESS_CODE'])
					->where('USER_ID', $userId)
			)
			->where($prefix . 'ACCESS_CODE', UserAccessRepository::CODE_ALL_USER)
		;

		$accessGroups = $this->getCodesForUserGroup($userId);
		if (!empty($accessGroups))
		{
			$accessConditions->whereIn($prefix . 'ACCESS_CODE', $accessGroups);
		}

		return $accessConditions;
	}

	public function getAccessConditionsForUsers(array $userList): ConditionTree
	{
		$accessConditions = (new ConditionTree())
			->logic(ConditionTree::LOGIC_OR)
			->whereIn(
				'ROLE_SHARES.ACCESS_CODE',
				UserAccessTable::query()
					->setSelect(['ACCESS_CODE'])
					->whereIn('USER_ID', $userList)
			)
			->where('ROLE_SHARES.ACCESS_CODE', UserAccessRepository::CODE_ALL_USER)
		;

		$accessGroups = $this->getAccessCodesForUsers($userList);
		if (!empty($accessGroups))
		{
			$accessConditions->whereIn('ROLE_SHARES.ACCESS_CODE', $accessGroups);
		}

		return $accessConditions;
	}

	/**
	 * Return main access codes group for users
	 *
	 * @param int[] $usersIdList
	 * @return array
	 */
	private function getAccessCodesForUsers(array $usersIdList): array
	{
		if (empty($usersIdList))
		{
			return [];
		}

		$userGroupsCodes = [];
		foreach ($usersIdList as $userId)
		{
			$userGroupsCodes[] = $this->getCodesForUserGroup($userId);
		}
		$accessGroups = array_merge(...$userGroupsCodes);

		return array_unique($accessGroups);
	}

	/**
	 * Return Main Access codes group for user
	 */
	public function getCodesForUserGroup(int $userId): array
	{
		if (array_key_exists($userId, $this->accessGroupsByUserId))
		{
			return $this->accessGroupsByUserId[$userId];
		}

		$this->accessGroupsByUserId[$userId] = [];
		$groupIds = $this->getGroupService()->getGroupIdsForUser($userId);
		if (empty($groupIds))
		{
			return [];
		}

		$groups = $this->getShareService()->getProjectAccessCodes($groupIds);
		if (empty($groups))
		{
			return [];
		}

		$this->accessGroupsByUserId[$userId] = $groups;

		return $groups;
	}

	protected function getGroupService(): GroupService
	{
		return Container::init()->getItem(GroupService::class);
	}

	protected function getShareService(): ShareService
	{
		return Container::init()->getItem(ShareService::class);
	}
}
