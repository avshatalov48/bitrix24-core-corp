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
		/** @var \CCrmLead|\CCrmDeal|\CCrmInvoice|\CCrmQuote|\CCrmContact|\CCrmCompany $provider */
		$provider = $this->getItemsProvider();
		$method = method_exists($provider, 'getListEx') ? 'getListEx' : 'getList';

		$options = [];
		if(isset($parameters['limit'], $parameters['offset']))
		{
			$options = [
				'QUERY_OPTIONS' => [
					'LIMIT' => $parameters['limit'],
					'OFFSET' => $parameters['offset'],
				],
			];
		}

		return $provider::$method($parameters['order'], $parameters['filter'], false, false, $parameters['select'], $options);
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
