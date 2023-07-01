<?php
namespace Bitrix\Tasks\Comments\Viewed;


use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Internals\Registry\UserRegistry;
use Bitrix\Tasks\Internals\Task\ViewedGroupTable;
use \Bitrix\Tasks\Internals\Task\ViewedTable;
use \Bitrix\Tasks\Internals\Counter\Role;
use \Bitrix\Main\Result;
use \Bitrix\Main\Error;
use Bitrix\Tasks\MemberTable;

final class Group
{
	const MEMBER_TYPE_UNDEFINED = '';

	const GROUP_0 = '0';
	const GROUP_MNEMONIC = '0';
	const PROJECT_GROUP_LIST = '0';

	const ROLE_ALL = null;

	const ACTION_PROJECT_GROUP_ID_ROLE_ALL = 'ACTION_GROUP_COMMENTS';
	const ACTION_SCRUM_GROUP_ID_ROLE_ALL = 'ACTION_SCRUM_COMMENTS';

	const ACTION_PROJECT_GROUP_LIST_ROLE_ALL = 'ACTION_ALL_GROUP_COMMENTS';
	const ACTION_SCRUM_GROUP_LIST_ROLE_ALL = 'ACTION_ALL_SCRUM_COMMENTS';

	const ACTION_USER_GROUP_ALL_ROLE_ALL = 'ACTION_TOTAL_ALL_COMMENTS';
	const ACTION_USER_GROUP_ALL_ROLE_ID = 'ACTION_TOTAL_ALL_COMMENTS_ROLE';
	const ACTION_USER_GROUP_ID_ROLE_ALL = 'ACTION_TOTAL_ALL_COMMENTS_GROUP';
	const ACTION_USER_GROUP_ID_ROLE_ID = 'ACTION_TOTAL_ALL_COMMENTS_ROLE_GROUP';

	const ACTION_UNDEFINED = 'ACTION_UNDEFINED';

	private int $userId;

	public function __construct()
	{
		$this->userId = (int)CurrentUser::get()
			->getId();
	}

	protected function prepare(array $fields): array
	{
		$role = $fields['role'];
		$groupId = $fields['groupId'];

		$withGroupId = (bool)$groupId;

		$withRole = false;
		if (is_null($role) === false)
		{
			$type = Group::resolveMemberTypeByRole($role);
			$withRole = is_null($type) === false;
		}

		return [
			$withGroupId,
			$withRole
		];
	}

	public function resolveAction( ?int $groupId, ?string $role, string $type): string
	{
		[$withGroupId, $withRole] = $this->prepare([
			'groupId' => $groupId,
			'roleId' => $role,
		]);

		if ($type === Enum::PROJECT_NAME)
		{
			if ($withGroupId && !$withRole)
			{
				return Group::ACTION_PROJECT_GROUP_ID_ROLE_ALL;
			}
			else if(!$withGroupId && !$withRole)
			{
				return Group::ACTION_PROJECT_GROUP_LIST_ROLE_ALL;
			}
		}
		else if( $type === Enum::SCRUM_NAME)
		{
			if ($withGroupId && !$withRole)
			{
				return Group::ACTION_SCRUM_GROUP_ID_ROLE_ALL;
			}
			else if(!$withGroupId && !$withRole)
			{
				return Group::ACTION_SCRUM_GROUP_LIST_ROLE_ALL;
			}
		}
		else if( $type === Enum::USER_NAME)
		{
			if ($withGroupId && $withRole)
			{
				return Group::ACTION_USER_GROUP_ID_ROLE_ID;
			}
			else if(!$withGroupId && $withRole)
			{
				return Group::ACTION_USER_GROUP_ALL_ROLE_ID;
			}
			else if($withGroupId && !$withRole)
			{
				return Group::ACTION_USER_GROUP_ID_ROLE_ALL;
			}
			else if(!$withGroupId && !$withRole)
			{
				return Group::ACTION_USER_GROUP_ALL_ROLE_ALL;
			}
		}

		return Group::ACTION_UNDEFINED;
	}

	static private function resolveMemberTypeByRole($role)
	{
		$memberRole = null;
		if (array_key_exists($role, Role::ROLE_MAP))
		{
			$memberRole = Role::ROLE_MAP[$role];
		}
		return $memberRole;
	}

	public function markAsRead( ?int $groupId, ?string $role, string $type): Result
	{
		$result = new Result();

		$action = $this->resolveAction($groupId, $role, $type);

		$r = $this->prepareByAction($action, ['groupId' => $groupId, 'role' => $role]);
		if ($r->isSuccess())
		{
			[$groupCondition, $userJoin, $select] = $r->getData();
		}
		else
		{
			$result->addErrors($r->getErrors());
			return $result;
		}

		$listGroup = ViewedTable::getListForReadAll($this->userId, $userJoin, $groupCondition, $select);

		$list = $this->collapse($listGroup);

		$items = $this->fillByAction($action, $list);

		$typeName = $type == Enum::SCRUM_NAME ?  Enum::PROJECT_NAME : $type;

		foreach ($items as $item)
		{
			// TODO: replace to batchInert
			ViewedGroupTable::upsert([
				'USER_ID' => $item['USER_ID'],
				'GROUP_ID' => $item['GROUP_ID'],
				'MEMBER_TYPE' => $item['MEMBER_TYPE'],
				'VIEWED_DATE' => new DateTime(),
				'TYPE_ID' => Enum::resolveIdByName($typeName)
			]);
		}

		return $result;
	}

	protected function collapse($list): array
	{
		$result = [];
		foreach ($list as $row)
		{
			// for distinct GROUP_ID
			// if there is a MEMBER_TYPE field, then filtering was performed on it
			$result[$row['GROUP_ID']] = $row;
		}
		return array_values($result);
	}

	protected function fillByAction($action, $items): array
	{
		$result = [];

		array_walk($items, static function($item) use (&$result, $action)
		{
			switch ($action)
			{
				case Group::ACTION_PROJECT_GROUP_ID_ROLE_ALL:
				case Group::ACTION_SCRUM_GROUP_ID_ROLE_ALL:
				case Group::ACTION_PROJECT_GROUP_LIST_ROLE_ALL:
				case Group::ACTION_SCRUM_GROUP_LIST_ROLE_ALL:

				case Group::ACTION_USER_GROUP_ID_ROLE_ALL:
				case Group::ACTION_USER_GROUP_ALL_ROLE_ALL:

					foreach (MemberTable::possibleTypes() as $type)
					{
						$item['MEMBER_TYPE'] = $type;
						$result[] = $item;
					}
					break;
				default:
					$result[] = $item;
			}
		});
		return $result;
	}

	protected function prepareByAction( string $action, array $fields): Result
	{
		$r = new Result();

		$role = $fields['role'];
		$groupId = $fields['groupId'];

		$select = [
			'USER_ID' => [
				'FIELD_NAME' => 'TS.USER_ID'
			],
		];

		switch ($action)
		{
			case Group::ACTION_PROJECT_GROUP_ID_ROLE_ALL:
			case Group::ACTION_SCRUM_GROUP_ID_ROLE_ALL:
				$groupCondition = $this->getConditionForGroup($groupId);
				$userJoin = '';
				$select['GROUP_ID'] = ['FIELD_NAME' => 'TS.GROUP_ID'];

				break;

			case Group::ACTION_PROJECT_GROUP_LIST_ROLE_ALL:
				$indexList = $this->getIndexGroupByType(UserRegistry::MODE_SCRUM);
				$groupCondition = $this->getExcludeConditionForListGroup($indexList);
				$userJoin = '';
				$select['GROUP_ID'] = ['FIELD_NAME' => 'TS.GROUP_ID'];

				break;

			case Group::ACTION_SCRUM_GROUP_LIST_ROLE_ALL:
				$indexList = $this->getIndexGroupByType(UserRegistry::MODE_SCRUM);
				$groupCondition = $this->getConditionForListGroup($indexList);
				$userJoin = '';
				$select['GROUP_ID'] = ['FIELD_NAME' => 'TS.GROUP_ID'];

				break;

			case Group::ACTION_USER_GROUP_ALL_ROLE_ALL:
				$groupCondition = '';
				$userJoin = $this->getConditionForUser($this->userId);
				$select['GROUP_ID'] = ['FIELD_NAME' => Group::GROUP_MNEMONIC];

				break;
			case Group::ACTION_USER_GROUP_ALL_ROLE_ID:
				$groupCondition = '';
				$userJoin = $this->getConditionForUser($this->userId);
				$userJoin .= $this->getConditionForMemberType(Group::resolveMemberTypeByRole($role));
				$select['MEMBER_TYPE'] = ['FIELD_NAME'=>'TM.TYPE'];
				$select['GROUP_ID'] = ['FIELD_NAME' => Group::GROUP_MNEMONIC];

				break;
			case Group::ACTION_USER_GROUP_ID_ROLE_ALL:
				$groupCondition = $this->getConditionForGroup($groupId);
				$userJoin = $this->getConditionForUser($this->userId);
				$select['MEMBER_TYPE'] = ['FIELD_NAME'=>'TM.TYPE'];
				$select['GROUP_ID'] = ['FIELD_NAME' => Group::GROUP_MNEMONIC];

				break;
			case Group::ACTION_USER_GROUP_ID_ROLE_ID:
				$groupCondition = $this->getConditionForGroup($groupId);
				$userJoin = $this->getConditionForUser($this->userId);
				$userJoin .= $this->getConditionForMemberType(Group::resolveMemberTypeByRole($role));
				$select['MEMBER_TYPE'] = ['FIELD_NAME' => 'TM.TYPE'];
				$select['GROUP_ID'] = ['FIELD_NAME' => Group::GROUP_MNEMONIC];

				break;
			default:
				$r->addError(new Error('Undefined Action'));
				return $r;
				break;
		}

		return $r->setData([
			$groupCondition,
			$userJoin,
			$select
		]);
	}

	protected function getListGroupByType(string $type): array
	{
		return UserRegistry::getInstance($this->userId)
			->getUserGroups($type);
	}

	protected function getIndexGroupByType(string $type): array
	{
		$list = $this->getListGroupByType($type);
		$result = array_keys($list);

		$result[] = Group::GROUP_0;

		return $result;
	}

	private function getConditionForMemberType(string $type): string
	{
		return " AND TM.TYPE = '". $type ."'";
	}

	private function getConditionForUser(int $id): string
	{
		return "INNER JOIN b_tasks_member TM ON TM.TASK_ID = T.ID AND TM.USER_ID = {$id}";
	}

	private function getConditionForGroup(int $id): string
	{
		return "AND TS.GROUP_ID = {$id}";
	}

	private function getConditionForListGroup(array $list): string
	{
		return "AND TS.GROUP_ID IN (". implode(',', $list) .")";
	}

	private function getExcludeConditionForListGroup(array $list): string
	{
		return "AND TS.GROUP_ID NOT IN (". implode(',', $list) .")";
	}

	static public function isOn(): bool
	{
		return \Bitrix\Main\Config\Option::get("tasks", "task_comments_viewed_group", 'N') == 'Y';
	}
}