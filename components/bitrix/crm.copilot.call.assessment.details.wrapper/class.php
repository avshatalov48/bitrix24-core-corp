<?php

use Bitrix\Crm\Component\Base;
use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\Bitrix24Manager;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CrmCopilotCallAssessmentDetailsWrapper extends Base
{
	public function executeComponent(): void
	{
		if (!Bitrix24Manager::isFeatureEnabled(AIManager::AI_COPILOT_FEATURE_NAME))
		{
			$this->includeComponentTemplate('restrictions');
			
			return;
		}

		$this->init();

		$this->arResult = $this->arParams;

		$this->includeComponentTemplate();
	}
}
