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

	public static function isEnabled()
	{
		return Loader::includeModule('sale');
	}

	public static function getCode()
	{
		return 'FILL_TRACKNUM';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_FILL_TRACKNUM_NAME_1');
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

	protected static function getPropertiesMap(): array
	{
		return [
			[
				'Id' => 'DELIVERY_ID',
				'Name' => Loc::getMessage('CRM_AUTOMATION_TRIGGER_FILL_TRACKNUM_PROPERTY_SERVICE'),
				'Type' => 'select',
				'EmptyValueText' => Loc::getMessage('CRM_AUTOMATION_TRIGGER_FILL_TRACKNUM_DEFAULT'),
				'Options' => array_values(array_map(
					function($item)
					{
						return ['value' => $item['ID'], 'name' => $item['NAME']];
					},
					\Bitrix\Sale\Delivery\Services\Manager::getActiveList()
				)),
			]
		];
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_FILL_TRACKNUM_DESCRIPTION') ?? '';
	}

	public static function getGroup(): array
	{
		return ['delivery'];
	}
}