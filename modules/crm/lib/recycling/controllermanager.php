<?php

namespace Bitrix\Crm\Recycling;

class ControllerManager
{
	/**
	 * @param int $entityTypeID
	 * @return BaseController|null
	 */
	public static function resolveController($entityTypeID)
	{
		$entityTypeID = (int)$entityTypeID;

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
		elseif (\CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeID))
		{
			return DynamicController::getInstance($entityTypeID);
		}

		return null;
	}
}
