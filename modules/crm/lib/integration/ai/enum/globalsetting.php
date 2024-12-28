<?php

namespace Bitrix\Crm\Integration\AI\Enum;

use Bitrix\Crm\Integration\AI\EventHandler;

enum GlobalSetting: string
{
	/** @see EventHandler::SETTINGS_FILL_CRM_TEXT_ENABLED_CODE */
	case FillCrmText = 'crm_copilot_fill_crm_text_enabled';

	/** @see EventHandler::SETTINGS_FILL_ITEM_FROM_CALL_ENABLED_CODE */
	case FillItemFromCall = 'crm_copilot_fill_item_from_call_enabled';

	/** @see EventHandler::SETTINGS_CALL_ASSESSMENT_ENABLED_CODE */
	case CallAssessment = 'crm_copilot_call_assessment_enabled';
}
