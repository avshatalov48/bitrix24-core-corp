<?php

namespace Bitrix\Crm\Service;

use Bitrix\Crm\Filter;
use Bitrix\Crm\Integration\PullManager;
use Bitrix\Crm\Model\Dynamic\Type;
use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Crm\Relation\RelationManager;
use Bitrix\Crm\Service\Factory\Dynamic;
use Bitrix\Crm\UserField;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\InvalidOperationException;

class Container
{
	protected static $dynamicFactoriesClassName = Dynamic::class;

	public static function getInstance(): Container
	{
		return ServiceLocator::getInstance()->get('crm.service.container');
	}

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
		if ($entityTypeId === \CCrmOwnerType::Quote)
		{
			return ServiceLocator::getInstance()->get('crm.service.factory.quote');
		}
		if(\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
		{
			$identifier = static::getIdentifierByClassName(static::$dynamicFactoriesClassName, [$entityTypeId]);
			if(!ServiceLocator::getInstance()->has($identifier))
			{
				$type = $this->getTypeByEntityTypeId($entityTypeId);
				if($type)
				{
					$factory = new static::$dynamicFactoriesClassName($type);
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
			/** @var Type $type */
			$type = $this->getDynamicTypeDataClass()::getById($id)
					->fetchObject();
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
			$type = $this->getDynamicTypeDataClass()::getByEntityTypeId($entityTypeId)
					->fetchObject();
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

	public function getUserBroker(): Broker\User
	{
		return ServiceLocator::getInstance()->get('crm.service.broker.user');
	}

	public function getCompanyBroker(): Broker\Company
	{
		return ServiceLocator::getInstance()->get('crm.service.broker.company');
	}

	public function getContactBroker(): Broker\Contact
	{
		return ServiceLocator::getInstance()->get('crm.service.broker.contact');
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
}
