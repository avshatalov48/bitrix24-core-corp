<?php
namespace Bitrix\Crm\Color;

/**
 * @deprecated
 */
class PhaseColorSchemeManager
{
	public static function resolveSchemeByEntityTypeID($entityTypeID, array $params = null)
	{
		if($entityTypeID === \CCrmOwnerType::Lead)
		{
			return LeadStatusColorScheme::getCurrent();
		}
		if($entityTypeID === \CCrmOwnerType::Quote)
		{
			return QuoteStatusColorScheme::getCurrent();
		}
		if($entityTypeID === \CCrmOwnerType::Invoice)
		{
			return InvoiceStatusColorScheme::getCurrent();
		}
		if($entityTypeID === \CCrmOwnerType::Order)
		{
			return OrderStatusColorScheme::getCurrent();
		}
		if($entityTypeID === \CCrmOwnerType::OrderShipment)
		{
			return OrderShipmentStatusColorScheme::getCurrent();
		}

		$dealCategoryID = is_array($params) && isset($params['DEAL_CATEGORY_ID']) ? $params['DEAL_CATEGORY_ID'] : -1;
		if($dealCategoryID >= 0)
		{
			return DealStageColorScheme::getByCategory($dealCategoryID);
		}

		return null;
	}

	public static function resolveSchemeByName($name)
	{
		if(LeadStatusColorScheme::getName() === $name)
		{
			return LeadStatusColorScheme::getCurrent();
		}
		if(QuoteStatusColorScheme::getName() === $name)
		{
			return QuoteStatusColorScheme::getCurrent();
		}
		if(InvoiceStatusColorScheme::getName() === $name)
		{
			return InvoiceStatusColorScheme::getCurrent();
		}
		if(OrderStatusColorScheme::getName() === $name)
		{
			return OrderStatusColorScheme::getCurrent();
		}
		if(OrderShipmentStatusColorScheme::getName() === $name)
		{
			return OrderShipmentStatusColorScheme::getCurrent();
		}

		$dealCategoryID = DealStageColorScheme::resolveCategoryByName($name);
		if($dealCategoryID >= 0)
		{
			return DealStageColorScheme::getByCategory($dealCategoryID);
		}

		return null;
	}
}