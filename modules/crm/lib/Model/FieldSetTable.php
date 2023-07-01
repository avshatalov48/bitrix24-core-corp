<?php
namespace Bitrix\Crm\Model;

use Bitrix\Main;

/**
 * Class FieldSetTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_FieldSet_Query query()
 * @method static EO_FieldSet_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_FieldSet_Result getById($id)
 * @method static EO_FieldSet_Result getList(array $parameters = [])
 * @method static EO_FieldSet_Entity getEntity()
 * @method static \Bitrix\Crm\Model\EO_FieldSet createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Model\EO_FieldSet_Collection createCollection()
 * @method static \Bitrix\Crm\Model\EO_FieldSet wakeUpObject($row)
 * @method static \Bitrix\Crm\Model\EO_FieldSet_Collection wakeUpCollection($rows)
 */
class FieldSetTable extends Main\ORM\Data\DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_field_set';
	}

	public static function getMap(): array
	{
		return [
			(new Main\ORM\Fields\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
			,
			(new Main\ORM\Fields\IntegerField('ENTITY_TYPE_ID'))
				->configureRequired()
			,
			(new Main\ORM\Fields\IntegerField('CLIENT_ENTITY_TYPE_ID'))
				->configureRequired()
			,
			(new Main\ORM\Fields\IntegerField('RQ_PRESET_ID'))
				->configureRequired()
			,
			(new Main\ORM\Fields\ArrayField('FIELDS'))
				->configureSerializationJson()
			,
			(new Main\ORM\Fields\StringField('CODE')),
			(new Main\ORM\Fields\BooleanField('IS_SYSTEM'))
				->configureStorageValues(0, 1)
				->configureDefaultValue(0)
				->configureRequired()
			,
			(new Main\ORM\Fields\StringField('TITLE'))
				->configureDefaultValue('')
			,
		];
	}

	public static function upsertFieldSet(
		int $entityTypeId,
		int $clientEntityTypeId,
		int $rqPresetId,
		array $fields
	): Main\ORM\Data\Result
	{
		$fields = static::filterFieldSetFields($fields);
		$id = self::fetchFieldSet($entityTypeId, $clientEntityTypeId)['ID'] ?? null;
		if ($id)
		{
			return static::update(
				$id,
				[
					'FIELDS' => $fields,
					'RQ_PRESET_ID' => $rqPresetId,
				]
			);
		}
		else
		{
			return static::add([
				'ENTITY_TYPE_ID' => $entityTypeId,
				'CLIENT_ENTITY_TYPE_ID' => $clientEntityTypeId,
				'RQ_PRESET_ID' => $rqPresetId,
				'FIELDS' => $fields,
			]);
		}
	}

	public static function getFieldSet(int $entityTypeId, int $clientEntityTypeId): ?array
	{
		return static::fetchFieldSet($entityTypeId, $clientEntityTypeId)['FIELDS'] ?? null;
	}

	private static function fetchFieldSet(int $entityTypeId, int $clientEntityTypeId): ?array
	{
		return static::query()
			->where('ENTITY_TYPE_ID', $entityTypeId)
			->where('CLIENT_ENTITY_TYPE_ID', $clientEntityTypeId)
			->fetch()
		;
	}

	private static function filterFieldSetFields(array $fields = [])
	{
		$fields = array_map(
			function ($item)
			{
				if (!is_array($item))
				{
					$item = [];
				}

				return [
					'name' => $item['name'] ?? '',
					'label' => $item['label'] ?? '',
					'required' => (bool)($item['required'] ?? 0),
					'multiple' => (bool)($item['multiple'] ?? 0),
				];
			},
			$fields
		);

		return array_filter(
			$fields,
			function (array $item)
			{
				return !empty($item['name']);
			}
		);
	}
}