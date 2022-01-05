<?php
namespace Bitrix\Tasks\Rest\Controllers;

use Bitrix\Main\Engine;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Tasks\Integration\Socialnetwork;
use Bitrix\Tasks\Internals\Project\Filter\MobileFilter;
use Bitrix\Tasks\Internals\Project\Provider;

class Project extends Base
{
	public function listAction(
		PageNavigation $pageNavigation,
		array $filter = [],
		array $select = [],
		array $order = [],
		array $params = []
	): ?Engine\Response\DataType\Page
	{
		$projects = [];

		$provider = new Provider();
		$mobileFilter = new MobileFilter();

		if (array_key_exists('GET_LAST_ACTIVE', $params) && $params['GET_LAST_ACTIVE'] === 'Y')
		{
			$filter['ID'] = $provider->getLastActiveProjectIds();
		}

		$querySelect = $provider->prepareQuerySelect($select);

		$query = $provider->getPrimaryProjectsQuery($querySelect);
		$query = $mobileFilter->process($query, $filter);
		$query
			->setOrder($order)
			->setOffset($pageNavigation->getOffset())
			->setLimit(($pageNavigation->getLimit()))
			->countTotal(true)
		;
		$res = $query->exec();
		while ($project = $res->fetch())
		{
			$projects[$project['ID']] = $project;
		}

		if (!empty($projects))
		{
			if (in_array('MEMBERS', $select, true))
			{
				$projects = $provider->fillMembers($projects);
			}
			if (in_array('IMAGE_ID', $select, true))
			{
				$projects = $provider->fillAvatars($projects);
			}
			if (in_array('COUNTERS', $select, true))
			{
				$projects = $provider->fillCounters($projects);
			}
			if (in_array('ACTIONS', $select, true))
			{
				$projects = $this->fillActions($projects);
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

	public function pinAction(int $projectId): void
	{
		(new Provider())->pin([$projectId]);
	}

	public function unpinAction(int $projectId): void
	{
		(new Provider())->unpin([$projectId]);
	}
}
