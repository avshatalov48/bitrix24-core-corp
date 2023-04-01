<?php

namespace Bitrix\Crm\Service;

use Bitrix\Crm\Badge\Badge;
use Bitrix\Crm\Conversion;
use Bitrix\Crm\Filter;
use Bitrix\Crm\Integration;
use Bitrix\Crm\Integration\PullManager;
use Bitrix\Crm\Model\Dynamic\Type;
use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Crm\Relation\Registrar;
use Bitrix\Crm\Relation\RelationManager;
use Bitrix\Crm\Service\Factory\Dynamic;
use Bitrix\Crm\Service\Sale\Shipment\ProductService;
use Bitrix\Crm\Timeline;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\Loader;

class Container
{
	protected static $dynamicFactoriesClassName = Dynamic::class;

	public static function getInstance(): Container
	{
		return ServiceLocator::getInstance()->get('crm.service.container');
	}

	/**
	 * Returns ServiceLocator service identifier by the provided class name
	 *
	 * For example, \Bitrix\Crm\Service\Container -> crm.service.container
	 *
	 * @param string $className
	 * @param array|null $parameters
	 *
	 * @return string
	 * @throws ArgumentException
	 */
	public static function getIdentifierByClassName(string $className, array $parameters = null): string
	{
		$words = explode('\\', $className);
		$identifier = '';
		foreach ($words as $index => $word)
		{
			$word = lcfirst($word);
			if ($word === 'bitrix' && $index === 0)
			{
				continue;
			}
			if (!empty($identifier))
			{
				$identifier .= '.';
			}
			$identifier .= $word;
		}
		if (empty($identifier))
		{
			throw new ArgumentException('className should be a valid string');
		}
		if(!empty($parameters))
		{
			$parameters = array_filter($parameters, static function($parameter) {
				return (!empty($parameter) && (is_string($parameter) || is_numeric($parameter)));
			});
			if(!empty($parameters))
			{
				$identifier .= '.' . implode('.', $parameters);
			}
		}

		return $identifier;
	}

	public function getFactory(int $entityTypeId): ?Factory
	{
		if ($entityTypeId === \CCrmOwnerType::Lead)
		{
			return ServiceLocator::getInstance()->get('crm.service.factory.lead');
		}
		if ($entityTypeId === \CCrmOwnerType::Deal)
		{
			return ServiceLocator::getInstance()->get('crm.service.factory.deal');
		}
		if ($entityTypeId === \CCrmOwnerType::Contact)
		{
			return ServiceLocator::getInstance()->get('crm.service.factory.contact');
		}
		if ($entityTypeId === \CCrmOwnerType::Company)
		{
			return ServiceLocator::getInstance()->get('crm.service.factory.company');
		}
		if ($entityTypeId === \CCrmOwnerType::Quote)
		{
			return ServiceLocator::getInstance()->get('crm.service.factory.quote');
		}
		if ($entityTypeId === \CCrmOwnerType::Order)
		{
			if (!Loader::includeModule('sale'))
			{
				return null;
			}

			return ServiceLocator::getInstance()->get('crm.service.factory.order');
		}
		if (\CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeId))
		{
			$identifier = static::getIdentifierByClassName(static::$dynamicFactoriesClassName, [$entityTypeId]);
			if(!ServiceLocator::getInstance()->has($identifier))
			{
				$type = $this->getTypeByEntityTypeId($entityTypeId);
				if($type)
				{
					if ($entityTypeId === \CCrmOwnerType::SmartInvoice)
					{
						$factoryClassName = Factory\SmartInvoice::class;
					}
					elseif ($entityTypeId === \CCrmOwnerType::SmartDocument)
					{
						$factoryClassName = Factory\SmartDocument::class;
					}
					else
					{
						$factoryClassName = static::$dynamicFactoriesClassName;
					}
					$factory = new $factoryClassName($type);
					ServiceLocator::getInstance()->addInstance(
						$identifier,
						$factory
					);

					return $factory;
				}

				return null;
			}

			return ServiceLocator::getInstance()->get($identifier);
		}

		return null;
	}

	public function getDynamicFactoryByType(Type $type): Dynamic
	{
		if (!$type->getId())
		{
			throw new InvalidOperationException('Type should be saved before use');
		}
		$identifierById = static::getIdentifierByClassName(Type::class, ['id', $type->getId()]);
		$identifierByEntityTypeId = static::getIdentifierByClassName(
			Type::class,
			['entityTypeId', $type->getEntityTypeId()]
		);
		$serviceLocator = ServiceLocator::getInstance();
		if (!$serviceLocator->has($identifierById))
		{
			$serviceLocator->addInstance($identifierById, $type);
		}
		if (!$serviceLocator->has($identifierByEntityTypeId))
		{
			$serviceLocator->addInstance($identifierByEntityTypeId, $type);
		}

		return $this->getFactory($type->getEntityTypeId());
	}

	public function getUserPermissions(?int $userId = null): UserPermissions
	{
		if($userId === null)
		{
			$userId = $this->getContext()->getUserId();
		}

		$identifier = static::getIdentifierByClassName(UserPermissions::class, [$userId]);

		if(!ServiceLocator::getInstance()->has($identifier))
		{
			$userPermissions = $this->createUserPermissions($userId);
			ServiceLocator::getInstance()->addInstance($identifier, $userPermissions);
		}

		return ServiceLocator::getInstance()->get($identifier);
	}

	protected function createUserPermissions(int $userId): UserPermissions
	{
		return new UserPermissions($userId);
	}

	public function getType(int $id): ?Type
	{
		$identifierById = static::getIdentifierByClassName(Type::class, ['id', $id]);
		if(!ServiceLocator::getInstance()->has($identifierById))
		{
			$type = $this->getDynamicTypesMap()->getTypesCollection()->getByPrimary($id);
			/** @var Type $type */
			if($type)
			{
				ServiceLocator::getInstance()->addInstance($identifierById, $type);
				$identifierByEntityTypeId = static::getIdentifierByClassName(
					Type::class,
					['entityTypeId', $type->getEntityTypeId()]
				);
				ServiceLocator::getInstance()->addInstance($identifierByEntityTypeId, $type);

				return $type;
			}

			return null;
		}

		return ServiceLocator::getInstance()->get($identifierById);
	}

	public function getTypeByEntityTypeId(int $entityTypeId): ?Type
	{
		$identifierByEntityTypeId = static::getIdentifierByClassName(Type::class, ['entityTypeId', $entityTypeId]);
		if(!ServiceLocator::getInstance()->has($identifierByEntityTypeId))
		{
			/** @var Type $type */
			$type = null;
			foreach ($this->getDynamicTypesMap()->getTypesCollection() as $typeCandidate)
			{
				if ($typeCandidate->getEntityTypeId() === $entityTypeId)
				{
					$type = $typeCandidate;
					break;
				}
			}
			if($type)
			{
				ServiceLocator::getInstance()->addInstance($identifierByEntityTypeId, $type);
				$identifierById = static::getIdentifierByClassName(
					Type::class,
					['id', $type->getId()]
				);
				ServiceLocator::getInstance()->addInstance($identifierById, $type);

				return $type;
			}

			return null;
		}

		return ServiceLocator::getInstance()->get($identifierByEntityTypeId);
	}

	public function getLocalization(): Localization
	{
		return ServiceLocator::getInstance()->get('crm.service.localization');
	}

	public function getRouter(): Router
	{
		return ServiceLocator::getInstance()->get('crm.service.router');
	}

	public function getContext(): Context
	{
		return ServiceLocator::getInstance()->get('crm.service.context');
	}

	public function getOrmObjectConverter(): Converter\OrmObject
	{
		return ServiceLocator::getInstance()->get('crm.service.converter.ormObject');
	}

	public function getItemConverter(): Converter\Item
	{
		return ServiceLocator::getInstance()->get('crm.service.converter.item');
	}

	public function getStageConverter(): Converter\Stage
	{
		return ServiceLocator::getInstance()->get('crm.service.converter.stage');
	}

	/**
	 * Returns a type converter object
	 *
	 * @return Converter\Type
	 */
	public function getTypeConverter(): Converter\Type
	{
		return ServiceLocator::getInstance()->get('crm.service.converter.type');
	}

	public function getProductRowConverter(): Converter\ProductRow
	{
		return ServiceLocator::getInstance()->get('crm.service.converter.productRow');
	}

	public function getCategoryConverter(): Converter\Category
	{
		return ServiceLocator::getInstance()->get('crm.service.converter.category');
	}

	public function getEntityBroker(int $entityTypeId): ?Broker
	{
		if ($entityTypeId === \CCrmOwnerType::Lead)
		{
			return $this->getLeadBroker();
		}
		elseif ($entityTypeId === \CCrmOwnerType::Deal)
		{
			return $this->getDealBroker();
		}
		elseif ($entityTypeId === \CCrmOwnerType::Contact)
		{
			return $this->getContactBroker();
		}
		elseif ($entityTypeId === \CCrmOwnerType::Company)
		{
			return $this->getCompanyBroker();
		}
		elseif ($entityTypeId === \CCrmOwnerType::Activity)
		{
			return $this->getActivityBroker();
		}
		elseif ($entityTypeId === \CCrmOwnerType::Quote)
		{
			return $this->getQuoteBroker();
		}
		elseif ($entityTypeId === \CCrmOwnerType::Order)
		{
			return $this->getOrderBroker();
		}
		elseif (\CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeId))
		{
			return $this->getDynamicBroker()->setEntityTypeId($entityTypeId);
		}

		return null;
	}

	public function getUserBroker(): Broker\User
	{
		return ServiceLocator::getInstance()->get('crm.service.broker.user');
	}

	public function getEnumerationBroker(): Broker\Enumeration
	{
		return ServiceLocator::getInstance()->get('crm.service.broker.enumeration');
	}

	public function getFileBroker(): Broker\File
	{
		return ServiceLocator::getInstance()->get('crm.service.broker.file');
	}

	public function getIBlockElementBroker(): Broker\IBlockElement
	{
		return ServiceLocator::getInstance()->get('crm.service.broker.iblockelement');
	}

	public function getIBlockSectionBroker(): Broker\IBLockSection
	{
		return ServiceLocator::getInstance()->get('crm.service.broker.iblocksection');
	}

	public function getCompanyBroker(): Broker\Company
	{
		return ServiceLocator::getInstance()->get('crm.service.broker.company');
	}

	public function getContactBroker(): Broker\Contact
	{
		return ServiceLocator::getInstance()->get('crm.service.broker.contact');
	}

	public function getLeadBroker(): Broker\Lead
	{
		return ServiceLocator::getInstance()->get('crm.service.broker.lead');
	}

	public function getDealBroker(): Broker\Deal
	{
		return ServiceLocator::getInstance()->get('crm.service.broker.deal');
	}

	public function getOrderBroker(): Broker\Order
	{
		return ServiceLocator::getInstance()->get('crm.service.broker.order');
	}

	public function getDynamicBroker(): Broker\Dynamic
	{
		return ServiceLocator::getInstance()->get('crm.service.broker.dynamic');
	}

	public function getActivityBroker(): Broker\Activity
	{
		return ServiceLocator::getInstance()->get('crm.service.broker.activity');
	}

	public function getQuoteBroker(): Broker\Quote
	{
		return ServiceLocator::getInstance()->get('crm.service.broker.quote');
	}

	public function getBadge(string $type, string $value): Badge
	{
		$identifier = static::getIdentifierByClassName(Badge::class, [$type, $value]);

		if(!ServiceLocator::getInstance()->has($identifier))
		{
			$badge = $this->createBadge($type, $value);
			ServiceLocator::getInstance()->addInstance($identifier, $badge);
		}

		return ServiceLocator::getInstance()->get($identifier);
	}

	protected function createBadge(string $type, string $value): Badge
	{
		return Badge::createByType($type, $value);
	}

	public function getDirector(): Director
	{
		return ServiceLocator::getInstance()->get('crm.service.director');
	}

	/**
	 * @return string|TypeTable
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 */
	public function getDynamicTypeDataClass(): string
	{
		return ServiceLocator::getInstance()->get('crm.type.factory')->getTypeDataClass();
	}

	public function getEventHistory(): EventHistory
	{
		return ServiceLocator::getInstance()->get('crm.service.eventhistory');
	}

	public function getRelationRegistrar(): Registrar
	{
		return ServiceLocator::getInstance()->get('crm.service.relation.registrar');
	}

	/**
	 * Get an instance of TypesMap
	 *
	 * @return TypesMap
	 */
	public function getTypesMap(): TypesMap
	{
		return ServiceLocator::getInstance()->get('crm.service.typesMap');
	}

	public function getDynamicTypesMap(): DynamicTypesMap
	{
		return ServiceLocator::getInstance()->get('crm.service.dynamicTypesMap');
	}

	public function getRelationManager(): RelationManager
	{
		return ServiceLocator::getInstance()->get('crm.relation.relationManager');
	}

	public function getTypePresetBroker(): Broker\TypePreset
	{
		return ServiceLocator::getInstance()->get('crm.service.broker.typePreset');
	}

	public function getParentFieldManager(): ParentFieldManager
	{
		return ServiceLocator::getInstance()->get('crm.service.parentFieldManager');
	}

	public function getPullManager(): PullManager
	{
		return ServiceLocator::getInstance()->get('crm.integration.pullmanager');
	}

	public function getFilterFactory(): Filter\Factory
	{
		return ServiceLocator::getInstance()->get('crm.filter.factory');
	}

	public function getAccounting(): Accounting
	{
		return ServiceLocator::getInstance()->get('crm.service.accounting');
	}

	public function getFileUploader(): FileUploader
	{
		return ServiceLocator::getInstance()->get('crm.service.fileUploader');
	}

	public function getTimelineEntryFacade(): Timeline\TimelineEntry\Facade
	{
		return ServiceLocator::getInstance()->get('crm.timeline.timelineEntry.facade');
	}

	public function getTimelinePusher(): Timeline\Pusher
	{
		return ServiceLocator::getInstance()->get('crm.timeline.pusher');
	}

	public function getTimelineHistoryDataModelMaker(): Timeline\HistoryDataModel\Maker
	{
		return ServiceLocator::getInstance()->get('crm.timeline.historyDataModel.maker');
	}

	public function getTimelineScheduledItemFactory(): \Bitrix\Crm\Service\Timeline\Item\Factory\ScheduledItem
	{
		return ServiceLocator::getInstance()->get('crm.timeline.factory.scheduledItem');
	}

	public function getTimelineHistoryItemFactory(): \Bitrix\Crm\Service\Timeline\Item\Factory\HistoryItem
	{
		return ServiceLocator::getInstance()->get('crm.timeline.factory.historyItem');
	}

	public function getTimelineActivityItemFactory(): \Bitrix\Crm\Service\Timeline\Item\Factory\ConfigurableActivity
	{
		return ServiceLocator::getInstance()->get('crm.timeline.factory.activityItem');
	}

	public function getRestEventManager(): Integration\Rest\EventManager
	{
		return ServiceLocator::getInstance()->get('crm.integration.rest.eventManager');
	}

	public function getConversionMapper(): Conversion\Mapper
	{
		return ServiceLocator::getInstance()->get('crm.conversion.mapper');
	}

	public function getMultifieldStorage(): MultifieldStorage
	{
		return ServiceLocator::getInstance()->get('crm.service.multifieldStorage');
	}

	public function getShipmentProductService(): ProductService
	{
		return ServiceLocator::getInstance()->get('crm.shipment.product');
	}
}
