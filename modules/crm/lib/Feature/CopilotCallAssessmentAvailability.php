<?php

namespace Bitrix\Crm\Feature;

use Bitrix\Crm\Feature\Category\BaseCategory;
use Bitrix\Crm\Feature\Category\Experimental;
use Bitrix\Main\Localization\Loc;

final class CopilotCallAssessmentAvailability extends BaseFeature
{
	public function getName(): string
	{
		return Loc::getMessage('COPILOT_CALL_ASSESSMENT_AVAILABILITY_FEATURE_NAME');
	}
	
	public function getCategory(): BaseCategory
	{
		return Experimental::getInstance();
	}
	
	protected function getOptionName(): string
	{
		return 'use_copilot_call_assessment_availability';
	}
}
