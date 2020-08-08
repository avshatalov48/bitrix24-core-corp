<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Model\Member;

use Bitrix\Tasks\Access\Exception\UnknownMemberException;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Access\Model\UserModel;
use Bitrix\Tasks\Access\Role\RoleDictionary;

class MembersProvider
{

	private const CACHE_TTL = 3153600;
	private const CACHE_DIR = '/socnet/dest/user/';

	private const ID_BLACKLIST = [
		"bot",
		"imconnector",
		"replica"
	];

	private $bSelf = true;

	private $userId;

	private $userModel;
	private $taskModel;

	private $cache;
	private $cacheManager;

	public function __construct(int $userId, int $groupId)
	{
		global $CACHE_MANAGER;
		$this->cacheManager = $CACHE_MANAGER;

		$this->userId = $userId;

		$this->userModel = UserModel::createFromId($this->userId);
		$this->taskModel = TaskModel::createNew($groupId);
	}

	public function getMembers($role): ?array
	{
		$list = $this->getListObject($role);

		$accessibleUsers = $list->getAccesibleUsers();
		$hasRightUsers = $list->getHasRightUsers();

		if (is_array($accessibleUsers) && is_array($hasRightUsers))
		{
			$res = array_intersect($accessibleUsers, $hasRightUsers);
		}
		else
		{
			$res = $accessibleUsers ?? $hasRightUsers;
		}

		return is_array($res) ? $res : null;
	}

	private function getListObject($role): MemberListInterface
	{
		switch ($role)
		{
			case RoleDictionary::ROLE_DIRECTOR:
				$object = new DirectorList($this->userModel, $this->taskModel);
				break;
			case RoleDictionary::ROLE_RESPONSIBLE:
			case RoleDictionary::ROLE_ACCOMPLICE:
				$object = new ResponsibleList($this->userModel, $this->taskModel);
				break;
			case RoleDictionary::ROLE_AUDITOR:
				$object = new AuditorList($this->userModel, $this->taskModel);
				break;
			default:
				throw new UnknownMemberException();
		}

		return $object;
	}
}