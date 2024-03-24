<?php

namespace Bitrix\Crm\Timeline\Rest\HistoryItem;

use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Engine\Response\DataType\Page;

final class ItemList
{
	use Singleton;

	private ListParams\Builder $listParamsBuilder;
	private ItemListQueries $queries;
	private PermissionChecker $permissionChecker;
	private ListAnswerMaker $answerMaker;


	public function __construct()
	{
		$this->listParamsBuilder = ListParams\Builder::getInstance();
		$this->queries = ItemListQueries::getInstance();
		$this->permissionChecker = PermissionChecker::getInstance();
		$this->answerMaker = ListAnswerMaker::getInstance();
	}

	public function execute(ListParams\Params $params, UserPermissions $userPermissions): ?Page
	{
		if (!$this->permissionChecker->userHasPermissionToEntity($params, $userPermissions))
		{
			return $this->emptyResult();
		}

		$foundIds = $this->queries->queryTimelineIdsByFilter($params);

		if (empty($foundIds))
		{
			return $this->emptyResult();
		}

		$rows = $this->queries->queryTimelineWithBindingsByIds($foundIds, $params->getOrder());

		$result = $this->answerMaker->makeAnswer($params->getSelect(), $userPermissions, $rows);

		return new Page(
			'items',
			$result,
			$this->queries->totalCount($params),
		);
	}

	private function emptyResult(): Page
	{
		return new Page('items', [], 0);
	}



}
