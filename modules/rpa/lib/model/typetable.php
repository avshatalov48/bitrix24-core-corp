<?php

namespace Bitrix\Rpa\Model;

use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\Result;
use Bitrix\Main\Security\Random;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserField;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Event;
use Bitrix\Rpa\Components\Base;
use Bitrix\Rpa\Driver;
use Bitrix\Rpa\UserPermissions;

class TypeTable extends UserField\Internal\TypeDataManager
{
	public const ITEM_TITLE_UF_SUFFIX = 'NAME';
	protected const MAX_TRIES_GENERATE_NAME = 5;
	protected const NAME_RANDOM_STRING_LENGTH = 10;

	public static function getTableName(): string
	{
		return 'b_rpa_type';
	}

	public static function getMap(): array
	{
		$fieldsMap = parent::getMap();
		Base::loadBaseLanguageMessages();

		$fieldsMap[] = (new ORM\Fields\StringField('TITLE'))->configureRequired()->configureTitle(Loc::getMessage('RPA_COMMON_TITLE'));
		$fieldsMap[] = (new ORM\Fields\StringField('IMAGE'))->configureTitle(Loc::getMessage('RPA_COMMON_IMAGE'));
		$fieldsMap[] = (new ORM\Fields\IntegerField('CREATED_BY'))
			->configureTitle(Loc::getMessage('RPA_ITEM_CREATED_BY'))
			->configureRequired()
			->configureDefaultValue(static function()
			{
				return Driver::getInstance()->getUserId();
			});
		$fieldsMap[] = (new ORM\Fields\ArrayField('SETTINGS'));

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
			if($try > 0)
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

	public static function onBeforeAdd(Event $event): ORM\EventResult
	{
		$fields = $event->getParameter('fields');
		$result = new ORM\EventResult();
		if(!empty($fields['NAME']))
		{
			$fields['NAME'] = static::prepareName($fields['NAME']);
			$result->modifyFields([
				'NAME' => $fields['NAME'],
				'TABLE_NAME' => static::getItemTableName($fields['NAME']),
			]);
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
			'replace_space' => '_',
			'replace_other' => '_',
			'delete_repeat_replace' => true,
			'safe_chars' => '',
		];
	}

	public static function onBeforeUpdate(Event $event): ORM\EventResult
	{
		$result = parent::onBeforeUpdate($event);

		$fields = $event->getParameter('fields');
		if(isset($fields['NAME']) && !empty($fields['NAME']))
		{
			$fields['NAME'] = static::prepareName($fields['NAME']);
			$result->modifyFields([
				'NAME' => $fields['NAME'],
				'TABLE_NAME' => static::getItemTableName($fields['NAME']),
			]);
		}

		return $result;
	}

	protected static function getItemTableName(string $name): string
	{
		if(empty($name))
		{
			throw new ArgumentNullException('NAME');
		}
		$name = mb_strtolower($name);
		return 'b_rpa_items_'.$name;
	}

	/**
	 * @return ORM\Entity|string
	 */
	public static function getObjectClass(): string
	{
		return Type::class;
	}

	public static function onBeforeDelete(Event $event): EventResult
	{
		$result = new EventResult();

		$id = $event->getParameter('id');
		if(is_array($id))
		{
			$id = $id['ID'];
		}
		$id = (int) $id;

		$type = static::getById($id)->fetchObject();
		if($type && $type->getItemsCount() > 0)
		{
			$result->addError(new ORM\EntityError(Loc::getMessage('RPA_TYPE_TABLE_DELETE_ERROR_ITEMS')));
			return $result;
		}

		return parent::onBeforeDelete($event);
	}

	public static function onAfterDelete(Event $event): EventResult
	{
		$result = new EventResult();
		$id = $event->getParameter('id');
		if(is_array($id))
		{
			$id = $id['ID'];
		}
		$id = (int) $id;

		$stages = StageTable::getList([
			'select' => [
				'ID',
			],
			'filter' => [
				'=TYPE_ID' => $id,
			],
		]);
		while($stage = $stages->fetchObject())
		{
			$deleteResult = $stage->delete();
			if(!$deleteResult->isSuccess())
			{
				foreach($deleteResult->getErrors() as $error)
				{
					$result->addError($error);
				}
			}
		}

		if(!$result->getErrors())
		{
			PermissionTable::deleteByEntity(UserPermissions::ENTITY_TYPE, $id);
			FieldTable::removeByTypeId($id);
			ItemSortTable::removeByTypeId($id);
			TimelineTable::removeByTypeId($id);
			ItemHistoryTable::removeByTypeId($id);
			$typeData = static::getTemporaryStorage()->getData($id);
			static::getTemporaryStorage()->saveData($id, $typeData);
			$itemIndexEntity = static::compileItemIndexEntity($typeData);
			if($itemIndexEntity)
			{
				$tableName = $itemIndexEntity->getDBTableName();
				if(Application::getConnection()->isTableExists($tableName))
				{
					Application::getConnection()->dropTable($tableName);
				}
			}
		}

		$parentResult = parent::onAfterDelete($event);
		foreach($parentResult->getErrors() as $error)
		{
			$result->addError($error);
		}

		return $result;
	}

	public static function onAfterAdd(Event $event): EventResult
	{
		$result = parent::onAfterAdd($event);

		if(!$result->getErrors())
		{
			static::createItemIndexTable(static::getTemporaryStorage()->getIdByPrimary($event->getParameter('primary')));
		}

		return $result;
	}

	public static function createItemIndexTable($type): Result
	{
		$result = new Result();
		$entity = static::compileItemIndexEntity($type);
		$entity->createDbTable();
		$connection = Application::getConnection();

		if(!$connection->isTableExists($entity->getDBTableName()))
		{
			$result->addError(new Error('Could not create item index table'));
			return $result;
		}

		$createIndexResult = $connection->createIndex(
			$entity->getDBTableName(),
			$entity->getDBTableName() . '_search',
			['SEARCH_CONTENT'],
			null,
			$connection::INDEX_FULLTEXT,
		);

		if (!$createIndexResult)
		{
			$result->addError(new Error('Could not create item fulltext index'));
		}

		return $result;
	}

	public static function compileEntity($type): Entity
	{
		$entity = parent::compileEntity($type);
		$typeData = static::resolveType($type);
		if(isset($typeData['ID']))
		{
			$type = Driver::getInstance()->getType($typeData['ID']);
			if($type)
			{
				foreach($type->getUserFieldCollection() as $userField)
				{
					if($entity->hasField($userField->getName()))
					{
						$field = $entity->getField($userField->getName());
						$field->configureTitle($userField->getTitle());
						$field->configureRequired($userField->isMandatory());
					}
				}
			}
		}

		return $entity;
	}

	public static function compileItemIndexEntity($type): Entity
	{
		$rawType = $type;
		$type = static::resolveType($type);
		if(empty($type))
		{
			throw new SystemException(sprintf(
				'Invalid type description \'%s\'.', mydump($rawType)
			));
		}
		$factory = Driver::getInstance()->getFactory();
		$dataClass = $factory->getItemIndexPrototypeDataClass();
		$entityName = $factory->getUserFieldEntityPrefix().$type['NAME'].'Index';
		$entityClassName = $entityName.'Table';
		$entityTableName = $type['TABLE_NAME'].'_index';
		if(class_exists($entityClassName))
		{
			Entity::destroy($entityClassName);
			$entity = Entity::getInstance($entityClassName);
		}
		else
		{
			$entity = Entity::compileEntity($entityName, [], [
				'table_name' => $entityTableName,
				'parent' => $dataClass,
			]);
		}

		return $entity;
	}
}