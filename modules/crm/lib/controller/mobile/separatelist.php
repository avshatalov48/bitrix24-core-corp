<?php


namespace Bitrix\Crm\Controller\Mobile;


use Bitrix\Crm\Controller\Mobile\SeparateListAction\GetList;
use Bitrix\UI\Mobile\SeparateList\BaseController;

class SeparateList extends BaseController
{
	protected const PREFIX = 'crm.mobile.separatelist';

	public function configureActions(): array
	{
		return [
			'loadItems' => [
				'class' => GetList::class,
			],
		];
	}
}
