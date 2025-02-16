<?php

namespace Bitrix\Crm\Copilot\AiQualityAssessment;

enum ViewModeEnum: string
{
	case usedNotAssessmentScript = 'usedNotAssessmentScript';
	case usedCurrentVersionOfScript = 'usedCurrentVersionOfScript';
	case usedOtherVersionOfScript = 'usedOtherVersionOfScript';
	case emptyScriptList = 'emptyScriptList';
	case assessmentSettingsPending = 'assessmentSettingsPending';
	case pending = 'pending';
	case error = 'error';
}
