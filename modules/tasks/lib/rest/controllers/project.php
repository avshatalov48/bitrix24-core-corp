<?php
namespace Bitrix\Tasks\Rest\Controllers;

use Bitrix\Main\Engine;
use Bitrix\Main\Loader;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Mobile\Project\Helper;
use Bitrix\Socialnetwork\Component\WorkgroupList;
use Bitrix\Socialnetwork\Helper\Workgroup;
use Bitrix\Tasks\Integration\Socialnetwork;
use Bitrix\Tasks\Internals\Project\Provider;

class Project extends Base
{
	private const MODE_MOBILE = 'mobile';
	private const MODE_WEB = 'web';

	public function listAction(
		array $filter = [],
		array $select = [],
		array $order = [],
		array $params = [],
		PageNavigation $pageNavigation = null
	): ?Engine\Response\DataType\Page
	{
		$projects = [];

		$params['listMode'] = ($params['listMode'] ?? WorkgroupList::MODE_TASKS_PROJECT);

		$provider = new Provider($this->getUserId(), $params['listMode']);
		$preparedSelect = $provider->prepareQuerySelect($select);

		$query = $provider->getPrimaryProjectsQuery($preparedSelect);
		$query = $provider->getQueryWithFilter($query, $filter, ($params['siftThroughFilter']['presetId'] ?? ''));
		$query
			->setOrder($order)
			->setOffset($pageNavigation->getOffset())
			->setLimit($pageNavigation->getLimit())
			->countTotal(true)
		;
		$res = $query->exec();
		while ($project = $res->fetch())
		{
			$projects[$project['ID']] = $project;
		}

		if (!empty($projects))
		{
			$mode = ($params['mode'] ?? Project::MODE_WEB);
			$mode = ($mode === Project::MODE_MOBILE ? Project::MODE_MOBILE : Project::MODE_WEB);

			if (in_array('MEMBERS', $select, true))
			{
				$projects = $provider->fillMembers($projects);
			}
			if (in_array('IMAGE_ID', $select, true))
			{
				$projects = $provider->fillAvatars($projects, $mode);
			}
			if (in_array('COUNTERS', $select, true))
			{
				$projects = $provider->fillCounters($projects);
			}
			if (in_array('ACTIONS', $select, true))
			{
				$projects = $this->fillActions($projects);
			}
			if (in_array('IS_EXTRANET', $select, true))
			{
				$projects = $this->fillIsExtranet($projects);
			}
			if ($mode === Project::MODE_MOBILE)
			{
				$projects = $this->fillTabsData($projects);
			}
		}
		$projects = $this->convertKeysToCamelCase($projects);

		return new Engine\Response\DataType\Page('projects', array_values($projects), $res->getCount());
	}

	private function fillActions(array $projects): array
	{
		foreach ($projects as $id => $project)
		{
			$permissions = SocialNetwork\Group::getUserPermissionsInGroup($id);

			$projects[$id]['ACTIONS'] = [
				'EDIT' => $permissions['UserCanModifyGroup'],
				'DELETE' => $permissions['UserCanModifyGroup'],
				'INVITE' => $permissions['UserCanInitiate'],
				'JOIN' => (
					!$permissions['UserIsMember']
					&& !$permissions['UserRole']
				),
				'LEAVE' => (
					$permissions['UserIsMember']
					&& !$permissions['UserIsAutoMember']
					&& !$permissions['UserIsOwner']
				),
			];
		}

		return $projects;
	}

	private function fillIsExtranet(array $projects): array
	{
		foreach (array_keys($projects) as $id)
		{
			$projects[$id]['IS_EXTRANET'] = 'N';
		}

		if (!Loader::includeModule('extranet'))
		{
			return $projects;
		}

		$sites = [];
		$extranetSiteId = \CExtranet::GetExtranetSiteID();
		$projectsSiteIdsResult = \CSocNetGroup::GetSite(array_keys($projects));
		while ($site = $projectsSiteIdsResult->Fetch())
		{
			$sites[$site['GROUP_ID']][] = $site['LID'];
		}

		foreach (array_keys($projects) as $id)
		{
			$projects[$id]['IS_EXTRANET'] = (in_array($extranetSiteId, $sites[$id], true) ? 'Y' : 'N');
		}

		return $projects;
	}

	private function fillTabsData(array $projects): array
	{
		$ids = array_keys($projects);
		if (!empty($ids))
		{
			$additionalData = Workgroup::getAdditionalData([
				'ids' => $ids,
				'features' => Helper::getMobileFeatures(),
				'mandatoryFeatures' => Helper::getMobileMandatoryFeatures(),
				'currentUserId' => (int)$this->getCurrentUser()->getId(),
			]);

			foreach (array_keys($projects) as $id)
			{
				if (!isset($additionalData[$id]))
				{
					continue;
				}

				$projects[$id]['ADDITIONAL_DATA'] = ($additionalData[$id] ?? []) ;
			}
		}

		return $projects;
	}

	public function pinAction(int $projectId, string $mode = WorkgroupList::MODE_TASKS_PROJECT): void
	{
		(new Provider($this->getUserId(), $mode))->pin([$projectId]);
	}

	public function unpinAction(int $projectId, string $mode = WorkgroupList::MODE_TASKS_PROJECT): void
	{
		(new Provider($this->getUserId(), $mode))->unpin([$projectId]);
	}
}
