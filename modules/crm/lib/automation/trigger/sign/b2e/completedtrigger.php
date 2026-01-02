<?php

namespace Bitrix\Crm\Automation\Trigger\Sign\B2e;

use Bitrix\Main\Localization\Loc;

final class CompletedTrigger extends AbstractB2eDocumentTrigger
{
	private const SELECT_ID = 'result_type';
	private const TYPE_ON_DONE = 'TYPE_ON_DONE';
	private const OPTION_VALUE_DEFAULT = 0;
	private const OPTION_VALUE_DONE = 1;
	private const OPTION_VALUE_STOPPED = 2;

	public static function getCode(): string
	{
		return 'B2E_COMPLETED';
	}

	public static function getName(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_B2E_COMPLETED_NAME') ?? '';
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_B2E_COMPLETED_DESCRIPTION') ?? '';
	}

	public function checkApplyRules(array $trigger): bool
	{
		if (parent::checkApplyRules($trigger) === false)
		{
			return false;
		}

		$selectedValue = (int)($trigger['APPLY_RULES'][self::SELECT_ID] ?? self::OPTION_VALUE_DEFAULT);
		if ($selectedValue === self::OPTION_VALUE_DEFAULT)
		{
			return true;
		}

		$eventType = $this->inputData['eventType'] ?? '';

		return match ($selectedValue)
		{
			self::OPTION_VALUE_DONE => $eventType === self::TYPE_ON_DONE,
			self::OPTION_VALUE_STOPPED => $eventType !== self::TYPE_ON_DONE,
			default => false,
		};
	}

	protected static function getPropertiesMap(): array
	{
		return [
			[
				'Id' => self::SELECT_ID,
				'Name' => Loc::getMessage('CRM_AUTOMATION_TRIGGER_B2E_COMPLETED_SELECT_TITLE'),
				'Type' => 'select',
				'EmptyValueText' => Loc::getMessage('CRM_AUTOMATION_TRIGGER_B2E_COMPLETED_OPTION_ALL'),
				'Options' => [
					[
						'value' => self::OPTION_VALUE_DONE,
						'name' => Loc::getMessage('CRM_AUTOMATION_TRIGGER_B2E_COMPLETED_OPTION_SIGNED')
					],
					[
						'value' => self::OPTION_VALUE_STOPPED,
						'name' => Loc::getMessage('CRM_AUTOMATION_TRIGGER_B2E_COMPLETED_OPTION_STOPPED')
					],
				],
			],
		];
	}
}
