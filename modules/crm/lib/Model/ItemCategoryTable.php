<?php
namespace Bitrix\Crm\Model;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Entity\BooleanField;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\EntityError;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

class ItemCategoryTable extends DataManager
{
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

	public static function onBeforeUpdate(Event $event): EventResult
	{
		$object = $event->getParameter('object');
		$object->reset('ENTITY_TYPE_ID');

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
		elseif ($factory->getItemsCount($category->getItemsFilter()) > 0)
		{
			$result->addError(new EntityError(Loc::getMessage('CRM_CATEGORY_TABLE_DELETE_ERROR_ITEMS')));
		}
		if (!$result->getErrors())
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
}
