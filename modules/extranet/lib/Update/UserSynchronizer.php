<?php

namespace Bitrix\Extranet\Update;

use Bitrix\Extranet\Enum\User\ExtranetRole;
use Bitrix\Extranet\Service\ServiceContainer;
use Bitrix\Main\Update\Stepper;
use Bitrix\Main\UserTable;

class UserSynchronizer extends Stepper
{
	protected static $moduleId = 'extranet';
	private int $limit = 50;

	/**
	 * @inheritDoc
	 */
	public function execute(array &$option): bool
	{
		$userService = ServiceContainer::getInstance()->getUserService();

		if (empty($option))
		{
			$option['steps'] = 0;
			$option['count'] = 1;
			$option['lastId'] = 0;
		}

		$userIds = $this->getExtranetUserIdsByLastId((int)($option['lastId'] ?? 0));

		foreach ($userIds as $id)
		{
			if (!$userService->isCurrentExtranetUserById($id))
			{
				$userService->setRoleById($id, ExtranetRole::Extranet);
			}
		}

		if (count($userIds) < $this->limit)
		{
			return self::FINISH_EXECUTION;
		}

		$option['lastId'] = $userIds[array_key_last($userIds)];

		return self::CONTINUE_EXECUTION;
	}

	private function getExtranetUserIdsByLastId(int $lastId): array
	{
		return UserTable::query()
			->setSelect(['ID'])
			->addFilter('=IS_REAL_USER', 'Y')
			->addFilter('UF_DEPARTMENT', false)
			->addFilter('GROUPS.GROUP_ID', \CExtranet::GetExtranetUserGroupID())
			->addFilter('>ID', $lastId)
			->setLimit($this->limit)
			->exec()
			->fetchCollection()
			->getIdList()
		;
	}
}
