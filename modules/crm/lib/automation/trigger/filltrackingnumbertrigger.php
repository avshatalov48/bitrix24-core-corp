<?php
namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Main\Loader;
Use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class FillTrackingNumberTrigger extends BaseTrigger
{
	public static function isSupported($entityTypeId)
	{
		return ($entityTypeId === \CCrmOwnerType::Order);
	}

	public static function getCode()
	{
		return 'FILL_TRACKNUM';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_FILL_TRACKNUM_NAME');
	}

	public function checkApplyRules(array $trigger)
	{
		if (!parent::checkApplyRules($trigger))
		{
			return false;
		}

		if (
			is_array($trigger['APPLY_RULES'])
			&& isset($trigger['APPLY_RULES']['DELIVERY_ID'])
			&& $trigger['APPLY_RULES']['DELIVERY_ID'] > 0
		)
		{
			$shipment = $this->getInputData('SHIPMENT');

			return (int)$trigger['APPLY_RULES']['DELIVERY_ID'] === (int)$shipment->getField('DELIVERY_ID');
		}
		return true;
	}

	public static function toArray()
	{
		$result = parent::toArray();
		if (static::isEnabled() && Loader::includeModule('sale'))
		{
			$result['DELIVERY_LIST'] = [];

			foreach(\Bitrix\Sale\Delivery\Services\Manager::getActiveList() as $service)
			{
				$result['DELIVERY_LIST'][] = ['id' => $service['ID'], 'name' => $service['NAME']];
			}
		}
		return $result;
	}
}