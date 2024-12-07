<?php

namespace Bitrix\Crm\Badge\Type;

use Bitrix\Crm\Badge\Badge;
use Bitrix\Crm\Badge\ValueItem;
use Bitrix\Crm\Badge\ValueItemOptions;
use Bitrix\Main\Localization\Loc;

class AiCallFieldsFillingResult extends Badge
{
	protected const TYPE = 'ai_call_fields_filling_result';

	public const CONFLICT_FIELDS_VALUE = 'conflict_fields';
	public const SUCCESS_FIELDS_VALUE = 'success_fields';
	public const ERROR_PROCESS_VALUE = 'error_process';
	public const ERROR_PROCESS_THIRDPARTY_VALUE = 'error_process_thirdparty';

	public const ERROR_LIMIT_EXCEEDED = 'error_limit_exceeded';

	public function getFieldName(): string
	{
		return Loc::getMessage('CRM_COMMON_COPILOT');
	}

	public function getValuesMap(): array
	{
		return [
			new ValueItem(
				self::CONFLICT_FIELDS_VALUE,
				Loc::getMessage('CRM_BADGE_AI_CALL_FIELDS_FILLING_RESULT_WARNING'),
				ValueItemOptions::TEXT_COLOR_WARNING,
				ValueItemOptions::BG_COLOR_WARNING
			),
			new ValueItem(
				self::SUCCESS_FIELDS_VALUE,
				Loc::getMessage('CRM_BADGE_AI_CALL_FIELDS_FILLING_RESULT_SUCCESS'),
				ValueItemOptions::TEXT_COLOR_SUCCESS,
				ValueItemOptions::BG_COLOR_SUCCESS
			),
			new ValueItem(
				self::ERROR_PROCESS_VALUE,
				Loc::getMessage('CRM_BADGE_AI_CALL_FIELDS_FILLING_RESULT_ERROR'),
				ValueItemOptions::TEXT_COLOR_FAILURE,
				ValueItemOptions::BG_COLOR_FAILURE
			),
			new ValueItem(
				self::ERROR_PROCESS_THIRDPARTY_VALUE,
				Loc::getMessage('CRM_BADGE_AI_CALL_FIELDS_FILLING_THIRDPARTY_RESULT_ERROR'),
				ValueItemOptions::TEXT_COLOR_FAILURE,
				ValueItemOptions::BG_COLOR_FAILURE
			),
			new ValueItem(
				self::ERROR_LIMIT_EXCEEDED,
				self::getLimitExceededTextValue(),
				ValueItemOptions::TEXT_COLOR_FAILURE,
				ValueItemOptions::BG_COLOR_FAILURE
			),
		];
	}

	public function getType(): string
	{
		return self::TYPE;
	}

	public static function getLimitExceededTextValue(): string
	{
		return Loc::getMessage('CRM_BADGE_AI_CALL_FIELDS_FILLING_RESULT_ERROR_LIMIT_EXCEEDED');
	}
}
