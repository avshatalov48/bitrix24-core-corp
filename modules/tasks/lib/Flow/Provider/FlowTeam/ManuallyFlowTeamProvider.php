<?php

namespace Bitrix\Tasks\Flow\Provider\FlowTeam;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\FlowCollection;
use Bitrix\Tasks\Flow\Provider\Exception\ProviderException;

class ManuallyFlowTeamProvider implements FlowTeamProviderInterface
{
	/**
	 * @throws ProviderException
	 * @throws LoaderException
	 */
	public function getTeamMembers(Flow $flow, ?int $offset = null, ?int $limit = null): array
	{
		$projectId = $flow->getGroupId();

		try
		{
			if (!Loader::includeModule('socialnetwork'))
			{
				return [];
			}

			$query = UserToGroupTable::query()
				->setSelect(['ID', 'USER_ID'])
				->where('GROUP_ID', $projectId)
				->where('USER.ACTIVE', 'Y')
				->setOffset($offset)
				->setLimit($limit);

			return $query->exec()->fetchCollection()->getUserIdList();
		}
		catch (SystemException $e)
		{
			throw new ProviderException($e->getMessage());
		}
	}

	/**
	 * @throws SystemException
	 * @throws ArgumentException
	 * @throws LoaderException
	 */
	public function getTeamCount(FlowCollection $flows): array
	{
		$flows = $flows->getManuallyFlows();

		if ($flows->isEmpty() || !Loader::includeModule('socialnetwork'))
		{
			return [];
		}

		$counts = UserToGroupTable::query()
			->setSelect(['GROUP_ID', Query::expr('TOTAL_COUNT')->countDistinct('USER_ID')])
			->whereIn('GROUP_ID', array_unique($flows->getGroupIdList()))
			->where('USER.ACTIVE', 'Y')
			->setGroup(['GROUP_ID'])
			->exec()
			->fetchAll();

		$flowCounts = [];

		foreach ($counts as $count)
		{
			$flowCounts[(int)$count['GROUP_ID']] = (int)$count['TOTAL_COUNT'];
		}

		$teams = [];

		foreach ($flows as $flow)
		{
			if (!isset($flowCounts[$flow->getGroupId()]))
			{
				continue;
			}

			$teams[$flow->getId()] = (int)$flowCounts[$flow->getGroupId()];
		}

		return $teams;
	}
}