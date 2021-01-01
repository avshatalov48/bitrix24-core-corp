<?php
namespace Bitrix\Timeman\Monitor\Group;

use Bitrix\Main\Config\Configuration;
use Bitrix\Timeman\Model\Monitor\MonitorGroupTable;
use Bitrix\Timeman\Monitor\Utils\Department;

class Group
{
	public const CODE_WORKING = 'WORKING';
	public const CODE_NOT_WORKING = 'NOT_WORKING';
	public const CODE_OTHER = 'OTHER';

	protected $userId;
	protected $accesses;
	protected $groups;
	protected $masks;

	public function __construct(int $userId)
	{
		$this->userId = $userId;
		$this->accesses = $this->createUserGroup();
		$this->groups = self::loadGroups();
		$this->masks = self::loadMasks();
	}

	public function getAccesses(): array
	{
		return $this->accesses;
	}

	public static function loadGroups(): array
	{
		$rawGroups = MonitorGroupTable::getList(['select' => ['*']])->fetchAll();

		$groups = [];
		foreach ($rawGroups as $group)
		{
			$groups[$group['CODE']] = $group;
		}

		return $groups;
	}

	public function get(): array
	{
		return $this->groups;
	}

	public function getMasks(): array
	{
		return $this->masks;
	}

	protected function createUserGroup(): array
	{
		$departmentIds = Department::getUserDepartments($this->userId);

		$userGroups = [];
		foreach ($departmentIds as $departmentId)
		{
			$userGroups[] = self::createGroupForDepartment($departmentId);
		}

		$userGroup = [];
		foreach ($userGroups as $group)
		{
			foreach ($group as $entityType => $groups)
			{
				if (!$userGroup[$entityType])
				{
					$userGroup[$entityType] = [];
				}

				foreach ($groups as $groupCode => $accesses)
				{
					foreach ($accesses as $access)
					{
						if (isset($userGroup[$entityType][$access['ID']]))
						{
							$previousGroup = $userGroup[$entityType][$access['ID']];
							$currentGroup = $access['GROUP'];

							$userGroup[$entityType][$access['ID']] = self::getPriorityGroup($previousGroup, $currentGroup);

							continue;
						}

						$userGroup[$entityType][$access['ID']] = $access['GROUP'];
					}
				}
			}
		}

		return $userGroup;
	}

	public static function loadMasks(): array
	{
		$groupMasks = Configuration::getValue('monitor');
		if (!$groupMasks)
		{
			return [];
		}

		$masks = [];
		foreach ($groupMasks as $groupCode => $groupMask)
		{
			foreach ($groupMask as $mask)
			{
				$masks[$mask] = $groupCode;
			}
		}

		return $masks;
	}

	public static function getPriorityGroup($previousGroup, $currentGroup): string
	{
		$isWorkingInSomeGroup = ($previousGroup === self::CODE_WORKING || $currentGroup === self::CODE_WORKING);
		if ($isWorkingInSomeGroup)
		{
			return self::CODE_WORKING;
		}

		$isNotWorkingInSomeGroup = ($previousGroup === self::CODE_NOT_WORKING || $currentGroup === self::CODE_NOT_WORKING);
		if ($isNotWorkingInSomeGroup)
		{
			return self::CODE_NOT_WORKING;
		}

		return self::CODE_OTHER;
	}

	public static function createGroupForDepartment($departmentId): array
	{
		//Rejection of the hierarchical creation of groups. It can be useful in the future.
		//$idsFromHeadToDepartment = Department::getPathFromHeadToDepartment($departmentId);
		//return GroupAccess::getByDepartments($idsFromHeadToDepartment);

		return GroupAccess::getByDepartment((int)$departmentId);
	}

	public static function add(string $name, string $code, string $color, bool $hidden = false, bool $defaultGroup = false): bool
	{
		return MonitorGroupTable::add([
			'NAME' => $name,
			'COLOR' => $color,
			'CODE' => $code,
			'DEFAULT_GROUP' => $defaultGroup ? 'Y' : 'N',
			'HIDDEN' => $hidden ? 'Y' : 'N'
		])->isSuccess();
	}

	public static function isTableEmpty(): bool
	{
		return !(bool)MonitorGroupTable::getList(['select' => ['*']])->fetch();
	}
}