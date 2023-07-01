<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Crm;

use Bitrix\Crm\Attribute\Entity\FieldAttributeTable;
use Bitrix\Crm\Attribute\FieldAttributeManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\Result;
use Bitrix\Main\UserField\Internal\TemporaryStorage;

Loc::loadMessages(__FILE__);

/**
 * Class StatusTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Status_Query query()
 * @method static EO_Status_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Status_Result getById($id)
 * @method static EO_Status_Result getList(array $parameters = [])
 * @method static EO_Status_Entity getEntity()
 * @method static \Bitrix\Crm\EO_Status createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\EO_Status_Collection createCollection()
 * @method static \Bitrix\Crm\EO_Status wakeUpObject($row)
 * @method static \Bitrix\Crm\EO_Status_Collection wakeUpCollection($rows)
 */
class StatusTable extends Entity\DataManager
{
	public const ENTITY_ID_SOURCE = 'SOURCE';
	public const ENTITY_ID_HONORIFIC = 'HONORIFIC';
	public const ENTITY_ID_DEAL_TYPE = 'DEAL_TYPE';
	public const ENTITY_ID_COMPANY_TYPE = 'COMPANY_TYPE';
	public const ENTITY_ID_CONTACT_TYPE = 'CONTACT_TYPE';
	public const ENTITY_ID_INDUSTRY = 'INDUSTRY';
	public const ENTITY_ID_EMPLOYEES = 'EMPLOYEES';

	public const DEFAULT_SUCCESS_COLOR = '#DBF199';
	public const DEFAULT_FAILURE_COLOR = '#FFBEBD';
	public const DEFAULT_PROCESS_COLOR = '#ACE9FB';

	// entityId => array of status data arrays
	protected const CACHE_TTL = 3600;
	/** @var array[][] */
	protected static $statusesCache = [];

	protected static $temporaryStorage;

	public static function getTableName(): string
	{
		return 'b_crm_status';
	}

	public static function getMap(): array
	{
		return [
			(new ORM\Fields\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new ORM\Fields\StringField('ENTITY_ID'))
				->configureRequired()
				->configureSize(50),
			(new ORM\Fields\StringField('STATUS_ID'))
				->configureRequired()
				->configureSize(50),
			(new ORM\Fields\StringField('NAME'))
				->configureRequired()
				->configureSize(100),
			(new ORM\Fields\StringField('NAME_INIT'))
				->configureSize(100),
			(new ORM\Fields\IntegerField('SORT'))
				->configureRequired(),
			(new ORM\Fields\BooleanField('SYSTEM'))
				->configureRequired()
				->configureValues('N', 'Y')
				->configureDefaultValue('N'),
			(new ORM\Fields\StringField('COLOR'))
				->configureSize(10),
			(new ORM\Fields\EnumField('SEMANTICS'))
				->configureValues([
					PhaseSemantics::PROCESS,
					PhaseSemantics::FAILURE,
					PhaseSemantics::SUCCESS,
				]),
			(new ORM\Fields\IntegerField('CATEGORY_ID')),
		];
	}

	public static function onBeforeAdd(Event $event): ORM\EventResult
	{
		$result = new ORM\EventResult();
		$fields = $event->getParameter('fields');

		if(isset($fields['SEMANTICS']) && $fields['SEMANTICS'] === PhaseSemantics::SUCCESS)
		{
			$existingSuccessStatus = static::getList([
				'select' => ['ID'],
				'filter' => [
					'=ENTITY_ID' => $fields['ENTITY_ID'],
					'=SEMANTICS' => PhaseSemantics::SUCCESS,
				],
				'limit' => 1,
			])->fetch();
			if($existingSuccessStatus)
			{
				$result->addError(new ORM\EntityError(Loc::getMessage('CRM_STATUS_MORE_THAN_ONE_SUCCESS_ERROR')));
			}
		}

		return $result;
	}

	public static function onAfterAdd(Event $event)
	{
		/** @var EO_Status $status */
		$status = $event->getParameter('object');
		static::removeStatusesFromCache($status->fillEntityId());

		$data = $event->getParameter('fields');
		$entity = [];
		$entityId = '';
		$entityTypeId = \CCrmOwnerType::Undefined;
		if (isset($data['ENTITY_ID']))
		{
			$entityId = $data['ENTITY_ID'];
			$entityTypes = \CCrmStatus::GetEntityTypes();
			if (isset($entityTypes[$entityId]))
			{
				$entity = $entityTypes[$entityId];
			}
		}
		if (isset($entity['ENTITY_TYPE_ID']))
		{
			$entityTypeId = (int)$entity['ENTITY_TYPE_ID'];
		}
		if (\CCrmOwnerType::IsDefined($entityTypeId))
		{
			$entityScope = $entity['FIELD_ATTRIBUTE_SCOPE'] ?? '';

			FieldAttributeManager::processPhaseCreation(
				$data['STATUS_ID'],
				$entityTypeId,
				$entityScope,
				static::getStatusesByEntityId($entityId)
			);
		}
	}

	public static function onBeforeUpdate(Event $event): ORM\EventResult
	{
		$result = new ORM\EventResult();
		$id = $event->getParameter('id');
		$fields = $event->getParameter('fields');
		$data = null;
		if(isset($fields['ENTITY_ID']))
		{
			$data = static::getById($id)->fetch();
			if((int) $data['ENTITY_ID'] !== (int) $fields['ENTITY_ID'])
			{
				$result->addError(new ORM\EntityError(Loc::getMessage('CRM_STATUS_FIELD_UPDATE_ERROR', [
					'#STATUS_FIELD#' => 'ENTITY_ID',
				])));
			}
		}
		if(isset($fields['STATUS_ID']))
		{
			if(!$data)
			{
				$data = static::getById($id)->fetch();
			}
			if($data['STATUS_ID'] !== $fields['STATUS_ID'])
			{
				$result->addError(new ORM\EntityError(Loc::getMessage('CRM_STATUS_FIELD_UPDATE_ERROR', [
					'#STATUS_FIELD#' => 'STATUS_ID',
				])));
			}
		}
		if(isset($fields['SEMANTICS']) && $fields['SEMANTICS'] === PhaseSemantics::SUCCESS)
		{
			if(!$data)
			{
				$data = static::getById($id)->fetch();
			}
			if($data['SEMANTICS'] !== $fields['SEMANTICS'])
			{
				$result->addError(new ORM\EntityError(Loc::getMessage('CRM_STATUS_SUCCESS_SEMANTIC_UPDATE_ERROR')));
			}
		}

		return $result;
	}

	public static function onAfterUpdate(Event $event): EventResult
	{
		$result = new EventResult();

		$id = $event->getParameter('id');
		$data = static::getById($id)->fetch();

		$entityId = $data['ENTITY_ID'] ?? null;
		$entity = \CCrmStatus::GetEntityTypes()[$entityId] ?? null;
		if (!$entity)
		{
			return $result;
		}

		static::removeStatusesFromCache($entityId);

		$entityTypeId = $entity['ENTITY_TYPE_ID'] ?? null;
		if (!$entityTypeId)
		{
			return $result;
		}
		$entityScope = $entity['FIELD_ATTRIBUTE_SCOPE'] ?? '';

		FieldAttributeManager::processPhaseModification(
			$data['STATUS_ID'],
			$entityTypeId,
			$entityScope,
			static::getStatusesByEntityId($entityId)
		);

		return $result;
	}

	public static function deleteByEntityId(string $entityId): Result
	{
		$result = new Result();

		$list = static::getList([
			'select' => ['ID'],
			'filter' => [
				'=ENTITY_ID' => $entityId,
			],
		]);
		while($item = $list->fetch())
		{
			$deleteResult = static::delete($item['ID']);
			if(!$deleteResult->isSuccess())
			{
				$result->addErrors($deleteResult->getErrors());
			}
		}

		return $result;
	}

	protected static function getTemporaryStorage(): TemporaryStorage
	{
		if(!static::$temporaryStorage)
		{
			static::$temporaryStorage = new TemporaryStorage();
		}

		return static::$temporaryStorage;
	}

	public static function onBeforeDelete(Event $event): EventResult
	{
		$data = static::getByPrimary($event->getParameter('id'))->fetch();

		$result = new EventResult();
		if (!$data)
		{
			return $result;
		}

		static::getTemporaryStorage()->saveData($event->getParameter('id'), $data);

		$entityId = $data['ENTITY_ID'];
		$entity = \CCrmStatus::GetEntityTypes()[$entityId] ?? null;
		if (!$entity)
		{
			return $result;
		}

		$factory = static::getFactoryByStagesEntityId($entityId);
		if (!$factory)
		{
			return $result;
		}

		$itemsOnStageCount = $factory->getItemsCount([
			'=' . Item::FIELD_NAME_STAGE_ID => $data['STATUS_ID'],
		]);

		if ($itemsOnStageCount > 0)
		{
			$result->addError(new ORM\EntityError(Loc::getMessage('CRM_STATUS_STAGE_WITH_ITEMS_ERROR')));
		}

		$entityTypeId = $entity['ENTITY_TYPE_ID'] ?? null;
		if ($entityTypeId)
		{
			FieldAttributeManager::processPhaseDeletion(
				$data['STATUS_ID'],
				$entityTypeId,
				$entity['FIELD_ATTRIBUTE_SCOPE'] ?? '',
				static::getStatusesByEntityId($entityId)
			);
		}

		return $result;
	}

	protected static function getFactoryByStagesEntityId(string $stagesEntityId): ?Factory
	{
		foreach (Container::getInstance()->getTypesMap()->getFactories() as $factory)
		{
			if (!$factory->isStagesSupported())
			{
				continue;
			}

			if ($factory->getStagesEntityId() === $stagesEntityId)
			{
				return $factory;
			}

			if ($factory->isCategoriesSupported())
			{
				foreach ($factory->getCategories() as $category)
				{
					if ($factory->getStagesEntityId($category->getId()) === $stagesEntityId)
					{
						return $factory;
					}
				}
			}
		}

		return null;
	}

	public static function onAfterDelete(Event $event): EventResult
	{
		$result = new EventResult();

		$data = static::getTemporaryStorage()->getData($event->getParameter('id'));

		$entityId = $data['ENTITY_ID'];
		$entity = \CCrmStatus::GetEntityTypes()[$entityId] ?? null;
		if (!$entity)
		{
			return $result;
		}

		static::removeStatusesFromCache($entityId);

		return $result;
	}

	/**
	 * There should be a way to determine that entityId matches particular entity.
	 * todo refactor it on event or some other way
	 *
	 * @param string $entityId
	 * @return array|null
	 */
	public static function parseStageEntityId(string $entityId): ?array
	{
		if(preg_match('#^TYPE_(\d+)_STAGE$#', $entityId, $matches))
		{
			return [
				'entityTypeId' => (int) $matches[1],
			];
		}

		return null;
	}

	/**
	 * @param string $entityId
	 *
	 * @return array[]
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getStatusesByEntityId(string $entityId): array
	{
		if (defined('CACHED_b_crm_status') && CACHED_b_crm_status === false)
		{
			return static::loadStatusesByEntityId($entityId);
		}

		$cachedStatuses = static::getStatusesFromCache($entityId);
		if (!is_null($cachedStatuses))
		{
			return $cachedStatuses;
		}

		$statuses = static::loadStatusesByEntityId($entityId);
		static::addStatusesToCache($entityId, $statuses);

		return $statuses;
	}

	protected static function getStatusesFromCache(string $entityId): ?array
	{
		return static::$statusesCache[$entityId] ?? null;
	}

	protected static function addStatusesToCache(string $entityId, array $statuses): void
	{
		static::$statusesCache[$entityId] = $statuses;
	}

	protected static function removeStatusesFromCache(string $entityId): void
	{
		unset(static::$statusesCache[$entityId]);
	}

	protected static function clearStatusesCache(): void
	{
		static::$statusesCache = [];
	}

	/**
	 * @param string $entityId
	 *
	 * @return array[]
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function loadStatusesByEntityId(string $entityId): array
	{
		$result = [];

		$list = static::getList([
			'filter' => [
				'=ENTITY_ID' => $entityId,
			],
			'order' => [
				'SORT' => 'ASC',
				'ID' => 'ASC'
			],
			'cache' => ['ttl' => static::CACHE_TTL],
		]);
		while($status = $list->fetch())
		{
			$result[$status['STATUS_ID']] = $status;
		}

		return $result;
	}

	/**
	 * Returns flat list of statuses for $entityId, where key is STATUS_ID and value is NAME.
	 *
	 * @param string $entityId
	 *
	 * @return string[]
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getStatusesList(string $entityId): array
	{
		$statusList = [];
		foreach (static::loadStatusesByEntityId($entityId) as $status)
		{
			$statusId = $status['STATUS_ID'];
			$statusList[$statusId] = $status['NAME'];
		}

		return $statusList;
	}

	/**
	 * @param string $entityId
	 *
	 * @return string[]|int[]
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getStatusesIds(string $entityId): array
	{
		return array_keys(static::getStatusesList($entityId));
	}
}
