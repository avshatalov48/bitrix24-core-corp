<?php

use Bitrix\Crm\Component\Base;
use Bitrix\Crm\Service\Container;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CrmRepeatSaleSegmentDetailsWrapper extends Base
{
	public function executeComponent(): void
	{
		$intranetToolManager = Container::getInstance()->getIntranetToolsManager();
		if (!$intranetToolManager->checkRepeatSaleAvailability())
		{
			$componentPage = 'disabled';

			$this->includeComponentTemplate($componentPage);

			return;
		}

		if (!Container::getInstance()->getRepeatSaleAvailabilityChecker()->hasPermission())
		{
			$componentPage = 'restrictions';

			$this->includeComponentTemplate($componentPage);

			return;
		}

		if (!Container::getInstance()->getRepeatSaleAvailabilityChecker()->isItemsCountsLessThenLimit())
		{
			$componentPage = 'limit';

			$this->includeComponentTemplate($componentPage);

			return;
		}

		$this->init();

		$this->arResult = $this->arParams;

		$this->includeComponentTemplate();
	}
}
