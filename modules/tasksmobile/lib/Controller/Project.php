<?php

namespace Bitrix\TasksMobile\Controller;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Engine\Response;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Tasks\Internals\Project\Provider;
use Bitrix\Tasks\Internals\Project\Pull\PullDictionary;

Loader::requireModule('socialnetwork');

class Project extends Controller
{
	/**
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws ArgumentException
	 * @throws SystemException
	 * @throws LoaderException
	 */
	public function listAction(
		array $filter = [],
		array $select = [],
		array $order = [],
		array $params = [],
		PageNavigation $pageNavigation = null
	): ?Response\DataType\Page
	{
		$projects = [];

		$provider = new Provider(CurrentUser::get()->getId(), $params['mode']);

		$query = $provider->getPrimaryProjectsQuery($select);
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
			$projects = $provider->fillIsExtranet($projects);
			$projects = $provider->fillActions($projects);
			$projects = $provider->fillTabsData($projects);
			$projects = $provider->fillAvatars($projects);
			$projects = $provider->fillMembers($projects);
			$projects = $provider->fillCounters($projects);

			$projects = $this->convertKeysToCamelCase($projects);
		}

		return new Response\DataType\Page('projects', array_values($projects), $res->getCount());
	}

	public function pinAction(int $projectId, string $mode): void
	{
		(new Provider(CurrentUser::get()->getId(), $mode))->pin([$projectId]);
	}

	public function unpinAction(int $projectId, string $mode): void
	{
		(new Provider(CurrentUser::get()->getId(), $mode))->unpin([$projectId]);
	}

	public function startWatchListAction(): bool
	{
		return \CPullWatch::Add(CurrentUser::get()->getId(), PullDictionary::PULL_PROJECTS_TAG, true);
	}
}