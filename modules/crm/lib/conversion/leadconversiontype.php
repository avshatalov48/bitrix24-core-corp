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
		static $cache = [];
		$cacheKey = (string)$entityID;

		if (array_key_exists($cacheKey, $cache))
		{
			return $cache[$cacheKey];
		}

		$fields = self::loadLeadDataById($entityID);

		$result = is_array($fields) ? self::resolveByEntityFields($fields) : self::UNDEFINED;

		$cache[$cacheKey] = $result;

		return $result;
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

	public static function loadLeadDataById(int $id): ?array
	{
		static $cache = [];
		$cacheKey = (string)$id;

		if (array_key_exists($cacheKey, $cache))
		{
			return $cache[$cacheKey];
		}

		$dbResult = \CCrmLead::GetListEx(
			[],
			['ID' => $id, 'CHECK_PERMISSIONS' => 'N'],
			false,
			false,
			['ID', 'STATUS_ID', 'IS_RETURN_CUSTOMER']
		);

		$fields = $dbResult->Fetch();

		return is_array($fields) ? $fields : null;
	}
}