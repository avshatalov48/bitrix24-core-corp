<?php

namespace Bitrix\Rpa\Model;

use Bitrix\Main;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserField\Internal\UserFieldHelper;
use Bitrix\Rpa\Components\Base;
use Bitrix\Rpa\Driver;

abstract class PrototypeItem extends Main\UserField\Internal\PrototypeItemDataManager
{
	public const DEFAULT_SORT = 1000;

	protected static $isCheckUserFields = true;

	public static function disableUserFieldsCheck(): void
	{
		static::$isCheckUserFields = false;
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap(): array
	{
		Base::loadBaseLanguageMessages();

		return [
			(new ORM\Fields\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new ORM\Fields\IntegerField('STAGE_ID'))
				->configureRequired()
				->configureDefaultValue([static::class, 'getDefaultStageId'])
				->configureTitle(Main\Localization\Loc::getMessage('RPA_COMMON_STAGE')),
			(new Reference(
				'STAGE',
				StageTable::class,
				['=this.STAGE_ID' => 'ref.ID']
			)),
			(new ORM\Fields\IntegerField('PREVIOUS_STAGE_ID')),
			(new Reference(
				'PREVIOUS_STAGE',
				StageTable::class,
				['=this.PREVIOUS_STAGE_ID' => 'ref.ID']
			)),
			(new ORM\Fields\StringField('XML_ID')),
			(new ORM\Fields\IntegerField('CREATED_BY'))
				->configureRequired()
				->configureDefaultValue(static function()
				{
					return Driver::getInstance()->getUserId();
				})
				->configureTitle(Main\Localization\Loc::getMessage('RPA_ITEM_CREATED_BY')),
			(new ORM\Fields\IntegerField('UPDATED_BY'))
				->configureTitle(Main\Localization\Loc::getMessage('RPA_ITEM_UPDATED_BY')),
			(new ORM\Fields\IntegerField('MOVED_BY'))
				->configureTitle(Main\Localization\Loc::getMessage('RPA_ITEM_MOVED_BY')),
			(new ORM\Fields\DatetimeField('CREATED_TIME'))
				->configureRequired()
				->configureDefaultValue(static function()
				{
					return new DateTime();
				})
				->configureTitle(Main\Localization\Loc::getMessage('RPA_ITEM_CREATED_TIME')),
			(new ORM\Fields\DatetimeField('UPDATED_TIME'))
				->configureTitle(Main\Localization\Loc::getMessage('RPA_ITEM_UPDATED_TIME')),
			(new ORM\Fields\DatetimeField('MOVED_TIME'))
				->configureTitle(Main\Localization\Loc::getMessage('RPA_ITEM_MOVED_TIME')),
		];
	}

	public static function updateUserFieldValues(int $id, array $fields): Result
	{
		/** @var Item $item */
		$item = static::getById($id)->fetchObject();
		foreach($fields as $name => $value)
		{
			$item->set($name, $value);
		}

		return Driver::getInstance()->getFactory()->getUpdateCommand($item)->run();
	}

	public static function deleteUserFieldValues(int $id): Main\Result
	{
		/** @var Item $item */
		$item = static::getById($id)->fetchObject();
		$userTypeManager = UserFieldHelper::getInstance()->getManager();
		if ($userTypeManager instanceof \CUserTypeManager)
		{
			$userFields = $userTypeManager->GetUserFields(static::getItemUserFieldEntityId());
			foreach($userFields as $userField)
			{
				$item->set($userField['FIELD_NAME'], null);
			}
		}

		return Driver::getInstance()->getFactory()->getUpdateCommand($item)->run();
	}

	public static function getDefaultStageId(): ?int
	{
		$type = static::getType();
		/** @var TypeTable $typeDataClass */
		$typeDataClass = Driver::getInstance()->getFactory()->getTypeDataClass();
		$type = $typeDataClass::getById($type['ID'])->fetchObject();
		/** @var Type $type */
		$firstStage = $type->getFirstStage();
		if($firstStage)
		{
			return $firstStage->getId();
		}

		return null;
	}

	public static function onBeforeUpdate(Event $event): ORM\EventResult
	{
		/** @var Item $item */
		$item = $event->getParameter('object');
		if($item->isChanged('STAGE_ID'))
		{
			$item->setPreviousStageId($item->remindActual('STAGE_ID'));
		}
		return parent::onBeforeUpdate($event);
	}

	public static function onAfterDelete(Event $event): ORM\EventResult
	{
		$result = parent::onAfterDelete($event);

		$id = static::getTemporaryStorage()->getIdByPrimary($event->getParameter('id'));
		$type = static::getType();

		if($id > 0 && $type['ID'] > 0)
		{
			ItemSortTable::removeForItem($type['ID'], $id);
			TimelineTable::removeForItem($type['ID'], $id);
			ItemHistoryTable::removeForItem($type['ID'], $id);
			$itemIndexTableDataClass = Driver::getInstance()->getFactory()->getItemIndexDataClass($type);
			if($itemIndexTableDataClass)
			{
				$itemIndexTableDataClass::delete($id);
			}
		}

		return $result;
	}

	public static function getUserSortReferenceField(int $typeId, int $userId): Reference
	{
		return new Reference('USER_SORT', 'Bitrix\Rpa\Model\ItemSort', [
			'=this.ID' => 'ref.ITEM_ID',
			'ref.TYPE_ID' => new SqlExpression('?i', $typeId),
			'ref.USER_ID' => new SqlExpression('?i', $userId),
		]);
	}

	public static function getFullTextReferenceField(string $referenceName = 'FULL_TEXT'): Reference
	{
		$typeData = static::getType();
		$itemIndexTableDataClass = Driver::getInstance()->getFactory()->getItemIndexDataClass($typeData);
		return new Reference(
			$referenceName,
			$itemIndexTableDataClass::getEntity(),
			[
				'=this.ID' => 'ref.ITEM_ID',
			],
			[
				'join_type' => \Bitrix\Main\ORM\Query\Join::TYPE_INNER,
			]
		);
	}

	protected static function updateFullTextIndex(Item $item): Result
	{
		$typeData = static::getType();
		$manager = UserFieldHelper::getInstance()->getManager();
		if(!$manager)
		{
			return new Result();
		}
		$userFieldsSearchIndex = $manager->OnSearchIndex(Driver::getInstance()->getFactory()->getUserFieldEntityId($typeData['ID']), $item->getId());
		$searchContent = Main\Search\MapBuilder::create()
			->addText($userFieldsSearchIndex)
			->addInteger($item->getId());

		$stage = $item->getStage();
		if($stage)
		{
			$searchContent->addText($stage->getName());
		}

		$searchContent->addUser($item->getUserIds());
		$itemIndexTableDataClass = Driver::getInstance()->getFactory()->getItemIndexDataClass($typeData);

		return $itemIndexTableDataClass::merge($item->getId(), $searchContent->build());
	}

	public static function onAfterAdd(Event $event): ORM\EventResult
	{
		$result = parent::onAfterAdd($event);

		if(!$result->getErrors())
		{
			$item = $event->getParameter('object');
			if($item)
			{
				static::updateFullTextIndex($item);
			}
		}

		return $result;
	}

	public static function onAfterUpdate(Event $event): ORM\EventResult
	{
		$result = parent::onAfterUpdate($event);

		if(!$result->getErrors())
		{
			$item = $event->getParameter('object');
			if($item)
			{
				static::updateFullTextIndex($item);
			}
		}

		return $result;
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
					true
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
