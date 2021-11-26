<?php

namespace Bitrix\Crm\Model\Dynamic;

use Bitrix\Crm\Binding\EntityContactTable;
use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\ContactTable;
use Bitrix\Crm\Currency;
use Bitrix\Crm\Model\AssignedTable;
use Bitrix\Crm\ProductRowTable;
use Bitrix\Crm\Relation\EntityRelationTable;
use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\ParentFieldManager;
use Bitrix\Crm\StatusTable;
use Bitrix\Crm\Timeline\TimelineEntry;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DateField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\DecimalField;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserField\Internal\UserFieldHelper;

abstract class PrototypeItem extends Main\UserField\Internal\PrototypeItemDataManager
{
	public const DEFAULT_SORT = 1000;

	protected static $isCheckUserFields = true;

	/**
	 * Returns entity map definition.
	 *
	 * @return ORM\Fields\Field[]
	 */
	public static function getMap(): array
	{
		Container::getInstance()->getLocalization()->loadMessages();

		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new StringField('XML_ID'))
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_XML_ID')),
			(new StringField('TITLE'))
				->configureTitle(Loc::getMessage('CRM_COMMON_TITLE')),
			(new IntegerField('CREATED_BY'))
				->configureRequired()
				->configureDefaultValue(static function()
				{
					return Container::getInstance()->getContext()->getUserId();
				})
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_CREATED_BY')),
			(new IntegerField('UPDATED_BY'))
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_UPDATED_BY')),
			(new IntegerField('MOVED_BY'))
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_MOVED_BY')),
			(new DatetimeField('CREATED_TIME'))
				->configureRequired()
				->configureDefaultValue(static function()
				{
					return new DateTime();
				})
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_CREATED_TIME')),
			(new DatetimeField('UPDATED_TIME'))
				->configureDefaultValue(static function()
				{
					return new DateTime();
				})
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_UPDATED_TIME')),
			(new DatetimeField('MOVED_TIME'))
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_MOVED_TIME')),
			(new IntegerField('CATEGORY_ID'))
				->configureTitle(Loc::getMessage('CRM_COMMON_CATEGORY'))
				->configureRequired()
				->configureDefaultValue([static::class, 'getDefaultCategoryId']),
			(new BooleanField('OPENED'))
				->configureStorageValues('N', 'Y')
				->configureDefaultValue('N')
				->configureRequired()
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_OPENED')),
			(new StringField('STAGE_ID'))
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_STAGE_ID'))
				->configureDefaultValue([static::class, 'getDefaultStageId']),
			(new StringField('PREVIOUS_STAGE_ID'))
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_PREVIOUS_STAGE_ID')),
			(new DateField('BEGINDATE'))
				->configureRequired()
				->configureDefaultValue(static function()
				{
					return new Date();
				})
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_BEGINDATE')),
			(new DateField('CLOSEDATE'))
				->configureRequired()
				->configureDefaultValue([static::class, 'getDefaultCloseDate'])
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_CLOSEDATE')),
			(new IntegerField('COMPANY_ID'))
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_COMPANY_ID')),
			(new Reference('COMPANY', CompanyTable::class, Join::on('this.COMPANY_ID', 'ref.ID'))),
			(new IntegerField('CONTACT_ID'))
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_CONTACT_ID')),
			(new Reference('CONTACT', ContactTable::class, Join::on('this.CONTACT_ID', 'ref.ID'))),
			(new DecimalField('OPPORTUNITY'))
				->configurePrecision(18)
				->configureScale(2)
				->configureDefaultValue(0.00)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_OPPORTUNITY')),
			(new BooleanField('IS_MANUAL_OPPORTUNITY'))
				->configureStorageValues('N', 'Y')
				->configureDefaultValue('N')
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_IS_MANUAL_OPPORTUNITY')),
			(new DecimalField('TAX_VALUE'))
				->configurePrecision(18)
				->configureScale(2)
				->configureDefaultValue(0.00)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_TAX_VALUE')),
			(new StringField('CURRENCY_ID'))
				->configureSize(50)
				->configureDefaultValue(Currency::getBaseCurrencyId())
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_CURRENCY_ID')),
			(new DecimalField('OPPORTUNITY_ACCOUNT'))
				->configurePrecision(18)
				->configureScale(2)
				->configureDefaultValue(0.00)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_OPPORTUNITY_ACCOUNT')),
			(new DecimalField('TAX_VALUE_ACCOUNT'))
				->configurePrecision(18)
				->configureScale(2)
				->configureDefaultValue(0.00)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_TAX_VALUE_ACCOUNT')),
			(new StringField('ACCOUNT_CURRENCY_ID'))
				->configureSize(50)
				->configureDefaultValue(Currency::getAccountCurrencyId())
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_ACCOUNT_CURRENCY_ID')),
			(new IntegerField('MYCOMPANY_ID'))
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_MYCOMPANY_ID'))
				->configureDefaultValue(static function() {
					$defaultMyCompanyId = (int)EntityLink::getDefaultMyCompanyId();
					if ($defaultMyCompanyId > 0)
					{
						return $defaultMyCompanyId;
					}

					return null;
				}),
			(new Reference('MYCOMPANY', CompanyTable::class, Join::on('this.MYCOMPANY_ID', 'ref.ID'))),
			(new StringField('SOURCE_ID'))
				->configureSize(50)
				->addValidator([static::class, 'validateSourceId'])
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_SOURCE_ID')),
			(new TextField('SOURCE_DESCRIPTION'))
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_SOURCE_DESCRIPTION')),
			(new IntegerField('WEBFORM_ID'))
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_WEBFORM_ID')),
		];
	}

	public static function getFactory(): ?\Bitrix\Crm\Service\Factory
	{
		return Container::getInstance()->getFactory((int) static::getType()['ENTITY_TYPE_ID']);
	}

	public static function getList(array $parameters = []): ORM\Query\Result
	{
		return parent::getList(static::prepareGetListParameters($parameters));
	}

	public static function getCount($filter = [], array $cache = []): int
	{
		$parameters = [
			'select' => [new ExpressionField('CNT', 'COUNT(1)')],
			'filter' => $filter,
		];
		/** @var array $result */
		$result = static::getList($parameters)->fetch();

		return (int)$result['CNT'];
	}

	public static function prepareGetListParameters(array $parameters = []): array
	{
		if (empty($parameters['filter']) || !is_array($parameters['filter']))
		{
			return $parameters;
		}

		$parameters['filter'] = static::unsetSearchContentPairs(
			$parameters['filter'],
			!static::isSimilarKeyPresentInArray($parameters['filter'], 'FULL_TEXT.SEARCH_CONTENT')
		);

		if (static::isSimilarKeyPresentInArray($parameters['filter'], 'FULL_TEXT.'))
		{
			$parameters['runtime'] = $parameters['runtime'] ?? [];
			$parameters['runtime'][] = static::getFullTextReferenceField();
		}

		$parameters = static::prepareParentFieldsInParameters($parameters);
		$parameters['filter'] = static::replaceAssignedInFilter($parameters['filter']);

		return $parameters;
	}

	protected static function unsetSearchContentPairs(array $filter, bool $isReplaceWithFullyQualifiedReference): array
	{
		foreach ($filter as $index => $value)
		{
			$isSearchContent = (mb_strpos($index, 'SEARCH_CONTENT') !== false);
			$isFullyQualified = (mb_strpos($index, 'FULL_TEXT.SEARCH_CONTENT') !== false);
			if ($isSearchContent && !$isFullyQualified)
			{
				unset($filter[$index]);

				if ($isReplaceWithFullyQualifiedReference)
				{
					/** @var string $replacedIndex */
					$replacedIndex = str_replace('SEARCH_CONTENT', 'FULL_TEXT.SEARCH_CONTENT', $index);
					if (mb_strpos($replacedIndex, 'FULL_TEXT.SEARCH_CONTENT') === 0)
					{
						$replacedIndex = '*' . $replacedIndex;
					}
					$filter[$replacedIndex] = $value;
				}
			}

			if (is_array($value))
			{
				if (isset($replacedIndex) && isset($filter[$replacedIndex]))
				{
					$filter[$replacedIndex] = static::unsetSearchContentPairs($value, $isReplaceWithFullyQualifiedReference);
				}
				elseif (isset($filter[$index]))
				{
					$filter[$index] = static::unsetSearchContentPairs($value, $isReplaceWithFullyQualifiedReference);
				}
			}

			// isset($replacedIndex) should return true only for a variable that was set on a current iteration
			unset($replacedIndex);
		}

		return $filter;
	}

	protected static function isSimilarKeyPresentInArray(array $filter, string $keyToFind): bool
	{
		foreach ($filter as $key => $value)
		{
			if (mb_strpos($key, $keyToFind) !== false)
			{
				return true;
			}

			if (is_array($value))
			{
				$isFoundInSubFilter = static::isSimilarKeyPresentInArray($value, $keyToFind);
				if ($isFoundInSubFilter)
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @param array $parameters
	 * @return array
	 */
	protected static function prepareParentFieldsInParameters(array $parameters): array
	{
		if (isset($parameters['filter']))
		{
			$filterItem = [
				'LOGIC' => 'AND',
			];

			foreach($parameters['filter'] as $name => $value)
			{
				if(ParentFieldManager::isParentFieldName($name))
				{
					$parentEntityTypeId = ParentFieldManager::getEntityTypeIdFromFieldName($name);
					$parentEntityId = self::getParentEntityIdFromFilterValue($value);

					if (
						$parentEntityId < 1
						|| !\CCrmOwnerType::ResolveName($parentEntityTypeId)
					)
					{
						continue;
					}

					$referenceName = 'REFERENCE_RELATION_' . $parentEntityTypeId;
					$parameters['runtime'][] = new Reference(
						$referenceName,
						EntityRelationTable::getEntity(),
						[
							'=this.ID' => 'ref.DST_ENTITY_ID',
						]
					);

					unset($parameters['filter'][$name]);

					$filterItem[] = [
						$referenceName . '.SRC_ENTITY_ID' => $parentEntityId,
						$referenceName . '.SRC_ENTITY_TYPE_ID' => $parentEntityTypeId
					];
				}
			}

			if (count($filterItem) > 1)
			{
				$parameters['filter'][] = $filterItem;
			}
		}

		return $parameters;
	}

	/**
	 * @param string $value
	 * @return int
	 */
	protected static function getParentEntityIdFromFilterValue(string $value): int
	{
		// if not dynamic entity, e.g. CRMDEAL8
		if (preg_match('/^CRM[A-Z]+(\d+)$/', $value, $matches))
		{
			$parentEntityId = $matches[1];
		}
		// dynamic entity, e.g. CRMDYNAMIC-128_41
		else
		{
			$arr = explode('_', $value);
			$parentEntityId = (int)($arr[1] ?? 0);
		}

		return $parentEntityId;
	}

	protected static function replaceAssignedInFilter(array $filter): array
	{
		foreach ($filter as $index => $value)
		{
			if (is_array($value))
			{
				$filter[$index] = static::replaceAssignedInFilter($value);
			}
			elseif (mb_strpos($index, 'ASSIGNED_BY_ID') !== false)
			{
				$filter['@ID'] = static::getAssignedSqlExpression($index, $value);

				unset($filter[$index]);
			}
		}

		return $filter;
	}

	protected static function getAssignedSqlExpression(string $filterKey, $filterValue): Main\DB\SqlExpression
	{
		preg_match('/([=%><@!]*)ASSIGNED_BY_ID/', $filterKey, $pregResult);
		$operation = $pregResult[1];

		$subQuery = AssignedTable::query()->addSelect('ENTITY_ID')->addFilter($operation.'ASSIGNED_BY', $filterValue);

		return new Main\DB\SqlExpression($subQuery->getQuery());
	}

	/**
	 * @return string|PrototypeItemIndex
	 * @throws Main\ObjectNotFoundException
	 */
	public static function getFullTextDataClass(): string
	{
		$typeData = static::getType();

		return \Bitrix\Main\DI\ServiceLocator::getInstance()->get('crm.type.factory')->getItemIndexDataClass($typeData);
	}

	public static function getFullTextReferenceField(): Reference
	{
		return new Reference('FULL_TEXT', static::getFullTextDataClass()::getEntity(), [
			'=this.ID' => 'ref.ITEM_ID',
		]);
	}

	public static function onAfterUpdate(Event $event): ORM\EventResult
	{
		$result = parent::onAfterUpdate($event);
		if ($result->getErrors())
		{
			return $result;
		}

		/** @var \Bitrix\Crm\Model\Dynamic\Item|null $item */
		$item = $event->getParameter('object');
		if (!$item)
		{
			return $result;
		}

		ProductRowTable::handleOwnerUpdate($item, $result);

		return $result;
	}

	public static function onAfterDelete(ORM\Event $event): ORM\EventResult
	{
		$result = parent::onAfterDelete($event);

		if (!$result->getErrors())
		{
			$typeData = static::getType();
			$entityTypeId = (int)$typeData['ENTITY_TYPE_ID'];
			$id = static::getTemporaryStorage()->getIdByPrimary($event->getParameter('primary'));

			EntityContactTable::deleteByItem($entityTypeId, $id);
			ProductRowTable::deleteByItem($entityTypeId, $id);
			AssignedTable::deleteByItem($entityTypeId, $id);
			EntityRelationTable::deleteByItem($entityTypeId, $id);
			TimelineEntry::deleteByOwner($entityTypeId, $id);

			static::getFullTextDataClass()::delete($id);
		}

		return $result;
	}

	public static function getDefaultCategoryId(): ?int
	{
		$factory = static::getFactory();

		if($factory)
		{
			return $factory->createDefaultCategoryIfNotExist()->getId();
		}

		return null;
	}

	public static function getDefaultStageId(): ?string
	{
		$factory = static::getFactory();
		if ($factory)
		{
			$categoryId = $factory->createDefaultCategoryIfNotExist()->getId();
			$stages = $factory->getStages($categoryId);
			$firstStage = $stages->getAll()[0] ?? null;

			return $firstStage ? $firstStage->getStatusId() : null;
		}

		return null;
	}

	public static function getDefaultCloseDate(): Date
	{
		$currentDate = new Date();

		return $currentDate->add(static::getCloseDateOffset());
	}

	protected static function getCloseDateOffset(): string
	{
		return '7D';
	}

	protected static function convertValuesBeforeSave(array $data, array $userFields): array
	{
		$data = parent::convertValuesBeforeSave($data, $userFields);

		if (isset($data['ASSIGNED_BY_ID']))
		{
			$data['ASSIGNED_BY_ID'] = static::normalizeAssignedById(
				$data['ASSIGNED_BY_ID'],
				static::getFactory()->isMultipleAssignedEnabled()
			);
		}

		return $data;
	}

	protected static function saveMultipleValues($id, array $data, array $options = []): ORM\EventResult
	{
		if (isset($data['ASSIGNED_BY_ID']))
		{
			$id = (int)static::getTemporaryStorage()->getIdByPrimary($id);
			$isMultipleAssignedEnabled = static::getFactory()->isMultipleAssignedEnabled();
			$data['ASSIGNED_BY_ID'] = static::normalizeAssignedById(
				$data['ASSIGNED_BY_ID'],
				$isMultipleAssignedEnabled
			);
			if(!$isMultipleAssignedEnabled)
			{
				$data['ASSIGNED_BY_ID'] = [$data['ASSIGNED_BY_ID']];
			}

			$entityTypeId = (int)static::getType()['ENTITY_TYPE_ID'];
			AssignedTable::deleteByItem($entityTypeId, $id);
			foreach($data['ASSIGNED_BY_ID'] as $assignedId)
			{
				AssignedTable::add([
					'ENTITY_TYPE_ID' => $entityTypeId,
					'ENTITY_ID' => $id,
					'ASSIGNED_BY' => $assignedId,
				]);
			}
		}

		return parent::saveMultipleValues($id, $data, $options);
	}

	protected static function normalizeAssignedById($value, bool $isMultipleAssignedEnabled)
	{
		$isArray = is_array($value);
		if($isMultipleAssignedEnabled && !$isArray)
		{
			$value = [$value];
		}
		elseif(!$isMultipleAssignedEnabled && $isArray)
		{
			$value = (int) reset($value);
		}

		return $value;
	}

	public static function unserializeAssignedById($value): array
	{
		$isJson = (mb_strpos($value, '[') === 0);
		if(!$isJson)
		{
			return [$value];
		}

		return Main\Web\Json::decode($value);
	}

	public static function getOwnFieldNames(): array
	{
		$names = parent::getOwnFieldNames();
		$names['ASSIGNED_BY_ID'] = 'ASSIGNED_BY_ID';

		return $names;
	}

	/**
	 * We can't use \Bitrix\Main\ORM\Fields\EnumField explicitly.
	 * Reason - we can't configure the size of a created VARCHAR column in
	 * the table that is created by \Bitrix\Main\ORM\Entity::createDbTable
	 * For details @see \Bitrix\Main\DB\MysqlCommonSqlHelper::getColumnTypeByField
	 *
	 * Therefore, we simply imitate its validation on a basic string field
	 *
	 * @param $value
	 *
	 * @return true|string
	 */
	public static function validateSourceId($value)
	{
		if (empty($value))
		{
			return true;
		}

		$possibleValues = StatusTable::getStatusesIds(StatusTable::ENTITY_ID_SOURCE);

		/** @noinspection TypeUnsafeArraySearchInspection */
		if (in_array($value, $possibleValues))
		{
			return true;
		}

		return 'SOURCE_ID is invalid';
	}

	public static function onBeforeAdd(Event $event): ORM\EventResult
	{
		$result = parent::onBeforeAdd($event);

		/** @var \Bitrix\Crm\Model\Dynamic\Item $item */
		$item = $event->getParameter('object');
		$factory = static::getFactory();
		if ($factory && empty($item->getStageId()))
		{
			$categoryId = $item->getCategoryId();
			if (!$categoryId)
			{
				$categoryId = $factory->createDefaultCategoryIfNotExist()->getId();
			}
			$stage = $factory->getStages($categoryId)->getAll()[0];
			$result->modifyFields(array_merge($result->getModified(), [
				'STAGE_ID' => $stage->getStatusId(),
			]));
		}

		return $result;
	}

	public static function disableUserFieldsCheck(): void
	{
		static::$isCheckUserFields = false;
	}

	/**
	 * @inheritDoc
	 */
	protected static function modifyValuesBeforeSave($id, array $data, array $options = []): ORM\EventResult
	{
		$userFieldManager = UserFieldHelper::getInstance()->getManager();
		$isUpdate = (isset($options['isUpdate']) && $options['isUpdate'] === true);

		$result = new Main\ORM\EventResult();
		if (!$userFieldManager)
		{
			static::$isCheckUserFields = true;
			return $result;
		}

		if($isUpdate)
		{
			$oldData = static::getByPrimary($id)->fetch();
			static::getTemporaryStorage()->saveData($id, $oldData);
			if (
				static::$isCheckUserFields
				&& !$userFieldManager->checkFieldsWithOldData(
					static::getItemUserFieldEntityId(),
					$oldData,
					$data
				)
			)
			{
				$result->addError(static::getErrorFromException());
			}

			$fields = $userFieldManager->getUserFieldsWithReadyData(
				static::getItemUserFieldEntityId(),
				$oldData,
				LANGUAGE_ID,
				false,
				'ID'
			);
		}
		else
		{
			$fields = $userFieldManager->getUserFields(static::getItemUserFieldEntityId());

			if(
				static::$isCheckUserFields
				&& !$userFieldManager->checkFields(
					static::getItemUserFieldEntityId(),
					null,
					$data,
					false,
					false
				)
			)
			{
				$result->addError(static::getErrorFromException());
			}
		}

		if(!$result->getErrors())
		{
			$data = static::convertValuesBeforeSave($data, $fields);
			$result->modifyFields($data);
		}

		static::$isCheckUserFields = true;

		return $result;
	}
}
