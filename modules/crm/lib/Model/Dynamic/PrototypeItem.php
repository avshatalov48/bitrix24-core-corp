<?php

namespace Bitrix\Crm\Model\Dynamic;

use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\ContactTable;
use Bitrix\Crm\FieldContext\Repository;
use Bitrix\Crm\Model\AssignedTable;
use Bitrix\Crm\ProductRowTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\Fields\DecimalField;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Query\Join;
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

		$fieldRepository = Main\DI\ServiceLocator::getInstance()->get('crm.model.fieldRepository');

		return [
			// be aware that this field map is used for DDL and sql table generation!
			/** @see ORM\Entity::createDbTable() */
			// do not use EnumFields here, since they generate wrong column types

			$fieldRepository->getId(),

			(new StringField('XML_ID'))
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_XML_ID'))
				->configureDefaultValue('')
			,

			$fieldRepository->getTitle(),

			$fieldRepository->getCreatedBy(),

			$fieldRepository->getUpdatedBy()
				->configureRequired(false)
			,

			$fieldRepository->getMovedBy(),

			$fieldRepository->getCreatedTime(),

			$fieldRepository->getUpdatedTime()
				->configureRequired(false)
			,

			$fieldRepository->getMovedTime(),

			$fieldRepository->getCategoryId()
				// when getMap is called, we yet to know entityTypeId. postpone it to later moment
				->configureDefaultValue([static::class, 'getDefaultCategoryId'])
			,

			$fieldRepository->getOpened()
				->configureDefaultValue(false)
			,

			$fieldRepository->getStageId()
				// when getMap is called, we yet to know entityTypeId. postpone it to later moment
				->configureDefaultValue([static::class, 'getDefaultStageId'])
			,

			(new StringField('PREVIOUS_STAGE_ID'))
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_PREVIOUS_STAGE_ID'))
				->configureDefaultValue('')
			,

			$fieldRepository->getBeginDate(),

			$fieldRepository->getCloseDate(),

			$fieldRepository->getCompanyId()
				->configureDefaultValue(0)
			,

			(new Reference('COMPANY', CompanyTable::class, Join::on('this.COMPANY_ID', 'ref.ID'))),

			$fieldRepository->getContactId()
				->configureDefaultValue(0)
			,

			(new Reference('CONTACT', ContactTable::class, Join::on('this.CONTACT_ID', 'ref.ID'))),

			// common fields are float because of backward compatibility
			// these fields are decimal to define appropriate column type on DDL generation
			(new DecimalField('OPPORTUNITY'))
				->configurePrecision(18)
				->configureScale(2)
				->configureDefaultValue($fieldRepository->getOpportunity()->getDefaultValue())
				->configureTitle($fieldRepository->getOpportunity()->getTitle())
			,

			$fieldRepository->getIsManualOpportunity()
				->configureRequired(false)
			,

			(new DecimalField('TAX_VALUE'))
				->configurePrecision(18)
				->configureScale(2)
				->configureDefaultValue($fieldRepository->getTaxValue()->getDefaultValue())
				->configureTitle($fieldRepository->getTaxValue()->getTitle())
			,

			$fieldRepository->getCurrencyId(),

			(new DecimalField('OPPORTUNITY_ACCOUNT'))
				->configurePrecision(18)
				->configureScale(2)
				->configureDefaultValue($fieldRepository->getOpportunityAccount()->getDefaultValue())
				->configureTitle($fieldRepository->getOpportunityAccount()->getTitle())
			,

			(new DecimalField('TAX_VALUE_ACCOUNT'))
				->configurePrecision(18)
				->configureScale(2)
				->configureDefaultValue($fieldRepository->getTaxValueAccount()->getDefaultValue())
				->configureTitle($fieldRepository->getTaxValueAccount()->getTitle())
			,

			$fieldRepository->getAccountCurrencyId(),

			$fieldRepository->getMyCompanyId(),

			(new Reference('MYCOMPANY', CompanyTable::class, Join::on('this.MYCOMPANY_ID', 'ref.ID'))),

			$fieldRepository->getSourceId(),

			$fieldRepository->getSourceDescription()
				->configureDefaultValue('')
			,

			$fieldRepository->getWebformId()
				->configureDefaultValue(0)
			,

			// $fieldRepository->getLastActivityBy(),
			//
			// $fieldRepository->getLastActivityTime(),
		];
	}

	public static function getFactory(): ?\Bitrix\Crm\Service\Factory
	{
		return Container::getInstance()->getFactory(static::getEntityTypeId());
	}

	protected static function getEntityTypeId(): int
	{
		return (int)static::getType()['ENTITY_TYPE_ID'];
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

		$parameters['filter'] = PrototypeItemFilter::replaceParameters($parameters['filter'], static::getEntityTypeId());

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
		return new Reference(
			'FULL_TEXT',
			static::getFullTextDataClass()::getEntity(),
			[
				'=this.ID' => 'ref.ITEM_ID',
			],
			[
				'join_type' => Join::TYPE_INNER,
			]
		);
	}

	public static function getFieldsContextDataClass(): string
	{
		$typeData = static::getType();

		return \Bitrix\Main\DI\ServiceLocator::getInstance()
			->get('crm.type.factory')
			->getItemFieldsContextDataClass($typeData)
		;
	}

	public static function onAfterUpdate(Event $event): ORM\EventResult
	{
		$result = parent::onAfterUpdate($event);
		if ($result->getErrors())
		{
			return $result;
		}

		/** @var Item|null $item */
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
			$id = static::getTemporaryStorage()->getIdByPrimary($event->getParameter('primary'));

			static::getFullTextDataClass()::delete($id);

			if (Repository::hasFieldsContextTables())
			{
				static::getFieldsContextDataClass()::deleteByItemId($id);
			}
		}

		return $result;
	}

	public static function getDefaultCategoryId(): ?int
	{
		$fieldRepository = Main\DI\ServiceLocator::getInstance()->get('crm.model.fieldRepository');

		$resolver = $fieldRepository->getDefaultCategoryIdResolver(static::getEntityTypeId());

		return $resolver();
	}

	public static function getDefaultStageId(): ?string
	{
		$fieldRepository = Main\DI\ServiceLocator::getInstance()->get('crm.model.fieldRepository');

		$resolver = $fieldRepository->getDefaultStageIdResolver(static::getEntityTypeId());

		return $resolver();
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
		$names[\Bitrix\Crm\Item::FIELD_NAME_ASSIGNED] = \Bitrix\Crm\Item::FIELD_NAME_ASSIGNED;
		$names[\Bitrix\Crm\Item::FIELD_NAME_LAST_ACTIVITY_TIME] = \Bitrix\Crm\Item::FIELD_NAME_LAST_ACTIVITY_TIME;
		$names[\Bitrix\Crm\Item::FIELD_NAME_LAST_ACTIVITY_BY] = \Bitrix\Crm\Item::FIELD_NAME_LAST_ACTIVITY_BY;
		$names[\Bitrix\Crm\Item\SmartInvoice::FIELD_NAME_COMMENTS] = \Bitrix\Crm\Item\SmartInvoice::FIELD_NAME_COMMENTS;
		$names[\Bitrix\Crm\Item\SmartInvoice::FIELD_NAME_ACCOUNT_NUMBER] = \Bitrix\Crm\Item\SmartInvoice::FIELD_NAME_ACCOUNT_NUMBER;
		$names[\Bitrix\Crm\Item\SmartInvoice::FIELD_NAME_LOCATION_ID] = \Bitrix\Crm\Item\SmartInvoice::FIELD_NAME_LOCATION_ID;
		$names[\Bitrix\Crm\Item\SmartDocument::FIELD_NAME_NUMBER] = \Bitrix\Crm\Item\SmartDocument::FIELD_NAME_NUMBER;
		$names[\Bitrix\Crm\Item\SmartB2eDocument::FIELD_NAME_NUMBER] = \Bitrix\Crm\Item\SmartB2eDocument::FIELD_NAME_NUMBER;

		return $names;
	}

	public static function onBeforeAdd(Event $event): ORM\EventResult
	{
		$result = parent::onBeforeAdd($event);

		/** @var Item $item */
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
