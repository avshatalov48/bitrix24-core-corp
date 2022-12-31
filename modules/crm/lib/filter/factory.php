<?php

namespace Bitrix\Crm\Filter;

use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Main\Filter\DataProvider;
use Bitrix\Crm\Tracking;

class Factory
{
	public function getSettings(int $entityTypeId, string $filterId, ?array $parameters = []): EntitySettings
	{
		$parameters = (array)$parameters;
		$parameters['ID'] = $filterId;
		
		if ($entityTypeId === \CCrmOwnerType::Lead)
		{
			return new LeadSettings($parameters);
		}
		if ($entityTypeId === \CCrmOwnerType::Deal)
		{
			return new DealSettings($parameters);
		}
		if ($entityTypeId === \CCrmOwnerType::Contact)
		{
			return new ContactSettings($parameters);
		}
		if ($entityTypeId === \CCrmOwnerType::Company)
		{
			return new CompanySettings($parameters);
		}
		if ($entityTypeId === \CCrmOwnerType::Quote)
		{
			return new QuoteSettings($parameters);
		}
		if ($entityTypeId === \CCrmOwnerType::Invoice)
		{
			return new InvoiceSettings($parameters);
		}
		if ($entityTypeId === \CCrmOwnerType::Order)
		{
			return new OrderSettings($parameters);
		}
		if (\CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeId))
		{
			$type = $parameters['type'] ?? null;
			if (!$type)
			{
				$type = Crm\Service\Container::getInstance()->getTypeByEntityTypeId($entityTypeId);
			}
			if ($type)
			{
				return new ItemSettings($parameters, $type);
			}
		}

		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeId);
		throw $this->getNotSupportedException($entityTypeName);
	}

	public function getDataProvider(Main\Filter\EntitySettings $settings): \Bitrix\Main\Filter\DataProvider
	{
		if ($settings instanceof LeadSettings)
		{
			return new LeadDataProvider($settings);
		}
		if ($settings instanceof DealSettings)
		{
			return new DealDataProvider($settings);
		}
		if ($settings instanceof ContactSettings)
		{
			return new ContactDataProvider($settings);
		}
		if ($settings instanceof CompanySettings)
		{
			return new CompanyDataProvider($settings);
		}
		if ($settings instanceof QuoteSettings)
		{
			return new QuoteDataProvider($settings);
		}
		if ($settings instanceof InvoiceSettings)
		{
			return new InvoiceDataProvider($settings);
		}
		if ($settings instanceof DealSettings)
		{
			return new DealDataProvider($settings);
		}
		if ($settings instanceof OrderSettings)
		{
			return new OrderDataProvider($settings);
		}
		if ($settings instanceof TimelineSettings)
		{
			return new TimelineDataProvider($settings);
		}
		if ($settings instanceof ItemSettings)
		{
			$factory = Crm\Service\Container::getInstance()->getFactory($settings->getType()->getEntityTypeId());
			if ($factory)
			{
				return new ItemDataProvider($settings, $factory);
			}
		}

		throw $this->getNotSupportedException($settings->getEntityTypeName());
	}

	public function getUserFieldDataProvider(Main\Filter\EntitySettings $settings): DataProvider
	{
		if ($settings instanceof ItemSettings)
		{
			return new ItemUfDataProvider($settings);
		}

		return new UserFieldDataProvider($settings);
	}

	public function getRequisiteDataProvider(EntitySettings $settings): RequisiteDataProvider
	{
		return new RequisiteDataProvider($settings);
	}

	public function getClientDataProviders(EntitySettings $settings): array
	{
		$dataProviders = [];
		$firstClientEntityId = \Bitrix\Crm\Component\EntityList\ClientDataProvider::getPriorityEntityTypeId();
		$secondClientEntityId =
			($firstClientEntityId === \CCrmOwnerType::Contact)
				? \CCrmOwnerType::Company
				: \CCrmOwnerType::Contact
		;

		$dataProviders[] = new ClientDataProvider($firstClientEntityId, $settings);
		$dataProviders[] = new ClientUserFieldDataProvider($firstClientEntityId, $settings);
		$dataProviders[] = new ClientDataProvider($secondClientEntityId, $settings);
		$dataProviders[] = new ClientUserFieldDataProvider($secondClientEntityId, $settings);

		return $dataProviders;
	}

	public function getFilter(EntitySettings $settings, ?array $parameters = []): ?Filter
	{
		$filterId = $settings->getID();
		$parameters = (array)$parameters;
		$factory = Container::getInstance()->getFactory($settings->getEntityTypeID());
		$provider = $this->getDataProvider($settings);
		if (!$provider)
		{
			throw $this->getNotSupportedException($settings->getEntityTypeName());
		}
		$additionalProviders = [];
		if (!($settings instanceof TimelineSettings))
		{
			$additionalProviders[] = $this->getUserFieldDataProvider($settings);
		}
		if ($settings instanceof ContactSettings || $settings instanceof CompanySettings)
		{
			$additionalProviders[] = $this->getRequisiteDataProvider($settings);
		}
		if (
			$settings instanceof DealSettings
			&& !$settings->checkFlag(DealSettings::FLAG_RECURRING)
			&& $settings->checkFlag(DealSettings::FLAG_ENABLE_CLIENT_FIELDS)
		)
		{
			$additionalProviders = array_merge($additionalProviders, $this->getClientDataProviders($settings));
		}

		$parameters['filterFieldsCallback'] = null;
		$parameters['modifyFieldsCallback'] = null;

		if (
			$settings instanceof ISettingsSupportsCategory
			&& $factory
			&& ($categoryId = $settings->getCategoryId())
			&& ($category = $factory->getCategory($categoryId))
		)
		{
			$parameters['filterFieldsCallback'] = function ($code) use ($factory, $category)
			{
				// filter out tracking fields for categories that do not support tracking
				if (
					in_array($code, Tracking\UI\Filter::getFields(), true)
					&& !$category->isTrackingEnabled()
				)
				{
					return false;
				}

				// filter out category-specific disabled fields
				return !in_array(
					$factory->getCommonFieldNameByMap($code),
					$category->getDisabledFieldNames(),
					true
				);
			};

			$categoryUISettings = $category->getUISettings();
			$defaultFilterFields = isset($categoryUISettings['grid']['defaultFields'])
				? $categoryUISettings['filter']['defaultFields']
				: [];

			if ($defaultFilterFields)
			{
				$parameters['modifyFieldsCallback'] = function (Main\Filter\Field $field) use ($defaultFilterFields)
				{
					$field->markAsDefault(
						in_array($field->getId(), $defaultFilterFields, true)
					);

					return $field;
				};
			}
		}

		return $this->createFilter($filterId, $provider, $additionalProviders, $parameters);
	}

	public function createFilter(
		$ID,
		DataProvider $entityDataProvider,
		array $extraDataProviders = null,
		array $params = null
	): Filter
	{
		return new Filter($ID, $entityDataProvider, (array)$extraDataProviders, (array)$params);
	}

	protected function getNotSupportedException($entityTypeName): Main\NotSupportedException
	{
		return new Main\NotSupportedException(
			"Entity type: '{$entityTypeName}' is not supported in current context."
		);
	}

	/**
	 * Create Filter by specified entity type ID.
	 * @param EntitySettings $settings Entity Filter settings.
	 * @return Filter
	 * @throws Main\NotSupportedException
	 */
	public static function createEntityFilter(EntitySettings $settings)
	{
		return Crm\Service\Container::getInstance()->getFilterFactory()->getFilter($settings);
	}

	public static function createEntitySettings($entityTypeID, $filterID, array $parameters = [])
	{
		return Crm\Service\Container::getInstance()->getFilterFactory()->getSettings($entityTypeID, $filterID, $parameters);
	}

	/**
	 * Convert $parameters in suitable for EntitySettings format
	 * @param int $entityTypeId
	 * @param array $parameters
	 * @return array
	 */
	public static function convertSettingsParams(int $entityTypeId, array $parameters): array
	{
		if ($entityTypeId === \CCrmOwnerType::Deal)
		{
			if (!isset($parameters['flags']))
			{
				$parameters['flags'] = DealSettings::FLAG_NONE | DealSettings::FLAG_ENABLE_CLIENT_FIELDS;
			}
			if (isset($parameters['IS_RECURRING']))
			{
				if ($parameters['IS_RECURRING'] === 'Y')
				{
					$parameters['flags'] |= DealSettings::FLAG_RECURRING;
				}
				unset($parameters['IS_RECURRING']);
			}
		}
		if (isset($parameters['CATEGORY_ID']))
		{
			$parameters['categoryID'] = $parameters['CATEGORY_ID'];
			unset($parameters['CATEGORY_ID']);
		}

		return $parameters;
	}

	/**
	 * Get Settings object with correct parameters according to $gridId
	 * @param int $entityTypeId
	 * @param string $gridId
	 * @return EntitySettings
	 * @throws Main\NotSupportedException
	 */
	public static function getSettingsByGridId(int $entityTypeId, string $gridId): EntitySettings
	{
		$parameters = self::extractSettingsParamsFromGridId($entityTypeId, $gridId);
		$parameters = self::convertSettingsParams($entityTypeId, $parameters);

		return Crm\Service\Container::getInstance()->getFilterFactory()->getSettings($entityTypeId, $gridId, $parameters);
	}

	protected static function extractSettingsParamsFromGridId(int $entityTypeId, string $gridId): array
	{
		$parameters = [];
		if ($entityTypeId === \CCrmOwnerType::Deal && mb_strpos($gridId, 'CRM_DEAL_RECUR') === 0)
		{
			$parameters['IS_RECURRING'] = 'Y';
		}
		$factory = Crm\Service\Container::getInstance()->getFactory($entityTypeId);
		if ($factory && $factory->isCategoriesEnabled())
		{
			if (in_array($entityTypeId, [\CCrmOwnerType::Contact, \CCrmOwnerType::Company]))
				// category = 0 should be used by default in contacts and companies only
			{
				$parameters['CATEGORY_ID'] = 0;
			}
			// Deal, Contacts, Companies format:
			if (preg_match('/_C_(\d+)$/', $gridId, $matches))
			{
				$parameters['CATEGORY_ID'] = (int)$matches[1];
			}
			// Smart processes format:
			if (preg_match('/^crm-type-item-list-(\d+)-(\d+)$/', $gridId, $matches))
			{
				$parameters['CATEGORY_ID'] = (int)$matches[2];
			}
		}

		return $parameters;
	}
}
