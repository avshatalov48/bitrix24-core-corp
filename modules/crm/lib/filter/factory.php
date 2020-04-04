<?php
namespace Bitrix\Crm\Filter;

use Bitrix\Main;
use Bitrix\Crm;

class Factory
{
	/**
	 * Create Filter by specified entity type ID.
	 * @param EntitySettings $settings Entity Filter settings.
	 * @return Filter
	 * @throws Main\NotSupportedException
	 */
	public static function createEntityFilter(EntitySettings $settings)
	{
		$filterID = $settings->getID();
		if($settings instanceof LeadSettings)
		{
			return new Filter(
				$filterID,
				new LeadDataProvider($settings),
				array(new UserFieldDataProvider($settings))
			);
		}
		elseif($settings instanceof DealSettings)
		{
			return new Filter(
				$filterID,
				new DealDataProvider($settings),
				array(new UserFieldDataProvider($settings))
			);
		}
		elseif($settings instanceof ContactSettings)
		{
			return new Filter(
				$filterID,
				new ContactDataProvider($settings),
				array(
					new UserFieldDataProvider($settings),
					new RequisiteDataProvider($settings)
				)
			);
		}
		elseif($settings instanceof CompanySettings)
		{
			return new Filter(
				$filterID,
				new CompanyDataProvider($settings),
				array(
					new UserFieldDataProvider($settings),
					new RequisiteDataProvider($settings)
				)
			);
		}
		elseif($settings instanceof QuoteSettings)
		{
			return new Filter(
				$filterID,
				new QuoteDataProvider($settings),
				array(new UserFieldDataProvider($settings))
			);
		}
		elseif($settings instanceof InvoiceSettings)
		{
			return new Filter(
				$filterID,
				new InvoiceDataProvider($settings),
				array(new UserFieldDataProvider($settings))
			);
		}
		elseif($settings instanceof OrderSettings)
		{
			return new Filter(
				$filterID,
				new OrderDataProvider($settings),
				array(new UserFieldDataProvider($settings))
			);
		}
		else
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($settings->getEntityTypeID());
			throw new Main\NotSupportedException(
				"Entity type: '{$entityTypeName}' is not supported in current context."
			);
		}
	}

	public static function createEntitySettings($entityTypeID, $filterID)
	{
		if($entityTypeID === \CCrmOwnerType::Lead)
		{
			return new LeadSettings(array('ID' => $filterID));
		}
		elseif($entityTypeID === \CCrmOwnerType::Deal)
		{
			return new DealSettings(array('ID' => $filterID));
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			return new ContactSettings(array('ID' => $filterID));
		}
		elseif($entityTypeID === \CCrmOwnerType::Company)
		{
			return new CompanySettings(array('ID' => $filterID));
		}
		elseif($entityTypeID === \CCrmOwnerType::Quote)
		{
			return new QuoteSettings(array('ID' => $filterID));
		}
		elseif($entityTypeID === \CCrmOwnerType::Invoice)
		{
			return new InvoiceSettings(array('ID' => $filterID));
		}
		elseif($entityTypeID === \CCrmOwnerType::Order)
		{
			return new OrderSettings(array('ID' => $filterID));
		}

		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
		throw new Main\NotSupportedException(
			"Entity type: '{$entityTypeName}' is not supported in current context."
		);
	}
}