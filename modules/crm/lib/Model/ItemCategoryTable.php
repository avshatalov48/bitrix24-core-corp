<?php

namespace Bitrix\Crm\Model;

use Bitrix\Crm\Category\Entity\ItemCategory;
use Bitrix\Crm\Category\ItemCategoryUserField;
use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory\SmartDocument;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlException;
use Bitrix\Main\Entity\BooleanField;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\EntityError;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Json;
use Bitrix\Crm\Security\Role\Utils\RolePermissionLogContext;

/**
 * Class ItemCategoryTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ItemCategory_Query query()
 * @method static EO_ItemCategory_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ItemCategory_Result getById($id)
 * @method static EO_ItemCategory_Result getList(array $parameters = [])
 * @method static EO_ItemCategory_Entity getEntity()
 * @method static \Bitrix\Crm\Model\EO_ItemCategory createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Model\EO_ItemCategory_Collection createCollection()
 * @method static \Bitrix\Crm\Model\EO_ItemCategory wakeUpObject($row)
 * @method static \Bitrix\Crm\Model\EO_ItemCategory_Collection wakeUpCollection($rows)
 */
class ItemCategoryTable extends DataManager
{
	private static $categoryToEntityTypeRelations = [];

	public static function getTableName(): string
	{
		return 'b_crm_item_category';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new IntegerField('ENTITY_TYPE_ID'))
				->configureRequired(),
			(new BooleanField('IS_DEFAULT'))
				->configureStorageValues('N', 'Y')
				->configureDefaultValue('N'),
			(new BooleanField('IS_SYSTEM'))
				->configureStorageValues('N', 'Y')
				->configureDefaultValue('N'),
			(new StringField('CODE'))
				->configureSize(255)
				->configureUnique(),
			(new DatetimeField('CREATED_DATE'))
				->configureRequired()
				->configureDefaultValue(static function() {
					return new DateTime();
				}),
			(new StringField('NAME'))
				->configureRequired()
				->configureSize(255)
				->configureDefaultValue(static function() {
					Container::getInstance()->getLocalization()->loadMessages();

					return Loc::getMessage('CRM_TYPE_CATEGORY_DEFAULT_NAME');
				}),
			(new IntegerField('SORT'))
				->configureRequired()
				->configureDefaultValue(500),
			(new ArrayField('SETTINGS'))
				->configureSerializationJson(),
		];
	}

	public static function deleteByEntityTypeId(int $entityTypeId): Result
	{
		$result = new Result();

		$list = static::getList([
			'select' => ['ID'],
			'filter' => [
				'=ENTITY_TYPE_ID' => $entityTypeId,
			],
		]);
		while($item = $list->fetch())
		{
			$deleteResult = static::delete($item['ID']);
			if (!$deleteResult->isSuccess())
			{
				$result->addErrors($deleteResult->getErrors());
			}
		}

		return $result;
	}

	public static function onBeforeAdd(Event $event): EventResult
	{
		$result = new EventResult();

		$fields = $event->getParameter('fields');
		if ($fields['IS_SYSTEM'])
		{
			$result->addError(new EntityError(Loc::getMessage('CRM_TYPE_CATEGORY_ADD_ERROR_SYSTEM')));
		}

		return $result;
	}

	public static function onBeforeUpdate(Event $event): EventResult
	{
		$object = $event->getParameter('object');
		$object->reset('ENTITY_TYPE_ID');
		$object->reset('IS_SYSTEM');
		$object->reset('CODE');

		return new EventResult();
	}

	public static function onBeforeDelete(Event $event): EventResult
	{
		$result = new EventResult();

		$id = $event->getParameter('id');
		if (is_array($id))
		{
			$id = $id['ID'];
		}
		$id = (int) $id;

		$data = static::getById($id)->fetch();
		if (!$data)
		{
			return $result;
		}
		$factory = Container::getInstance()->getFactory((int) $data['ENTITY_TYPE_ID']);
		if (!$factory)
		{
			return $result;
		}
		$category = $factory->getCategory($id);
		if (!$category)
		{
			return $result;
		}

		if ($category->getIsDefault() && $factory->getItemsCount() > 0)
		{
			$result->addError(new EntityError(Loc::getMessage('CRM_CATEGORY_TABLE_DELETE_ERROR_DEFAULT')));
		}
		elseif ($category->getIsSystem())
		{
			$result->addError(new EntityError(Loc::getMessage('CRM_TYPE_CATEGORY_DELETE_ERROR_SYSTEM')));
		}
		elseif ($factory->getItemsCount($category->getItemsFilter()) > 0)
		{
			$result->addError(new EntityError(Loc::getMessage('CRM_CATEGORY_TABLE_DELETE_ERROR_ITEMS')));
		}

		if (!$result->getErrors() && $factory->isStagesSupported())
		{
			$stages = $factory->getStages($category->getId());
			foreach ($stages as $stage)
			{
				$deleteStageResult = $stage->delete();
				if (!$deleteStageResult->isSuccess())
				{
					foreach ($deleteStageResult->getErrorMessages() as $message)
					{
						$result->addError(new EntityError($message));
					}
				}
			}
		}
		if (!$result->getErrors())
		{
			static::$categoryToEntityTypeRelations[$id] = $data['ENTITY_TYPE_ID'];
		}

		return $result;
	}

	public static function onAfterAdd(Event $event): EventResult
	{
		$result = new EventResult();

		$object = $event->getParameter('object');
		$scenarios = Container::getInstance()->getDirector()->getScenariosForNewCategory(
			$object->getEntityTypeId(),
			$object->getId()
		);
		$scenariosResult = $scenarios->playAll();
		if (!$scenariosResult->isSuccess())
		{
			foreach ($scenariosResult->getErrorMessages() as $message)
			{
				$result->addError(new EntityError($message));
			}
		}

		return $result;
	}

	public static function onAfterDelete(Event $event): EventResult
	{
		$result = new EventResult();

		$id = $event->getParameter('id');
		if (is_array($id))
		{
			$id = $id['ID'];
		}
		$id = (int) $id;

		$entityTypeId = static::$categoryToEntityTypeRelations[$id] ?? null;

		if ($entityTypeId)
		{
			RolePermissionLogContext::getInstance()->set([
				'scenario' => 'remove category',
				'entityTypeId' => $entityTypeId,
				'categoryId' => $id,
			]);

			\CCrmRole::EraseEntityPermissons(
				(new PermissionEntityTypeHelper($entityTypeId))->getPermissionEntityTypeForCategory($id)
			);
			Container::getInstance()->getFactory($entityTypeId)->clearCategoriesCache();
			unset(static::$categoryToEntityTypeRelations[$id]);

			(new ItemCategoryUserField($entityTypeId))->deleteByCategoryId($id);

			RolePermissionLogContext::getInstance()->clear();
		}

		return $result;
	}

	public static function getItemCategoriesByEntityTypeId(int $entityTypeId): array
	{
		$categories = [];

		$list = static::query()
			->where('ENTITY_TYPE_ID', $entityTypeId)
			->setSelect(['*'])
			->addOrder('SORT')
			->setCacheTtl(3600)
			->exec()
		;

		while($item = $list->fetchObject())
		{
			$categories[] = new ItemCategory($item);
		}

		return $categories;
	}

	public static function installBundledCategoriesIfNotExists(): Result
	{
		$result = new Result();

		$contactCategoryForSign =
			self::query()
				->setSelect(['ID'])
				->where('IS_SYSTEM', true)
				->where('CODE', SmartDocument::CONTACT_CATEGORY_CODE)
				->fetchObject()
		;

		if (!$contactCategoryForSign)
		{
			$settings = [
				'disabledFieldNames' => [
					'COMPANY',
				],
				'isTrackingEnabled' => false,
				'uiSettings' => [
					'grid' => [
						'defaultFields' => [
							'CONTACT_SUMMARY',
							'ACTIVITY_ID',
							'POST',
							'PHONE',
							'EMAIL',
						],
					],
					'filter' => [
						'defaultFields' => [
							'NAME',
							'SECOND_NAME',
							'LAST_NAME',
							'PHONE',
							'EMAIL',
						],
					],
				],
			];

			try
			{
				// we cant add system category via DataManager because of onBeforeAdd
				Application::getConnection()->add(
					self::getTableName(),
					[
						'CODE' => SmartDocument::CONTACT_CATEGORY_CODE,
						'IS_SYSTEM' => 'Y',
						'ENTITY_TYPE_ID' => \CCrmOwnerType::Contact,
						'CREATED_DATE' => new DateTime(),
						'NAME' => '',
						'SORT' => 500,
						'SETTINGS' => Json::encode($settings),
					],
				);

				self::cleanCache();
				Container::getInstance()->getFactory(\CCrmOwnerType::Contact)?->clearCategoriesCache();
			}
			catch (SqlException $exception)
			{
				$result->addError(new Error($exception->getMessage(), $exception->getCode()));
			}
		}

		return $result;
	}
}
