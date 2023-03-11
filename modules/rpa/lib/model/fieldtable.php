<?php

namespace Bitrix\Rpa\Model;

use Bitrix\Main\ORM;
use Bitrix\Main\Result;

/**
 * Class FieldTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Field_Query query()
 * @method static EO_Field_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Field_Result getById($id)
 * @method static EO_Field_Result getList(array $parameters = [])
 * @method static EO_Field_Entity getEntity()
 * @method static \Bitrix\Rpa\Model\EO_Field createObject($setDefaultValues = true)
 * @method static \Bitrix\Rpa\Model\EO_Field_Collection createCollection()
 * @method static \Bitrix\Rpa\Model\EO_Field wakeUpObject($row)
 * @method static \Bitrix\Rpa\Model\EO_Field_Collection wakeUpCollection($rows)
 */
class FieldTable extends ORM\Data\DataManager
{
	public const VISIBILITY_VISIBLE = 'visible';
	public const VISIBILITY_EDITABLE = 'editable';
	public const VISIBILITY_MANDATORY = 'mandatory';
	public const VISIBILITY_KANBAN = 'kanban';
	public const VISIBILITY_CREATE = 'create';

	public static function getTableName(): string
	{
		return 'b_rpa_field';
	}

	public static function getMap(): array
	{
		return [
			(new ORM\Fields\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new ORM\Fields\IntegerField('TYPE_ID'))
				->configureRequired(),
			(new ORM\Fields\IntegerField('STAGE_ID'))
				->configureRequired(),
			(new ORM\Fields\Relations\Reference(
				'STAGE',
				StageTable::class,
				['=this.STAGE_ID' => 'ref.ID']
			)),
			(new ORM\Fields\StringField('FIELD'))
				->configureRequired(),
			(new ORM\Fields\EnumField('VISIBILITY'))
				->configureRequired()
				->configureValues(static::getVisibilityTypes()),
		];
	}

	public static function removeByTypeId(int $typeId): Result
	{
		$result = new Result();

		$list = static::getList([
			'filter' => [
				'=TYPE_ID' => $typeId,
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

	public static function deleteByStageId(int $stageId): Result
	{
		$result = new Result();

		$list = static::getList([
			'filter' => [
				'=STAGE_ID' => $stageId,
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

	public static function getGroupedList(int $typeId, int $stageId, bool $isFullInfo = false): array
	{
		$settings = [];
		$list = static::getList([
			'filter' => [
				'=STAGE_ID' => $stageId,
				'=TYPE_ID' => $typeId,
			]
		]);
		while($field = $list->fetch())
		{
			$settings[$field['VISIBILITY']][$field['FIELD']] = ($isFullInfo ? $field : true);
		}

		return $settings;
	}

	public static function mergeSettings(int $typeId, int $stageId, array $fields, string $visibilityType = null): Result
	{
		$result = new Result();

		$skipAdding = [];
		$currentSettings = static::getGroupedList($typeId, $stageId, true);
		foreach($currentSettings as $visibility => $settings)
		{
			if(!empty($visibilityType) && $visibility !== $visibilityType)
			{
				continue;
			}
			foreach($settings as $field => $setting)
			{
				if(!isset($fields[$visibility]) || !in_array($field, $fields[$visibility], true))
				{
					static::delete($setting['ID']);
				}
				else
				{
					$skipAdding[$visibility][$field] = $field;
				}
			}
		}
		foreach($fields as $visibility => $visibilityFields)
		{
			foreach($visibilityFields as $field)
			{
				if(!isset($skipAdding[$visibility][$field]))
				{
					$addResult = static::add([
						'TYPE_ID' => $typeId,
						'STAGE_ID' => $stageId,
						'FIELD' => $field,
						'VISIBILITY' => $visibility,
					]);
					if(!$addResult->isSuccess())
					{
						$result->addErrors($addResult->getErrors());
					}
				}
			}
		}

		return $result;
	}

	public static function getVisibilityTypes(): array
	{
		return [
			static::VISIBILITY_VISIBLE,
			static::VISIBILITY_EDITABLE,
			static::VISIBILITY_MANDATORY,
			static::VISIBILITY_KANBAN,
			static::VISIBILITY_CREATE,
		];
	}
}