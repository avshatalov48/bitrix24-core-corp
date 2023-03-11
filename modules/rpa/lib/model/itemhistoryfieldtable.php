<?php

namespace Bitrix\Rpa\Model;

use Bitrix\Main\ORM;

/**
 * Class ItemHistoryFieldTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ItemHistoryField_Query query()
 * @method static EO_ItemHistoryField_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ItemHistoryField_Result getById($id)
 * @method static EO_ItemHistoryField_Result getList(array $parameters = [])
 * @method static EO_ItemHistoryField_Entity getEntity()
 * @method static \Bitrix\Rpa\Model\EO_ItemHistoryField createObject($setDefaultValues = true)
 * @method static \Bitrix\Rpa\Model\EO_ItemHistoryField_Collection createCollection()
 * @method static \Bitrix\Rpa\Model\EO_ItemHistoryField wakeUpObject($row)
 * @method static \Bitrix\Rpa\Model\EO_ItemHistoryField_Collection wakeUpCollection($rows)
 */
class ItemHistoryFieldTable extends ORM\Data\DataManager
{
	public static function getTableName(): string
	{
		return 'b_rpa_item_history_fields';
	}

	public static function getMap(): array
	{
		return [
			(new ORM\Fields\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new ORM\Fields\IntegerField('ITEM_HISTORY_ID'))
				->configureRequired(),
			(new ORM\Fields\Relations\Reference(
				'ITEM_HISTORY',
				ItemHistoryTable::class,
				['=this.ITEM_HISTORY_ID' => 'ref.ID'])),
			(new ORM\Fields\StringField('FIELD_NAME'))
				->configureRequired(),
		];
	}

	public static function deleteByItemHistory(int $id): void
	{
		if($id > 0)
		{
			$list = static::getList([
				'select' => [
					'ID',
				],
				'filter' => [
					'=ITEM_HISTORY_ID' => $id,
				],
			]);
			while($field = $list->fetch())
			{
				static::delete($field['ID']);
			}
		}
	}
}