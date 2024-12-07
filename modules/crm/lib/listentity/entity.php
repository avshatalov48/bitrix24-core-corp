<?php


namespace Bitrix\Crm\ListEntity;


use Bitrix\Crm\Service\Container;
use Bitrix\Main\DI\ServiceLocator;

abstract class Entity
{
	protected static $instances = [];

	public static function getInstance(string $entityTypeName): ?Entity
	{
		if(!array_key_exists($entityTypeName, static::$instances))
		{
			$instance = null;
			if($entityTypeName === \CCrmOwnerType::LeadName)
			{
				$instance = ServiceLocator::getInstance()->get('crm.listEntity.entity.lead');
			}
			elseif($entityTypeName === \CCrmOwnerType::DealName)
			{
				$instance = ServiceLocator::getInstance()->get('crm.listEntity.entity.deal');
			}
			elseif($entityTypeName === \CCrmOwnerType::ContactName)
			{
				$instance = ServiceLocator::getInstance()->get('crm.listEntity.entity.contact');
			}
			elseif($entityTypeName === \CCrmOwnerType::CompanyName)
			{
				$instance = ServiceLocator::getInstance()->get('crm.listEntity.entity.company');
			}
			elseif($entityTypeName === \CCrmOwnerType::InvoiceName)
			{
				$instance = ServiceLocator::getInstance()->get('crm.listEntity.entity.invoice');
			}
			elseif($entityTypeName === \CCrmOwnerType::QuoteName)
			{
				$instance = ServiceLocator::getInstance()->get('crm.listEntity.entity.quote');
			}
			elseif($entityTypeName === \CCrmOwnerType::OrderName)
			{
				$instance = ServiceLocator::getInstance()->get('crm.listEntity.entity.order');
			}
			elseif($entityTypeName === \CCrmOwnerType::ActivityName)
			{
				$instance = ServiceLocator::getInstance()->get('crm.listEntity.entity.activity');
			}
			elseif($entityTypeName === \CCrmOwnerType::SmartInvoiceName)
			{
				$factory = Container::getInstance()->getFactory(\CCrmOwnerType::SmartInvoice);
				$instance = ServiceLocator::getInstance()->get('crm.listEntity.entity.smartInvoice');
				if ($factory)
				{
					$instance->setFactory($factory);
				}
				else
				{
					return null;
				}
			}
			else
			{
				$typeId = \CCrmOwnerType::ResolveID($entityTypeName);
				if (\CCrmOwnerType::isPossibleDynamicTypeId($typeId))
				{
					$factory = Container::getInstance()->getFactory($typeId);
					if ($factory)
					{
						if (ServiceLocator::getInstance()->has('crm.listEntity.entity.dynamic'))
						{
							$instance = clone ServiceLocator::getInstance()->get('crm.listEntity.entity.dynamic');
						}
						else
						{
							$instance = ServiceLocator::getInstance()->get('crm.listEntity.entity.dynamic');
						}
						$instance->setFactory($factory);
					}
				}
			}
			static::$instances[$entityTypeName] = $instance;
		}

		return static::$instances[$entityTypeName];
	}

	/**
	 * @param array $parameters
	 * @return \CDBResult
	 */
	public function getItems(array $parameters): \CDBResult
	{
		/** @var \CCrmLead|\CCrmDeal|\CCrmInvoice|\CCrmQuote|\CCrmContact|\CCrmCompany|\CCrmActivity $provider */
		$provider = $this->getItemsProvider();
		$method = method_exists($provider, 'getListEx') ? 'getListEx' : 'getList';

		$options = [];
		if (isset($parameters['options']))
		{
			if (isset($parameters['options']['FIELD_OPTIONS']))
			{
				$options['FIELD_OPTIONS'] = $parameters['options']['FIELD_OPTIONS'];
			}
			if (isset($parameters['options']['IS_EXTERNAL_CONTEXT']))
			{
				$options['IS_EXTERNAL_CONTEXT'] = $parameters['options']['IS_EXTERNAL_CONTEXT'];
			}
		}

		if(isset($parameters['limit'], $parameters['offset']))
		{
			$options['QUERY_OPTIONS'] = [
				'LIMIT' => $parameters['limit'],
				'OFFSET' => $parameters['offset'],
			];
		}
		$filter = $parameters['filter'];
		if (is_array($filter) && ($filter['CATEGORY_ID'] ?? null) === 0)
		{
			$filter['@CATEGORY_ID'] = $filter['CATEGORY_ID'];
			unset($filter['CATEGORY_ID']);
		}
		$resourceBookingFilter = null;
		if (is_array($filter) && (isset($filter['CALENDAR_FIELD'])) && (isset($filter['CALENDAR_DATE_FROM'])) && (isset($filter['CALENDAR_DATE_TO'])))
		{
			$resourceBookingFilter = [
				'CALENDAR_DATE_FROM' => $filter['CALENDAR_DATE_FROM'],
				'CALENDAR_DATE_TO' => $filter['CALENDAR_DATE_TO'],
				'CALENDAR_FIELD' => $filter['CALENDAR_FIELD'],
			];
		}

		$fieldsSelect = array_unique($parameters['select']);
		if (count($fieldsSelect) > 1 || !isset($fieldsSelect['ID']))
		{
			$onlyIdsSelect = ['ID'];
			$idsResult = $provider::$method($parameters['order'], $filter, false, false, $onlyIdsSelect, $options);

			$ids = [];
			while ($item = $idsResult->Fetch())
			{
				$ids[] = $item['ID'];
			}

			if (empty($ids))
			{
				return new \CDBResult();
			}

			if (isset($options['QUERY_OPTIONS']))
			{
				unset($options['QUERY_OPTIONS']);
			}
			$filter = [
				'@ID' => $ids,
				'CHECK_PERMISSIONS' => 'N',
			];
			if (is_array($resourceBookingFilter))
			{
				$filter = array_merge($filter, $resourceBookingFilter);
			}
		}

		return $provider::$method($parameters['order'], $filter, false, false, $fieldsSelect, $options);
	}

	final public function getCount(array $filter): int
	{
		if (($filter['CATEGORY_ID'] ?? null) === 0)
		{
			$filter['@CATEGORY_ID'] = $filter['CATEGORY_ID'];
			unset($filter['CATEGORY_ID']);
		}

		$provider = $this->getItemsProvider();
		$method = method_exists($provider, 'getListEx') ? 'getListEx' : 'getList';

		$result = $provider::$method([], $filter, [], false, [], []);

		if (is_numeric($result))
		{
			return (int)$result;
		}

		return 0;
	}

	/**
	 * @return \CCrmLead|\CCrmDeal|\CCrmInvoice|\CCrmQuote|\CCrmContact|\CCrmCompany
	 */
	protected function getItemsProvider(): string
	{
		return '\CCrm' . $this->getTypeName();
	}

	abstract public function getTypeName(): string;
}
