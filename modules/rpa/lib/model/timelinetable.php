<?php

namespace Bitrix\Rpa\Model;

use Bitrix\Main\ORM;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

/**
 * Class TimelineTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Timeline_Query query()
 * @method static EO_Timeline_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Timeline_Result getById($id)
 * @method static EO_Timeline_Result getList(array $parameters = [])
 * @method static EO_Timeline_Entity getEntity()
 * @method static \Bitrix\Rpa\Model\Timeline createObject($setDefaultValues = true)
 * @method static \Bitrix\Rpa\Model\EO_Timeline_Collection createCollection()
 * @method static \Bitrix\Rpa\Model\Timeline wakeUpObject($row)
 * @method static \Bitrix\Rpa\Model\EO_Timeline_Collection wakeUpCollection($rows)
 */
class TimelineTable extends ORM\Data\DataManager
{
	public static function getTableName(): string
	{
		return 'b_rpa_timeline';
	}

	public static function getMap(): array
	{
		return [
			(new ORM\Fields\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new ORM\Fields\IntegerField('TYPE_ID'))
				->configureRequired(),
			(new ORM\Fields\IntegerField('ITEM_ID'))
				->configureRequired(),
			(new ORM\Fields\DatetimeField('CREATED_TIME'))
				->configureRequired()
				->configureDefaultValue(static function()
				{
					return new DateTime();
				}),
			(new ORM\Fields\IntegerField('USER_ID')),
			(new ORM\Fields\StringField('TITLE'))
				->configureSize(255),
			(new ORM\Fields\TextField('DESCRIPTION')),
			(new ORM\Fields\StringField('ACTION'))
				->configureSize(255),
			(new ORM\Fields\BooleanField('IS_FIXED'))
				->configureValues('N', 'Y')
				->configureRequired()
				->configureDefaultValue('N'),
			(new ORM\Fields\ArrayField('DATA')),
		];
	}

	public static function getObjectClass(): string
	{
		return Timeline::class;
	}

	public static function getListByItem(int $typeId, int $itemId, array $parameters = []): EO_Timeline_Collection
	{
		$listParameters = [
			'order' => [
				'CREATED_TIME' => 'DESC',
				'ID' => 'DESC',
			],
			'filter' => [
				'=TYPE_ID' => $typeId,
				'=ITEM_ID' => $itemId,
			],
		];

		if(isset($parameters['limit']))
		{
			$listParameters['limit'] = $parameters['limit'];
		}

		if(isset($parameters['offset']))
		{
			$listParameters['offset'] = $parameters['offset'];
		}

		return static::getList($listParameters)->fetchCollection();
	}

	public static function removeForItem(int $typeId, int $itemId): Result
	{
		$result = new Result();

		$list = static::getListByItem($typeId, $itemId);
		foreach($list as $timeline)
		{
			$deleteResult = $timeline->delete();
			if(!$deleteResult->isSuccess())
			{
				$result->addErrors($deleteResult->getErrors());
			}
		}

		return $result;
	}

	public static function removeByTypeId(int $typeId): Result
	{
		$result = new Result();

		$list = static::getList([
			'select' => ['ID'],
			'filter' => [
				'=TYPE_ID' => $typeId,
			]
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
}