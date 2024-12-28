<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Component\Base;
use Bitrix\Crm\Security\Role\Manage\Manager\CustomSectionListSelection;
use Bitrix\Main\Loader;

if (!Loader::includeModule('crm'))
{
	return;
}

class CrmAutomatedSolutionPermissionsComponent extends Base
{
	public function executeComponent(): void
	{
		$this->init();

		if ($this->getErrors())
		{
			$this->showFirstErrorViaInfoErrorUI();

			return;
		}

		$this->getApplication()->IncludeComponent(
			'bitrix:crm.config.perms.wrapper',
			'',
			[
				'criterion' => CustomSectionListSelection::CRITERION,
				'isAutomation' => true,
			],
		);
	}
}
