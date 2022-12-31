<?php

namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class ToDo extends Base
{
	public const PROVIDER_ID = 'CRM_TODO';
	public const PROVIDER_TYPE_ID_DEFAULT = 'TODO';

	public static function getId(): string
	{
		return self::PROVIDER_ID;
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_ACTIVITY_TODO_NAME');
	}

	public static function isTypeEditable($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined): bool
	{
		return false;
	}

	public static function getTypes()
	{
		return [
			[
				'NAME' => Loc::getMessage('CRM_ACTIVITY_TODO_NAME'),
				'PROVIDER_ID' => self::getId(),
				'PROVIDER_TYPE_ID' => self::PROVIDER_TYPE_ID_DEFAULT
			]
		];
	}

	public static function hasPlanner(array $activity): bool
	{
		return false;
	}

	public static function getAdditionalFieldsForEdit(array $activity)
	{
		return [
			['TYPE' => 'DESCRIPTION'],
			['TYPE' => 'FILE'],
		];
	}

	public static function checkFields($action, &$fields, $id, $params = null)
	{
		if (isset($fields['END_TIME']) && $fields['END_TIME'] != '')
		{
			$fields['DEADLINE'] = $fields['END_TIME'];
		}

		return new Result();
	}

	public static function getDefaultPingOffsets(): array
	{
		return [0, 15];
	}
}
