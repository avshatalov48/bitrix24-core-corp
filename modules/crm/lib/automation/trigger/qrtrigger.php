<?php

namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Main\Localization\Loc;

class QrTrigger extends BaseTrigger
{
	public static function isSupported($entityTypeId)
	{
		return ($entityTypeId === \CCrmOwnerType::Deal);
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
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_QR_NAME');
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
}
