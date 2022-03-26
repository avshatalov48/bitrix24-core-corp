<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Registry;


use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\Socialnetwork\WorkgroupTable;

class UserRegistry
{
	public const MODE_GROUP_ALL = 'all';
	public const MODE_GROUP = 'group';
	public const MODE_PROJECT = 'project';
	public const MODE_SCRUM = 'scrum';
	public const MODE_EXCLUDE_SCRAM = 'ex_scram';

	private static $instance = [];

	private $userId;
	private $userGroups = [];
	private $userProjects = [];
	private $userScrum = [];
	private $userWorkgroups = [];

	/**
	 * @param int $userId
	 * @return static
	 */
	public static function getInstance(int $userId): self
	{
		if (!array_key_exists($userId, self::$instance))
		{
			self::$instance[$userId] = new self($userId);
		}
		return self::$instance[$userId];
	}

	/**
	 * @param string $mode
	 * @return array
	 */
	public function getUserGroups(string $mode = self::MODE_GROUP_ALL): array
	{
		switch ($mode)
		{
			case self::MODE_GROUP:
				$groups = $this->userWorkgroups;
				break;
			case self::MODE_PROJECT:
				$groups = $this->userProjects;
				break;
			case self::MODE_SCRUM:
				$groups = $this->userScrum;
				break;
			case self::MODE_EXCLUDE_SCRAM:
				$groups = array_replace($this->userProjects, $this->userWorkgroups);
				break;
			default:
				$groups = $this->userGroups;
		}

		return $groups;
	}

	/**
	 * @param int $groupId
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function isGroupAdmin(int $groupId): bool
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		$userGroups = $this->getUserGroups();

		if (!array_key_exists($groupId, $userGroups))
		{
			return false;
		}

		return in_array($userGroups[$groupId], [UserToGroupTable::ROLE_OWNER, UserToGroupTable::ROLE_MODERATOR]);
	}

	/**
	 * UserRegistry constructor.
	 * @param int $userId
	 */
	private function __construct(int $userId)
	{
		$this->userId = $userId;
		$this->loadInfo();
	}

	/**
	 *
	 */
	private function loadInfo(): void
	{
		$this->loadGroupInfo();
	}

	/**
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function loadGroupInfo(): void
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return;
		}

		$res = UserToGroupTable::query()
			->addSelect('GROUP_ID')
			->addSelect('ROLE')
			->addSelect('WORKGROUP.PROJECT', 'PROJECT')
			->addSelect('WORKGROUP.SCRUM_MASTER_ID', 'SCRUM_MASTER')
			->registerRuntimeField(
				new Reference(
					'WORKGROUP',
					WorkgroupTable::class,
					Join::on('this.GROUP_ID', 'ref.ID'),
					['join_type' => 'LEFT']
				)
			)
			->setFilter([
				'=USER_ID' => $this->userId,
				'@ROLE' => [UserToGroupTable::ROLE_OWNER, UserToGroupTable::ROLE_MODERATOR, UserToGroupTable::ROLE_USER]
			])
			->fetchAll();

		foreach ($res as $row)
		{
			$this->userGroups[$row['GROUP_ID']] = $row['ROLE'];
			if ((int)$row['SCRUM_MASTER'] > 0)
			{
				$this->userScrum[$row['GROUP_ID']] = $row['ROLE'];
			}
			elseif ($row['PROJECT'] === 'Y')
			{
				$this->userProjects[$row['GROUP_ID']] = $row['ROLE'];
			}
			else
			{
				$this->userWorkgroups[$row['GROUP_ID']] = $row['ROLE'];
			}
		}
	}
}