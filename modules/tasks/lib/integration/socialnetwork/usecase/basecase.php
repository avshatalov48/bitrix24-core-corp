<?php

namespace Bitrix\Tasks\Integration\SocialNetwork\UseCase;

class BaseCase
{
	protected array $siteIds = [];
	protected $dateFormat = null;

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
		}
		elseif (\Bitrix\Main\Loader::includeModule("crm"))
		{
			$res = \CCrmActivity::getList(
				[],
				[
					'TYPE_ID' => \CCrmActivityType::Task,
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
		}

		return $filter;
	}

	protected function isSpawnedByAgent(array $params = []): bool
	{
		if (
			isset($params['SPAWNED_BY_AGENT'])
			&&
			(($params['SPAWNED_BY_AGENT'] === 'Y') || ($params['SPAWNED_BY_AGENT'] === true))
		)
		{
			return true;
		}

		return false;
	}

	/**
	 * @param \Bitrix\Tasks\Internals\Notification\User[] $recepients
	 * @return array
	 */
	protected function recepients2Rights(array $recepients): array
	{
		$rights = [];
		foreach($recepients as $user)
		{
			$rights[] = 'U' . $user->getId();
		}

		return $rights;
	}

	protected function getSiteIds(): array
	{
		if (empty($this->siteIds))
		{
			$dbSite = \CSite::GetList(
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
			$this->dateFormat = \CSite::GetDateFormat('FULL', SITE_ID);
		}

		return $this->dateFormat;
	}
}