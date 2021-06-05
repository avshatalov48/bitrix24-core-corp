<?php
namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Integration;

Loc::loadMessages(__FILE__);

class OpenLineAnswerTrigger extends OpenLineTrigger
{
	public static function isSupported($entityTypeId)
	{
		return $entityTypeId !== \CCrmOwnerType::Quote ? parent::isSupported($entityTypeId) : false;
	}

	protected static function areDynamicTypesSupported(): bool
	{
		return false;
	}

	public static function isEnabled()
	{
		return (Integration\OpenLineManager::isEnabled()
			&& class_exists('\Bitrix\ImOpenLines\Crm')
			&& method_exists('\Bitrix\ImOpenLines\Crm', 'executeAutomationAnswerTrigger')
		);
	}

	public static function getCode()
	{
		return 'OPENLINE_ANSWER';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_OPENLINE_ANSWER_NAME');
	}

	public function setInputData($data)
	{
		if (isset($data['ANSWER_TIME_SEC']) && is_callable([$this, 'setReturnValues']))
		{
			$this->setReturnValues(['OpenLineAnswerTimeSec' => $data['ANSWER_TIME_SEC']]);
		}
		return parent::setInputData($data);
	}

	public static function getReturnProperties(): array
	{
		return [
			[
				'Id' => 'OpenLineAnswerTimeSec',
				'Name' => Loc::getMessage('CRM_AUTOMATION_TRIGGER_OPENLINE_ANSWER_RETURN_ANSWER_TIME'),
				'Type' => 'int',
			]
		];
	}
}