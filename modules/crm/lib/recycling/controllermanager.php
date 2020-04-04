<?php
namespace Bitrix\Crm\Recycling;

use Bitrix\Crm\Volume\Activity;

class ControllerManager
{
	public static function resolveController($entityTypeID)
	{
		if($entityTypeID === \CCrmOwnerType::Activity)
		{
			return ActivityController::getInstance();
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			return ContactController::getInstance();
		}
		elseif($entityTypeID === \CCrmOwnerType::Company)
		{
			return CompanyController::getInstance();
		}
		elseif($entityTypeID === \CCrmOwnerType::Deal)
		{
			return DealController::getInstance();
		}
		elseif($entityTypeID === \CCrmOwnerType::Lead)
		{
			return LeadController::getInstance();
		}

		return null;
	}
}