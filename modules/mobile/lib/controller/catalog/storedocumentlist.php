<?php

namespace Bitrix\Mobile\Controller\Catalog;

use Bitrix\Main\Loader;
use Bitrix\Mobile\Controller\Catalog\Actions\GetListAction;
use Bitrix\Mobile\UI\StatefulList\BaseController;

Loader::requireModule('catalog');

class StoreDocumentList extends BaseController
{
	protected const PREFIX = 'mobile.catalog.storedocumentlist';

	public function configureActions(): array
	{
		return [
			'loadItems' => [
				'class' => GetListAction::class,
			],
		];
	}
}
