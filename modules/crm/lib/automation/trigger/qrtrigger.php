<?php

namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Main\Localization\Loc;

class QrTrigger extends BaseTrigger
{
	public static function isSupported($entityTypeId)
	{
		if (\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
		{
			return parent::isSupported($entityTypeId);
		}

		$supported = [\CCrmOwnerType::Deal, \CCrmOwnerType::Lead, \CCrmOwnerType::SmartDocument];

		return in_array($entityTypeId, $supported, true);
	}

	public static function isEnabled()
	{
		//TODO: temporary, skip version control
		return file_exists(\Bitrix\Main\Application::getDocumentRoot() . '/pub/crm/qr/index.php');
	}

	public static function getCode()
	{
		return 'QR';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_QR_NAME_1');
	}

	public function checkApplyRules(array $trigger)
	{
		if (!parent::checkApplyRules($trigger))
		{
			return false;
		}

		$ownerId = $this->getInputData('ownerId');

		if (
			$ownerId
			&& is_array($trigger['APPLY_RULES'])
			&& !empty($trigger['APPLY_RULES']['ownerId'])
		)
		{
			return $trigger['APPLY_RULES']['ownerId'] === $ownerId;
		}

		return true;
	}

	protected static function getPropertiesMap(): array
	{
		return [
			[
				'Id' => 'ownerId',
				'Name' => Loc::getMessage('CRM_AUTOMATION_TRIGGER_QR_PROPERTY_OWNER'),
				'Type' => '@robot-select',
				'EmptyValueText' => Loc::getMessage('CRM_AUTOMATION_TRIGGER_QR_DEFAULT_OWNER'),
				'Settings' => [
					'Filter' => ['Type' => 'CrmGenerateQr'],
					'OptionNameProperty' => 'QrTitle'
				],
			],
		];
	}

	public static function getGroup(): array
	{
		return ['other'];
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_QR_DESCRIPTION') ?? '';
	}
}
