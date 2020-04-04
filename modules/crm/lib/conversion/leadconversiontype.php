<?php
namespace Bitrix\Crm\Conversion;
use Bitrix\Crm\CustomerType;
use Bitrix\Crm\PhaseSemantics;
class LeadConversionType
{
	const UNDEFINED = 0;
	const GENERAL = 1;
	const RETURNING_CUSTOMER = 2;
	const SUPPLEMENT = 3;

	public static function resolveByEntityID($entityID)
	{
		$dbResult = \CCrmLead::GetListEx(
			array(),
			array('ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'STATUS_ID', 'IS_RETURN_CUSTOMER')
		);

		$fields = $dbResult->Fetch();
		return is_array($fields) ? self::resolveByEntityFields($fields) : self::UNDEFINED;
	}
	public static function resolveByEntityFields(array $fields)
	{
		$customerType = \CCrmLead::ResolveCustomerType($fields);
		if($customerType === CustomerType::RETURNING)
		{
			return self::RETURNING_CUSTOMER;
		}

		$semanticID = \CCrmLead::GetSemanticID(isset($fields['STATUS_ID']) ? $fields['STATUS_ID'] : '');
		if($semanticID === PhaseSemantics::SUCCESS)
		{
			return self::SUPPLEMENT;
		}

		return self::GENERAL;
	}
}