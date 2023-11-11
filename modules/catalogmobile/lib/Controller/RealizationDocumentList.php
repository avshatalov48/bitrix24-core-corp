<?php

namespace Bitrix\CatalogMobile\Controller;

use Bitrix\Main\Loader;
use Bitrix\CatalogMobile\Controller\Actions\GetRealizationDocumentListAction;
use Bitrix\Mobile\UI\StatefulList\BaseController;

Loader::requireModule('catalog');

class RealizationDocumentList extends BaseController
{
	protected const PREFIX = 'catalogmobile.RealizationDocumentList';

	public function configureActions(): array
	{
		return [
			'loadItems' => [
				'class' => GetRealizationDocumentListAction::class,
			],
		];
	}
}
