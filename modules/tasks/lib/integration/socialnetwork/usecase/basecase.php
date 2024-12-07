<?php

namespace Bitrix\Tasks\Integration\SocialNetwork\UseCase;

use Bitrix\Main\Loader;
use Bitrix\Tasks\Internals\Notification\Message;
use Bitrix\Tasks\Internals\Notification\User;
use CCrmActivity;
use CCrmActivityType;
use CSite;

class BaseCase
{
	protected array $siteIds = [];
	protected $dateFormat = null;

	private static $cache = [];

	protected function getSonetLogFilter(int $taskId, bool $isCrm): array
	{
		// TODO: this code was moved from classes/tasksnotifications propably needs reraftoring
		$filter = [];

		if (!$isCrm)
		{
			$filter = [
				'EVENT_ID' => 'tasks',
				'SOURCE_ID' => $taskId
			];

			return $filter;
		}

		if (array_key_exists($taskId, self::$cache))
		{
			return self::$cache[$taskId];
		}

		$res = CCrmActivity::getList(
			[],
			[
				'TYPE_ID' => CCrmActivityType::Task,
				'ASSOCIATED_ENTITY_ID' => $taskId,
				'CHECK_PERMISSIONS' => 'N'
			],
			false,
			false,
			['ID']
		);

		if ($activity = $res->fetch())
		{
			$filter = [
				'EVENT_ID' => 'crm_activity_add',
				'ENTITY_ID' => $activity
			];
		}

		self::$cache[$taskId] = $filter;

		return self::$cache[$taskId];
	}

	protected function getSiteIds(): array
	{
		if (empty($this->siteIds))
		{
			$dbSite = CSite::GetList(
				'sort',
				'desc',
				['ACTIVE' => 'Y']
			);

			while ($arSite = $dbSite->Fetch())
			{
				$this->siteIds[] = $arSite['ID'];
			}
		}

		return $this->siteIds;
	}

	protected function getDateFormat()
	{
		if ($this->dateFormat === null)
		{
			$this->dateFormat = CSite::GetDateFormat('FULL', SITE_ID);
		}

		return $this->dateFormat;
	}

	protected function addUserRights(Message $message, User $user, int $logId): void
	{
		$message->addMetaData('rights', ['U' . $user->getId() . '_' . $logId]);
	}

	protected function addGroupRights(Message $message, int $groupId, int $logId): void
	{
		$message->addMetaData('rights', ['SG' . $groupId . '_' . $logId]);
	}

	protected function recepients2Rights(array $recepients): array
	{
		$rights = [];
		foreach($recepients as $user)
		{
			$rights[] = 'U' . $user->getId();
		}

		return $rights;
	}
}