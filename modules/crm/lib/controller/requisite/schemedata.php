<?php
namespace Bitrix\Crm\Controller\Requisite;

use Bitrix\Crm\Controller\Base;
use CCrmComponentHelper;

class SchemeData extends Base
{
	public static function getRequisitesSchemeDataAction(int $entityTypeId): array
	{
		return CCrmComponentHelper::getFieldInfoData($entityTypeId, 'requisite');
	}

	public static function getRequisiteAutocompleteSchemeDataAction(int $countryId)
	{
		return CCrmComponentHelper::getRequisiteAutocompleteFieldInfoData($countryId);
	}
}