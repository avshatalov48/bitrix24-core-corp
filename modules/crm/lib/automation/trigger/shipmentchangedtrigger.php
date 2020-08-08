<?php
namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Bizproc\Automation\Engine\ConditionGroup;
use Bitrix\Main\Loader;
Use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class ShipmentChangedTrigger extends BaseTrigger
{
	public static function isSupported($entityTypeId)
	{
		return (
			$entityTypeId === \CCrmOwnerType::Order
			&& method_exists('\Bitrix\Bizproc\Automation\Engine\ConditionGroup', 'evaluateByDocument')
		);
	}

	public static function getCode()
	{
		return 'SHIPMENT_CHANGED';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_SHIPMENT_CHANGED_NAME');
	}

	public function checkApplyRules(array $trigger)
	{
		if (!parent::checkApplyRules($trigger))
		{
			return false;
		}

		if (
			is_array($trigger['APPLY_RULES'])
			&& !empty($trigger['APPLY_RULES']['shipmentCondition'])
		)
		{
			/** @var \Bitrix\Crm\Order\Shipment $shipment */
			$shipment = $this->getInputData('SHIPMENT');

			$conditionGroup = new ConditionGroup($trigger['APPLY_RULES']['shipmentCondition']);
			$documentType = \CCrmBizProcHelper::ResolveDocumentType(\CCrmOwnerType::OrderShipment);
			$documentId = [$documentType[0], $documentType[1], $shipment->getId()];

			return $conditionGroup->evaluateByDocument(
				$documentType,
				$documentId,
				$shipment->getFieldValues()
			);
		}
		return true;
	}

	public static function toArray()
	{
		$result = parent::toArray();
		if (static::isEnabled() && Loader::includeModule('sale'))
			if (static::isEnabled())
			{
				$result['FIELDS'] = array_values(\Bitrix\Bizproc\Automation\Helper::getDocumentFields(
					\CCrmBizProcHelper::ResolveDocumentType(\CCrmOwnerType::OrderShipment)
				));
			}
		return $result;
	}
}