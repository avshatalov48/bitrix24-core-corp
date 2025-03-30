<?php

namespace Bitrix\Extranet\Update;

use Bitrix\Extranet\Enum\User\ExtranetRole;
use Bitrix\Extranet\Model\ExtranetUserTable;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Update\Stepper;
use Bitrix\HumanResources;

class NonConformingUserRemover extends Stepper
{
	protected static $moduleId = 'extranet';
	private int $limit = 50;

	function execute(array &$option)
	{
		if (empty($option))
		{
			$option['steps'] = 0;
			$option['count'] = 1;
			$option['lastId'] = 0;
		}

		$userIds = $this->getExtranetUserIdsByLastId((int)($option['lastId'] ?? 0));
		$hrIncluded = Loader::includeModule('humanresources');
		$usersWithExtranetGroup = array_map(static fn($id) => (int)$id, \CExtranet::GetExtranetGroupUsers());
		$userIdsToRemove = [];

		foreach ($userIds as $userId)
		{
			if (
				!in_array((int)$userId, $usersWithExtranetGroup, true)
				|| ($hrIncluded && HumanResources\Service\Container::getUserService()->isEmployee((int)$userId))
			)
			{
				$userIdsToRemove[] = $userId;
			}
		}

		if (!empty($userIdsToRemove))
		{
			$this->removeByUserIds($userIdsToRemove);
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
		return ExtranetUserTable::query()
			->setSelect(['USER_ID'])
			->addFilter('>USER_ID', $lastId)
			->addFilter('=ROLE', ExtranetRole::Extranet->value)
			->setOrder(['USER_ID' => 'ASC'])
			->setLimit($this->limit)
			->exec()
			->fetchCollection()
			->getUserIdList()
		;
	}

	private function removeByUserIds(array $userIds): void
	{
		$employees = implode(',', $userIds);
		$role = ExtranetRole::Extranet->value;
		$connection = Application::getConnection();
		$connection->query("DELETE FROM b_extranet_user WHERE ROLE = '$role' AND USER_ID IN ($employees)");
	}
}
