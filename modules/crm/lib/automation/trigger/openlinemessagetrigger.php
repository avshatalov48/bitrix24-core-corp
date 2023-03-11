<?php
namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Integration;

Loc::loadMessages(__FILE__);

class OpenLineMessageTrigger extends OpenLineTrigger
{
	public static function getCode()
	{
		return 'OPENLINE_MSG';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_OPENLINE_MESSAGE_NAME_1');
	}

	public static function getGroup(): array
	{
		return ['clientCommunication'];
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_OPENLINE_MESSAGE_DESCRIPTION') ?? '';
	}

	protected static function getPropertiesMap(): array
	{
		$map = parent::getPropertiesMap();
		$map[] = [
			'Id' => 'msg_text',
			'Name' => Loc::getMessage('CRM_AUTOMATION_TRIGGER_OPENLINE_MESSAGE_PROPERTY_MSG_TEXT'),
			'Type' => 'string',
		];

		return $map;
	}
}
