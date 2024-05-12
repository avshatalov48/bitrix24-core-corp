<?php

namespace Bitrix\CrmMobile\Controller;

use Bitrix\Intranet\ActionFilter\IntranetUser;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Loader;
use Bitrix\Mobile\UI\StatefulList\BaseController;

Loader::requireModule('crm');

class Kanban extends BaseController
{
	protected const PREFIX = 'crmmobile.Kanban';

	public function configureActions(): array
	{
		return [
			'loadItems' => [
				'class' => Action\GetListAction::class,
				'+prefilters' => [
					new ActionFilter\CloseSession(),
				],
			],
			'loadEntityStages' => [
				'class' => Action\GetStagesAction::class,
				'+prefilters' => [
					new ActionFilter\CloseSession(),
				],
			],
			'loadTabs' => [
				'class' => Action\GetTabsAction::class,
				'+prefilters' => [
					new ActionFilter\CloseSession(),
				],
			],
			'updateItemStage' => [
				'class' => Action\UpdateItemStageAction::class,
			],
			'deleteItem' => [
				'class' => Action\DeleteItemAction::class,
			],
			'changeCategory' => [
				'class' => Action\ChangeCategoryAction::class,
			],
			'getSearchData' => [
				'class' => Action\GetSearchDataAction::class,
				'+prefilters' => [
					new ActionFilter\CloseSession(),
				],
			],
			'setSortType' => [
				'class' => Action\SetSortTypeAction::class,
			],
		];
	}

	protected function getDefaultPreFilters(): array
	{
		return [
			new ActionFilter\Authentication(),
			new ActionFilter\Csrf(),
			new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
			new ActionFilter\Scope(ActionFilter\Scope::NOT_REST),
			new IntranetUser(),
		];
	}

}
