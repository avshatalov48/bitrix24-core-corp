<?php

use Bitrix\Crm\Component\Base;
use Bitrix\Crm\Feature;
use Bitrix\Crm\Router\ResponseHelper;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CrmCopilotCallAssessmentDetailsWrapper extends Base
{
	public function executeComponent(): void
	{
		if (!Feature::enabled(Feature\CopilotInCallGrading::class))
		{
			ResponseHelper::showPageNotFound();
		}

		$this->init();

		$this->arResult = $this->arParams;

		$this->includeComponentTemplate();
	}
}
