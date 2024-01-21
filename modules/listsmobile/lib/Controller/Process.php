<?php

namespace Bitrix\ListsMobile\Controller;

use Bitrix\Main\Loader;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Mobile\UI\StatefulList\BaseController;

Loader::requireModule('lists');
Loader::requireModule('mobile');

class Process extends BaseController
{
	public function configureActions(): array
	{
		return [
			'loadPersonalList' => [
				'class' => Action\Process\LoadPersonalListAction::class,
				'+prefilters' => [
					new ActionFilter\CloseSession(),
				],
			],
			'loadCatalog' => [
				'class' => Action\Process\LoadCatalogAction::class,
				'+prefilters' => [
					new ActionFilter\CloseSession(),
				],
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
		];
	}
}
