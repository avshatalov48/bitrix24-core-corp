<?php
namespace Bitrix\Crm\Automation\Trigger;

Use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class InvoiceTrigger extends BaseTrigger
{
	public static function isSupported($entityTypeId)
	{
		return ($entityTypeId === \CCrmOwnerType::Deal);
	}

	public static function getCode()
	{
		return 'INVOICE';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_INVOICE_NAME');
	}

	public static function onAfterCrmInvoiceSetStatus($params)
	{
		if (\CCrmStatusInvoice::isStatusSuccess($params['STATUS_ID']))
		{
			$iterator = \CCrmInvoice::GetList(
				array(),
				array('ID' => $params['ID'], 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID', 'UF_DEAL_ID')
			);
			$fields = is_object($iterator) ? $iterator->fetch() : null;
			$dealId = 0;
			if(is_array($fields))
			{
				$dealId = isset($fields['UF_DEAL_ID']) ? $fields['UF_DEAL_ID'] : 0;
			}

			if ($dealId > 0)
			{
				static::execute(array(array(
					'OWNER_TYPE_ID' => \CCrmOwnerType::Deal,
					'OWNER_ID' => $dealId
				)), array('INVOICE_ID' => $params['ID']));
			}
		}
	}
}