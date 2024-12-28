<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Tasks\Integration\SocialNetwork;

use Bitrix\Socialnetwork\Integration\UI\EntitySelector\UserProvider;
use Bitrix\Socialnetwork\UserToGroupTable;

final class User extends \Bitrix\Tasks\Integration\SocialNetwork
{
	public static function isAdmin($userId = 0)
	{
		if(static::includeModule())
		{
			return \CSocNetUser::isCurrentUserModuleAdmin();
		}

		return false;
	}

	/**
	 * @param $groupId
	 * @param $operation
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getUsersCanPerformOperation($groupId, $operation): array
	{
		$users = [];

		if (!static::includeModule())
		{
			return $users;
		}

		$role = \CSocNetFeaturesPerms::GetOperationPerm(SONET_ENTITY_GROUP, $groupId, 'tasks', $operation);
		$usersRes = UserToGroupTable::getList([
			'select' => ['USER_ID'],
			'filter' => [
				'=GROUP_ID' => $groupId,
				'=USER.ACTIVE' => 'Y',
				'<=ROLE' => $role,
			],
			'order' => ['DATE_CREATE' => 'ASC'],
		]);
		while ($user = $usersRes->fetch())
		{
			$users[] = $user['USER_ID'];
		}

		return array_unique($users);
	}

	public static function getUsers(array $userIds)
	{
		if(!static::includeModule())
		{
			return [];
		}

		return UserProvider::getUsers([
			'userId' => $userIds,
		]);
	}

	public static function getUserRole($userId, $groupId): ?array
	{
		if(!static::includeModule())
		{
			return [];
		}

		return \CSocNetUserToGroup::GetUserRole($userId, $groupId);
	}
}
