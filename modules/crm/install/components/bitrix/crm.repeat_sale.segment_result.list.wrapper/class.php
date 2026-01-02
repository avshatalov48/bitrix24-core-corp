<?php

use Bitrix\Crm\Component\Base;
use Bitrix\Crm\Service\Container;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CrmRepeatSaleSegmentResultListWrapper extends Base
{
	public function executeComponent(): void
	{
		if (!Container::getInstance()->getRepeatSaleAvailabilityChecker()->hasPermission())
		{
			$componentPage = 'restrictions';

			$this->includeComponentTemplate($componentPage);

			return;
		}

		$this->init();

		$this->arResult = $this->arParams;

		$this->includeComponentTemplate();
	}
}
