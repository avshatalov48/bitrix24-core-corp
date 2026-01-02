<?php

namespace Bitrix\Crm\Copilot\CallAssessment\Enum;

use Bitrix\Main\Localization\Loc;

enum AvailabilityWeekdayType: string
{
	case MONDAY = 'monday';
	case TUESDAY = 'tuesday';
	case WEDNESDAY = 'wednesday';
	case THURSDAY = 'thursday';
	case FRIDAY = 'friday';
	case SATURDAY = 'saturday';
	case SUNDAY = 'sunday';
	case WORKING = 'working';
	case WEEKENDS = 'weekends';

	public static function values(): array
	{
		return array_map(static fn ($case) => $case->value, self::cases());
	}
	
	public static function getTitle(string $input): ?string
	{
		return match($input)
		{
			self::MONDAY->value => Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_WEEKDAY_TYPE_MONDAY'),
			self::TUESDAY->value => Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_WEEKDAY_TYPE_TUESDAY'),
			self::WEDNESDAY->value => Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_WEEKDAY_TYPE_WEDNESDAY'),
			self::THURSDAY->value => Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_WEEKDAY_TYPE_THURSDAY'),
			self::FRIDAY->value => Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_WEEKDAY_TYPE_FRIDAY'),
			self::SATURDAY->value => Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_WEEKDAY_TYPE_SATURDAY'),
			self::SUNDAY->value => Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_WEEKDAY_TYPE_SUNDAY'),
			self::WORKING->value => Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_WEEKDAY_TYPE_WORKING'),
			self::WEEKENDS->value => Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_WEEKDAY_TYPE_WEEKENDS'),
			default => null,
		};
	}
	
	public static function getCurrentDayOfWeek(): ?string
	{
		$dayOfWeekNumber = date('w');
		switch ($dayOfWeekNumber)
		{
			case 0:
				$result = self::SUNDAY->value;
				break;
			case 1:
				$result = self::MONDAY->value;
				break;
			case 2:
				$result = self::TUESDAY->value;
				break;
			case 3:
				$result = self::WEDNESDAY->value;
				break;
			case 4:
				$result= self::THURSDAY->value;
				break;
			case 5:
				$result = self::FRIDAY->value;
				break;
			case 6:
				$result = self::SATURDAY->value;
				break;
		}
		
		return $result ?? null;
	}
}
