<?php

namespace Bitrix\CatalogMobile\Controller;

use Bitrix\Main\Loader;
use Bitrix\CatalogMobile\Controller\Actions\GetStoreDocumentListAction;
use Bitrix\Mobile\UI\StatefulList\BaseController;

Loader::requireModule('catalog');

class StoreDocumentList extends BaseController
{
	protected const PREFIX = 'catalogmobile.StoreDocumentList';

	public function configureActions(): array
	{
		return [
			'loadItems' => [
				'class' => GetStoreDocumentListAction::class,
			],
		];
	}
}
