<?php

namespace Bitrix\Crm\Model\Dynamic;

use Bitrix\Crm\AutomatedSolution\Entity\AutomatedSolutionTable;
use Bitrix\Crm\Automation\Trigger\Entity\TriggerTable;
use Bitrix\Crm\Binding\EntityContactTable;
use Bitrix\Crm\Conversion\Entity\EntityConversionMapTable;
use Bitrix\Crm\EventRelationsTable;
use Bitrix\Crm\Field;
use Bitrix\Crm\Integration;
use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Crm\Integration\Recyclebin;
use Bitrix\Crm\Model\AssignedTable;
use Bitrix\Crm\Model\FieldContentTypeTable;
use Bitrix\Crm\Model\ItemCategoryTable;
use Bitrix\Crm\Observer\Entity\ObserverTable;
use Bitrix\Crm\ProductRowTable;
use Bitrix\Crm\Recycling\DynamicController;
use Bitrix\Crm\Relation\EntityRelationTable;
use Bitrix\Crm\SequenceService;
use Bitrix\Crm\Security\AccessAttribute\DynamicBasedAttrTableLifecycle;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory\Dynamic;
use Bitrix\Crm\StatusTable;
use Bitrix\Crm\Timeline\TimelineEntry;
use Bitrix\Crm\Update\Entity\LastActivityFields;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\Validator\RegExp;
use Bitrix\Main\Error;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Result as QueryResult;
use Bitrix\Main\Result;
use Bitrix\Main\Security\Random;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserField;
use Bitrix\Crm\Security\Role\Utils\RolePermissionLogContext;
use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\Crm\Feature;

/**
 * Class TypeTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Type_Query query()
 * @method static EO_Type_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Type_Result getById($id)
 * @method static EO_Type_Result getList(array $parameters = [])
 * @method static EO_Type_Entity getEntity()
 * @method static \Bitrix\Crm\Model\Dynamic\Type createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Model\Dynamic\EO_Type_Collection createCollection()
 * @method static \Bitrix\Crm\Model\Dynamic\Type wakeUpObject($row)
 * @method static \Bitrix\Crm\Model\Dynamic\EO_Type_Collection wakeUpCollection($rows)
 */
class TypeTable extends UserField\Internal\TypeDataManager
{
	protected const MAX_TRIES_GENERATE_NAME = 5;
	protected const NAME_RANDOM_STRING_LENGTH = 10;

	private static bool $skipEvents = false;

	public static function getTableName(): string
	{
		return 'b_crm_dynamic_type';
	}

	public static function getMap(): array
	{
//		todo return back after main module release
//		$fieldsMap = parent::getMap();

		$fieldsMap = [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new StringField('NAME'))
				->configureRequired()
				->configureUnique()
				->configureSize(100)
				->configureFormat('/^[A-Z][A-Za-z0-9]*$/')
				->addValidator(new RegExp(
					'/(?<!Table)$/i'
				)),
			(new StringField('TABLE_NAME'))
				->configureRequired()
				->configureUnique()
				->configureSize(64)
				->configureFormat('/^[a-z0-9_]+$/')
				->addValidator([get_called_class(), 'validateTableExisting']),
		];

		Container::getInstance()->getLocalization()->loadMessages();

		$fieldsMap[] = (new ORM\Fields\StringField('TITLE'))
			->configureTitle(Loc::getMessage('CRM_COMMON_TITLE'))
			->configureRequired();
		$fieldsMap[] = (new ORM\Fields\StringField('CODE'))
			->configureTitle(Loc::getMessage('CRM_COMMON_CODE'))
			->configureSize(255);
		//$fieldsMap[] = (new ORM\Fields\StringField('IMAGE'));
		$fieldsMap[] = (new ORM\Fields\IntegerField('CREATED_BY'))
			->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_CREATED_BY'))
			->configureRequired()
			->configureDefaultValue(static function()
			{
				return Container::getInstance()->getContext()->getUserId();
			});
		$fieldsMap[] = (new ORM\Fields\IntegerField('ENTITY_TYPE_ID'))
			->configureTitle(Loc::getMessage('CRM_TYPE_ENTITY_TYPE_ID_TITLE_MSGVER_1'))
			->configureRequired()
			->configureUnique()
			->addValidator([static::class, 'validateEntityTypeId'])
			->configureDefaultValue(static function()
			{
				$nextId = static::getNextAvailableEntityTypeId();
				if(!$nextId)
				{
					throw new InvalidOperationException(Loc::getMessage('CRM_TYPE_ENTITY_TYPE_ID_LIMIT_ERROR_MSGVER_1'));
				}

				return $nextId;
			});
		$fieldsMap[] = (new ORM\Fields\IntegerField('CUSTOM_SECTION_ID'))
			->configureNullable()
			->configureTitle(Loc::getMessage('CRM_TYPE_CUSTOM_SECTION_ID_TITLE'));
		$fieldsMap[] = new ReferenceField(
			'AUTOMATED_SOLUTION',
			AutomatedSolutionTable::class,
			['=this.CUSTOM_SECTION_ID' => 'ref.ID']
		);
		$fieldsMap[] = (new ORM\Fields\BooleanField('IS_CATEGORIES_ENABLED'))
			->configureStorageValues('N', 'Y')
			->configureDefaultValue('N')
			->configureRequired()
			->configureTitle(Loc::getMessage('CRM_TYPE_TYPE_IS_CATEGORIES_ENABLED_TITLE'));
		$fieldsMap[] = (new ORM\Fields\BooleanField('IS_STAGES_ENABLED'))
			->configureStorageValues('N', 'Y')
			->configureDefaultValue('N')
			->configureRequired()
			->configureTitle(Loc::getMessage('CRM_TYPE_TYPE_IS_STAGES_ENABLED_TITLE'));
		$fieldsMap[] = (new ORM\Fields\BooleanField('IS_BEGIN_CLOSE_DATES_ENABLED'))
			->configureStorageValues('N', 'Y')
			->configureDefaultValue('N')
			->configureRequired()
			->configureTitle(Loc::getMessage('CRM_TYPE_TYPE_IS_BEGIN_CLOSE_DATES_ENABLED_TITLE'));
		$fieldsMap[] = (new ORM\Fields\BooleanField('IS_CLIENT_ENABLED'))
			->configureStorageValues('N', 'Y')
			->configureDefaultValue('N')
			->configureRequired()
			->configureTitle(Loc::getMessage('CRM_TYPE_TYPE_IS_CLIENT_ENABLED_TITLE'));
		$fieldsMap[] = (new ORM\Fields\BooleanField('IS_USE_IN_USERFIELD_ENABLED'))
			->configureStorageValues('N', 'Y')
			->configureDefaultValue('N')
			->configureRequired()
			->configureTitle(Loc::getMessage('CRM_TYPE_TYPE_IS_USE_IN_USERFIELD_TITLE'));
		$fieldsMap[] = (new ORM\Fields\BooleanField('IS_LINK_WITH_PRODUCTS_ENABLED'))
			->configureStorageValues('N', 'Y')
			->configureDefaultValue('N')
			->configureRequired()
			->configureTitle(Loc::getMessage('CRM_TYPE_TYPE_IS_LINK_WITH_PRODUCTS_ENABLED_TITLE'));
		$fieldsMap[] = (new ORM\Fields\BooleanField('IS_CRM_TRACKING_ENABLED'))
			->configureStorageValues('N', 'Y')
			->configureDefaultValue('N')
			->configureRequired()
			->configureTitle(Loc::getMessage('CRM_TYPE_TYPE_IS_CRM_TRACKING_ENABLED_TITLE'));
		$fieldsMap[] = (new ORM\Fields\BooleanField('IS_MYCOMPANY_ENABLED'))
			->configureStorageValues('N', 'Y')
			->configureDefaultValue('N')
			->configureRequired()
			->configureTitle(Loc::getMessage('CRM_TYPE_TYPE_IS_MYCOMPANY_ENABLED_TITLE'));
		$fieldsMap[] = (new ORM\Fields\BooleanField('IS_DOCUMENTS_ENABLED'))
			->configureStorageValues('N', 'Y')
			->configureDefaultValue('N')
			->configureRequired()
			->configureTitle(Loc::getMessage('CRM_TYPE_TYPE_IS_DOCUMENTS_ENABLED_TITLE'));
		$fieldsMap[] = (new ORM\Fields\BooleanField('IS_SOURCE_ENABLED'))
			->configureStorageValues('N', 'Y')
			->configureDefaultValue('N')
			->configureRequired()
			->configureTitle(Loc::getMessage('CRM_TYPE_TYPE_IS_SOURCE_ENABLED_TITLE'));
		$fieldsMap[] = (new ORM\Fields\BooleanField('IS_OBSERVERS_ENABLED'))
			->configureStorageValues('N', 'Y')
			->configureDefaultValue('N')
			->configureRequired()
			->configureTitle(Loc::getMessage('CRM_TYPE_TYPE_IS_OBSERVERS_ENABLED_TITLE'));
		$fieldsMap[] = (new ORM\Fields\BooleanField('IS_RECYCLEBIN_ENABLED'))
			->configureStorageValues('N', 'Y')
			->configureDefaultValue('N')
			->configureRequired()
			->configureTitle(Loc::getMessage('CRM_TYPE_TYPE_IS_RECYCLEBIN_ENABLED_TITLE'));
		$fieldsMap[] = (new ORM\Fields\BooleanField('IS_AUTOMATION_ENABLED'))
			->configureStorageValues('N', 'Y')
			->configureDefaultValue('N')
			->configureRequired()
			->configureTitle(Loc::getMessage('CRM_TYPE_TYPE_IS_AUTOMATION_ENABLED_TITLE'));
		$fieldsMap[] = (new ORM\Fields\BooleanField('IS_BIZ_PROC_ENABLED'))
			->configureStorageValues('N', 'Y')
			->configureDefaultValue('N')
			->configureRequired()
			->configureTitle(Loc::getMessage('CRM_TYPE_TYPE_IS_BIZ_PROC_ENABLED_TITLE'));
		$fieldsMap[] = (new ORM\Fields\BooleanField('IS_SET_OPEN_PERMISSIONS'))
			->configureStorageValues('N', 'Y')
			->configureDefaultValue('Y')
			->configureRequired()
			->configureTitle(Loc::getMessage('CRM_TYPE_TYPE_IS_SET_OPEN_PERMISSIONS_TITLE'));
		$fieldsMap[] = (new ORM\Fields\BooleanField('IS_PAYMENTS_ENABLED'))
			->configureStorageValues('N', 'Y')
			->configureDefaultValue('N')
			->configureRequired()
			->configureTitle(Loc::getMessage('CRM_TYPE_TYPE_IS_PAYMENTS_ENABLED_TITLE'));
		$fieldsMap[] = (new ORM\Fields\BooleanField('IS_COUNTERS_ENABLED'))
			->configureStorageValues('N', 'Y')
			->configureDefaultValue('N')
			->configureRequired()
			->configureTitle(Loc::getMessage('CRM_TYPE_TYPE_IS_COUNTERS_ENABLED_TITLE'));
		$fieldsMap[] = (new ORM\Fields\DatetimeField('CREATED_TIME'))
			->configureTitle(Loc::getMessage('CRM_COMMON_CREATED_TIME'))
			->configureRequired()
			->configureDefaultValue(static function () {
				return new DateTime();
			});
		$fieldsMap[] = (new ORM\Fields\DatetimeField('UPDATED_TIME'))
			->configureTitle(Loc::getMessage('CRM_COMMON_MODIFY_DATE'))
			->configureRequired()
			->configureDefaultValue(static function () {
				return new DateTime();
			});
		$fieldsMap[] = (new ORM\Fields\IntegerField('UPDATED_BY'))
			->configureTitle(Loc::getMessage('CRM_COMMON_UPDATED_BY'))
			->configureRequired()
			->configureDefaultValue(static function()
			{
				return Container::getInstance()->getContext()->getUserId();
			});
		$fieldsMap[] = (new ORM\Fields\BooleanField('IS_INITIALIZED'))
			->configureStorageValues('N', 'Y')
			->configureDefaultValue('N')
		;

		return $fieldsMap;
	}

	public static function generateName(string $title = null, int $try = 0): ?string
	{
		if($try > static::MAX_TRIES_GENERATE_NAME)
		{
			return null;
		}
		if(!empty($title))
		{
			$name = \CUtil::translit($title, Loc::getCurrentLang(), static::getParamsForNameTransliteration());
			if (!preg_match('/^[A-Z][A-Za-z0-9]*$/', $name))
			{
				$name = '';
			}
			if($try > 0 || empty($name))
			{
				$name .= Random::getStringByAlphabet(static::NAME_RANDOM_STRING_LENGTH + $try, Random::ALPHABET_ALPHALOWER);
			}
		}
		else
		{
			$name = Random::getStringByAlphabet(static::NAME_RANDOM_STRING_LENGTH + $try, Random::ALPHABET_ALPHALOWER);
		}

		$name = static::prepareName($name);

		$existingType = static::getList([
			'filter' => [
				'=NAME' => $name,
			],
		])->fetch();
		if($existingType)
		{
			$name = static::generateName($title, ($try + 1));
		}

		return $name;
	}

	public static function getNextAvailableEntityTypeId(): ?int
	{
		return SequenceService::getInstance()->nextDynamicTypeId();
	}

	public static function getByEntityTypeId(int $entityTypeId): QueryResult
	{
		return static::getList([
			'filter' => [
				'=ENTITY_TYPE_ID' => $entityTypeId,
				'=IS_INITIALIZED' => true,
			],
		]);
	}

	public static function isCreatingInProgress(int $entityTypeId): bool
	{
		return (bool)static::query()
			->where('ENTITY_TYPE_ID', $entityTypeId)
			->where('IS_INITIALIZED', false)
			->where('CREATED_TIME', '>', (new DateTime())->add('-30sec'))
			->setSelect(['ID'])
			->setLimit(1)
			->fetch()
		;
	}

	public static function onBeforeAdd(Event $event): ORM\EventResult
	{
		$fields = $event->getParameter('fields');
		$result = new ORM\EventResult();
		$modifiedFields = [];

		if(!empty($fields['NAME']))
		{
			$fields['NAME'] = static::prepareName($fields['NAME']);
			$modifiedFields = [
				'NAME' => $fields['NAME'],
				'TABLE_NAME' => static::getItemTableName((string)$fields['ENTITY_TYPE_ID']),
			];
		}
		$modifiedFields['IS_INITIALIZED'] = false;
		$result->modifyFields($modifiedFields);

		if (!empty($fields['ENTITY_TYPE_ID']))
		{
			$existedType = static::query()
				->where('ENTITY_TYPE_ID', $fields['ENTITY_TYPE_ID'])
				->where('IS_INITIALIZED', false)
				->setSelect(['*'])
				->fetchObject();
			if ($existedType) // delete previous uncompleted version of this smart process
			{
				$elementsTableName = $existedType->getTableName();
				$connection = Application::getConnection();
				if ($connection->isTableExists($elementsTableName))
				{
					$connection->dropTable($elementsTableName);
				}
				static::$skipEvents = true;
				$existedType->delete();
				static::$skipEvents = false;
			}
		}

		return $result;
	}

	public static function onAfterAdd(Event $event): ORM\EventResult
	{
		$type = $event->getParameter('object');

		Container::getInstance()->getDynamicTypesMap()->invalidateTypesCollectionCache();
		Container::getInstance()->registerType($type->getEntityTypeId(), $type);
		$result = parent::onAfterAdd($event);

		if (!$result->getErrors())
		{
			static::createIndexes($type->getId());
			static::createItemIndexTable($type->getId());
			static::createItemFieldsContextTable($type->getId());
			DynamicBasedAttrTableLifecycle::getInstance()
				->createTable(\CCrmOwnerType::ResolveName($type->getEntityTypeId()));

			$type->set('IS_INITIALIZED', true);
			static::$skipEvents = true;
			static::update($type->getId(), ['IS_INITIALIZED' => true]);
			static::$skipEvents = false;

			Container::getInstance()->getDynamicTypesMap()->invalidateTypesCollectionCache(); // clear cache again to avoid invalid cached TABLE_NAME field value

			$factory = Container::getInstance()->getFactory($type->getEntityTypeId());
			$factory?->createDefaultCategoryIfNotExist();

			static::clearBindingMenuCache();
		}

		return $result;
	}

	public static function onAfterDelete(Event $event): ORM\EventResult
	{
		if (static::$skipEvents)
		{
			return new EventResult();
		}
		Container::getInstance()->getDynamicTypesMap()->invalidateTypesCollectionCache();

		$result = new ORM\EventResult();
		$primary = $event->getParameter('primary');
		$typeData = static::getTemporaryStorage()->getData($primary);
		static::getTemporaryStorage()->saveData($primary, $typeData);

		static::deleteItemIndexTable($typeData);
		static::deleteItemFieldsContextTable($typeData);

		DynamicBasedAttrTableLifecycle::getInstance()->dropTable(
			 \CCrmOwnerType::ResolveName($typeData['ENTITY_TYPE_ID'] ?? '')
		);

		$entityTypeId = (int)$typeData['ENTITY_TYPE_ID'];

		LastActivityFields::onAfterTypeDelete($entityTypeId);
		FieldContentTypeTable::deleteByEntityTypeId($entityTypeId);
		EventRelationsTable::deleteByEntityType(\CCrmOwnerType::ResolveName($entityTypeId));
		TimelineEntry::deleteByAssociatedEntityType($entityTypeId);
		ItemCategoryTable::deleteByEntityTypeId($entityTypeId);
		AssignedTable::deleteByEntityTypeId($entityTypeId);
		ObserverTable::deleteByEntityTypeId($entityTypeId);
		EntityConversionMapTable::deleteByEntityTypeId($entityTypeId);
		EntityRelationTable::deleteByEntityTypeId($entityTypeId);
		IntranetManager::deleteCustomPagesByEntityTypeId($entityTypeId);

		DynamicController::getInstance($entityTypeId)->eraseAll();

		static::deleteEntityAutomation($entityTypeId);

		// get zombie type to get factory
		$type = static::wakeUpObject($typeData);
		// factory was put into cache in onBeforeDelete
		$factory = Container::getInstance()->getFactory($type->getEntityTypeId());
		if ($factory)
		{
			$categories = $factory->getCategories();
			foreach ($categories as $category)
			{
				$stagesEntityId = $factory->getStagesEntityId($category->getId());
				if ($stagesEntityId)
				{
					StatusTable::deleteByEntityId($stagesEntityId);
				}
				$category->delete();
			}
		}

		Container::getInstance()->getRestEventManager()->deleteDynamicItemEventsByEntityTypeId($type->getEntityTypeId());
		Integration\Rest\AppPlacementManager::deleteAllHandlersForType($type->getEntityTypeId());
		static::clearBindingMenuCache();

		$parentResult = parent::onAfterDelete($event);
		foreach($parentResult->getErrors() as $error)
		{
			$result->addError($error);
		}
		// @todo purge user fields settings!

		return $result;
	}

	public static function deleteEntityAutomation(int $entityTypeId): void
	{
		if (!Loader::includeModule('bizproc'))
		{
			return;
		}

		$documentType = \CCrmBizProcHelper::ResolveDocumentType($entityTypeId);

		$iterator = \Bitrix\Bizproc\WorkflowTemplateTable::getList([
			'filter' => [
				'=MODULE_ID' => $documentType[0],
				'=ENTITY' => $documentType[1],
				'=DOCUMENT_TYPE' => $documentType[2],
			],
			'select' => ['ID']
		]);

		if ($iterator)
		{
			while ($template = $iterator->fetch())
			{
				\CBPDocument::DeleteWorkflowTemplate($template['ID'], $documentType, $errors);
			}
		}

		TriggerTable::deleteByEntityTypeId($entityTypeId);
	}

	public static function createItemIndexTable($type): Result
	{
		$result = new Result();
		$entity = static::compileItemIndexEntity($type);
		$entity->createDbTable();
		global $DB;
		if (!$DB->TableExists($entity->getDBTableName()))
		{
			$result->addError(new Error('Could not create item index table'));
			return $result;
		}

		if (!$DB->CreateIndex($entity->getDBTableName().'_search', $entity->getDBTableName(), ['SEARCH_CONTENT'], false, true))
		{
			$result->addError(new Error('Could not create item fulltext index'));
		}

		return $result;
	}

	public static function createItemIndexTableInAgent($type): string
	{
		static::createItemIndexTable($type);

		return '';
	}

	public static function deleteItemIndexTable($type): Result
	{
		$result = new Result();
		$entity = static::compileItemIndexEntity($type);
		if ($entity)
		{
			$tableName = $entity->getDBTableName();
			if (Application::getConnection()->isTableExists($tableName))
			{
				Application::getConnection()->dropTable($tableName);
			}
		}

		return $result;
	}

	public static function createItemFieldsContextTable($type): Result
	{
		$result = new Result();

		$entity = static::compileItemFieldsContextEntity($type);
		$tableName = $entity->getDBTableName();

		$connection = Application::getConnection();
		if ($connection->isTableExists($tableName))
		{
			return $result;
		}

		$entity->createDbTable();

		if (!$connection->isTableExists($tableName))
		{
			$result->addError(new Error('Could not create item fields context table'));

			return $result;
		}

		$tableNameInUpperCase = mb_strtoupper($tableName);

		try
		{
			$connection->createIndex($tableName, 'IX_' . $tableNameInUpperCase . '_FIELD_NAME', ['FIELD_NAME']);
		}
		catch(\Exception $e)
		{
			$result->addError(new Error('Could not create item fields context index'));
		}

		return $result;
	}

	public static function deleteItemFieldsContextTable($type): Result
	{
		$result = new Result();

		$entity = static::compileItemFieldsContextEntity($type);
		if (!$entity)
		{
			return $result;
		}

		$tableName = $entity->getDBTableName();
		$connection = Application::getConnection();

		if ($connection->isTableExists($tableName))
		{
			$connection->dropTable($tableName);
		}

		return $result;
	}

	protected static function prepareName(string $name): string
	{
		$name = \CUtil::translit($name, 'en', static::getParamsForNameTransliteration());

		$name = ucfirst($name);

		return $name;
	}

	protected static function getParamsForNameTransliteration(): array
	{
		/** @var ORM\Fields\StringField $nameField */
		$nameField = static::getEntity()->getField('NAME');
		$maxLength = $nameField->getSize();
		return [
			'max_len' => $maxLength,
			'change_case' => false,
			'replace_space' => '',
			'replace_other' => '',
			'delete_repeat_replace' => true,
			'safe_chars' => '',
		];
	}

	public static function onBeforeUpdate(Event $event): ORM\EventResult
	{
		if (static::$skipEvents)
		{
			return new EventResult();
		}
		$result = parent::onBeforeUpdate($event);

		$fields = $event->getParameter('fields');
		if(isset($fields['NAME']) && !empty($fields['NAME']))
		{
			$fields['NAME'] = static::prepareName($fields['NAME']);
			$result->modifyFields([
				'NAME' => $fields['NAME'],
				'TABLE_NAME' => static::getItemTableName((string)$fields['ENTITY_TYPE_ID']),
			]);
		}
		$id = $event->getParameter('id');
		$typeData = static::getTemporaryStorage()->getData($id);
		static::getTemporaryStorage()->saveData($id, $typeData);
		$entityTypeId = (int)$typeData['ENTITY_TYPE_ID'];
		if(isset($fields['ENTITY_TYPE_ID']))
		{
			if($entityTypeId !== (int)$fields['ENTITY_TYPE_ID'])
			{
				$result->addError(static::getFieldIsNotChangeableError('ENTITY_TYPE_ID'));
			}
		}

		$isTrue = static function($value): bool {
			if(!is_bool($value))
			{
				return ($value === 'Y');
			}

			return $value;
		};

		// if trying to disable categories
		if (
			isset($fields['IS_CATEGORIES_ENABLED'])
			&& !$isTrue($fields['IS_CATEGORIES_ENABLED'])
			&& $isTrue($typeData['IS_CATEGORIES_ENABLED'])
		)
		{
			$categoriesCount = ItemCategoryTable::getCount([
				'=ENTITY_TYPE_ID' => $entityTypeId,
			]);
			if ($categoriesCount > 1)
			{
				$result->addError(new ORM\EntityError(Loc::getMessage('CRM_TYPE_TABLE_DISABLING_CATEGORIES_IF_MORE_THAN_ONE')));
			}
		}
		// if trying to disable recyclebin
		if (
			isset($fields['IS_RECYCLEBIN_ENABLED'])
			&& !$isTrue($fields['IS_RECYCLEBIN_ENABLED'])
			&& $isTrue($typeData['IS_RECYCLEBIN_ENABLED'])
		)
		{
			$itemsInRecycleBinCount = DynamicController::getInstance($entityTypeId)->countItemsInRecycleBin();
			if ($itemsInRecycleBinCount > 0)
			{
				$result->addError(new ORM\EntityError(Loc::getMessage('CRM_TYPE_TABLE_DISABLING_RECYCLEBIN_WHILE_NOT_EMPTY')));
			}
		}

		$result->modifyFields([
			'UPDATED_TIME' => new DateTime(),
			'UPDATED_BY' => Container::getInstance()->getContext()->getUserId(),
		]);

		return $result;
	}

	protected static function getFieldIsNotChangeableError(string $fieldName): ORM\EntityError
	{
		$title = Loc::getMessage('CRM_TYPE_TYPE_' . $fieldName . '_TITLE') ?? $fieldName;

		return new ORM\EntityError(Loc::getMessage('CRM_TYPE_TABLE_FIELD_NOT_CHANGEABLE_ERROR', [
			'#FIELD#' => $title,
		]));
	}

	public static function onAfterUpdate(Event $event): EventResult
	{
		if (static::$skipEvents)
		{
			return new EventResult();
		}
		Container::getInstance()->getDynamicTypesMap()->invalidateTypesCollectionCache();

		$id = static::getTemporaryStorage()->getIdByPrimary($event->getParameter('primary'));
		$data = $event->getParameter('fields');
		$oldData = static::getTemporaryStorage()->getData($id);
		if(!$oldData)
		{
			return new EventResult();
		}
		static::getTemporaryStorage()->saveData($id, $oldData);

		if (isset($data['TABLE_NAME']) && $data['TABLE_NAME'] !== $oldData['TABLE_NAME'])
		{
			$oldIndexTableName = static::prepareItemIndexTableName($oldData['TABLE_NAME']);
			$newIndexTableName = static::prepareItemIndexTableName($data['TABLE_NAME']);

			Application::getConnection()->renameTable($oldIndexTableName, $newIndexTableName);
		}

		$oldAutomatedSolutionId = (int)($oldData['CUSTOM_SECTION_ID'] ?? 0);
		$newAutomatedSolutionId = (int)($data['CUSTOM_SECTION_ID'] ?? 0);

		// remove all existed permissions when move smart process to another automated solution:
		if (
			array_key_exists('CUSTOM_SECTION_ID', $oldData)
			&& array_key_exists('CUSTOM_SECTION_ID', $data)
			&& $oldAutomatedSolutionId !== $newAutomatedSolutionId
			&& Feature::enabled(Feature\PermissionsLayoutV2::class)
		)
		{
			$entityTypeId = Container::getInstance()->getType($id)?->getEntityTypeId();
			$factory = $entityTypeId ? Container::getInstance()->getFactory($entityTypeId) : null;
			$categories = $factory?->getCategories() ?? [];

			RolePermissionLogContext::getInstance()->set([
				'scenario' => 'change smart process automated solution',
				'entityTypeId' => $entityTypeId,
				'categoryId' => $id,
				'oldAutomatedSolution' => $oldAutomatedSolutionId,
				'newAutomatedSolution' => $newAutomatedSolutionId,
			]);

			foreach ($categories as $category)
			{
				\CCrmRole::EraseEntityPermissons(
					(new PermissionEntityTypeHelper($entityTypeId))->getPermissionEntityTypeForCategory($category->getId())
				);
			}

			RolePermissionLogContext::getInstance()->clear();
		}

		static::clearBindingMenuCache();

		return parent::onAfterUpdate($event);
	}

	protected static function getItemTableName(string $suffix): string
	{
		if(empty($suffix))
		{
			throw new ArgumentNullException('entityTypeId');
		}
		$suffix = strtolower($suffix);

		return 'b_crm_dynamic_items_'.$suffix;
	}

	/**
	 * @return ORM\Entity|string
	 */
	public static function getObjectClass(): string
	{
		return Type::class;
	}

	public static function compileItemIndexEntity($type): ORM\Entity
	{
		$type = self::checkAndResolveType($type);

		$factory = ServiceLocator::getInstance()->get('crm.type.factory');
		$entityName = $factory->getUserFieldEntityPrefix() . $type['NAME'].'Index';
		$entityTableName = static::prepareItemIndexTableName($type['TABLE_NAME']);
		$dataClass = $factory->getItemIndexPrototypeDataClass();

		return self::buildEntity($entityName, $entityTableName, $dataClass);
	}

	protected static function prepareItemIndexTableName(string $typeTableName): string
	{
		return $typeTableName.'_index';
	}

	public static function compileItemFieldsContextEntity($type): ORM\Entity
	{
		$type = self::checkAndResolveType($type);

		$factory = ServiceLocator::getInstance()->get('crm.type.factory');
		$entityName = $factory->getUserFieldEntityPrefix() . $type['NAME'] . 'FieldsContext';
		$entityTableName = static::prepareItemFieldsContextTableName($type['TABLE_NAME']);
		$dataClass = $factory->getItemFieldsContextPrototypeDataClass();

		return self::buildEntity($entityName, $entityTableName, $dataClass);
	}

	protected static function checkAndResolveType($type): array
	{
		$rawType = $type;
		$type = static::resolveType($type);

		if (empty($type))
		{
			throw new SystemException(
				sprintf(
					'Invalid type description `%s`.',
					mydump($rawType)
				)
			);
		}

		return $type;
	}

	protected static function buildEntity(
		string $entityName,
		string $entityTableName,
		string $dataClass
	): Entity
	{
		$entityClassName = $entityName . 'Table';

		if (class_exists($entityClassName))
		{
			ORM\Entity::destroy($entityClassName);

			return ORM\Entity::getInstance($entityClassName);
		}

		return ORM\Entity::compileEntity($entityName, [], [
			'table_name' => $entityTableName,
			'parent' => $dataClass,
		]);
	}

	protected static function prepareItemFieldsContextTableName(string $typeTableName): string
	{
		return $typeTableName.'_fields_context';
	}

	protected static function createIndexes($type): void
	{
		$rawType = $type;
		$type = static::resolveType($type);
		if(empty($type))
		{
			throw new SystemException(
				sprintf(
					'Invalid type description `%s`.',
					mydump($rawType)
				)
			);
		}

		self::createIndexIfNotExists(
			$type['TABLE_NAME'],
			'ix_crm_type_item_' . (int)$type['ID'] . '_category',
			[\Bitrix\Crm\Item::FIELD_NAME_CATEGORY_ID],
		);
		self::createIndexIfNotExists(
			$type['TABLE_NAME'],
			'ix_crm_type_item_' . (int)$type['ID'] . '_contact',
			[\Bitrix\Crm\Item::FIELD_NAME_CONTACT_ID],
		);
		self::createIndexIfNotExists(
			$type['TABLE_NAME'],
			'ix_crm_type_item_' . (int)$type['ID'] . '_company',
			[\Bitrix\Crm\Item::FIELD_NAME_COMPANY_ID],
		);
		self::createIndexIfNotExists(
			$type['TABLE_NAME'],
			'ix_crm_type_item_' . (int)$type['ID'] . '_last_activity_time',
			[\Bitrix\Crm\Item::FIELD_NAME_LAST_ACTIVITY_TIME],
		);
	}

	private static function createIndexIfNotExists(string $tableName, string $indexName, array $columns): void
	{
		$connection = Application::getConnection();

		if ($connection->getIndexName($tableName, $columns, true) === null)
		{
			$connection->createIndex($tableName, $indexName, $columns);
		}
	}

	public static function onBeforeDelete(Event $event): EventResult
	{
		if (static::$skipEvents)
		{
			return new EventResult();
		}
		$result = new EventResult();

		$id = $event->getParameter('id');
		if(is_array($id))
		{
			$id = $id['ID'];
		}
		$id = (int) $id;

		$container = Container::getInstance();
		$type = $container->getType($id);
		if($type)
		{
			$factory = $container->getFactory($type->getEntityTypeId());
			if($factory && $factory->getItemsCount() > 0)
			{
				$result->addError(new ORM\EntityError(Loc::getMessage('CRM_TYPE_TABLE_DELETE_ERROR_ITEMS')));
				return $result;
			}
		}

		return parent::onBeforeDelete($event);
	}

	public static function compileEntity($type): Entity
	{
		$entity = parent::compileEntity($type);

		// disable checking required fields here as it can be disabled
		foreach ($entity->getFields() as $field)
		{
			if (!$entity->getDataClass()::isOwnField($field->getName()) && $field instanceof ORM\Fields\ScalarField)
			{
				$field->configureRequired(false);
			}
		}

		$type = static::resolveType($type);
		$factory = Container::getInstance()->getFactory((int) $type['ENTITY_TYPE_ID']);
		if ($factory)
		{
			$assignedByIdField = static::compileAssignedByIdField($entity->getDataClass(), $factory->isMultipleAssignedEnabled());
			$entity->addField($assignedByIdField);
			if($factory->isStagesSupported())
			{
				$entity->addField(new ORM\Fields\Relations\Reference(
					'STAGE',
					StatusTable::class,
					Join::on('this.STAGE_ID', 'ref.STATUS_ID')
				));
			}

			static::addReferencesToEntity($entity, $factory);

			if ($factory instanceof Dynamic)
			{
				foreach ($factory->getAdditionalTableFields() as $field)
				{
					if ($field instanceof ORM\Fields\Field)
					{
						$entity->addField($field);
					}
				}
			}
		}

		if (LastActivityFields::wereLastActivityColumnsAddedSuccessfullyOnModuleUpdate((int)$type['ENTITY_TYPE_ID']))
		{
			$fieldRepository = ServiceLocator::getInstance()->get('crm.model.fieldRepository');

			$entity->addField($fieldRepository->getLastActivityBy());
			$entity->addField($fieldRepository->getLastActivityTime());
		}

		return $entity;
	}

	protected static function compileAssignedByIdField(string $dataClass, bool $isMultipleAssignedEnabled): ORM\Fields\ScalarField
	{
		if ($isMultipleAssignedEnabled)
		{
			$assignedByIdField = (new ORM\Fields\ArrayField('ASSIGNED_BY_ID'))
				->configureUnserializeCallback([$dataClass, 'unserializeAssignedById'])
			;
		}
		else
		{
			$assignedByIdField = (new ORM\Fields\TextField('ASSIGNED_BY_ID'));
		}
		$assignedByIdField
			->configureRequired()
			->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_ASSIGNED_BY_ID'));

		return $assignedByIdField;
	}

	protected static function addReferencesToEntity(Entity $localEntity, \Bitrix\Crm\Service\Factory $factory): void
	{
		$localFieldName = $factory->getEntityName();

		// If compileEntity was called previously, the field below had been added to the AssignedTable entity already
		if (!AssignedTable::getEntity()->hasField($localFieldName))
		{
			AssignedTable::getEntity()->addField(new ORM\Fields\Relations\Reference(
				$localFieldName,
				$localEntity,
				Join::on('this.ENTITY_ID', 'ref.ID')
					->where('this.ENTITY_TYPE_ID', new SqlExpression('?i', $factory->getEntityTypeId()))
			));
		}
		$oneToManyAssigned =
			(new ORM\Fields\Relations\OneToMany('ASSIGNED', AssignedTable::class, $localFieldName))
				->configureCascadeDeletePolicy(ORM\Fields\Relations\CascadePolicy::FOLLOW)
		;
		// $localEntity is rebuilt on every compileEntity call, we have to add the reference field anyway
		$localEntity->addField($oneToManyAssigned);

		// Similar to the code above
		if (!EntityContactTable::getEntity()->hasField($localFieldName))
		{
			EntityContactTable::getEntity()->addField((new ORM\Fields\Relations\Reference(
				$localFieldName,
				$localEntity,
				Join::on('this.ENTITY_ID', 'ref.ID')
					->where('this.ENTITY_TYPE_ID', new SqlExpression('?i', $factory->getEntityTypeId()))
			)));
		}
		$oneToManyBindings = (new ORM\Fields\Relations\OneToMany('CONTACT_BINDINGS', EntityContactTable::class, $localFieldName))
			->configureCascadeDeletePolicy(ORM\Fields\Relations\CascadePolicy::FOLLOW);
		$localEntity->addField($oneToManyBindings);

		// Similar to the code above
		if (!ProductRowTable::getEntity()->hasField($localFieldName))
		{
			ProductRowTable::getEntity()->addField((new ORM\Fields\Relations\Reference(
				$localFieldName,
				$localEntity,
				Join::on('this.OWNER_ID', 'ref.ID')
					->where('this.OWNER_TYPE', new SqlExpression('?s', $factory->getEntityAbbreviation()))
			)));
		}
		$oneToManyProducts = (new ORM\Fields\Relations\OneToMany(\Bitrix\Crm\Item::FIELD_NAME_PRODUCTS, ProductRowTable::class, $localFieldName))
			// products will be deleted in onAfterDelete, if it's needed
			->configureCascadeDeletePolicy(ORM\Fields\Relations\CascadePolicy::NO_ACTION)
			->configureTitle(Loc::getMessage('CRM_COMMON_PRODUCTS'));
		$localEntity->addField($oneToManyProducts);

		if (!ObserverTable::getEntity()->hasField($localFieldName))
		{
			ObserverTable::getEntity()->addField((new ORM\Fields\Relations\Reference(
				$localFieldName,
				$localEntity,
				Join::on('this.ENTITY_ID', 'ref.ID')
					->where('this.ENTITY_TYPE_ID', new SqlExpression('?i', $factory->getEntityTypeId()))
			)));
		}
		$oneToManyObservers = (new ORM\Fields\Relations\OneToMany(\Bitrix\Crm\Item::FIELD_NAME_OBSERVERS, ObserverTable::class, $localFieldName))
			->configureCascadeDeletePolicy(ORM\Fields\Relations\CascadePolicy::FOLLOW)
			->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_OBSERVERS'));
		$localEntity->addField($oneToManyObservers);
	}

	protected static function clearBindingMenuCache(): void
	{
		Integration\Intranet\BindingMenu::clearCache();
	}

	public static function getFieldsInfo(): array
	{
		return [
			'ID' => [
				'TYPE' => Field::TYPE_INTEGER,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::ReadOnly],
			],
			'TITLE' => [
				'TYPE' => Field::TYPE_STRING,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::Required],
				'TITLE' => Loc::getMessage('CRM_COMMON_TITLE'),
			],
			'CODE' => [
				'TYPE' => Field::TYPE_STRING,
				'TITLE' => Loc::getMessage('CRM_COMMON_CODE'),
			],
			'CREATED_BY' => [
				'TYPE' => Field::TYPE_USER,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::ReadOnly],
				'TITLE' => Loc::getMessage('CRM_TYPE_ITEM_FIELD_CREATED_BY'),
			],
			'ENTITY_TYPE_ID' => [
				'TYPE' => Field::TYPE_INTEGER,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::Required, \CCrmFieldInfoAttr::Unique, \CCrmFieldInfoAttr::Immutable],
				'TITLE' => Loc::getMessage('CRM_TYPE_ENTITY_TYPE_ID_TITLE_MSGVER_1'),
			],
			'CUSTOM_SECTION_ID' => [
				'TYPE' => Field::TYPE_INTEGER,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::Immutable],
				'TITLE' => Loc::getMessage('CRM_TYPE_CUSTOM_SECTION_ID_TITLE'),
			],
			'IS_CATEGORIES_ENABLED' => [
				'TYPE' => Field::TYPE_BOOLEAN,
				'TITLE' => Loc::getMessage('CRM_TYPE_TYPE_IS_CATEGORIES_ENABLED_TITLE'),
			],
			'IS_STAGES_ENABLED' => [
				'TYPE' => Field::TYPE_BOOLEAN,
				'TITLE' => Loc::getMessage('CRM_TYPE_TYPE_IS_STAGES_ENABLED_TITLE'),
			],
			'IS_BEGIN_CLOSE_DATES_ENABLED' => [
				'TYPE' => Field::TYPE_BOOLEAN,
				'TITLE' => Loc::getMessage('CRM_TYPE_TYPE_IS_BEGIN_CLOSE_DATES_ENABLED_TITLE'),
			],
			'IS_CLIENT_ENABLED' => [
				'TYPE' => Field::TYPE_BOOLEAN,
				'TITLE' => Loc::getMessage('CRM_TYPE_TYPE_IS_CLIENT_ENABLED_TITLE'),
			],
			'IS_USE_IN_USERFIELD_ENABLED' => [
				'TYPE' => Field::TYPE_BOOLEAN,
				'TITLE' => Loc::getMessage('CRM_TYPE_TYPE_IS_USE_IN_USERFIELD_ENABLED_TITLE'),
			],
			'IS_LINK_WITH_PRODUCTS_ENABLED' => [
				'TYPE' => Field::TYPE_BOOLEAN,
				'TITLE' => Loc::getMessage('CRM_TYPE_TYPE_IS_LINK_WITH_PRODUCTS_ENABLED_TITLE'),
			],
//			'IS_CRM_TRACKING_ENABLED' => [
//				'TYPE' => Field::TYPE_BOOLEAN,
//				'TITLE' => Loc::getMessage('CRM_TYPE_TYPE_IS_CRM_TRACKING_ENABLED_TITLE'),
//			],
			'IS_MYCOMPANY_ENABLED' => [
				'TYPE' => Field::TYPE_BOOLEAN,
				'TITLE' => Loc::getMessage('CRM_TYPE_TYPE_IS_MYCOMPANY_ENABLED_TITLE'),
			],
			'IS_DOCUMENTS_ENABLED' => [
				'TYPE' => Field::TYPE_BOOLEAN,
				'TITLE' => Loc::getMessage('CRM_TYPE_TYPE_IS_DOCUMENTS_ENABLED_TITLE'),
			],
			'IS_SOURCE_ENABLED' => [
				'TYPE' => Field::TYPE_BOOLEAN,
				'TITLE' => Loc::getMessage('CRM_TYPE_TYPE_IS_SOURCE_ENABLED_TITLE'),
			],
			'IS_OBSERVERS_ENABLED' => [
				'TYPE' => Field::TYPE_BOOLEAN,
				'TITLE' => Loc::getMessage('CRM_TYPE_TYPE_IS_OBSERVERS_ENABLED_TITLE'),
			],
			'IS_RECYCLEBIN_ENABLED' => [
				'TYPE' => Field::TYPE_BOOLEAN,
				'TITLE' => Loc::getMessage('CRM_TYPE_TYPE_IS_RECYCLEBIN_ENABLED_TITLE'),
			],
			'IS_AUTOMATION_ENABLED' => [
				'TYPE' => Field::TYPE_BOOLEAN,
				'TITLE' => Loc::getMessage('CRM_TYPE_TYPE_IS_AUTOMATION_ENABLED_TITLE'),
			],
			'IS_BIZ_PROC_ENABLED' => [
				'TYPE' => Field::TYPE_BOOLEAN,
				'TITLE' => Loc::getMessage('CRM_TYPE_TYPE_IS_BIZ_PROC_ENABLED_TITLE'),
			],
			'IS_SET_OPEN_PERMISSIONS' => [
				'TYPE' => Field::TYPE_BOOLEAN,
				'TITLE' => Loc::getMessage('CRM_TYPE_TYPE_IS_SET_OPEN_PERMISSIONS_TITLE'),
			],
			'CREATED_TIME' => [
				'TYPE' => Field::TYPE_DATETIME,
				'TITLE' => Loc::getMessage('CRM_COMMON_CREATED_TIME'),
			],
			'UPDATED_TIME' => [
				'TYPE' => Field::TYPE_DATETIME,
				'TITLE' => Loc::getMessage('CRM_COMMON_MODIFY_DATE'),
			],
			'UPDATED_BY' => [
				'TYPE' => Field::TYPE_USER,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::ReadOnly],
				'TITLE' => Loc::getMessage('CRM_COMMON_UPDATED_BY'),
			],
		];
	}

	public static function validateEntityTypeId($value, $primary, array $row, \Bitrix\Main\ORM\Fields\Field $field)
	{
		$value = (int)$value;
		if (\CCrmOwnerType::isUseDynamicTypeBasedApproach($value))
		{
			return true;
		}

		return 'EntityTypeId should be more or equal than 128 and less than 192';
	}
}
